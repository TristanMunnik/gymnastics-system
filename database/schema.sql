CREATE DATABASE IF NOT EXISTS gymnastics_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gymnastics_db;

-- Every person who can sign in. `role` is 'admin' or 'gymnast'.
-- `password` holds a hash (never plain text), so it needs room for 255 characters.
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membership details for users with the 'gymnast' role (one row per user).
-- UNIQUE keys stop duplicate membership IDs; deleting a user cascades to their gymnast row.
CREATE TABLE IF NOT EXISTS gymnasts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    membership_id VARCHAR(40) NOT NULL,
    contact_no VARCHAR(40) NOT NULL,
    date_of_birth DATE NOT NULL,
    training_program VARCHAR(30) NOT NULL,
    enrollment_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    PRIMARY KEY (id),
    UNIQUE KEY gymnasts_user_unique (user_id),
    UNIQUE KEY gymnasts_membership_unique (membership_id),
    CONSTRAINT gymnasts_user_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
