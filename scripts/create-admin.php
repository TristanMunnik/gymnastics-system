<?php

// Command-line only: pretend the page does not exist if someone requests it in a browser.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../php/src/db.php';

$email = trim(getenv('GYMNAST_ADMIN_EMAIL') ?: '');
$password = getenv('GYMNAST_ADMIN_PASSWORD') ?: '';
$fullName = trim(getenv('GYMNAST_ADMIN_NAME') ?: 'Academy Administrator');

if ($email === '' || $password === '') {
    fwrite(STDERR, "Set GYMNAST_ADMIN_EMAIL and GYMNAST_ADMIN_PASSWORD before running this script.\n");
    exit(1);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Enter a valid email address.\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Use an administrator password of at least 8 characters.\n");
    exit(1);
}

try {
    $statement = getPdo()->prepare(
        'INSERT INTO users (full_name, email, `password`, role) VALUES (?, ?, ?, ?)'
    );
    $statement->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), 'admin']);
    fwrite(STDOUT, "Administrator account created for $email.\n");
} catch (PDOException $error) {
    if ($error->getCode() === '23000') {
        fwrite(STDERR, "An account with that email already exists.\n");
        exit(1);
    }
    error_log($error->getMessage());
    fwrite(STDERR, "Could not create the administrator. Check the database configuration.\n");
    exit(1);
}
