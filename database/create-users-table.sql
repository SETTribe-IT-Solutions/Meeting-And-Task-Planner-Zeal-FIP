-- Create users table for Meeting & Task Planner
-- Run this SQL in your phpMyAdmin or MySQL console

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` BOOLEAN DEFAULT TRUE,
  INDEX idx_username (username),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a test user (password: password123)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES 
('admin', 'admin@example.com', '$2y$10$YIjlrTyKfhLMJ6niqnC4/.eHE8s7R.AH.fYl0Nf.eVTMX4.0Yd4Rm', 'admin'),
('user1', 'user1@example.com', '$2y$10$YIjlrTyKfhLMJ6niqnC4/.eHE8s7R.AH.fYl0Nf.eVTMX4.0Yd4Rm', 'user');

-- The password hashes above are for: password123
-- To generate your own, use PHP: echo password_hash('your_password', PASSWORD_BCRYPT);
