<?php
require_once 'config.php';
require_once 'auth.php';
session_start();
$isLoggedIn = !empty($_SESSION['paste_admin']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $hash = get_admin_password_hash($config);
    if (password_verify($_POST['password'], $hash)) {
        $_SESSION['paste_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Incorrect password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paste - Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body.login-page { max-width: 400px; margin: 100px auto; }
    </style>
</head>
<body class="login-page">
    <h2>Paste Admin</h2>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="post">
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <button type="submit" name="login">Login</button>
    </form>
    <p><a href="index.php">← Back to paste list</a></p>
</body>
</html>
