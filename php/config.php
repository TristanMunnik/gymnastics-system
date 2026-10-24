<?php

$localConfig = __DIR__ . '/config.local.php';
$config = file_exists($localConfig) ? require $localConfig : [];

return [
    'host' => $config['host'] ?? (getenv('DB_HOST') ?: 'localhost'),
    'database' => $config['database'] ?? (getenv('DB_NAME') ?: 'gymnastics_db'),
    'username' => $config['username'] ?? (getenv('DB_USER') ?: 'root'),
    'password' => $config['password'] ?? (getenv('DB_PASS') ?: ''),
    'frontend_url' => $config['frontend_url'] ?? (getenv('FRONTEND_URL') ?: 'dashboard/'),
];
