<?php
session_start();
require 'dbcon.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["error" => "Unauthorized access."]);
  exit;
}

$userId = (int)$_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'mydrive';
$path   = $_GET['path'] ?? '';
$q      = $_GET['q'] ?? '';

$where  = "user_id = ?";
$params = [$userId];
$types  = "i";

switch ($filter) {
  case 'trash':
    $where .= " AND trashed = 1";
    break;
  case 'starred':
    $where .= " AND trashed = 0 AND starred = 1";
    break;
  case 'recent':
    $where .= " AND trashed = 0 AND uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    break;
  default: // mydrive
    $where .= " AND trashed = 0";
}

if ($path !== '') {
  $where .= " AND path = ?";
  $params[] = $path;
  $types   .= "s";
}
if ($q !== '') {
  $where .= " AND name LIKE ?";
  $params[] = "%{$q}%";
  $types   .= "s";
}

$sql = "SELECT id, name, path, type, size, is_directory, starred, trashed,
               created_at, modified_at, accessed_at
        FROM files
        WHERE {$where}
        ORDER BY is_directory DESC, name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
  $out[] = $row;
}
$stmt->close();

echo json_encode($out);
