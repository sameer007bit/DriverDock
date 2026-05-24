<?php
session_start();
require 'dbcon.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["status"=>"error","message"=>"Unauthorized access."]); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['userfile'])) {
  echo json_encode(["status"=>"error","message"=>"No file uploaded or invalid request method."]); exit;
}

$userId      = (int)$_SESSION['user_id'];
$baseRelPath = "uploads/user_{$userId}";
$baseAbsPath = __DIR__ . "/{$baseRelPath}";
$currentPath = trim($_POST['current_path'] ?? '', '/');
$targetRel   = $currentPath !== '' ? $currentPath : $baseRelPath;

// force path under user root
if (strpos($targetRel, $baseRelPath) !== 0) $targetRel = $baseRelPath;

$targetAbs = __DIR__ . '/' . $targetRel;
if (!is_dir($targetAbs) && !mkdir($targetAbs, 0777, true)) {
  echo json_encode(["status"=>"error","message"=>"Failed to prepare upload directory."]); exit;
}

$allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','jpg','jpeg','png','gif','zip','rar','7z','csv','mp4','mp3'];
$maxSize = 10 * 1024 * 1024; // 10MB

$uploaded = [];

foreach ($_FILES['userfile']['name'] as $i => $origName) {
  $tmp  = $_FILES['userfile']['tmp_name'][$i];
  $size = (int)$_FILES['userfile']['size'][$i];
  $err  = (int)$_FILES['userfile']['error'][$i];

  if ($err !== UPLOAD_ERR_OK) { $uploaded[] = ["name"=>$origName,"status"=>"error","message"=>"Upload error code $err"]; continue; }

  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if ($ext && !in_array($ext, $allowed, true)) { $uploaded[] = ["name"=>$origName,"status"=>"error","message"=>"Unsupported file type"]; continue; }
  if ($size > $maxSize) { $uploaded[] = ["name"=>$origName,"status"=>"error","message"=>"File exceeds 10MB"]; continue; }

  $safeName = preg_replace('/[^A-Za-z0-9_\.\-]/', '_', basename($origName));
  $destAbs  = $targetAbs . '/' . $safeName;
  $destRel  = $targetRel; // DB stores parent path; name stored separately

  if (file_exists($destAbs)) { $uploaded[] = ["name"=>$safeName,"status"=>"error","message"=>"File already exists"]; continue; }

  if (!move_uploaded_file($tmp, $destAbs)) { $uploaded[] = ["name"=>$safeName,"status"=>"error","message"=>"Failed to move uploaded file"]; continue; }

  // Insert DB row
  $type = $ext ?: 'file';
  $stmt = $conn->prepare("INSERT INTO files (name, path, type, size, is_directory, starred, trashed, user_id) VALUES (?, ?, ?, ?, 0, 0, 0, ?)");
  $stmt->bind_param("sssii", $safeName, $destRel, $type, $size, $userId);
  if ($stmt->execute()) {
    $uploaded[] = ["name"=>$safeName,"status"=>"success","path"=>$destRel,"type"=>$type,"size"=>$size];
  } else {
    @unlink($destAbs);
    $uploaded[] = ["name"=>$safeName,"status"=>"error","message"=>$stmt->error];
  }
  $stmt->close();
}

echo json_encode(["status"=>"success","files"=>$uploaded]);
