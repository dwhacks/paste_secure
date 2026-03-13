<?php
require_once 'config.php';
require_once 'theme.php';
require_once 'auth.php';
session_start();

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$currentUsername = get_current_username();
$error = '';
$success = '';
$themeAssets = resolve_theme_assets($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = 'All fields are required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 1) {
        $error = 'Password cannot be empty';
    } else {
        // Verify current password using get_user_hash
        $storedHash = get_user_hash($config, $currentUsername);
        
        if ($storedHash === null || !password_verify($currentPassword, $storedHash)) {
            $error = 'Current password is incorrect';
        } else {
            // Update password
            if (update_user_password($currentUsername, $newPassword, $config)) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to update password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?php echo htmlspecialchars($config['site_name']); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['base']); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeAssets['theme']); ?>">
    <script defer src="<?php echo htmlspecialchars($themeAssets['script']); ?>"></script>
</head>
<body class="<?php echo htmlspecialchars(trim($themeAssets['body_class'] . ' login-page')); ?>">
    <h2>Change Password</h2>
    <p>Logged in as: <strong><?php echo htmlspecialchars($currentUsername); ?></strong></p>
    
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <?php if ($success): ?><p class="message"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>
    
    <form method="post">
        <input type="password" name="current_password" placeholder="Current Password" required autocomplete="current-password">
        <input type="password" name="new_password" placeholder="New Password" required autocomplete="new-password">
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required autocomplete="new-password">
        <button type="submit" name="change_password">Change Password</button>
    </form>
    <p><a href="index.php">← Back to paste list</a></p>
</body>
</html>
