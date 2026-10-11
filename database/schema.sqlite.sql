-- SQLite version of schema.sql, for running locally without MySQL.
CREATE TABLE IF NOT EXISTS tickets (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    student_name  TEXT NOT NULL,
    unit_code     TEXT NOT NULL,
    topic         TEXT NOT NULL,
    status        TEXT NOT NULL DEFAULT 'waiting' CHECK (status IN ('waiting', 'helping', 'resolved')),
    facilitator   TEXT NULL,
    created_at    TEXT NOT NULL,
    claimed_at    TEXT NULL,
    resolved_at   TEXT NULL
);
CREATE INDEX IF NOT EXISTS idx_status_created  ON tickets (status, created_at);
CREATE INDEX IF NOT EXISTS idx_status_resolved ON tickets (status, resolved_at);
