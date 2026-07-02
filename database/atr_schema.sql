-- ATR & Intelligent Alert System Schema
-- ---------------------------------------------------
-- Table: atr_reports (main Action Taken Report)
CREATE TABLE IF NOT EXISTS atr_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    meeting_id INT NULL,
    employee_id INT NOT NULL,
    assigned_date DATETIME NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('Pending','In Progress','Completed','Rejected') NOT NULL DEFAULT 'Pending',
    progress_percent TINYINT NOT NULL DEFAULT 0,
    action_description TEXT,
    evidence_path VARCHAR(255) NULL,
    remarks TEXT,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approval_status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_atr_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_atr_meeting FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL,
    CONSTRAINT fk_atr_employee FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_atr_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: atr_history (timeline of changes)
CREATE TABLE IF NOT EXISTS atr_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atr_id INT NOT NULL,
    event_type ENUM('Created','Progress Update','Status Change','Evidence Upload','Approved','Rejected','Escalated') NOT NULL,
    event_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    performed_by INT NOT NULL,
    CONSTRAINT fk_history_atr FOREIGN KEY (atr_id) REFERENCES atr_reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: alert_queue (for email, SMS, push, WhatsApp)
CREATE TABLE IF NOT EXISTS alert_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('email','sms','push','whatsapp') NOT NULL,
    recipient_id INT NOT NULL,
    payload TEXT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    CONSTRAINT fk_alert_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
