<?php
require_once 'config.php';
require_once 'theme.php';
require_once 'auth.php';
session_start();
$isLoggedIn = is_logged_in();
$error = '';
$themeAssets = resolve_theme_assets($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password required';
    } elseif (login($config, $username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paste - Login</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['base']); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['theme']); ?>">
    <script defer src="<?php echo htmlspecialchars($themeAssets['script']); ?>"></script>
</head>
<body class="<?php echo htmlspecialchars(trim($themeAssets['body_class'] . ' login-page')); ?>">
    <h2>Paste Login</h2>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required autocomplete="username" autofocus>
        <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
        <button type="submit" name="login">Login</button>
    </form>
    <p><a href="index.php">← Back to paste list</a></p>
</body>
</html>
