<?php
session_start();
require_once 'dbcon.php';

// Regenerate session ID to prevent fixation
session_regenerate_id(true);

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = "Please enter both username and password.";
        } else {
            $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($userId, $hashedPassword);
                $stmt->fetch();
                if (password_verify($password, $hashedPassword)) {
                    // Successful login
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username; // Fixed: Store username instead of email
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Wrong password.";
                }
            } else {
                $error = "User not found.";
            }

            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - DriveDock</title>
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
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="register-link">
            <a href="registration.php">Create an account</a>
        </div>
    </div>
</body>
</html>