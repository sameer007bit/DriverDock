<?php
session_start();
require 'dbcon.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$fileId = (int)($data['file_id'] ?? 0);
$star   = $data['star'] ? 1 : 0;

$stmt = $conn->prepare("UPDATE files SET starred = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("iii", $star, $fileId, $_SESSION['user_id']);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
