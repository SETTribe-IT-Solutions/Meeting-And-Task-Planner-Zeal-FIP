-- ============================================================
-- Phase 2A Migration — Production Safe
-- Run once on live DB. All statements are idempotent.
-- ============================================================
SET NAMES utf8mb4;

-- FEATURE 3: Arrival Time on attendance
ALTER TABLE attendance
    ADD COLUMN IF NOT EXISTS arrival_time TIME NULL AFTER status;

-- FEATURE 4 & 5: Progress Notes + updated_at on tasks
ALTER TABLE tasks
    ADD COLUMN IF NOT EXISTS progress_notes TEXT NULL AFTER notes;

ALTER TABLE tasks
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    AFTER created_at;

-- FEATURE 1: Meeting Attachments table
CREATE TABLE IF NOT EXISTS meeting_attachments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id    INT          NOT NULL,
    uploaded_by   INT          NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name   VARCHAR(255) NOT NULL,
    file_size     INT          NOT NULL COMMENT 'Size in bytes',
    mime_type     VARCHAR(100) NOT NULL,
    uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meeting_id)  REFERENCES meetings(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_ma_meeting_id (meeting_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FEATURE 2: Task Attachments table
CREATE TABLE IF NOT EXISTS task_attachments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    task_id       INT          NOT NULL,
    uploaded_by   INT          NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name   VARCHAR(255) NOT NULL,
    file_size     INT          NOT NULL COMMENT 'Size in bytes',
    mime_type     VARCHAR(100) NOT NULL,
    uploaded_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id)     REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_tka_task_id (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
