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

-- ============================================================
-- SEED DATA (INSERT IGNORE = skips duplicates, safe on any install)
-- ============================================================

INSERT IGNORE INTO `departments` (`id`,`name`,`description`,`is_active`,`created_at`) VALUES
(1,'Administration','General administration and coordination.','Yes','2026-06-26 16:41:14'),
(2,'Law & Order','Public safety, legal coordination, and order management.','Yes','2026-06-26 16:41:14'),
(3,'Revenue','Revenue administration and land records.','Yes','2026-06-26 16:41:14'),
(4,'Health','Public health services and medical coordination.','Yes','2026-06-26 16:41:14'),
(5,'Education','Education planning and institutional coordination.','Yes','2026-06-26 16:41:14'),
(6,'Agriculture','Agriculture services and farmer support.','Yes','2026-06-26 16:41:14'),
(7,'Finance','Financial planning, budget, and accounts.','Yes','2026-06-26 16:41:14'),
(8,'IT Department','Information technology services and digital systems.','Yes','2026-06-26 16:41:14'),
(9,'Rural Development','Rural development projects and schemes.','Yes','2026-06-26 16:41:14'),
(10,'Public Works Department','Public infrastructure and works management.','Yes','2026-06-26 16:41:14');

INSERT IGNORE INTO `users` (`id`,`name`,`email`,`password`,`role`,`department`,`phone`,`gender`,`designation`,`taluka`,`isDeleted`,`created_at`) VALUES
(1,'System Collector','collector@project.local','$2y$10$EUCb114nQtuyN3BUJUOXhuEIrraA9TM3jzXSWweb3ljFb31ShE5u.','Collector','Administration',NULL,NULL,NULL,NULL,'No','2026-06-26 16:41:14'),
(2,'Organizer Admin','organizer@project.local','$2y$10$jSMN9fMPMU7290I9R7jrRedC7DdLExIHZa4NsCPsCSy6v77KJO8DC','Organizer','Administration',NULL,NULL,NULL,NULL,'No','2026-06-26 16:41:14'),
(3,'Employee One','employee@project.local','$2y$10$pFnHJRqitgWMfLcnfvFc6OVsx1IsCbC5z7eo/7//Tr0EaCmCAz0Aa','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-26 16:41:14'),
(4,'Shamal Patil','shamal@project.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-26 16:41:14'),
(5,'Anuja Garande','anuja@project.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-26 16:41:14'),
(6,'Rajesh Kulkarni','rajesh@project.local','emp123','Employee','Revenue','9876543210',NULL,'Revenue Inspector',NULL,'No','2026-06-26 16:44:54'),
(7,'Priya Deshmukh','priya@project.local','emp123','Employee','Health','9123456780',NULL,'Health Officer',NULL,'No','2026-06-26 16:44:54'),
(8,'Manoj Shinde','manoj@project.local','emp123','Employee','Education','9988776655',NULL,'Education Coordinator',NULL,'No','2026-06-26 16:44:54'),
(9,'Sunita Jadhav','sunita@project.local','emp123','Employee','Finance','9090909090',NULL,'Accounts Officer',NULL,'No','2026-06-26 16:44:54'),
(10,'Collector 2','collector2@project.local','collect2','Collector','Administration','8800000001',NULL,'District Collector',NULL,'No','2026-06-26 16:44:54'),
(11,'Testuser','anujatest@gmail.com','$2y$10$cJI/tgX6RdGwjqHDAh/uzuP7PHqeE6T92Am7suhGhqlPCb.zG4R1i','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-26 17:52:14'),
(12,'Administration User 1','user1administration@project.local','administration123','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(13,'Administration User 2','user2administration@project.local','administration123','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(14,'Administration User 3','user3administration@project.local','administration123','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(15,'Administration User 4','user4administration@project.local','administration123','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(16,'Administration User 5','user5administration@project.local','administration123','Employee','Administration',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(17,'Law Order User 1','user1laworder@project.local','laworder123','Employee','Law & Order',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(18,'Law Order User 2','user2laworder@project.local','laworder123','Employee','Law & Order',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(19,'Law Order User 3','user3laworder@project.local','laworder123','Employee','Law & Order',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(20,'Law Order User 4','user4laworder@project.local','laworder123','Employee','Law & Order',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(21,'Law Order User 5','user5laworder@project.local','laworder123','Employee','Law & Order',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(22,'Revenue User 1','user1revenue@project.local','revenue123','Employee','Revenue',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(23,'Revenue User 2','user2revenue@project.local','revenue123','Employee','Revenue',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(24,'Revenue User 3','user3revenue@project.local','revenue123','Employee','Revenue',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(25,'Revenue User 4','user4revenue@project.local','revenue123','Employee','Revenue',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(26,'Revenue User 5','user5revenue@project.local','revenue123','Employee','Revenue',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(27,'Health User 1','user1health@project.local','health123','Employee','Health',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(28,'Health User 2','user2health@project.local','health123','Employee','Health',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(29,'Health User 3','user3health@project.local','health123','Employee','Health',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(30,'Health User 4','user4health@project.local','health123','Employee','Health',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(31,'Health User 5','user5health@project.local','health123','Employee','Health',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(32,'Education User 1','user1education@project.local','education123','Employee','Education',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(33,'Education User 2','user2education@project.local','education123','Employee','Education',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(34,'Education User 3','user3education@project.local','education123','Employee','Education',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(35,'Education User 4','user4education@project.local','education123','Employee','Education',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(36,'Education User 5','user5education@project.local','education123','Employee','Education',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(37,'Agriculture User 1','user1agriculture@project.local','agriculture123','Employee','Agriculture',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(38,'Agriculture User 2','user2agriculture@project.local','agriculture123','Employee','Agriculture',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(39,'Agriculture User 3','user3agriculture@project.local','agriculture123','Employee','Agriculture',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(40,'Agriculture User 4','user4agriculture@project.local','agriculture123','Employee','Agriculture',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(41,'Agriculture User 5','user5agriculture@project.local','agriculture123','Employee','Agriculture',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(42,'Finance User 1','user1finance@project.local','finance123','Employee','Finance',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(43,'Finance User 2','user2finance@project.local','finance123','Employee','Finance',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(44,'Finance User 3','user3finance@project.local','finance123','Employee','Finance',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(45,'Finance User 4','user4finance@project.local','finance123','Employee','Finance',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(46,'Finance User 5','user5finance@project.local','finance123','Employee','Finance',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(47,'IT Department User 1','user1itdepartment@project.local','itdepartment123','Employee','IT Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(48,'IT Department User 2','user2itdepartment@project.local','itdepartment123','Employee','IT Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(49,'IT Department User 3','user3itdepartment@project.local','itdepartment123','Employee','IT Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(50,'IT Department User 4','user4itdepartment@project.local','itdepartment123','Employee','IT Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(51,'IT Department User 5','user5itdepartment@project.local','itdepartment123','Employee','IT Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(52,'Rural Development User 1','user1ruraldevelopment@project.local','ruraldevelopment123','Employee','Rural Development',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(53,'Rural Development User 2','user2ruraldevelopment@project.local','ruraldevelopment123','Employee','Rural Development',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(54,'Rural Development User 3','user3ruraldevelopment@project.local','ruraldevelopment123','Employee','Rural Development',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(55,'Rural Development User 4','user4ruraldevelopment@project.local','ruraldevelopment123','Employee','Rural Development',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(56,'Rural Development User 5','user5ruraldevelopment@project.local','ruraldevelopment123','Employee','Rural Development',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(57,'Public Works User 1','user1publicworks@project.local','publicworks123','Employee','Public Works Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(58,'Public Works User 2','user2publicworks@project.local','publicworks123','Employee','Public Works Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(59,'Public Works User 3','user3publicworks@project.local','publicworks123','Employee','Public Works Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(60,'Public Works User 4','user4publicworks@project.local','publicworks123','Employee','Public Works Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20'),
(61,'Public Works User 5','user5publicworks@project.local','publicworks123','Employee','Public Works Department',NULL,NULL,NULL,NULL,'No','2026-06-28 07:51:20');

INSERT IGNORE INTO `meetings` (`id`,`title`,`meeting_date`,`meeting_time`,`duration`,`location`,`meeting_url`,`mode`,`agenda`,`department`,`organizer_id`,`status`,`cancel_reason`,`created_at`,`updated_at`) VALUES
(1,'Revenue Department Quarterly Review','2026-06-05','10:00:00',90,'Collector Office - Room 1',NULL,'Offline','Review Q1 revenue collection targets.\nDiscuss arrears and pending recovery.\nAction plan for Q2.','Revenue',2,'Completed',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(2,'Health Department Emergency Meeting','2026-06-12','09:30:00',60,'Civil Hospital Conference Hall',NULL,'Offline','Review dengue outbreak response.\nStock status of medicines.\nField team deployment update.','Health',2,'Completed',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(3,'Education Infrastructure Planning','2026-06-18','11:00:00',120,'District Education Office',NULL,'Offline','School building repair status across all talukas.\nTeacher recruitment progress.\nMid-day meal scheme compliance.','Education',2,'Completed',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(4,'Finance Audit Preparatory Session','2026-06-20','14:00:00',60,'Finance Office',NULL,'Offline','Internal audit preparation.\nDocument checklist review.\nResponsibility assignment.','Finance',2,'Cancelled',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(5,'Latur District Weekly Coordination','2026-06-26','10:00:00',90,'Collector Office - Main Hall',NULL,'Hybrid','Weekly status update from all departments.\nPending action items review.\nUpcoming event coordination.','Administration',2,'Cancelled',NULL,'2026-06-26 16:44:54','2026-06-27 01:52:12'),
(6,'Agriculture Water Conservation Review','2026-06-30','11:30:00',75,'Agriculture Office',NULL,'Offline','Rainfall data analysis.\nIrrigation scheme status.\nFarmer compensation disbursement.','Agriculture',2,'Scheduled',NULL,'2026-06-26 16:44:54','2026-06-26 17:24:38'),
(7,'IT Infrastructure Modernisation Meeting','2026-07-03','10:00:00',60,'NIC Office',NULL,'Online','e-Governance portal update.\nBiometric attendance rollout.\nData security compliance.','IT Department',2,'Scheduled',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(8,'Public Works Department Monthly Review','2026-07-08','09:00:00',120,'PWD Office',NULL,'Offline','Road repair tender status.\nBridge inspection report.\nRain season maintenance plan.','Public Works Department',2,'Completed',NULL,'2026-06-26 16:44:54','2026-06-27 02:00:29'),
(9,'Rural Development Schemes Progress','2026-07-15','11:00:00',90,'Zilla Parishad Hall','','Offline','Updated agenda with new action items.','Rural Development',2,'Cancelled','Test','2026-06-26 16:44:54','2026-06-27 02:00:17'),
(10,'Law and Order Monthly Briefing','2026-06-26','08:00:00',180,'Police Headquarters',NULL,'Offline','Crime statistics for June.\nSensitive area monitoring.\nPolice personnel deployment.','Law & Order',2,'Scheduled',NULL,'2026-06-26 16:44:54','2026-06-27 02:37:26'),
(14,'AFter UI change Test','2026-06-27','15:30:00',30,'','https://www.mittalbuilders.com/sun-garnet','Online','1','Administration',2,'Scheduled',NULL,'2026-06-26 17:23:38','2026-06-26 17:23:38'),
(15,'Testuser meeting','2026-06-27','12:11:00',30,'','https://www.mittalbuilders.com/sun-garnet','Online','1','Administration',2,'Scheduled',NULL,'2026-06-26 17:53:22','2026-06-26 17:53:22'),
(16,'District Administration Review Meeting','2026-06-29','10:30:00',60,'Collectorate Office, Latur',NULL,'Offline','Review of district administrative performance, resource allocation for the current quarter, and status of pending files.','Administration',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(17,'Staff Coordination and Resource Planning','2026-07-01','14:00:00',60,'Online (Video Conference)',NULL,'Online','Inter-subdivision coordination, deployment of additional staff to high-workload units, and government scheme implementation review.','Administration',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(18,'Office Procedure Compliance Briefing','2026-07-03','11:00:00',60,'Zilla Parishad Hall, Latur',NULL,'Offline','Briefing on revised office procedures, updated documentation standards, and compliance with latest state government circulars.','Administration',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(19,'Monthly Performance Review Session','2026-07-07','15:30:00',60,'Conference Room B, Collectorate',NULL,'Hybrid','Monthly KPI review against departmental targets, identification of bottlenecks, and action plan for improvement.','Administration',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(20,'Administrative Reforms Workshop','2026-07-09','13:00:00',60,'Collectorate Office, Latur',NULL,'Offline','Workshop on administrative process reforms, digital record-keeping, and sharing of best practices across talukas.','Administration',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(21,'Public Safety Strategy Planning','2026-06-29','11:30:00',60,'District Council Hall, Latur',NULL,'Offline','Review of public safety incidents, planning preventive measures, and coordination with police stations across the district.','Law & Order',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(22,'Crime Prevention Coordination Meeting','2026-07-02','10:00:00',60,'SP Office Conference Hall, Latur',NULL,'Hybrid','Coordination with law enforcement units, review of ongoing investigations, and community policing initiatives.','Law & Order',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(23,'Traffic Management Review Session','2026-07-06','14:30:00',60,'Divisional Office, Latur',NULL,'Offline','Review of accident-prone zones, deployment of traffic personnel, and assessment of road safety measures.','Law & Order',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(24,'Law Enforcement Intelligence Briefing','2026-07-08','16:00:00',60,'Online (Video Conference)',NULL,'Online','Intelligence sharing briefing, inter-agency coordination, and review of cybercrime prevention activities.','Law & Order',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(25,'Emergency Response Preparedness Meeting','2026-07-10','11:00:00',60,'Divisional Office, Latur',NULL,'Offline','Review of emergency response protocols, disaster readiness, and coordination with fire and medical services.','Law & Order',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(26,'Land Records Verification Meeting','2026-06-30','10:00:00',60,'Revenue Bhavan, Latur',NULL,'Offline','Audit of digitised land records, correction of 7/12 extracts, and status of pending mutation entries.','Revenue',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(27,'Revenue Target Assessment Session','2026-07-02','15:00:00',60,'Collectorate Office, Latur',NULL,'Hybrid','Review of revenue collection targets, outstanding dues, and planning for accelerated recovery drives.','Revenue',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(28,'Property Registration Drive Planning','2026-07-06','13:30:00',60,'Revenue Bhavan, Latur',NULL,'Offline','Planning for property registration outreach camps, simplification of documentation, and staff assignment.','Revenue',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(29,'Tax Collection Progress Review','2026-07-08','11:30:00',60,'Online (Video Conference)',NULL,'Online','Monthly review of tax collection figures, pending defaulters list, and coordination with taluka revenue offices.','Revenue',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(30,'Revenue Department Monthly Coordination','2026-07-10','14:00:00',60,'Zilla Parishad Hall, Latur',NULL,'Offline','Monthly coordination meeting covering record-keeping, encroachment removal, and disaster-related compensation disbursements.','Revenue',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(31,'Public Health Campaign Planning','2026-06-29','14:00:00',60,'District Hospital, Latur',NULL,'Offline','Planning of health awareness campaigns, distribution of medicines, and coordination with PHCs and sub-centres.','Health',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(32,'Primary Health Centre Performance Review','2026-07-01','11:00:00',60,'Civil Hospital Conference Hall, Latur',NULL,'Hybrid','Review of PHC-level indicators: OPD attendance, bed occupancy, maternal health outcomes, and vaccination coverage.','Health',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(33,'Vaccination Drive Coordination Meeting','2026-07-03','10:30:00',60,'District Hospital, Latur',NULL,'Offline','Coordination for upcoming vaccination campaigns, cold chain management, and ASHA worker mobilisation.','Health',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(34,'Disease Surveillance and Response Briefing','2026-07-08','13:00:00',60,'Online (Video Conference)',NULL,'Online','Weekly disease surveillance data review, outbreak alert management, and vector-borne disease prevention planning.','Health',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(35,'Health Infrastructure Inspection Planning','2026-07-09','16:30:00',60,'PHC Training Hall, Latur',NULL,'Offline','Scheduling of health facility inspections, equipment maintenance review, and preparation of inspection reports.','Health',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(36,'School Enrollment Drive Planning','2026-06-30','13:30:00',60,'DIET Office, Latur',NULL,'Offline','Planning for out-of-school children survey, enrollment drive scheduling, and coordination with gram panchayats.','Education',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(37,'Mid-Day Meal Programme Review','2026-07-02','10:00:00',60,'Education Department Hall, Latur',NULL,'Hybrid','Review of MDM compliance, nutritional standards, vendor performance, and grievance redressal.','Education',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(38,'Teacher Recruitment and Training Planning','2026-07-07','14:30:00',60,'Divisional Board Hall, Latur',NULL,'Offline','Review of teacher vacancies, guest faculty appointments, and upcoming in-service training calendar.','Education',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(39,'Educational Infrastructure Needs Assessment','2026-07-09','11:00:00',60,'Online (Video Conference)',NULL,'Online','Assessment of school building conditions, toilet facilities, drinking water supply, and digital classroom readiness.','Education',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(40,'Academic Performance and Board Results Review','2026-07-10','15:00:00',60,'DIET Office, Latur',NULL,'Offline','Analysis of SSC/HSC board results, identification of low-performing schools, and remedial measures planning.','Education',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(41,'Kharif Crop Season Planning Meeting','2026-06-29','11:00:00',60,'Krishi Bhavan, Latur',NULL,'Offline','Planning for kharif crop advisory, seed distribution, and coordination with agriculture assistance centres.','Agriculture',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(42,'Irrigation Scheme Review and Monitoring','2026-06-30','16:00:00',60,'Agriculture Office, Latur',NULL,'Hybrid','Review of minor irrigation schemes, water availability forecasting, and distribution schedule for farm ponds.','Agriculture',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(43,'Farmer Subsidy Distribution Coordination','2026-07-03','12:30:00',60,'Krishi Bhavan, Latur',NULL,'Offline','Coordination for PM-KISAN disbursements, fertiliser subsidy pipeline, and beneficiary list verification.','Agriculture',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(44,'Agricultural Extension Services Briefing','2026-07-07','10:30:00',60,'Online (Video Conference)',NULL,'Online','Briefing on new crop varieties, soil health card distribution, and scheduling of krishi melas across talukas.','Agriculture',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(45,'Crop Insurance Drive and Farmer Outreach','2026-07-08','14:00:00',60,'Agriculture Training Centre, Latur',NULL,'Offline','Review of PMFBY enrolment, awareness camps, and coordination with insurance companies for timely claim settlement.','Agriculture',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(46,'Budget Utilisation and Expenditure Review','2026-07-01','10:00:00',60,'Finance Department Office, Latur',NULL,'Offline','Review of departmental budget utilisation, identification of underspent heads, and re-appropriation proposals.','Finance',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(47,'Annual Financial Target Planning','2026-07-02','13:00:00',60,'Treasury Office, Latur',NULL,'Hybrid','Annual planning session for revenue and expenditure targets, grant-in-aid tracking, and scheme-wise fund monitoring.','Finance',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(48,'Audit Compliance and Documentation Meeting','2026-07-06','15:30:00',60,'Finance Department Office, Latur',NULL,'Offline','Preparation for CAG/internal audit, pending audit paras, and documentation compliance review.','Finance',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(49,'Treasury Operations and Fund Flow Review','2026-07-08','11:30:00',60,'Online (Video Conference)',NULL,'Online','Review of treasury operations, salary disbursement status, pension payments, and fund flow to talukas.','Finance',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(50,'Financial Reporting Coordination Session','2026-07-10','13:30:00',60,'Treasury Office, Latur',NULL,'Offline','Monthly financial reporting consolidation, reconciliation of accounts, and preparation of state-level returns.','Finance',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(51,'Digital Services Implementation Review','2026-06-30','14:30:00',60,'NIC Office, Latur',NULL,'Offline','Review of citizen-facing digital services uptime, portal grievances, and new service deployment timeline.','IT Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(52,'Cybersecurity Compliance and Audit Planning','2026-07-03','11:00:00',60,'IT Department, Collectorate',NULL,'Hybrid','Audit of government systems for cybersecurity compliance, password policies, and data backup verification.','IT Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(53,'e-Governance Platform Development Meeting','2026-07-06','10:00:00',60,'Online (Video Conference)',NULL,'Online','Progress review of e-governance implementations, integration with state portals, and issue resolution.','IT Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(54,'IT Infrastructure Upgrade Coordination','2026-07-07','13:30:00',60,'NIC Office, Latur',NULL,'Offline','Planning for server upgrades, network expansion to talukas, and hardware procurement timelines.','IT Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(55,'Data Management and Records Digitisation Review','2026-07-09','16:00:00',60,'IT Department, Collectorate',NULL,'Offline','Review of document digitisation progress, storage capacity, and training needs for data entry operators.','IT Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(56,'MNREGA Progress Review Meeting','2026-06-29','12:00:00',60,'Rural Development Office, Latur',NULL,'Offline','Review of MNREGA job card issuance, wage disbursement, pending work orders, and muster roll maintenance.','Rural Development',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(57,'Gram Panchayat Development Planning','2026-07-01','14:00:00',60,'DRDA Office, Latur',NULL,'Hybrid','Planning of Gram Panchayat development works, 15th Finance Commission fund utilisation, and DPDP convergence.','Rural Development',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(58,'Rural Sanitation and Swachh Bharat Review','2026-07-02','11:30:00',60,'Rural Development Office, Latur',NULL,'Offline','Review of ODF status across villages, toilet construction progress, and maintenance of sanitation facilities.','Rural Development',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(59,'Village Road Construction Progress Meeting','2026-07-07','15:00:00',60,'Online (Video Conference)',NULL,'Online','Progress review of PM Gram Sadak Yojana works, quality certificates, and upcoming inspection schedule.','Rural Development',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(60,'Rural Housing Scheme Beneficiary Coordination','2026-07-10','10:30:00',60,'DRDA Office, Latur',NULL,'Offline','Coordination for PMAY-G beneficiary selection, house construction monitoring, and grievance redressal.','Rural Development',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(61,'Bridge Construction and Safety Review','2026-06-30','10:30:00',60,'PWD Office, Latur',NULL,'Offline','Status of ongoing bridge construction projects, structural safety inspections, and monsoon readiness assessment.','Public Works Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(62,'Road Maintenance and Pothole Repair Planning','2026-07-01','13:00:00',60,'PWD Conference Hall, Latur',NULL,'Hybrid','Planning of road maintenance calendar, pothole repair drives, contractor performance review, and cost estimates.','Public Works Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(63,'Public Infrastructure Development Meeting','2026-07-03','16:30:00',60,'PWD Office, Latur',NULL,'Offline','Review of public building construction projects, government rest houses, and drainage system works.','Public Works Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(64,'Tender Evaluation and Contract Award Review','2026-07-08','12:30:00',60,'Online (Video Conference)',NULL,'Online','Review of pending tenders, evaluation committee recommendations, and contract award transparency measures.','Public Works Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46'),
(65,'Quality Control and Site Inspection Planning','2026-07-09','14:30:00',60,'PWD Conference Hall, Latur',NULL,'Offline','Planning of quality audits for ongoing infrastructure works, 3rd-party inspection scheduling, and test certificate compliance.','Public Works Department',2,'Scheduled',NULL,'2026-06-28 08:43:46','2026-06-28 08:43:46');

INSERT IGNORE INTO `tasks` (`id`,`meeting_id`,`title`,`description`,`assigned_to`,`due_date`,`priority`,`status`,`notes`,`progress_notes`,`created_at`,`updated_at`) VALUES
(1,1,'Prepare Q2 revenue recovery plan','Detail district-wise recovery targets and timeline.',6,'2026-06-15','High','Completed','Submit to collector by EOD',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(2,1,'Compile arrears data from all talukas','Include officer-wise breakdown.',7,'2026-06-12','High','Completed','',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(3,1,'Send notices to defaulters','Coordinate with legal section for notice drafts.',3,'2026-07-01','Medium','In Progress','Legal notices required',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(4,2,'Procure ORS and IV fluids for PHCs','Minimum 3-month stock at each PHC.',7,'2026-06-18','High','Completed','Urgent procurement',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(5,2,'Deploy mobile medical teams to affected zones','Teams to report daily to district health office.',4,'2026-06-30','High','In Progress','',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(6,2,'Submit disease surveillance report','Use standard GOI format.',3,'2026-07-05','Medium','Pending','Weekly report format',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(7,3,'Audit school building repair submissions','Cross-check contractor submissions against site photos.',8,'2026-06-25','Medium','Completed','',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(8,3,'Prepare teacher posting order draft','Include rural preference candidates.',5,'2026-07-02','High','In Progress','NPC approval needed',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(9,3,'Mid-day meal compliance verification','Random school visits across 3 talukas.',3,'2026-07-10','Low','Pending','',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(10,5,'Consolidate weekly department status reports','Template shared on group.',3,'2026-06-28','High','Pending','Due Friday',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(11,5,'Update action item tracker','Mark completed items green.',4,'2026-06-28','Medium','Pending','',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(12,5,'Circulate minutes to all HODs','Use official letter format.',5,'2026-06-29','Low','Pending','',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(13,1,'Submit land records digitisation progress','Monthly report pending.',6,'2026-06-01','High','Pending','OVERDUE - escalated',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(14,2,'Update GIS mapping data','Coordinate with NIC.',7,'2026-06-10','Medium','Pending','',NULL,'2026-06-26 16:44:54','2026-06-26 16:44:54');

INSERT IGNORE INTO `task_assignments` (`id`,`task_id`,`user_id`,`assigned_at`) VALUES
(1,1,6,'2026-06-26 16:44:54'),(2,2,7,'2026-06-26 16:44:54'),(3,3,3,'2026-06-26 16:44:54'),
(4,3,6,'2026-06-26 16:44:54'),(5,4,7,'2026-06-26 16:44:54'),(6,5,4,'2026-06-26 16:44:54'),
(7,5,7,'2026-06-26 16:44:54'),(8,6,3,'2026-06-26 16:44:54'),(9,7,8,'2026-06-26 16:44:54'),
(10,8,5,'2026-06-26 16:44:54'),(11,8,9,'2026-06-26 16:44:54'),(12,9,3,'2026-06-26 16:44:54'),
(13,10,3,'2026-06-26 16:44:54'),(14,10,4,'2026-06-26 16:44:54'),(15,11,4,'2026-06-26 16:44:54'),
(16,12,5,'2026-06-26 16:44:54'),(17,13,6,'2026-06-26 16:44:54'),(18,14,7,'2026-06-26 16:44:54');

INSERT IGNORE INTO `meeting_notes` (`id`,`meeting_id`,`note_title`,`note_description`,`department`,`linked_task_id`,`created_by`,`created_at`,`updated_at`) VALUES
(1,1,'Q2 Revenue Target Finalised','Meeting resolved to set Q2 target at Rs. 45 crore across the district. Sub-divisional officers to submit ward-wise breakdown by June 15.','Revenue',1,2,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(2,1,'Defaulter Notice Process Approved','Legal section to prepare standard notice templates. First batch of 120 notices to be dispatched by July 1.','Revenue',3,2,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(3,2,'Emergency Procurement Approved','Collector approved emergency procurement of medicines without standard tender process under Rule 19. CMO to process PO within 24 hours.','Health',4,2,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(4,2,'Mobile Team Deployment Schedule','Three mobile teams deployed - Team A to Nilanga, Team B to Udgir, Team C to Ausa. Daily reporting to DHMO mandatory.','Health',5,2,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(5,3,'School Repair Fund Released','Education department fund of Rs. 1.2 crore released for 15 school building repairs. Audit by June 25.','Education',7,2,'2026-06-26 16:44:54','2026-06-26 16:44:54'),
(6,5,'Weekly Meeting Outcomes','All departments to submit pending action items by June 28. Finance to clear pending bills before month end. Revenue to expedite defaulter notices.','Administration',10,2,'2026-06-26 16:44:54','2026-06-26 16:44:54');

INSERT IGNORE INTO `attendance` (`id`,`meeting_id`,`user_id`,`status`,`arrival_time`,`remarks`) VALUES
(1,1,3,'Present','09:55:00','On time'),(2,1,4,'Present','10:02:00',''),(3,1,5,'Absent',NULL,'On leave'),
(4,1,6,'Present','10:00:00',''),(5,1,7,'Present','09:58:00',''),(6,2,3,'Present','09:25:00',''),
(7,2,4,'Present','09:30:00',''),(8,2,6,'Absent',NULL,'Field duty'),(9,2,7,'Present','09:28:00','On time'),
(10,3,3,'Present','10:55:00',''),(11,3,4,'Absent',NULL,'Sick leave'),(12,3,5,'Present','11:00:00',''),
(13,3,7,'Present','11:03:00',''),(14,3,8,'Present','10:58:00',''),(23,10,3,'Present','07:55:00',''),
(24,10,5,'Present','08:00:00',''),(25,10,6,'Absent',NULL,'WFH'),(27,14,5,'Present with Late',NULL,''),
(29,16,1,'Not Updated',NULL,NULL),(30,16,2,'Not Updated',NULL,NULL),(31,16,3,'Not Updated',NULL,NULL),
(32,16,4,'Not Updated',NULL,NULL),(33,16,5,'Not Updated',NULL,NULL),(34,16,10,'Not Updated',NULL,NULL),
(35,16,11,'Not Updated',NULL,NULL),(36,16,12,'Not Updated',NULL,NULL),(37,16,13,'Not Updated',NULL,NULL),
(38,16,14,'Not Updated',NULL,NULL),(39,16,15,'Not Updated',NULL,NULL),(40,16,16,'Not Updated',NULL,NULL),
(41,17,1,'Not Updated',NULL,NULL),(42,17,2,'Not Updated',NULL,NULL),(43,17,3,'Not Updated',NULL,NULL),
(44,17,4,'Not Updated',NULL,NULL),(45,17,5,'Not Updated',NULL,NULL),(46,17,10,'Not Updated',NULL,NULL),
(47,17,11,'Not Updated',NULL,NULL),(48,17,12,'Not Updated',NULL,NULL),(49,17,13,'Not Updated',NULL,NULL),
(50,17,14,'Not Updated',NULL,NULL),(51,17,15,'Not Updated',NULL,NULL),(52,17,16,'Not Updated',NULL,NULL),
(53,18,1,'Not Updated',NULL,NULL),(54,18,2,'Not Updated',NULL,NULL),(55,18,3,'Not Updated',NULL,NULL),
(56,18,4,'Not Updated',NULL,NULL),(57,18,5,'Not Updated',NULL,NULL),(58,18,10,'Not Updated',NULL,NULL),
(59,18,11,'Not Updated',NULL,NULL),(60,18,12,'Not Updated',NULL,NULL),(61,18,13,'Not Updated',NULL,NULL),
(62,18,14,'Not Updated',NULL,NULL),(63,18,15,'Not Updated',NULL,NULL),(64,18,16,'Not Updated',NULL,NULL),
(65,19,1,'Not Updated',NULL,NULL),(66,19,2,'Not Updated',NULL,NULL),(67,19,3,'Not Updated',NULL,NULL),
(68,19,4,'Not Updated',NULL,NULL),(69,19,5,'Not Updated',NULL,NULL),(70,19,10,'Not Updated',NULL,NULL),
(71,19,11,'Not Updated',NULL,NULL),(72,19,12,'Not Updated',NULL,NULL),(73,19,13,'Not Updated',NULL,NULL),
(74,19,14,'Not Updated',NULL,NULL),(75,19,15,'Not Updated',NULL,NULL),(76,19,16,'Not Updated',NULL,NULL),
(77,20,1,'Not Updated',NULL,NULL),(78,20,2,'Not Updated',NULL,NULL),(79,20,3,'Not Updated',NULL,NULL),
(80,20,4,'Not Updated',NULL,NULL),(81,20,5,'Not Updated',NULL,NULL),(82,20,10,'Not Updated',NULL,NULL),
(83,20,11,'Not Updated',NULL,NULL),(84,20,12,'Not Updated',NULL,NULL),(85,20,13,'Not Updated',NULL,NULL),
(86,20,14,'Not Updated',NULL,NULL),(87,20,15,'Not Updated',NULL,NULL),(88,20,16,'Not Updated',NULL,NULL),
(89,21,17,'Not Updated',NULL,NULL),(90,21,18,'Not Updated',NULL,NULL),(91,21,19,'Not Updated',NULL,NULL),
(92,21,20,'Not Updated',NULL,NULL),(93,21,21,'Not Updated',NULL,NULL),(94,22,17,'Not Updated',NULL,NULL),
(95,22,18,'Not Updated',NULL,NULL),(96,22,19,'Not Updated',NULL,NULL),(97,22,20,'Not Updated',NULL,NULL),
(98,22,21,'Not Updated',NULL,NULL),(99,23,17,'Not Updated',NULL,NULL),(100,23,18,'Not Updated',NULL,NULL),
(101,23,19,'Not Updated',NULL,NULL),(102,23,20,'Not Updated',NULL,NULL),(103,23,21,'Not Updated',NULL,NULL),
(104,24,17,'Not Updated',NULL,NULL),(105,24,18,'Not Updated',NULL,NULL),(106,24,19,'Not Updated',NULL,NULL),
(107,24,20,'Not Updated',NULL,NULL),(108,24,21,'Not Updated',NULL,NULL),(109,25,17,'Not Updated',NULL,NULL),
(110,25,18,'Not Updated',NULL,NULL),(111,25,19,'Not Updated',NULL,NULL),(112,25,20,'Not Updated',NULL,NULL),
(113,25,21,'Not Updated',NULL,NULL),(114,26,6,'Not Updated',NULL,NULL),(115,26,22,'Not Updated',NULL,NULL),
(116,26,23,'Not Updated',NULL,NULL),(117,26,24,'Not Updated',NULL,NULL),(118,26,25,'Not Updated',NULL,NULL),
(119,26,26,'Not Updated',NULL,NULL),(120,27,6,'Not Updated',NULL,NULL),(121,27,22,'Not Updated',NULL,NULL),
(122,27,23,'Not Updated',NULL,NULL),(123,27,24,'Not Updated',NULL,NULL),(124,27,25,'Not Updated',NULL,NULL),
(125,27,26,'Not Updated',NULL,NULL),(126,28,6,'Not Updated',NULL,NULL),(127,28,22,'Not Updated',NULL,NULL),
(128,28,23,'Not Updated',NULL,NULL),(129,28,24,'Not Updated',NULL,NULL),(130,28,25,'Not Updated',NULL,NULL),
(131,28,26,'Not Updated',NULL,NULL),(132,29,6,'Not Updated',NULL,NULL),(133,29,22,'Not Updated',NULL,NULL),
(134,29,23,'Not Updated',NULL,NULL),(135,29,24,'Not Updated',NULL,NULL),(136,29,25,'Not Updated',NULL,NULL),
(137,29,26,'Not Updated',NULL,NULL),(138,30,6,'Not Updated',NULL,NULL),(139,30,22,'Not Updated',NULL,NULL),
(140,30,23,'Not Updated',NULL,NULL),(141,30,24,'Not Updated',NULL,NULL),(142,30,25,'Not Updated',NULL,NULL),
(143,30,26,'Not Updated',NULL,NULL),(144,31,7,'Not Updated',NULL,NULL),(145,31,27,'Not Updated',NULL,NULL),
(146,31,28,'Not Updated',NULL,NULL),(147,31,29,'Not Updated',NULL,NULL),(148,31,30,'Not Updated',NULL,NULL),
(149,31,31,'Not Updated',NULL,NULL),(150,32,7,'Not Updated',NULL,NULL),(151,32,27,'Not Updated',NULL,NULL),
(152,32,28,'Not Updated',NULL,NULL),(153,32,29,'Not Updated',NULL,NULL),(154,32,30,'Not Updated',NULL,NULL),
(155,32,31,'Not Updated',NULL,NULL),(156,33,7,'Not Updated',NULL,NULL),(157,33,27,'Not Updated',NULL,NULL),
(158,33,28,'Not Updated',NULL,NULL),(159,33,29,'Not Updated',NULL,NULL),(160,33,30,'Not Updated',NULL,NULL),
(161,33,31,'Not Updated',NULL,NULL),(162,34,7,'Not Updated',NULL,NULL),(163,34,27,'Not Updated',NULL,NULL),
(164,34,28,'Not Updated',NULL,NULL),(165,34,29,'Not Updated',NULL,NULL),(166,34,30,'Not Updated',NULL,NULL),
(167,34,31,'Not Updated',NULL,NULL),(168,35,7,'Not Updated',NULL,NULL),(169,35,27,'Not Updated',NULL,NULL),
(170,35,28,'Not Updated',NULL,NULL),(171,35,29,'Not Updated',NULL,NULL),(172,35,30,'Not Updated',NULL,NULL),
(173,35,31,'Not Updated',NULL,NULL),(174,36,8,'Not Updated',NULL,NULL),(175,36,32,'Not Updated',NULL,NULL),
(176,36,33,'Not Updated',NULL,NULL),(177,36,34,'Not Updated',NULL,NULL),(178,36,35,'Not Updated',NULL,NULL),
(179,36,36,'Not Updated',NULL,NULL),(180,37,8,'Not Updated',NULL,NULL),(181,37,32,'Not Updated',NULL,NULL),
(182,37,33,'Not Updated',NULL,NULL),(183,37,34,'Not Updated',NULL,NULL),(184,37,35,'Not Updated',NULL,NULL),
(185,37,36,'Not Updated',NULL,NULL),(186,38,8,'Not Updated',NULL,NULL),(187,38,32,'Not Updated',NULL,NULL),
(188,38,33,'Not Updated',NULL,NULL),(189,38,34,'Not Updated',NULL,NULL),(190,38,35,'Not Updated',NULL,NULL),
(191,38,36,'Not Updated',NULL,NULL),(192,39,8,'Not Updated',NULL,NULL),(193,39,32,'Not Updated',NULL,NULL),
(194,39,33,'Not Updated',NULL,NULL),(195,39,34,'Not Updated',NULL,NULL),(196,39,35,'Not Updated',NULL,NULL),
(197,39,36,'Not Updated',NULL,NULL),(198,40,8,'Not Updated',NULL,NULL),(199,40,32,'Not Updated',NULL,NULL),
(200,40,33,'Not Updated',NULL,NULL),(201,40,34,'Not Updated',NULL,NULL),(202,40,35,'Not Updated',NULL,NULL),
(203,40,36,'Not Updated',NULL,NULL),(204,41,37,'Not Updated',NULL,NULL),(205,41,38,'Not Updated',NULL,NULL),
(206,41,39,'Not Updated',NULL,NULL),(207,41,40,'Not Updated',NULL,NULL),(208,41,41,'Not Updated',NULL,NULL),
(209,42,37,'Not Updated',NULL,NULL),(210,42,38,'Not Updated',NULL,NULL),(211,42,39,'Not Updated',NULL,NULL),
(212,42,40,'Not Updated',NULL,NULL),(213,42,41,'Not Updated',NULL,NULL),(214,43,37,'Not Updated',NULL,NULL),
(215,43,38,'Not Updated',NULL,NULL),(216,43,39,'Not Updated',NULL,NULL),(217,43,40,'Not Updated',NULL,NULL),
(218,43,41,'Not Updated',NULL,NULL),(219,44,37,'Not Updated',NULL,NULL),(220,44,38,'Not Updated',NULL,NULL),
(221,44,39,'Not Updated',NULL,NULL),(222,44,40,'Not Updated',NULL,NULL),(223,44,41,'Not Updated',NULL,NULL),
(224,45,37,'Not Updated',NULL,NULL),(225,45,38,'Not Updated',NULL,NULL),(226,45,39,'Not Updated',NULL,NULL),
(227,45,40,'Not Updated',NULL,NULL),(228,45,41,'Not Updated',NULL,NULL),(229,46,9,'Not Updated',NULL,NULL),
(230,46,42,'Not Updated',NULL,NULL),(231,46,43,'Not Updated',NULL,NULL),(232,46,44,'Not Updated',NULL,NULL),
(233,46,45,'Not Updated',NULL,NULL),(234,46,46,'Not Updated',NULL,NULL),(235,47,9,'Not Updated',NULL,NULL),
(236,47,42,'Not Updated',NULL,NULL),(237,47,43,'Not Updated',NULL,NULL),(238,47,44,'Not Updated',NULL,NULL),
(239,47,45,'Not Updated',NULL,NULL),(240,47,46,'Not Updated',NULL,NULL),(241,48,9,'Not Updated',NULL,NULL),
(242,48,42,'Not Updated',NULL,NULL),(243,48,43,'Not Updated',NULL,NULL),(244,48,44,'Not Updated',NULL,NULL),
(245,48,45,'Not Updated',NULL,NULL),(246,48,46,'Not Updated',NULL,NULL),(247,49,9,'Not Updated',NULL,NULL),
(248,49,42,'Not Updated',NULL,NULL),(249,49,43,'Not Updated',NULL,NULL),(250,49,44,'Not Updated',NULL,NULL),
(251,49,45,'Not Updated',NULL,NULL),(252,49,46,'Not Updated',NULL,NULL),(253,50,9,'Not Updated',NULL,NULL),
(254,50,42,'Not Updated',NULL,NULL),(255,50,43,'Not Updated',NULL,NULL),(256,50,44,'Not Updated',NULL,NULL),
(257,50,45,'Not Updated',NULL,NULL),(258,50,46,'Not Updated',NULL,NULL),(259,51,47,'Not Updated',NULL,NULL),
(260,51,48,'Not Updated',NULL,NULL),(261,51,49,'Not Updated',NULL,NULL),(262,51,50,'Not Updated',NULL,NULL),
(263,51,51,'Not Updated',NULL,NULL),(264,52,47,'Not Updated',NULL,NULL),(265,52,48,'Not Updated',NULL,NULL),
(266,52,49,'Not Updated',NULL,NULL),(267,52,50,'Not Updated',NULL,NULL),(268,52,51,'Not Updated',NULL,NULL),
(269,53,47,'Not Updated',NULL,NULL),(270,53,48,'Not Updated',NULL,NULL),(271,53,49,'Not Updated',NULL,NULL),
(272,53,50,'Not Updated',NULL,NULL),(273,53,51,'Not Updated',NULL,NULL),(274,54,47,'Not Updated',NULL,NULL),
(275,54,48,'Not Updated',NULL,NULL),(276,54,49,'Not Updated',NULL,NULL),(277,54,50,'Not Updated',NULL,NULL),
(278,54,51,'Not Updated',NULL,NULL),(279,55,47,'Not Updated',NULL,NULL),(280,55,48,'Not Updated',NULL,NULL),
(281,55,49,'Not Updated',NULL,NULL),(282,55,50,'Not Updated',NULL,NULL),(283,55,51,'Not Updated',NULL,NULL),
(284,56,52,'Not Updated',NULL,NULL),(285,56,53,'Not Updated',NULL,NULL),(286,56,54,'Not Updated',NULL,NULL),
(287,56,55,'Not Updated',NULL,NULL),(288,56,56,'Not Updated',NULL,NULL),(289,57,52,'Not Updated',NULL,NULL),
(290,57,53,'Not Updated',NULL,NULL),(291,57,54,'Not Updated',NULL,NULL),(292,57,55,'Not Updated',NULL,NULL),
(293,57,56,'Not Updated',NULL,NULL),(294,58,52,'Not Updated',NULL,NULL),(295,58,53,'Not Updated',NULL,NULL),
(296,58,54,'Not Updated',NULL,NULL),(297,58,55,'Not Updated',NULL,NULL),(298,58,56,'Not Updated',NULL,NULL),
(299,59,52,'Not Updated',NULL,NULL),(300,59,53,'Not Updated',NULL,NULL),(301,59,54,'Not Updated',NULL,NULL),
(302,59,55,'Not Updated',NULL,NULL),(303,59,56,'Not Updated',NULL,NULL),(304,60,52,'Not Updated',NULL,NULL),
(305,60,53,'Not Updated',NULL,NULL),(306,60,54,'Not Updated',NULL,NULL),(307,60,55,'Not Updated',NULL,NULL),
(308,60,56,'Not Updated',NULL,NULL),(309,61,57,'Not Updated',NULL,NULL),(310,61,58,'Not Updated',NULL,NULL),
(311,61,59,'Not Updated',NULL,NULL),(312,61,60,'Not Updated',NULL,NULL),(313,61,61,'Not Updated',NULL,NULL),
(314,62,57,'Not Updated',NULL,NULL),(315,62,58,'Not Updated',NULL,NULL),(316,62,59,'Not Updated',NULL,NULL),
(317,62,60,'Not Updated',NULL,NULL),(318,62,61,'Not Updated',NULL,NULL),(319,63,57,'Not Updated',NULL,NULL),
(320,63,58,'Not Updated',NULL,NULL),(321,63,59,'Not Updated',NULL,NULL),(322,63,60,'Not Updated',NULL,NULL),
(323,63,61,'Not Updated',NULL,NULL),(324,64,57,'Not Updated',NULL,NULL),(325,64,58,'Not Updated',NULL,NULL),
(326,64,59,'Not Updated',NULL,NULL),(327,64,60,'Not Updated',NULL,NULL),(328,64,61,'Not Updated',NULL,NULL),
(329,65,57,'Not Updated',NULL,NULL),(330,65,58,'Not Updated',NULL,NULL),(331,65,59,'Not Updated',NULL,NULL),
(332,65,60,'Not Updated',NULL,NULL),(333,65,61,'Not Updated',NULL,NULL);

SET FOREIGN_KEY_CHECKS = 1;
