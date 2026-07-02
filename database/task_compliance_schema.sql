-- Task Compliance & Escalation Management System Schema
-- ---------------------------------------------------
-- Table: task_compliance (adds compliance scoring per task)
CREATE TABLE IF NOT EXISTS task_compliance (
    task_id INT NOT NULL,
    compliance_score INT DEFAULT 0,
    completed_on_time BOOLEAN DEFAULT FALSE,
    delay_days INT DEFAULT NULL,
    PRIMARY KEY (task_id),
    CONSTRAINT fk_task_compliance_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: escalation_log (records each escalation level and actions)
CREATE TABLE IF NOT EXISTS escalation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    level TINYINT NOT NULL COMMENT '1-4 escalation levels',
    escalated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    action_taken VARCHAR(255) NOT NULL,
    remarks TEXT,
    CONSTRAINT fk_escalation_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: disciplinary_notice (PDF notices for violations)
CREATE TABLE IF NOT EXISTS disciplinary_notice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    level TINYINT NOT NULL COMMENT 'Violation level 1-4',
    notice_type ENUM('Warning','Written Warning','Show Cause','Disciplinary Report') NOT NULL,
    generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pdf_path VARCHAR(255) NOT NULL,
    CONSTRAINT fk_notice_employee FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: audit_trail (generic audit log)
CREATE TABLE IF NOT EXISTS audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('task','escalation','notice','user') NOT NULL,
    entity_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    performed_by INT NOT NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT,
    CONSTRAINT fk_audit_user FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: notification_queue (queues emails, SMS, WhatsApp, push)
CREATE TABLE IF NOT EXISTS notification_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('email','sms','whatsapp','push') NOT NULL,
    recipient_id INT NOT NULL,
    channel VARCHAR(50) NOT NULL,
    payload TEXT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    CONSTRAINT fk_notification_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
