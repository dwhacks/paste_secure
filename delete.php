<?php
require_once 'config.php';
require_once 'auth.php';
session_start();
$isLoggedIn = is_logged_in();
$currentUsername = get_current_username();
$id = $_GET['id'] ?? '';

if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

if ($id && file_exists($config['data_dir'] . "/{$id}.json")) {
    $data = json_decode(file_get_contents($config['data_dir'] . "/{$id}.json"), true);
    
    // Check if user is admin or author
    if (is_admin($config) || $data['author'] === $currentUsername) {
        unlink($config['data_dir'] . "/{$id}.json");
    }
}

header('Location: index.php');
