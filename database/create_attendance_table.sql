-- SQL to create the attendance table
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `meeting_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status` ENUM('Present', 'Absent') NOT NULL,
  `arrival_time` DATETIME NOT NULL,
  UNIQUE KEY `idx_meeting_user` (`meeting_id`, `user_id`),
  FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;