// script.js - DriveDock full functionality with Context Menu + Search + Star

document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM loaded, initializing DriveDock...");

    // Elements
    const filesContainer = document.getElementById("filesContainer");
    const emptyState = document.getElementById("emptyState");
    const loadingOverlay = document.getElementById("loadingOverlay");
    const uploadBtn = document.getElementById("uploadBtn");
    const uploadModal = document.getElementById("uploadModal");
    const closeUpload = document.getElementById("closeUpload");
    const uploadForm = document.getElementById("uploadForm");
    const newFolderBtn = document.getElementById("newFolderBtn");
    const folderModal = document.getElementById("folderModal");
    const closeFolder = document.getElementById("closeFolder");
    const folderForm = document.getElementById("folderForm");
    const layoutRoot = document.getElementById("layoutRoot");
    const listViewBtn = document.getElementById("listViewBtn");
    const gridViewBtn = document.getElementById("gridViewBtn");
    const dropZone = document.getElementById("dropZone");
    const breadcrumbEl = document.getElementById("breadcrumb");
    const contextMenu = document.getElementById("contextMenu");
    const renameBtn = document.getElementById("renameFile");
    const deleteBtn = document.getElementById("deleteFile");
    const downloadBtn = document.getElementById("downloadFile");
    const searchInput = document.getElementById("searchInput");

    // Current state
    let currentPath = new URLSearchParams(window.location.search).get('path') || '';
    let currentFilter = new URLSearchParams(window.location.search).get('filter') || 'mydrive';
    let searchQuery = new URLSearchParams(window.location.search).get('q') || '';
    let contextTarget = null;

    // Hidden inputs
    const currentPathInput = document.getElementById("currentPathInput");
    if (currentPathInput) currentPathInput.value = currentPath;
    const folderCurrentPath = document.getElementById("folderCurrentPath");
    if (folderCurrentPath) folderCurrentPath.value = currentPath;

    showLoading(true);
    fetchFiles();

    // Upload modal
    if (uploadBtn && uploadModal && closeUpload) {
        uploadBtn.addEventListener("click", () => uploadModal.classList.remove("hidden"));
        closeUpload.addEventListener("click", () => uploadModal.classList.add("hidden"));
    }

    // Upload form
    if (uploadForm) {
        uploadForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            await handleFileUpload();
        });
    }

    // New folder modal
    if (newFolderBtn && folderModal) newFolderBtn.addEventListener("click", () => folderModal.classList.remove("hidden"));
    if (closeFolder && folderModal) closeFolder.addEventListener("click", () => folderModal.classList.add("hidden"));
    if (folderForm) folderForm.addEventListener("submit", async (e) => { e.preventDefault(); await handleFolderCreation(); });

    // List/Grid toggle
    if (listViewBtn && gridViewBtn && layoutRoot) {
        listViewBtn.addEventListener("click", () => {
            layoutRoot.classList.remove("grid");
            layoutRoot.classList.add("list");
            listViewBtn.classList.add("active");
            gridViewBtn.classList.remove("active");
        });
        gridViewBtn.addEventListener("click", () => {
            layoutRoot.classList.remove("list");
            layoutRoot.classList.add("grid");
            gridViewBtn.classList.add("active");
            listViewBtn.classList.remove("active");
        });
    }

    // Drag & Drop upload
    let dragCounter = 0;
    ['dragenter','dragover'].forEach(evt => document.addEventListener(evt, e => {
        e.preventDefault(); e.stopPropagation();
        dragCounter++;
        if (dropZone) dropZone.classList.add('active');
    }));
    ['dragleave','drop'].forEach(evt => document.addEventListener(evt, e => {
        e.preventDefault(); e.stopPropagation();
        dragCounter = Math.max(0, dragCounter - 1);
        if (dragCounter === 0 && dropZone) dropZone.classList.remove('active');
    }));
    document.addEventListener('drop', async (e) => {
        const dt = e.dataTransfer;
        if (!dt || !dt.files || !dt.files.length) return;
        for (const file of dt.files) await uploadSingleFile(file, currentPath);
    });

    // Context menu hide on click anywhere
    document.addEventListener("click", () => {
        if (contextMenu) contextMenu.classList.add("hidden");
    });

    // Context menu button actions
    if (renameBtn) renameBtn.addEventListener("click", async () => {
        if (!contextTarget) return;
        const newName = prompt("Enter new name", contextTarget.name);
        if (!newName || newName.trim() === "") return;
        await renameFile(contextTarget.id, newName);
        contextMenu.classList.add("hidden");
    });

    if (deleteBtn) deleteBtn.addEventListener("click", async () => {
        if (!contextTarget) return;
        if (currentFilter === 'trash') {
            if (confirm("Permanently delete this file?")) {
                await permanentlyDeleteFile(contextTarget.id);
            }
        } else {
            if (confirm("Move this file to Trash?")) {
                await deleteFile(contextTarget.id);
            }
        }
        contextMenu.classList.add("hidden");
    });

    if (downloadBtn) downloadBtn.addEventListener("click", () => {
        if (!contextTarget || contextTarget.is_directory) return;
        const a = document.createElement("a");
        a.href = contextTarget.path;
        a.download = contextTarget.name;
        a.click();
        contextMenu.classList.add("hidden");
    });

    // Loading overlay
    function showLoading(show) {
        if (!loadingOverlay) return;
        loadingOverlay.classList.toggle("hidden", show === false);
    }

    // FETCH FILES
    async function fetchFiles() {
        try {
            showLoading(true);
            let apiUrl;
            if (searchQuery && searchQuery.trim() !== '') {
                apiUrl = `search.php?q=${encodeURIComponent(searchQuery)}`;
            } else if (currentFilter === 'starred') {
                apiUrl = 'starred.php';
            } else {
                apiUrl = currentFilter === 'trash' ? 'trash.php' : `files.php?filter=${encodeURIComponent(currentFilter)}`;
                if (currentPath) apiUrl += `&path=${encodeURIComponent(currentPath)}`;
            }

            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            const files = await response.json();
            renderBreadcrumb(currentPath);
            renderFiles(files.files || files);
        } catch (error) {
            console.error("Error fetching files:", error);
            showError("Failed to load files.");
        } finally {
            showLoading(false);
        }
    }

    // SEARCH: live on input
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchFiles();
            }, 300);
        });
    }

    // Breadcrumb
    function renderBreadcrumb(path) {
        if (!breadcrumbEl) return;
        if (!path) { breadcrumbEl.textContent = currentFilter.charAt(0).toUpperCase() + currentFilter.slice(1); return; }
        const parts = path.split('/').filter(Boolean);
        let accum = '';
        breadcrumbEl.innerHTML = parts.map((seg, idx) => {
            accum += (idx ? '/' : '') + seg;
            const isLast = idx === parts.length - 1;
            return isLast ? `<span>${seg}</span>` : `<a href="index.php?filter=${encodeURIComponent(currentFilter)}&path=${encodeURIComponent(accum)}">${seg}</a>`;
        }).join(' <span>/</span> ');
    }

    // Render files + STAR button
    function renderFiles(files) {
        if (!filesContainer || !emptyState) return;
        filesContainer.innerHTML = "";
        if (!Array.isArray(files) || files.length === 0) {
            emptyState.textContent = "No files found.";
            emptyState.classList.remove("hidden");
            return;
        }
        emptyState.classList.add("hidden");

        files.forEach(file => {
            const card = document.createElement("div");
            card.className = "file-card";
            if (file.is_directory) {
                card.setAttribute("data-folder-path", file.path);
                card.style.cursor = "pointer";
            }

            card.innerHTML = `
                <i class="fa ${file.is_directory ? 'fa-folder folder' : 'fa-file'}"></i>
                <div class="file-info">
                    <strong>${escapeHtml(file.name)}</strong><br>
                    <small>${file.type || (file.is_directory ? "Folder" : "File")} | ${formatFileSize(file.size)}</small><br>
                    <small>Uploaded: ${formatDate(file.uploaded_at)}</small>
                </div>
                <div class="file-actions">
                    <button class="star-btn"><i class="fa ${file.starred ? 'fa-star' : 'fa-star-o'}"></i></button>
                    <button class="more-btn">⋮</button>
                </div>
            `;

            // STAR button click
            const starBtn = card.querySelector(".star-btn");
            if (starBtn) {
                starBtn.addEventListener("click", async (e) => {
                    e.stopPropagation();
                    await toggleStar(file.id, !file.starred);
                    file.starred = !file.starred;
                    starBtn.innerHTML = `<i class="fa ${file.starred ? 'fa-star' : 'fa-star-o'}"></i>`;
                });
            }

            // 3-dot menu
            const moreBtn = card.querySelector(".more-btn");
            if (moreBtn) {
                moreBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    contextTarget = file;
                    contextMenu.style.top = e.pageY + "px";
                    contextMenu.style.left = e.pageX + "px";
                    contextMenu.classList.remove("hidden");
                });
            }

            filesContainer.appendChild(card);
        });

        setupFolderNavigation();
    }

    // Folder navigation
    function setupFolderNavigation() {
        filesContainer.removeEventListener('click', handleFolderClick);
        filesContainer.addEventListener('click', handleFolderClick);
    }
    function handleFolderClick(event) {
        const card = event.target.closest('.file-card');
        if (!card) return;
        const folderPath = card.getAttribute('data-folder-path');
        if (!folderPath) return;
        currentPath = folderPath;
        searchQuery = '';
        const nextUrl = new URL(window.location.href);
        nextUrl.searchParams.set('filter', currentFilter);
        nextUrl.searchParams.set('path', folderPath);
        nextUrl.searchParams.delete('q');
        window.history.pushState({}, '', nextUrl.toString());
        fetchFiles();
    }

    // STAR toggle
    async function toggleStar(fileId, star) {
        try {
            const res = await fetch("star_toggle.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ file_id: fileId, star: star })
            });
            const data = await res.json();
            if (!data.success) showError("Failed to update star.");
        } catch (err) {
            console.error(err);
            showError("Failed to update star.");
        }
    }

    // Upload form
    async function handleFileUpload() {
        try {
            if (!uploadForm) return;
            const fd = new FormData(uploadForm);
            fd.set("current_path", currentPath || "");
            const res = await fetch("upload.php", { method: "POST", body: fd });
            const data = await res.json();
            if (!res.ok || data.status !== "success") {
                throw new Error(data.message || "Upload failed");
            }
            uploadModal?.classList.add("hidden");
            await fetchFiles();
        } catch (err) {
            console.error(err);
            showError(err.message || "Upload failed.");
        }
    }

    // Upload single file (drag-drop)
    async function uploadSingleFile(file, targetPath) {
        try {
            const fd = new FormData();
            fd.append("file", file);
            fd.set("current_path", targetPath || "");
            const res = await fetch("upload.php", { method: "POST", body: fd });
            const data = await res.json();
            if (!res.ok || data.status !== "success") {
                throw new Error(data.message || "Upload failed");
            }
            await fetchFiles();
        } catch (err) {
            console.error(err);
            showError(err.message || "Upload failed.");
        }
    }

    // Delete file → move to Trash
    async function deleteFile(fileId) {
        try {
            const res = await fetch("delete.php", {
                method: "POST",
                body: new URLSearchParams({ id: fileId })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || "Delete failed");
            }
            await fetchFiles();
        } catch (err) {
            console.error(err);
            showError(err.message || "Delete failed.");
        }
    }

    // Permanently delete file
    async function permanentlyDeleteFile(fileId) {
        try {
            const res = await fetch("permanentlydelete.php", {
                method: "POST",
                body: new URLSearchParams({ id: fileId })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || "Permanent delete failed");
            }
            await fetchFiles();
        } catch (err) {
            console.error(err);
            showError(err.message || "Permanent delete failed.");
        }
    }

    // New folder
    async function handleFolderCreation() {
        try {
            if (!folderForm) return;
            const fd = new FormData(folderForm);
            fd.set("current_path", currentPath || "");
            const res = await fetch("foldercreate.php", { method: "POST", body: fd });
            const data = await res.json();
            if (!res.ok || data.status !== "success") {
                throw new Error(data.message || "Folder creation failed");
            }
            folderModal?.classList.add("hidden");
            folderForm.reset();
            await fetchFiles();
        } catch (err) {
            console.error(err);
            showError(err.message || "Folder creation failed.");
        }
    }

    // Rename file
    async function renameFile(fileId, newName) {
        try {
            const res = await fetch("rename.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ file_id: fileId, new_name: newName })
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || "Rename failed");
            }
            await fetchFiles();
        } catch (err) {
            console.error(err);
            showError(err.message || "Rename failed.");
        }
    }

    // Helpers
    function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text || ''; return div.innerHTML; }
    function formatFileSize(bytes) { if(!bytes || bytes===0) return '0 Bytes'; const sizes=['Bytes','KB','MB','GB']; const i=Math.floor(Math.log(bytes)/Math.log(1024)); return Math.round(bytes/Math.pow(1024,i)*100)/100+' '+sizes[i]; }
    function formatDate(dateString) { if(!dateString) return 'Unknown date'; try { return new Date(dateString).toLocaleDateString(); } catch { return 'Invalid date'; } }
    function showError(message) { if(emptyState){ emptyState.textContent = message; emptyState.classList.remove("hidden"); } console.error(message); }
});
