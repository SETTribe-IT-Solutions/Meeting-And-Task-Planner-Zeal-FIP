CREATE DATABASE IF NOT EXISTS meeting_planner;
USE meeting_planner;

CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE COLLATE utf8mb4_bin,
  description TEXT NULL,
  is_active ENUM('Yes','No') NOT NULL DEFAULT 'Yes',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE COLLATE utf8mb4_bin,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL,
  department VARCHAR(100) NOT NULL COLLATE utf8mb4_bin,
  isDeleted ENUM('Yes','No') NOT NULL DEFAULT 'No'
);

CREATE TABLE IF NOT EXISTS meetings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  meeting_date DATE NOT NULL,
  meeting_time TIME NOT NULL,
  location VARCHAR(150) NOT NULL,
  mode VARCHAR(50) NOT NULL,
  agenda TEXT NOT NULL,
  department VARCHAR(100) NOT NULL,
  organizer_id INT NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Scheduled',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organizer_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  assigned_to INT NOT NULL,
  due_date DATE NOT NULL,
  priority VARCHAR(20) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('Present', 'Absent', 'Pending') DEFAULT 'Pending',
  remarks TEXT,
  FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS meeting_translations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meeting_id INT NOT NULL,
  language_code VARCHAR(10) NOT NULL,
  translated_agenda TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE CASCADE
);


INSERT INTO departments (name, description, is_active) VALUES
('Administration', 'General administration and coordination.', 'Yes'),
('Law & Order', 'Public safety, legal coordination, and order management.', 'Yes'),
('Revenue', 'Revenue administration and land records.', 'Yes'),
('Health', 'Public health services and medical coordination.', 'Yes'),
('Education', 'Education planning and institutional coordination.', 'Yes'),
('Agriculture', 'Agriculture services and farmer support.', 'Yes'),
('Finance', 'Financial planning, budget, and accounts.', 'Yes'),
('IT Department', 'Information technology services and digital systems.', 'Yes'),
('Rural Development', 'Rural development projects and schemes.', 'Yes'),
('Public Works Department', 'Public infrastructure and works management.', 'Yes')
ON DUPLICATE KEY UPDATE is_active=VALUES(is_active), description=VALUES(description);

INSERT INTO users (name, email, password, role, department, isDeleted) VALUES
('System Collector', 'collector@project.local', 'collector123', 'Collector', 'Administration', 'No'),
('Organizer Admin', 'organizer@project.local', 'admin123', 'Organizer', 'Administration', 'No'),
('Employee One', 'employee@project.local', 'employee123', 'Employee', 'HR', 'No'),
('Shamal Patil', 'shamal@project.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee', 'Administration', 'No'),
('Anuja Garande', 'anuja@project.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee', 'Administration', 'No')
ON DUPLICATE KEY UPDATE email=email;

INSERT INTO meetings (title, meeting_date, meeting_time, location, mode, agenda, department, organizer_id, status) VALUES
('Weekly Planning Meeting', '2026-06-20', '10:00:00', 'Conference Room A', 'Offline', 'Review weekly goals and pending tasks.', 'Administration', 2, 'Scheduled'),
('HR Policy Review', '2026-06-22', '14:30:00', 'Zoom', 'Online', 'Discuss HR policy updates and approvals.', 'HR', 2, 'Scheduled')
ON DUPLICATE KEY UPDATE title=title;

INSERT INTO tasks (meeting_id, title, assigned_to, due_date, priority, status, notes) VALUES
(1, 'Prepare agenda document', 3, '2026-06-19', 'High', 'Pending', 'Finalize agenda before the meeting.'),
(1, 'Send attendance reminder', 3, '2026-06-20', 'Medium', 'Pending', 'Email reminder to attendees.'),
(2, 'Update policy checklist', 3, '2026-06-21', 'High', 'In Progress', 'Review latest policy changes.')
ON DUPLICATE KEY UPDATE title=title;
