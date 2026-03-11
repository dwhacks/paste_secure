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
