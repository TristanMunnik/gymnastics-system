<?php

function handleSession() {
    respond(['user' => sessionUser(), 'csrfToken' => $_SESSION['csrf_token']]);
}

function handleLogin() {
    requireCsrfToken();
    $credentials = readJsonBody();
    $email = trim((string) ($credentials['email'] ?? ''));
    $password = (string) ($credentials['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        respond(['error' => 'Enter a valid email and password.'], 422);
    }

    $statement = getPdo()->prepare('SELECT id, full_name, `password`, role FROM users WHERE email = ?');
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

function handleLogout() {
    requireCsrfToken();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
    }
    session_destroy();
    respond(['loggedOut' => true]);
}
