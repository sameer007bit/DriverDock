<?php
session_start();
require 'dbcon.php';

echo "<h2>Database Connection Test</h2>";

// Check if connected
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "✓ Database connection successful<br>";
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

echo "✓ User ID: " . $_SESSION['user_id'] . "<br>";

// Check files table structure
$result = $conn->query("DESCRIBE files");
echo "<h3>Files Table Structure:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test insert
$testName = "Test Folder " . time();
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO files (name, type, is_directory, user_id, size) VALUES (?, 'folder', 1, ?, 0)");
$stmt->bind_param("si", $testName, $userId);

if ($stmt->execute()) {
    echo "✓ Test folder inserted successfully<br>";
    echo "✓ New folder ID: " . $stmt->insert_id . "<br>";
    
    // Clean up
    $conn->query("DELETE FROM files WHERE name = '$testName'");
    echo "✓ Test folder cleaned up<br>";
} else {
    echo "✗ Insert failed: " . $stmt->error . "<br>";
}

$stmt->close();
?>