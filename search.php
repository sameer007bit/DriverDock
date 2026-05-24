<?php
session_start();
require 'dbcon.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$files = [];
$userId = (int)$_SESSION['user_id'];
$q = trim($_GET['q'] ?? '');

try {
    if ($q !== '') {
        // Search term with wildcard
        $qParam = "%" . $q . "%";

        // Select files/folders for this user matching search, ignoring trashed items
        $stmt = $conn->prepare("
            SELECT id, name, path, type, size, is_directory, starred, trashed, created_at, modified_at, accessed_at
            FROM files
            WHERE user_id = ? AND trashed = 0 AND name LIKE ?
            ORDER BY accessed_at DESC
            LIMIT 50
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param("is", $userId, $qParam);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $files[] = $row;
        }

        $stmt->close();
    }

    echo json_encode(["status" => "success", "files" => $files]);
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "An error occurred while searching."]);
} finally {
    $conn->close();
}
