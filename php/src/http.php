<?php

// Small helpers shared by every endpoint: JSON responses, request body, CSRF check, session user.
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function readJsonBody() {
    try {
        $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        respond(['error' => 'Request body must be valid JSON.'], 400);
    }

    if (!is_array($data)) {
        respond(['error' => 'Request body must be a JSON object.'], 400);
    }

    return $data;
}

function requireCsrfToken() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        respond(['error' => 'Your session token is invalid. Refresh the page and try again.'], 403);
    }
}

function sessionUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role'],
    ];
}

function requireGymnastId() {
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        respond(['error' => 'A valid gymnast ID is required.'], 400);
    }
    return $id;
}
