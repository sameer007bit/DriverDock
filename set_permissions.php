<?php
// Set directory permissions (for Windows)
$dir = __DIR__ . '/uploads';

if (!is_dir($dir)) {
    if (mkdir($dir, 0777, true)) {
        echo "Uploads directory created successfully.<br>";
    } else {
        echo "Failed to create uploads directory.<br>";
        exit;
    }
}

// Try to create a test file to check permissions
$testFile = $dir . '/test_write.txt';
if (file_put_contents($testFile, 'test') !== false) {
    echo "Write permissions are working correctly.<br>";
    unlink($testFile); // Clean up test file
} else {
    echo "Write permissions issue. Please manually set permissions on the uploads folder.<br>";
    echo "Right-click the folder -> Properties -> Security -> Edit -> Add 'Users' with 'Modify' permissions.";
}
?>