-- MySQL 8 schema for Drop-in Queue.
-- Loaded automatically by the db container on first start (see docker-compose.yml).

CREATE TABLE IF NOT EXISTS tickets (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_name  VARCHAR(60)  NOT NULL,
    unit_code     CHAR(6)      NOT NULL,
    topic         VARCHAR(280) NOT NULL,
    status        ENUM('waiting', 'helping', 'resolved') NOT NULL DEFAULT 'waiting',
    facilitator   VARCHAR(60)  NULL,
    created_at    DATETIME     NOT NULL,
    claimed_at    DATETIME     NULL,
    resolved_at   DATETIME     NULL,

    -- The two queries the app runs most: open tickets in order, and today's resolved ones.
    INDEX idx_status_created  (status, created_at),
    INDEX idx_status_resolved (status, resolved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
