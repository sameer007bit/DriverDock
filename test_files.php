<?php
session_start();
require_once 'dbcon.php';

header('Content-Type: application/json');

// Simple test response
echo json_encode([
    [
        "id" => 1,
        "name" => "Test File.txt",
        "type" => "txt",
        "size" => 1024,
        "path" => "uploads/test.txt",
        "is_directory" => 0,
        "uploaded_at" => "2023-01-01 12:00:00"
    ],
    [
        "id" => 2,
        "name" => "Test Folder",
        "type" => "folder",
        "size" => 0,
        "path" => "",
        "is_directory" => 1,
        "uploaded_at" => "2023-01-01 12:00:00"
    ]
]);
exit;
?>