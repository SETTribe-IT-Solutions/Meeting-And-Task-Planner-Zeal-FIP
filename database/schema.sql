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
  status       ENUM('Present','Absent','Pending') DEFAULT 'Pending',
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

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO departments (name, description, is_active) VALUES
('Administration',        'General administration and coordination.',                   'Yes'),
('Law & Order',           'Public safety, legal coordination, and order management.',   'Yes'),
('Revenue',               'Revenue administration and land records.',                   'Yes'),
('Health',                'Public health services and medical coordination.',            'Yes'),
('Education',             'Education planning and institutional coordination.',          'Yes'),
('Agriculture',           'Agriculture services and farmer support.',                   'Yes'),
('Finance',               'Financial planning, budget, and accounts.',                  'Yes'),
('IT Department',         'Information technology services and digital systems.',       'Yes'),
('Rural Development',     'Rural development projects and schemes.',                    'Yes'),
('Public Works Department','Public infrastructure and works management.',               'Yes')
ON DUPLICATE KEY UPDATE is_active=VALUES(is_active), description=VALUES(description);

-- Passwords are plain-text here and will be auto-upgraded to bcrypt on first login.
-- For production: replace these with bcrypt hashes before go-live.
INSERT INTO users (name, email, password, role, department, isDeleted) VALUES
('System Collector', 'collector@project.local', 'collector123', 'Collector', 'Administration', 'No'),
('Organizer Admin',  'organizer@project.local', 'admin123',     'Organizer', 'Administration', 'No'),
('Employee One',     'employee@project.local',  'employee123',  'Employee',  'Administration', 'No'),
('Shamal Patil',     'shamal@project.local',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee', 'Administration', 'No'),
('Anuja Garande',    'anuja@project.local',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee', 'Administration', 'No')
ON DUPLICATE KEY UPDATE email=email;

INSERT INTO meetings (title, meeting_date, meeting_time, location, mode, agenda, department, organizer_id, status) VALUES
('Weekly Planning Meeting', '2026-06-20', '10:00:00', 'Conference Room A', 'Offline', 'Review weekly goals and pending tasks.',    'Administration', 2, 'Scheduled'),
('HR Policy Review',        '2026-06-22', '14:30:00', 'Zoom Link',         'Online',  'Discuss HR policy updates and approvals.', 'Administration', 2, 'Scheduled')
ON DUPLICATE KEY UPDATE title=title;

INSERT INTO tasks (meeting_id, title, assigned_to, due_date, priority, status, notes) VALUES
(1, 'Prepare agenda document', 3, '2026-06-19', 'High',   'Pending',     'Finalize agenda before the meeting.'),
(1, 'Send attendance reminder', 3, '2026-06-20', 'Medium', 'Pending',     'Email reminder to attendees.'),
(2, 'Update policy checklist',  3, '2026-06-21', 'High',   'In Progress', 'Review latest policy changes.')
ON DUPLICATE KEY UPDATE title=title;
