<?php

define('STATUS_ACTIVE', 'Active');
define('STATUS_ON_HOLD', 'On Hold');
define('STATUS_COMPLETED', 'Completed');
define('STATUS_PENDING', 'Pending');

// registers new gymnast
function registerGymnast($pdo, $data) {
    $hashPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, `password`, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['full_name'], $data['email'], $hashPassword, 'gymnast']);

    $userID = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO gymnasts (user_id, membership_id, contact_no, date_of_birth, training_program, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userID, $data['membership_id'], $data['contact_no'], $data['date_of_birth'], $data['training_program'], $data['enrollment_date'], STATUS_ACTIVE]);
}

// shows all gymnasts
function getAllGymnasts($pdo) {
    $stmt = $pdo->prepare("
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
    ");
    $stmt->execute([]);
    return $stmt->fetchAll();
}

// selects one gymnast
function getGymnastById($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
        WHERE gymnasts.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// updates gymnast information
function updateGymnast($pdo, $id, $data) {
    // Update users table
    $stmt = $pdo->prepare("
        UPDATE users 
        SET full_name = ?, email = ?
        WHERE id = (SELECT user_id FROM gymnasts WHERE id = ?)
    ");
    $stmt->execute([$data['full_name'], $data['email'], $id]);

    // updates gymnasts table
    $stmt = $pdo->prepare("
        UPDATE gymnasts 
        SET membership_id = ?, contact_no = ?, date_of_birth = ?, 
            training_program = ?, enrollment_date = ?, status = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['membership_id'],
        $data['contact_no'],
        $data['date_of_birth'],
        $data['training_program'],
        $data['enrollment_date'],
        $data['status'],
        $id
    ]);
}

// delete gymnast and log to file
function deleteGymnast($pdo, $id) {

    $gymnast = getGymnastById($pdo, $id);

    $logEntry = date('Y-m-d H:i:s') . " | DELETED | " .
                "Name: " . $gymnast['full_name'] . " | " .
                "Membership ID: " . $gymnast['membership_id'] . " | " .
                "Program: " . $gymnast['training_program'] . "\n";

    $logPath = __DIR__ . '/../logs/deleted_records.log';
    
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    
    file_put_contents($logPath, $logEntry, FILE_APPEND);

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM gymnasts WHERE id = ?)");
    $stmt->execute([$id]);
}

// gymnast user_id
function getGymnastByUserId($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
        WHERE users.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}