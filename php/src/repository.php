<?php

// Data access layer: every SQL query lives here, always as a prepared statement
// (placeholders, never string-built SQL) so user input cannot inject SQL.

define('STATUS_ACTIVE', 'Active');
define('STATUS_ON_HOLD', 'On Hold');
define('STATUS_COMPLETED', 'Completed');
define('STATUS_PENDING', 'Pending');

function registerGymnast(PDO $pdo, array $data) {
    $hashPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // A member account and its profile are a single logical operation, so save both atomically:
    // if either insert fails, the transaction rolls back and neither row is kept.
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, `password`, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['full_name'], $data['email'], $hashPassword, 'gymnast']);
        $userID = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO gymnasts (user_id, membership_id, contact_no, date_of_birth, training_program, enrollment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userID, $data['membership_id'], $data['contact_no'], $data['date_of_birth'], $data['training_program'], $data['enrollment_date'], STATUS_ACTIVE]);
        $gymnastID = $pdo->lastInsertId();
        $pdo->commit();

        return $gymnastID;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function getAllGymnasts(PDO $pdo) {
    // Name and email live in `users`, so join them onto each gymnast row. Newest first.
    $stmt = $pdo->query("
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
        ORDER BY gymnasts.id DESC
    ");
    return $stmt->fetchAll();
}

function getGymnastById(PDO $pdo, int $id) {
    $stmt = $pdo->prepare("
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
        WHERE gymnasts.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateGymnast(PDO $pdo, int $id, array $data) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            UPDATE users
            SET full_name = ?, email = ?
            WHERE id = (SELECT user_id FROM gymnasts WHERE id = ?)
        ");
        $stmt->execute([$data['full_name'], $data['email'], $id]);

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
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function deleteGymnast(PDO $pdo, int $id) {
    $gymnast = getGymnastById($pdo, $id);
    if (!$gymnast) {
        return false;
    }

    // Write the audit log first: if logging fails, nothing is deleted.
    $logEntry = date('Y-m-d H:i:s') . " | DELETED | " .
                "Name: " . $gymnast['full_name'] . " | " .
                "Membership ID: " . $gymnast['membership_id'] . " | " .
                "Program: " . $gymnast['training_program'] . "\n";

    $logDirectory = __DIR__ . '/../../storage';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0755, true) && !is_dir($logDirectory)) {
        throw new RuntimeException('Could not create the audit log directory.');
    }

    $logPath = $logDirectory . '/deleted_records.log';
    if (file_put_contents($logPath, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        throw new RuntimeException('Could not write the deletion audit log.');
    }

    // Deleting the user also removes the gymnast row (ON DELETE CASCADE in schema.sql).
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM gymnasts WHERE id = ?)");
        $stmt->execute([$id]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    return true;
}

// Used by the member portal: find the profile that belongs to the signed-in user.
function getGymnastByUserId(PDO $pdo, int $userId) {
    $stmt = $pdo->prepare("
        SELECT gymnasts.*, users.full_name, users.email 
        FROM gymnasts 
        JOIN users ON gymnasts.user_id = users.id
        WHERE users.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}