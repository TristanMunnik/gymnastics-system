<?php

// Server-side validation. The React form checks input too, but the browser can be bypassed,
// so PHP never trusts it.

// Trims the field, records an error if it is empty or too long, and returns the cleaned value.
function requiredText(array $data, string $key, string $label, int $maximumLength, array &$errors) {
    $value = trim((string) ($data[$key] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($value === '') {
        $errors[] = "$label is required.";
    } elseif ($length > $maximumLength) {
        $errors[] = "$label must be no more than $maximumLength characters.";
    }
    return $value;
}

// The '!' resets unspecified fields; comparing the formatted date back to the input
// rejects impossible dates such as 2025-02-31.
function validDate(string $value) {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

// Returns [cleaned values, list of error messages]. New members need a password;
// edits need a status instead.
function validateGymnast(array $data, bool $creating) {
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
