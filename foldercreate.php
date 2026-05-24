<?php
session_start();
require 'dbcon.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["status"=>"error","message"=>"Unauthorized access."]); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(["status"=>"error","message"=>"Invalid request method."]); exit;
}

$userId      = (int)$_SESSION['user_id'];
$baseRelPath = "uploads/user_{$userId}";
$baseAbsPath = __DIR__ . "/{$baseRelPath}";
$currentPath = trim($_POST['current_path'] ?? '', '/');
$parentRel   = $currentPath !== '' ? $currentPath : $baseRelPath;
if (strpos($parentRel, $baseRelPath) !== 0) $parentRel = $baseRelPath;

$name = trim($_POST['folder_name'] ?? '');
$name = preg_replace('/[^A-Za-z0-9 _\-\.\(\)]/', '_', $name);
if ($name === '') { echo json_encode(["status"=>"error","message"=>"Folder name required."]); exit; }

$folderAbs = __DIR__ . '/' . $parentRel . '/' . $name;
if (is_dir($folderAbs)) { echo json_encode(["status"=>"error","message"=>"Folder already exists."]); exit; }

if (!is_dir(dirname($folderAbs))) @mkdir(dirname($folderAbs), 0777, true);
if (!mkdir($folderAbs, 0777, true)) {
  echo json_encode(["status"=>"error","message"=>"Failed to create folder on disk."]); exit;
}

// Insert folder row
$stmt = $conn->prepare("INSERT INTO files (name, path, type, size, is_directory, starred, trashed, user_id) VALUES (?, ?, 'folder', 0, 1, 0, 0, ?)");
$stmt->bind_param("ssi", $name, $parentRel, $userId);

if ($stmt->execute()) {
  echo json_encode(["status"=>"success","message"=>"Folder created successfully."]);
} else {
  echo json_encode(["status"=>"error","message"=>$stmt->error]);
}
$stmt->close();
