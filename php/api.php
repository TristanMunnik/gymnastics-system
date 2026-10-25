<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// All browser requests enter through this JSON API; PHP keeps authentication and database access server-side.
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
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $action = $_GET['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    // Session and login endpoints are public so React can render the right screen before sign-in.
    if ($method === 'GET' && $action === 'session') {
        respond(['user' => sessionUser(), 'csrfToken' => $_SESSION['csrf_token']]);
    }

    if ($method === 'POST' && $action === 'login') {
        requireCsrfToken();
        $credentials = readJsonBody();
        $email = trim((string) ($credentials['email'] ?? ''));
        $password = (string) ($credentials['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            respond(['error' => 'Enter a valid email and password.'], 422);
        }

        require_once __DIR__ . '/db.php';
        $statement = $pdo->prepare('SELECT id, full_name, `password`, role FROM users WHERE email = ?');
        $statement->execute([$email]);
        $account = $statement->fetch();

        if (!$account || !password_verify($password, $account['password'])) {
            respond(['error' => 'Invalid email or password.'], 401);
        }
        if (!in_array($account['role'], ['admin', 'gymnast'], true)) {
            respond(['error' => 'This account does not have an allowed role.'], 403);
        }

        // Change the session ID after login and rotate the CSRF token with it.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $account['id'];
        $_SESSION['full_name'] = $account['full_name'];
        $_SESSION['role'] = $account['role'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        respond(['user' => sessionUser(), 'csrfToken' => $_SESSION['csrf_token']]);
    }

    if ($method === 'POST' && $action === 'logout') {
        requireCsrfToken();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
        }
        session_destroy();
        respond(['loggedOut' => true]);
    }

    if (!isset($_SESSION['user_id'])) {
        respond(['error' => 'Please sign in to continue.'], 401);
    }

    if ($method === 'GET' && $action === 'profile' && $_SESSION['role'] === 'gymnast') {
        require_once __DIR__ . '/db.php';
        require_once __DIR__ . '/functions.php';
        $gymnast = getGymnastByUserId($pdo, $_SESSION['user_id']);
        if (!$gymnast) {
            respond(['error' => 'No gymnast profile is linked to this account.'], 404);
        }
        respond($gymnast);
    }

    if (($_SESSION['role'] ?? '') !== 'admin') {
        respond(['error' => 'Administrator access is required.'], 403);
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

    requireCsrfToken();
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
