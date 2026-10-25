<?php

$config = require __DIR__ . '/config.php';
header('Location: ' . $config['frontend_url']);
exit;
