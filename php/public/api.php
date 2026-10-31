<?php

// The only HTTP entry point. It routes each request to a handler in src/handlers/;
// authentication, role checks, and database access all stay server-side.
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../src/http.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/repository.php';
require_once __DIR__ . '/../src/validation.php';
require_once __DIR__ . '/../src/handlers/auth.php';
require_once __DIR__ . '/../src/handlers/gymnasts.php';

try {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    // Session and login endpoints are public so React can render the right screen before sign-in.
    if ($method === 'GET' && $action === 'session') {
        handleSession();
    }
    if ($method === 'POST' && $action === 'login') {
        handleLogin();
    }
    if ($method === 'POST' && $action === 'logout') {
        handleLogout();
    }

    if (!isset($_SESSION['user_id'])) {
        respond(['error' => 'Please sign in to continue.'], 401);
    }

    // Everything below needs a signed-in user.
    if ($method === 'GET' && $action === 'profile' && $_SESSION['role'] === 'gymnast') {
        handleProfile();
    }

    if (($_SESSION['role'] ?? '') !== 'admin') {
        respond(['error' => 'Administrator access is required.'], 403);
    }

    // Admin-only member management. The HTTP method picks the action (REST style).
    if ($action === 'gymnasts') {
        switch ($method) {
            case 'GET':
                handleListGymnasts();
            case 'POST':
                handleCreateGymnast();
            case 'PUT':
                handleUpdateGymnast();
            case 'DELETE':
                handleDeleteGymnast();
        }
    }

    respond(['error' => 'The requested endpoint was not found.'], 404);
} catch (PDOException $error) {
    error_log('Gymnastics API database error: ' . $error->getMessage());
    $status = $error->getCode() === '23000' ? 409 : 500;
    $message = $status === 409
        ? 'That email address or membership ID is already in use.'
        : 'A database error prevented the request from completing.';
    respond(['error' => $message], $status);
} catch (Throwable $error) {
    error_log('Gymnastics API error: ' . $error->getMessage());
    respond(['error' => 'The request could not be completed. Check the server log for details.'], 500);
}
