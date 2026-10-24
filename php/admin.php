<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: gymnast.php');
    exit;
}

$config = require __DIR__ . '/config.php';
header('Location: ' . $config['frontend_url']);
exit;
