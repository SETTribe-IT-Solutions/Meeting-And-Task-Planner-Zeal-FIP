-- ============================================================
-- Meeting Planner — Schema + Full Seed Data
-- Auto-runs on fresh install. INSERT IGNORE = safe on existing DB.
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS meeting_planner;
USE meeting_planner;

-- ============================================================
-- DEPARTMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE COLLATE utf8mb4_bin,
  description TEXT NULL,
  is_active   ENUM('Yes','No') NOT NULL DEFAULT 'Yes',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE COLLATE utf8mb4_bin,
  password    VARCHAR(255) NOT NULL,
  role        VARCHAR(50)  NOT NULL,
  department  VARCHAR(100) NOT NULL COLLATE utf8mb4_bin,
  phone       VARCHAR(15)  NULL,
  gender      ENUM('Male','Female','Other') NULL,
  designation VARCHAR(100) NULL,
  taluka      VARCHAR(50)  NULL,
  isDeleted   ENUM('Yes','No') NOT NULL DEFAULT 'No',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role       (role),
  INDEX idx_users_department (department),
  INDEX idx_users_isDeleted  (isDeleted)
);

-- ============================================================
-- MEETINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS meetings (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  title         VARCHAR(150) NOT NULL,
  meeting_date  DATE         NOT NULL,
  meeting_time  TIME         NOT NULL,
  duration      INT          NULL COMMENT 'Duration in minutes',
  location      VARCHAR(255) NOT NULL,
  meeting_url   VARCHAR(500) NULL,
  mode          ENUM('Online','Offline','Hybrid') NOT NULL DEFAULT 'Offline',
  agenda        TEXT NOT NULL,
  department    VARCHAR(100) NOT NULL,
  organizer_id  INT  NOT NULL,
  status        ENUM('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  cancel_reason TEXT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organizer_id) REFERENCES users(id),
  INDEX idx_meetings_date         (meeting_date),
  INDEX idx_meetings_organizer_id (organizer_id),
  INDEX idx_meetings_department   (department),
  INDEX idx_meetings_status       (status)
);

-- ============================================================
-- ATTENDANCE
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id   INT  NOT NULL,
  user_id      INT  NOT NULL,
  status       ENUM('Present','Present with Late','Absent','Not Updated') NOT NULL DEFAULT 'Not Updated',
  arrival_time TIME NULL,
  remarks      TEXT NULL,
  UNIQUE KEY unique_attendance (meeting_id, user_id),
  FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  INDEX idx_attendance_user_id (user_id)
);

-- ============================================================
-- TASKS
-- ============================================================
CREATE TABLE IF NOT EXISTS tasks (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id     INT          NOT NULL,
  title          VARCHAR(150) NOT NULL,
  description    TEXT         NULL,
  assigned_to    INT          NOT NULL,
  due_date       DATE         NOT NULL,
  priority       ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  status         ENUM('Pending','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  notes          TEXT         NULL,
  progress_notes TEXT         NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (meeting_id)  REFERENCES meetings(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX idx_tasks_meeting_id  (meeting_id),
  INDEX idx_tasks_assigned_to (assigned_to),
  INDEX idx_tasks_status      (status),
  INDEX idx_tasks_due_date    (due_date)
);

-- ============================================================
-- TASK ASSIGNMENTS (multi-assignee junction table)
-- ============================================================
CREATE TABLE IF NOT EXISTS task_assignments (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  task_id     INT NOT NULL,
  user_id     INT NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_task_user (task_id, user_id),
  FOREIGN KEY (task_id)  REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_ta_user_id (user_id)
);

-- ============================================================
-- MEETING NOTES (MOM — Minutes of Meeting)
-- Fields per SRS 4.3: Note Title, Description, Department, Linked Task
-- ============================================================
CREATE TABLE IF NOT EXISTS meeting_notes (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id       INT          NOT NULL,
  note_title       VARCHAR(200) NOT NULL,
  note_description TEXT         NOT NULL,
  department       VARCHAR(100) NULL,
  linked_task_id   INT          NULL,
  created_by       INT          NOT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (meeting_id)     REFERENCES meetings(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by)     REFERENCES users(id),
  FOREIGN KEY (linked_task_id) REFERENCES tasks(id)   ON DELETE SET NULL,
  INDEX idx_notes_meeting_id (meeting_id)
);

-- ============================================================
-- MEETING ATTACHMENTS (file attach on meeting — SRS 4.1)
-- ============================================================
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
);

-- ============================================================
-- TASK ATTACHMENTS (file attach on task — SRS 4.4)
-- ============================================================
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
);

-- ============================================================
-- MEETING TRANSLATIONS (kept for backward compat, not actively used)
-- ============================================================
CREATE TABLE IF NOT EXISTS meeting_translations (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id       INT         NOT NULL,
  language_code    VARCHAR(10) NOT NULL,
  translated_agenda TEXT        NOT NULL,
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS task_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


SET FOREIGN_KEY_CHECKS = 1;
