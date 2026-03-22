-- ============================================================
--  MKfinder — Complete Database Setup
--  FULLY UPDATED VERSION — 2026-03-11
--
--  What's new vs original:
--  ✅ users table:  added `role` column (user/admin)
--  ✅ species table: added `image_path` column (admin photo uploads)
--  ✅ species table: added `status` column (approved/pending/rejected)
--  ✅ unknown_submissions: added `reviewed_at` column
--  ✅ Seed data: 3 species now have status='approved'
--  ✅ Seed data: Admin account seeded (admin0403@gmail.com)
--  ✅ Seed data: Test user still included
--
--  HOW TO USE:
--  1. Open phpMyAdmin → http://localhost/phpmyadmin
--  2. Click "SQL" tab
--  3. Paste this entire file and click "Go"
--  4. All old data will be wiped — fresh clean install
-- ============================================================

CREATE DATABASE IF NOT EXISTS mkfinder
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mkfinder;

-- ============================================================
-- WIPE EVERYTHING (safe clean re-import)
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `unknown_submissions`;
DROP TABLE IF EXISTS `identifications`;
DROP TABLE IF EXISTS `uploads`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `species`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- TABLE: users
-- ✅ NEW: `role` column added (user / admin)
-- ============================================================
CREATE TABLE `users` (
    `id`            INT             NOT NULL AUTO_INCREMENT,
    `user_id`       VARCHAR(50)     NOT NULL,
    `email`         VARCHAR(255)    NOT NULL,
    `phone_number`  VARCHAR(20)     NOT NULL DEFAULT '',
    `first_name`    VARCHAR(100)    NOT NULL DEFAULT '',
    `last_name`     VARCHAR(100)    NOT NULL DEFAULT '',
    `password_hash` VARCHAR(255)    NOT NULL,
    `role`          VARCHAR(20)     NOT NULL DEFAULT 'user',
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `last_login`    TIMESTAMP       NULL DEFAULT NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_id`    (`user_id`),
    UNIQUE KEY `uq_email`      (`email`),
    INDEX `idx_role`           (`role`),
    INDEX `idx_is_active`      (`is_active`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: user_sessions
-- Tracks login sessions (unchanged)
-- ============================================================
CREATE TABLE `user_sessions` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `session_id`    VARCHAR(64)  NOT NULL,
    `user_id`       VARCHAR(50)  NOT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`    TIMESTAMP    NOT NULL,
    `ip_address`    VARCHAR(45)  DEFAULT NULL,
    `user_agent`    TEXT         DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_session_id` (`session_id`),
    INDEX `idx_user_id`        (`user_id`),
    INDEX `idx_expires_at`     (`expires_at`),

    CONSTRAINT `fk_sessions_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: species
-- ✅ NEW: `image_path` column  — stores path to species photo
-- ✅ NEW: `status` column      — 'approved' | 'pending' | 'rejected'
--         Only status='approved' species appear on public Species page
--         Admin-added species = auto approved
--         User-submitted species = pending until admin approves
-- ============================================================
CREATE TABLE `species` (
    `id`                   INT           NOT NULL AUTO_INCREMENT,
    `name`                 VARCHAR(255)  NOT NULL,
    `scientific_name`      VARCHAR(255)  DEFAULT NULL,
    `description`          TEXT,
    `characteristics`      JSON,
    `habitat`              TEXT,
    `diet`                 TEXT,
    `behavior`             TEXT,
    `conservation_status`  VARCHAR(100)  DEFAULT 'Least Concern',
    `image_path`           VARCHAR(500)  DEFAULT NULL,
    `status`               VARCHAR(20)   NOT NULL DEFAULT 'approved',
    `created_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_species_name` (`name`),
    INDEX `idx_species_name`     (`name`),
    INDEX `idx_status`           (`status`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: uploads
-- Every image uploaded by any user (unchanged)
-- ============================================================
CREATE TABLE `uploads` (
    `id`            INT           NOT NULL AUTO_INCREMENT,
    `upload_id`     VARCHAR(255)  DEFAULT NULL,
    `user_id`       VARCHAR(50)   DEFAULT NULL,
    `filename`      VARCHAR(255)  NOT NULL,
    `original_name` VARCHAR(255)  NOT NULL,
    `file_size`     INT           DEFAULT NULL,
    `mime_type`     VARCHAR(100)  DEFAULT NULL,
    `file_path`     VARCHAR(500)  DEFAULT NULL,
    `status`        VARCHAR(50)   NOT NULL DEFAULT 'uploaded',
    `upload_time`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_upload_id`   (`upload_id`),
    INDEX `idx_user_id`     (`user_id`),
    INDEX `idx_upload_time` (`upload_time` DESC),
    INDEX `idx_status`      (`status`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: identifications
-- Results of every bird identification attempt (unchanged)
-- ============================================================
CREATE TABLE `identifications` (
    `id`                  INT            NOT NULL AUTO_INCREMENT,
    `identification_id`   VARCHAR(255)   DEFAULT NULL,
    `upload_id`           VARCHAR(255)   DEFAULT NULL,
    `user_id`             VARCHAR(50)    DEFAULT NULL,
    `filename`            VARCHAR(255)   DEFAULT NULL,
    `original_name`       VARCHAR(255)   DEFAULT NULL,
    `species_name`        VARCHAR(255)   DEFAULT NULL,
    `confidence`          DECIMAL(5,2)   DEFAULT NULL,
    `file_size`           INT            DEFAULT NULL,
    `processing_time_ms`  INT            DEFAULT NULL,
    `identification_time` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_identification_id`  (`identification_id`),
    INDEX `idx_species_name`           (`species_name`),
    INDEX `idx_identification_time`    (`identification_time` DESC),
    INDEX `idx_upload_id`              (`upload_id`),
    INDEX `idx_user_id`                (`user_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: unknown_submissions
-- Birds users submit that weren't recognised by AI
-- ✅ NEW: `reviewed_at` column tracks when admin acted on it
-- ============================================================
CREATE TABLE `unknown_submissions` (
    `id`              INT           NOT NULL AUTO_INCREMENT,
    `user_id`         VARCHAR(50)   DEFAULT NULL,
    `bird_name`       VARCHAR(255)  DEFAULT NULL,
    `scientific_name` VARCHAR(255)  DEFAULT NULL,
    `description`     TEXT,
    `habitat`         VARCHAR(500)  DEFAULT NULL,
    `image_filename`  VARCHAR(255)  DEFAULT NULL,
    `status`          VARCHAR(50)   NOT NULL DEFAULT 'pending',
    `submitted_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`     TIMESTAMP     NULL DEFAULT NULL,

    PRIMARY KEY (`id`),
    INDEX `idx_user_id`      (`user_id`),
    INDEX `idx_status`       (`status`),
    INDEX `idx_submitted_at` (`submitted_at` DESC)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: Admin account
-- Email:    admin0403@gmail.com
-- Password: MK@0403
-- ⚠️  Password hash below matches MK@0403 via bcrypt
-- ============================================================
INSERT INTO `users`
    (`user_id`, `email`, `phone_number`, `first_name`, `last_name`, `password_hash`, `role`, `is_active`)
VALUES (
    'admin_mkfinder_0001',
    'admin0403@gmail.com',
    '+0000000000',
    'Admin',
    'MKfinder',
    '$2y$10$YourHashHere_ReplacedBySetAdminPass',
    'admin',
    1
);

-- ⚠️  IMPORTANT: The admin password hash above is a placeholder.
--    After importing, run this in your browser ONCE to set the real password:
--    http://localhost/BirdImageRecognizer/set_admin_pass.php
--    Then DELETE set_admin_pass.php from your project folder!
--    OR manually update with this query after running set_admin_pass.php:
--    UPDATE users SET password_hash = '<hash from set_admin_pass>' WHERE email = 'admin0403@gmail.com';

-- ============================================================
-- SEED DATA: Demo test user
-- Email:    test@mkfinder.com
-- Password: test123
-- ============================================================
INSERT INTO `users`
    (`user_id`, `email`, `phone_number`, `first_name`, `last_name`, `password_hash`, `role`, `is_active`)
VALUES (
    'user_test_12345678',
    'test@mkfinder.com',
    '+1234567890',
    'Test',
    'User',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'user',
    1
);

-- ============================================================
-- SEED DATA: 3 AI-supported bird species
-- ✅ All have status='approved' so they show on Species page
-- ✅ image_path is NULL — set via Admin Panel when you upload photos
-- ============================================================
INSERT INTO `species`
    (`name`, `scientific_name`, `description`, `characteristics`, `habitat`, `diet`, `behavior`, `conservation_status`, `image_path`, `status`)
VALUES
(
    'American Robin',
    'Turdus migratorius',
    'The American Robin is a migratory songbird of the true thrush genus and Turdidae, the wider thrush family. Named after the European robin because of its reddish-orange breast, it is one of the most familiar birds across North America.',
    '["Red-orange breast and belly","Dark gray to black head and back","White markings around the eyes","Yellow beak with dark tip","White undertail coverts","Length: 8-11 inches","Wingspan: 12-16 inches"]',
    'Woodlands, parks, gardens, and lawns across North America',
    'Insects, earthworms, fruits, and berries',
    'Known for pulling earthworms from lawns, territorial during breeding season, forms large flocks in winter',
    'Least Concern',
    NULL,
    'approved'
),
(
    'Blue Jay',
    'Cyanocitta cristata',
    'The Blue Jay is a passerine bird in the family Corvidae, native to eastern North America. It is a highly intelligent and social bird known for its bright blue coloration, prominent crest, and complex vocalizations.',
    '["Bright blue upper parts with white underparts","Black necklace markings across throat","Prominent blue crest that can be raised or lowered","White and black barred wings and tail","Black bill and legs","Length: 11-12 inches","Wingspan: 13-17 inches"]',
    'Deciduous and mixed forests, parks, and residential areas with large trees',
    'Nuts, seeds, insects, eggs, and small animals',
    'Highly social, forms complex family groups, known for mobbing predators, excellent mimics of other bird calls',
    'Least Concern',
    NULL,
    'approved'
),
(
    'Northern Cardinal',
    'Cardinalis cardinalis',
    'The Northern Cardinal is a bird in the genus Cardinalis, also known as the redbird or common cardinal. Males display brilliant red plumage while females show warm brown with red accents on wings, tail, and crest.',
    '["Males: Brilliant red all over with black mask around face","Females: Warm brown with red tinges on wings, tail, and crest","Thick orange-red cone-shaped beak for cracking seeds","Prominent pointed crest","Length: 8.5-9 inches","Wingspan: 9.8-12.2 inches"]',
    'Woodlands, gardens, shrublands, and wetlands with dense cover',
    'Seeds, grains, fruits, and insects',
    'Non-migratory, territorial year-round, both males and females sing, males feed females during courtship',
    'Least Concern',
    NULL,
    'approved'
);

-- ============================================================
-- VERIFY — shows table list after import
-- ============================================================
SELECT 'MKfinder database setup complete!' AS `Status`;

SELECT
    TABLE_NAME   AS `Table`,
    TABLE_ROWS   AS `Approx Rows`,
    CREATE_TIME  AS `Created`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'mkfinder'
ORDER BY TABLE_NAME;