-- =============================================================
-- ReClaim Smart Lost & Found Platform — Database Schema
-- Database: reclaim_db
-- Compatible with MySQL 5.7+ / MariaDB 10.3+ (XAMPP)
-- =============================================================

CREATE DATABASE IF NOT EXISTS reclaim_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE reclaim_db;

-- -------------------------------------------------------------
-- 1. users
--    Stores all registered accounts. Role 'admin' is set
--    manually in SQL only — never through the registration form.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name         VARCHAR(100)    NOT NULL,
    email        VARCHAR(150)    NOT NULL,
    password_hash VARCHAR(255)   NOT NULL,
    role         ENUM('user','admin') NOT NULL DEFAULT 'user',
    phone        VARCHAR(30)     DEFAULT NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY   uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 2. reports
--    Each row is either a "lost" or "found" item report.
--    is_deleted = 1 means soft-deleted (hidden from listings).
--    status transitions: open → claimed → resolved | closed
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reports (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED    NOT NULL,
    type          ENUM('lost','found') NOT NULL,
    title         VARCHAR(200)    NOT NULL,
    category      VARCHAR(100)    NOT NULL,
    description   TEXT            DEFAULT NULL,
    location      VARCHAR(200)    DEFAULT NULL,
    incident_date DATE            NOT NULL,
    status        ENUM('open','claimed','resolved','closed') NOT NULL DEFAULT 'open',
    image_path    VARCHAR(500)    DEFAULT NULL,
    is_deleted    TINYINT(1)      NOT NULL DEFAULT 0,
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY   (id),
    KEY idx_user_id     (user_id),
    KEY idx_type        (type),
    KEY idx_status      (status),
    KEY idx_category    (category),
    KEY idx_incident_date (incident_date),
    KEY idx_is_deleted  (is_deleted),
    CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 3. matches
--    Stores potential lost↔found pairs scored by the
--    rule-based matching algorithm. Unique on (lost_id, found_id)
--    so we never duplicate a suggestion.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS matches (
    id             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    lost_id        INT UNSIGNED    NOT NULL,
    found_id       INT UNSIGNED    NOT NULL,
    score          TINYINT UNSIGNED NOT NULL DEFAULT 0,
    matched_fields JSON            DEFAULT NULL,   -- e.g. ["category","title"]
    status         ENUM('pending','confirmed','dismissed') NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY    (id),
    UNIQUE KEY     uq_match (lost_id, found_id),
    KEY idx_score  (score),
    KEY idx_status (status),
    CONSTRAINT fk_match_lost  FOREIGN KEY (lost_id)  REFERENCES reports (id) ON DELETE CASCADE,
    CONSTRAINT fk_match_found FOREIGN KEY (found_id) REFERENCES reports (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 4. claims
--    A user submits a claim against a *found* report asserting
--    ownership. A unique constraint on (report_id, claimant_id)
--    prevents duplicate pending claims by the same person.
--    Only one claim per report can be 'approved'.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS claims (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    report_id           INT UNSIGNED    NOT NULL,
    claimant_id         INT UNSIGNED    NOT NULL,
    verification_detail TEXT            NOT NULL,
    status              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewer_id         INT UNSIGNED    DEFAULT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_claim_pair (report_id, claimant_id),
    KEY idx_status   (status),
    KEY idx_report   (report_id),
    CONSTRAINT fk_claim_report   FOREIGN KEY (report_id)   REFERENCES reports (id) ON DELETE CASCADE,
    CONSTRAINT fk_claim_claimant FOREIGN KEY (claimant_id) REFERENCES users   (id) ON DELETE CASCADE,
    CONSTRAINT fk_claim_reviewer FOREIGN KEY (reviewer_id) REFERENCES users   (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 5. notifications
--    Simple in-app notification feed per user.
--    is_read = 0 means unread (shows badge in navbar).
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED    NOT NULL,
    message    VARCHAR(500)    NOT NULL,
    link       VARCHAR(300)    DEFAULT NULL,
    is_read    TINYINT(1)      NOT NULL DEFAULT 0,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_read (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- 6. claim_history
--    Audit trail every time a claim's status changes.
--    Useful for the viva: "how did you implement tracking?"
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS claim_history (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    claim_id   INT UNSIGNED    NOT NULL,
    actor_id   INT UNSIGNED    NOT NULL,
    old_status VARCHAR(50)     DEFAULT NULL,
    new_status VARCHAR(50)     DEFAULT NULL,
    note       TEXT            DEFAULT NULL,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_claim (claim_id),
    CONSTRAINT fk_history_claim FOREIGN KEY (claim_id) REFERENCES claims (id) ON DELETE CASCADE,
    CONSTRAINT fk_history_actor FOREIGN KEY (actor_id) REFERENCES users  (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- SAMPLE DATA (safe passwords, no real PII)
-- =============================================================

-- Admin user  (password: Admin@123)
-- User 1      (password: User@123)
-- User 2      (password: User@123)
INSERT INTO users (name, email, password_hash, role, phone) VALUES
('Super Admin',  'admin@reclaim.local',  '$2y$12$6R9yRf0gixU5gLBBQ0JssuM6F6tJZYN2l/Z4GV6vpRNy9V4wDy0ey', 'admin', '9000000001'),
('Aryan Sharma', 'aryan@example.com',    '$2y$12$pioXE45cG91P0eJi5S2x/exlj.N3xJdU5Dn/MNDEYuUUKYYFjPdHi', 'user',  '9000000002'),
('Priya Mehta',  'priya@example.com',    '$2y$12$pioXE45cG91P0eJi5S2x/exlj.N3xJdU5Dn/MNDEYuUUKYYFjPdHi', 'user',  '9000000003');

-- Sample reports
INSERT INTO reports (user_id, type, title, category, description, location, incident_date, status) VALUES
(2, 'lost',  'Blue Samsung Galaxy S21',      'Electronics',   'Lost my phone near the library. Black protective case with a red stripe.', 'College Library Block B', '2026-10-20', 'open'),
(3, 'found', 'Samsung Galaxy Smartphone',    'Electronics',   'Found a Samsung phone near Library Block B. Screen is locked.',            'College Library',         '2026-10-21', 'open'),
(2, 'lost',  'Brown Leather Wallet',         'Accessories',   'Contains ID card, debit card and cash. Brown bifold wallet.',             'Canteen Area',            '2026-10-22', 'open'),
(3, 'found', 'Wallet with ID cards',         'Accessories',   'Found a brown wallet at canteen. Has some cards inside.',                 'College Canteen',         '2026-10-22', 'open'),
(2, 'lost',  'Scientific Calculator Casio',  'Stationery',    'Casio FX-991 scientific calculator. Has my name sticker on the back.',   'Exam Hall Room 204',      '2026-10-18', 'open'),
(3, 'found', 'Red Umbrella',                 'Accessories',   'Found a red umbrella near the main gate.',                               'Main Gate',               '2026-10-25', 'open');

-- Sample match (phone reports)
INSERT INTO matches (lost_id, found_id, score, matched_fields, status) VALUES
(1, 2, 80, '["category","title","location","date"]', 'pending'),
(3, 4, 75, '["category","title","location","date"]', 'pending');

-- Sample notifications
INSERT INTO notifications (user_id, message, link, is_read) VALUES
(2, 'A potential match was found for your lost report: Blue Samsung Galaxy S21', 'matches.php?report_id=1', 0),
(2, 'A potential match was found for your lost report: Brown Leather Wallet',    'matches.php?report_id=3', 0),
(3, 'Your found report has a potential match suggestion.',                        'matches.php?report_id=2', 1);
