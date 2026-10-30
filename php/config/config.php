<?php

// Local settings win; environment variables are the fallback for hosted deployments.
// There are deliberately no default credentials: set them in config.local.php or the environment.
$localConfig = __DIR__ . '/config.local.php';
$config = file_exists($localConfig) ? require $localConfig : [];

return [
    'host' => $config['host'] ?? (getenv('DB_HOST') ?: 'localhost'),
    'database' => $config['database'] ?? (getenv('DB_NAME') ?: 'gymnastics_db'),
    'username' => $config['username'] ?? (getenv('DB_USER') ?: ''),
    'password' => $config['password'] ?? (getenv('DB_PASSWORD') ?: ''),
];
