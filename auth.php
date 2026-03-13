<?php

function get_admin_password_hash(array $config): string
{
    $hashFile = rtrim($config['data_dir'], '/').'/admin.hash';
    $plain = $config['admin_password'] ?? '';

    if (!is_dir($config['data_dir'])) {
        mkdir($config['data_dir'], 0775, true);
    }

    if (file_exists($hashFile)) {
        $stored = trim(file_get_contents($hashFile));
        if ($stored !== '' && password_verify($plain, $stored)) {
            return $stored;
        }
    }

    $hash = password_hash($plain, PASSWORD_DEFAULT);
    file_put_contents($hashFile, $hash);
    chmod($hashFile, 0640);
    return $hash;
}

function get_user_hash(array $config, string $username): ?string
{
    if ($username === 'admin') {
        return get_admin_password_hash($config);
    }

    // Check for stored hash file first
    $hashDir = rtrim($config['data_dir'], '/') . '/user_hashes';
    $hashFile = $hashDir . '/' . $username . '.hash';
    
    if (file_exists($hashFile)) {
        $stored = trim(file_get_contents($hashFile));
        if ($stored !== '') {
            return $stored;
        }
    }

    // Check config for user (plain text or bcrypt hash)
    $users = $config['users'] ?? [];
    $storedHash = $users[$username] ?? null;

    if ($storedHash === null) {
        return null;
    }

    // Check if it's already a bcrypt hash
    if (preg_match('/^\$2[ayb]\$.{56}$/', $storedHash)) {
        return $storedHash;
    }

    // Plain text password - hash it and save to file
    if (!is_dir($hashDir)) {
        mkdir($hashDir, 0775, true);
    }

    $hash = password_hash($storedHash, PASSWORD_DEFAULT);
    file_put_contents($hashFile, $hash);
    chmod($hashFile, 0640);

    return $hash;
}

function verify_password(string $password, ?string $hash): bool
{
    if ($hash === null) {
        return false;
    }
    return password_verify($password, $hash);
}

function login(array $config, string $username, string $password): bool
{
    $hash = get_user_hash($config, $username);
    if ($hash === null) {
        return false;
    }

    if (!verify_password($password, $hash)) {
        return false;
    }

    $_SESSION['username'] = $username;
    $_SESSION['login_time'] = time();
    return true;
}

function logout(): void
{
    unset($_SESSION['username']);
    unset($_SESSION['login_time']);
}

function is_logged_in(): bool
{
    return isset($_SESSION['username']) && isset($_SESSION['login_time']);
}

function get_current_username(): ?string
{
    return $_SESSION['username'] ?? null;
}

function is_admin(array $config): bool
{
    $username = get_current_username();
    if ($username === null) {
        return false;
    }
    return $username === 'admin';
}

function can_delete_paste(array $config, ?string $author): bool
{
    if (is_admin($config)) {
        return true;
    }

    // If no author (old paste), allow any logged in user to delete for backwards compatibility
    if ($author === null || $author === '') {
        return is_logged_in();
    }

    $username = get_current_username();
    return $username !== null && $username === $author;
}

function can_view_hidden_paste(?string $author): bool
{
    $username = get_current_username();
    return $username !== null && $username === $author;
}
