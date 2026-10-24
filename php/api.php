<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

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

function requiredText($data, $key, $label, $maximumLength, &$errors) {
    $value = trim((string) ($data[$key] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($value === '') {
        $errors[] = "$label is required.";
    } elseif ($length > $maximumLength) {
        $errors[] = "$label must be no more than $maximumLength characters.";
    }
    return $value;
}

function validDate($value) {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

function validateGymnast($data, $creating) {
    $errors = [];
    $result = [
        'full_name' => requiredText($data, 'full_name', 'Full name', 120, $errors),
        'email' => requiredText($data, 'email', 'Email', 190, $errors),
        'membership_id' => requiredText($data, 'membership_id', 'Membership ID', 40, $errors),
        'contact_no' => requiredText($data, 'contact_no', 'Contact number', 40, $errors),
        'date_of_birth' => requiredText($data, 'date_of_birth', 'Date of birth', 10, $errors),
        'training_program' => requiredText($data, 'training_program', 'Training program', 30, $errors),
        'enrollment_date' => requiredText($data, 'enrollment_date', 'Enrollment date', 10, $errors),
    ];

    if ($result['email'] !== '' && !filter_var($result['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    foreach (['date_of_birth', 'enrollment_date'] as $dateField) {
        if ($result[$dateField] !== '' && !validDate($result[$dateField])) {
            $errors[] = 'Enter valid dates in YYYY-MM-DD format.';
            break;
        }
    }
    if (!in_array($result['training_program'], ['Beginner', 'Intermediate', 'Advanced'], true)) {
        $errors[] = 'Select a valid training program.';
    }

    if ($creating) {
        $result['password'] = (string) ($data['password'] ?? '');
        if (strlen($result['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (strlen($result['password']) > 4096) {
            $errors[] = 'Password is too long.';
        }
    } else {
        $result['status'] = (string) ($data['status'] ?? '');
        if (!in_array($result['status'], [STATUS_ACTIVE, STATUS_ON_HOLD, STATUS_COMPLETED, STATUS_PENDING], true)) {
            $errors[] = 'Select a valid membership status.';
        }
    }

    return [$result, $errors];
}

try {
    if (!isset($_SESSION['user_id'])) {
        respond(['error' => 'Please sign in to continue.'], 401);
    }
    if (($_SESSION['role'] ?? '') !== 'admin') {
        respond(['error' => 'Administrator access is required.'], 403);
    }

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET' && $action === 'session') {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        respond([
            'user' => [
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role'],
                'csrfToken' => $_SESSION['csrf_token'],
            ],
        ]);
    }

    require_once __DIR__ . '/functions.php';

    if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
        if ($method === 'GET' && $action === 'gymnasts') {
            require_once __DIR__ . '/db.php';
            respond(getAllGymnasts($pdo));
        }
        respond(['error' => 'The requested endpoint was not found.'], 404);
    }

    if ($action !== 'gymnasts') {
        respond(['error' => 'The requested endpoint was not found.'], 404);
    }

    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        respond(['error' => 'Your session token is invalid. Refresh the page and try again.'], 403);
    }

    require_once __DIR__ . '/db.php';

    if ($method === 'DELETE') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            respond(['error' => 'A valid gymnast ID is required.'], 400);
        }
        if (!deleteGymnast($pdo, $id)) {
            respond(['error' => 'Gymnast not found.'], 404);
        }
        respond(['deleted' => true]);
    }

    $data = readJsonBody();
    [$gymnastData, $errors] = validateGymnast($data, $method === 'POST');
    if ($errors) {
        respond(['error' => implode(' ', $errors)], 422);
    }

    if ($method === 'POST') {
        $id = registerGymnast($pdo, $gymnastData);
        respond(getGymnastById($pdo, $id), 201);
    }

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        respond(['error' => 'A valid gymnast ID is required.'], 400);
    }
    if (!getGymnastById($pdo, $id)) {
        respond(['error' => 'Gymnast not found.'], 404);
    }
    updateGymnast($pdo, $id, $gymnastData);
    respond(getGymnastById($pdo, $id));
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
