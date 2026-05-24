<?php
session_start();
require 'dbcon.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$files = [];
$userId = $_SESSION['user_id'];

$sql = "SELECT id, name, path, type, size, is_directory, starred, trashed, created_at, modified_at, accessed_at
        FROM files
        WHERE trashed = 1 AND user_id = ?
        ORDER BY modified_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}

$stmt->close();
echo json_encode($files);
?>
