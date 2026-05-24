<?php
session_start();
require_once 'dbcon.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validation
        if ($username === '' || $password === '') {
            $error = "Please enter both username and password.";
        } elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
            $error = "Username must be 3-20 characters long and contain only letters, numbers, or underscores.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            // Check if username already exists
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Username already exists!";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $hashedPassword);
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - DriveDock</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="floating-elements">
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    </div>
    <div class="login-container">
        <h2>Register</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="message"><?= $success ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="text" name="username" placeholder="Username" required pattern="[A-Za-z0-9_]{3,20}" title="3-20 letters, numbers, or underscores">
            <input type="password" name="password" placeholder="Password" required minlength="6">
            <button type="submit">Register</button>
        </form>
        <div class="register-link">
            <a href="login.php">Already have an account? Login</a>
        </div>
    </div>
</body>
</html>