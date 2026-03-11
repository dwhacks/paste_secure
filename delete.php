<?php
require_once 'config.php';
session_start();
$isLoggedIn = !empty($_SESSION['paste_admin']);
$id = $_GET['id'] ?? '';

if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}

if ($id && file_exists($config['data_dir'] . "/{$id}.json")) {
    unlink($config['data_dir'] . "/{$id}.json");
}

header('Location: index.php');
