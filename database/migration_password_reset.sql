-- ============================================================
-- Password Reset & Email Notifications Table
-- Migration for Forgot Password Feature
-- ============================================================

-- Table for password reset tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_prt_email (email),
    INDEX idx_prt_token (token),
    INDEX idx_prt_expires (expires_at)
);

-- Table for email notifications log (optional - for audit trail)
CREATE TABLE IF NOT EXISTS email_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipient_email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    notification_type ENUM('password_reset', 'meeting_created', 'meeting_updated', 'task_assigned', 'task_completed', 'system_alert') NOT NULL,
    sent_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_en_user_id (user_id),
    INDEX idx_en_notification_type (notification_type),
    INDEX idx_en_sent_status (sent_status)
);

-- Table for system notifications/alerts (in-app notifications)
CREATE TABLE IF NOT EXISTS system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    alert_type ENUM('success', 'error', 'warning', 'info') NOT NULL DEFAULT 'info',
    notification_category ENUM('meeting', 'task', 'attendance', 'password', 'system') NOT NULL,
    related_entity_type VARCHAR(50) NULL,
    related_entity_id INT NULL,
    is_read ENUM('No', 'Yes') NOT NULL DEFAULT 'No',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sn_user_id (user_id),
    INDEX idx_sn_is_read (is_read),
    INDEX idx_sn_created_at (created_at)
);
