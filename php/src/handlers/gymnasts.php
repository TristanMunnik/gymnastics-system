<?php

// Gymnast endpoints. Each handler checks the CSRF token first when it changes data;
// api.php has already confirmed the user's role before calling them.

function handleProfile() {
    $gymnast = getGymnastByUserId(getPdo(), $_SESSION['user_id']);
    if (!$gymnast) {
        respond(['error' => 'No gymnast profile is linked to this account.'], 404);
    }
    respond($gymnast);
}

function handleListGymnasts() {
    respond(getAllGymnasts(getPdo()));
}

function handleCreateGymnast() {
    requireCsrfToken();
    [$gymnastData, $errors] = validateGymnast(readJsonBody(), true);
    if ($errors) {
        respond(['error' => implode(' ', $errors)], 422);
    }

    $pdo = getPdo();
    $id = registerGymnast($pdo, $gymnastData);
    respond(getGymnastById($pdo, $id), 201);
}

function handleUpdateGymnast() {
    requireCsrfToken();
    [$gymnastData, $errors] = validateGymnast(readJsonBody(), false);
    if ($errors) {
        respond(['error' => implode(' ', $errors)], 422);
    }

    $id = requireGymnastId();
    $pdo = getPdo();
    if (!getGymnastById($pdo, $id)) {
        respond(['error' => 'Gymnast not found.'], 404);
    }
    updateGymnast($pdo, $id, $gymnastData);
    respond(getGymnastById($pdo, $id));
}

function handleDeleteGymnast() {
    requireCsrfToken();
    $id = requireGymnastId();
    if (!deleteGymnast(getPdo(), $id)) {
        respond(['error' => 'Gymnast not found.'], 404);
    }
    respond(['deleted' => true]);
}
