<?php
session_start();
require 'dbcon.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["status" => "error", "message" => "Invalid CSRF token."]);
        exit;
    }

    $id = $_POST['id'] ?? '';
    $newName = trim($_POST['new_name'] ?? '');
    $userId = $_SESSION['user_id'];

    // Validate inputs
    if (!is_numeric($id) || $newName === '') {
        echo json_encode(["status" => "error", "message" => "Invalid ID or folder/file name."]);
        exit;
    }

    // Prevent invalid characters
    if (preg_match('/[\/\\\\:*?"<>|]/', $newName)) {
        echo json_encode(["status" => "error", "message" => "Name contains invalid characters."]);
        exit;
    }

    $id = (int)$id;
    // Verify file belongs to user
    $check = $conn->prepare("SELECT id FROM files WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $id, $userId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "File not found or unauthorized."]);
        $check->close();
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE files SET name = ?, modified_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $newName, $id, $userId);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>