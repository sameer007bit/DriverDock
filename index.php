<?php
session_start();
require_once 'dbcon.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentFilter = $_GET['filter'] ?? 'mydrive';
$searchQuery   = $_GET['q'] ?? '';

// Path handling: default is the user's root folder
$userId = (int)$_SESSION['user_id'];
$defaultPath = "uploads/user_$userId";
$currentPath = $_GET['path'] ?? $defaultPath;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>DriveDock</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* minimal styles to support grid/list + drop zone */
    .grid #filesContainer .file-card { display: grid; grid-template-columns: 40px 1fr auto; gap: 10px; align-items: center; }
    .list #filesContainer .file-card { display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #eee; padding: 8px 0; }
    #filesContainer.grid .file-card { display: block; }
    .file-card i { font-size: 20px; }
    .hidden { display: none !important; }
    .loading-overlay { position: fixed; inset: 0; display: grid; place-items: center; background: rgba(0,0,0,.25); color: #fff; font-weight: 600; z-index: 999; }
    .drop-zone {
      position: fixed; inset: 0; display: none; align-items: center; justify-content: center;
      background: rgba(0, 136, 255, 0.15); border: 3px dashed #0088ff; color: #004a88; font-size: 18px; z-index: 998;
    }
    .drop-zone.active { display: flex; }
    .breadcrumb { margin: 10px 0; color: #666; font-size: 14px; }
    .breadcrumb a { color: #0077cc; text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .view-toggle button.active { font-weight: 700; text-decoration: underline; }
  </style>
</head>
<body class="list" id="layoutRoot"><!-- default list view -->
  <!-- Navbar -->
  <div class="navbar">
    <h2><i class="fa fa-folder-open"></i> DriveDock</h2>
    <div class="nav-actions">
      <button id="uploadBtn"><i class="fa fa-upload"></i> Upload</button>
      <button id="newFolderBtn"><i class="fa fa-folder-plus"></i> New Folder</button>
      <input type="text" id="searchInput" placeholder="Search files..." value="<?= htmlspecialchars($searchQuery) ?>">
      <form action="logout.php" method="post" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <button type="submit"><i class="fa fa-sign-out-alt"></i> Logout</button>
      </form>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <ul>
      <li id="myDriveBtn" class="<?= $currentFilter === 'mydrive' ? 'active' : '' ?>">
        <a href="index.php?filter=mydrive"><i class="fa fa-home"></i> My Drive</a>
      </li>
      <li id="recentBtn" class="<?= $currentFilter === 'recent' ? 'active' : '' ?>">
        <a href="index.php?filter=recent"><i class="fa fa-clock"></i> Recent</a>
      </li>
      <li id="starredBtn" class="<?= $currentFilter === 'starred' ? 'active' : '' ?>">
        <a href="index.php?filter=starred"><i class="fa fa-star"></i> Starred</a>
      </li>
      <li id="trashBtn" class="<?= $currentFilter === 'trash' ? 'active' : '' ?>">
        <a href="index.php?filter=trash"><i class="fa fa-trash"></i> Trash</a>
      </li>
    </ul>
  </div>

  <!-- Main content -->
  <div class="main" id="main">
    <div id="breadcrumb" class="breadcrumb"></div>

    <div class="view-toggle">
      <button id="listViewBtn" class="active">List View</button>
      <button id="gridViewBtn">Grid View</button>
    </div>

    <div id="filesContainer" class="list"></div>
    <div id="emptyState" class="hidden">No files found.</div>
  </div>

  <!-- Upload Modal -->
  <div class="modal hidden" id="uploadModal">
    <div class="modal-content">
      <span class="close" id="closeUpload">&times;</span>
      <h3>Upload File</h3>
      <form id="uploadForm" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" id="currentPathInput" name="current_path" value="">
        <input type="file" name="userfile[]" multiple>
        <button type="submit">Upload</button>
      </form>
      <p style="margin-top:8px;color:#666;">Tip: You can also drag & drop files anywhere on the page.</p>
    </div>
  </div>

  <!-- New Folder Modal -->
  <div class="modal hidden" id="folderModal">
    <div class="modal-content">
      <span class="close" id="closeFolder">&times;</span>
      <h3>Create New Folder</h3>
      <form id="folderForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" id="folderCurrentPath" name="current_path" value="">
        <input type="text" name="folder_name" placeholder="Folder Name">
        <button type="submit">Create Folder</button>
      </form>
    </div>
  </div>

  <div id="contextMenu" class="context-menu hidden">
    <button id="renameFile">Rename</button>
    <button id="deleteFile">Delete</button>
    <button id="downloadFile">Download</button>
  </div>

  <div id="loadingOverlay" class="loading-overlay hidden">Loading...</div>
  <div id="dropZone" class="drop-zone">Drop files here</div>

  <script>
    const currentFilter = "<?= htmlspecialchars($currentFilter) ?>";
    const searchQuery  = "<?= htmlspecialchars($searchQuery) ?>";
    const csrfToken    = "<?= htmlspecialchars($_SESSION['csrf_token']) ?>";
    const currentPath  = "<?= htmlspecialchars($currentPath) ?>";
  </script>
  <script src="script.js"></script>
</body>
</html>
