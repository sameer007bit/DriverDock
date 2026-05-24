<?php
session_start();
require 'dbcon.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized access."]);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$fileId = (int)($_POST['id'] ?? 0);

if ($fileId <= 0) {
    echo json_encode(["error" => "Invalid file ID"]);
    exit;
}

// Delete only if already trashed
$stmt = $conn->prepare("DELETE FROM files WHERE id = ? AND user_id = ? AND trashed = 1");
$stmt->bind_param("ii", $fileId, $userId);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => "Failed to permanently delete"]);
}
$stmt->close();
