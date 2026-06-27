-- ============================================================
-- Meeting Planner — Full Data Backup
-- Generated : 2026-06-27 08:50:29 (Asia/Kolkata)
-- By        : Organizer
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: `departments`
-- --------------------------------------------------------
TRUNCATE TABLE `departments`;
INSERT INTO `departments` VALUES ('1', 'Administration', 'General administration and coordination.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('2', 'Law & Order', 'Public safety, legal coordination, and order management.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('3', 'Revenue', 'Revenue administration and land records.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('4', 'Health', 'Public health services and medical coordination.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('5', 'Education', 'Education planning and institutional coordination.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('6', 'Agriculture', 'Agriculture services and farmer support.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('7', 'Finance', 'Financial planning, budget, and accounts.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('8', 'IT Department', 'Information technology services and digital systems.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('9', 'Rural Development', 'Rural development projects and schemes.', 'Yes', '2026-06-26 22:11:14');
INSERT INTO `departments` VALUES ('10', 'Public Works Department', 'Public infrastructure and works management.', 'Yes', '2026-06-26 22:11:14');

-- --------------------------------------------------------
-- Table: `users`
-- --------------------------------------------------------
TRUNCATE TABLE `users`;
INSERT INTO `users` VALUES ('1', 'System Collector', 'collector@project.local', '$2y$10$EUCb114nQtuyN3BUJUOXhuEIrraA9TM3jzXSWweb3ljFb31ShE5u.', 'Collector', 'Administration', NULL, NULL, NULL, NULL, 'No', '2026-06-26 22:11:14');
INSERT INTO `users` VALUES ('2', 'Organizer Admin', 'organizer@project.local', '$2y$10$jSMN9fMPMU7290I9R7jrRedC7DdLExIHZa4NsCPsCSy6v77KJO8DC', 'Organizer', 'Administration', NULL, NULL, NULL, NULL, 'No', '2026-06-26 22:11:14');
INSERT INTO `users` VALUES ('3', 'Employee One', 'employee@project.local', '$2y$10$pFnHJRqitgWMfLcnfvFc6OVsx1IsCbC5z7eo/7//Tr0EaCmCAz0Aa', 'Employee', 'Administration', NULL, NULL, NULL, NULL, 'No', '2026-06-26 22:11:14');
INSERT INTO `users` VALUES ('4', 'Shamal Patil', 'shamal@project.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee', 'Administration', NULL, NULL, NULL, NULL, 'No', '2026-06-26 22:11:14');
INSERT INTO `users` VALUES ('5', 'Anuja Garande', 'anuja@project.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Employee', 'Administration', NULL, NULL, NULL, NULL, 'No', '2026-06-26 22:11:14');
INSERT INTO `users` VALUES ('6', 'Rajesh Kulkarni', 'rajesh@project.local', 'emp123', 'Employee', 'Revenue', '9876543210', NULL, 'Revenue Inspector', NULL, 'No', '2026-06-26 22:14:54');
INSERT INTO `users` VALUES ('7', 'Priya Deshmukh', 'priya@project.local', 'emp123', 'Employee', 'Health', '9123456780', NULL, 'Health Officer', NULL, 'No', '2026-06-26 22:14:54');
INSERT INTO `users` VALUES ('8', 'Manoj Shinde', 'manoj@project.local', 'emp123', 'Employee', 'Education', '9988776655', NULL, 'Education Coordinator', NULL, 'No', '2026-06-26 22:14:54');
INSERT INTO `users` VALUES ('9', 'Sunita Jadhav', 'sunita@project.local', 'emp123', 'Employee', 'Finance', '9090909090', NULL, 'Accounts Officer', NULL, 'No', '2026-06-26 22:14:54');
INSERT INTO `users` VALUES ('10', 'Collector 2', 'collector2@project.local', 'collect2', 'Collector', 'Administration', '8800000001', NULL, 'District Collector', NULL, 'No', '2026-06-26 22:14:54');
INSERT INTO `users` VALUES ('11', 'Testuser', 'anujatest@gmail.com', '$2y$10$cJI/tgX6RdGwjqHDAh/uzuP7PHqeE6T92Am7suhGhqlPCb.zG4R1i', 'Employee', 'Administration', NULL, NULL, NULL, NULL, 'No', '2026-06-26 23:22:14');

-- --------------------------------------------------------
-- Table: `meetings`
-- --------------------------------------------------------
TRUNCATE TABLE `meetings`;
INSERT INTO `meetings` VALUES ('1', 'Revenue Department Quarterly Review', '2026-06-05', '10:00:00', '90', 'Collector Office - Room 1', NULL, 'Offline', 'Review Q1 revenue collection targets.\nDiscuss arrears and pending recovery.\nAction plan for Q2.', 'Revenue', '2', 'Completed', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meetings` VALUES ('2', 'Health Department Emergency Meeting', '2026-06-12', '09:30:00', '60', 'Civil Hospital Conference Hall', NULL, 'Offline', 'Review dengue outbreak response.\nStock status of medicines.\nField team deployment update.', 'Health', '2', 'Completed', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meetings` VALUES ('3', 'Education Infrastructure Planning', '2026-06-18', '11:00:00', '120', 'District Education Office', NULL, 'Offline', 'School building repair status across all talukas.\nTeacher recruitment progress.\nMid-day meal scheme compliance.', 'Education', '2', 'Completed', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meetings` VALUES ('4', 'Finance Audit Preparatory Session', '2026-06-20', '14:00:00', '60', 'Finance Office', NULL, 'Offline', 'Internal audit preparation.\nDocument checklist review.\nResponsibility assignment.', 'Finance', '2', 'Cancelled', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meetings` VALUES ('5', 'Latur District Weekly Coordination', '2026-06-26', '10:00:00', '90', 'Collector Office - Main Hall', NULL, 'Hybrid', 'Weekly status update from all departments.\nPending action items review.\nUpcoming event coordination.', 'Administration', '2', 'Cancelled', NULL, '2026-06-26 22:14:54', '2026-06-27 07:22:12');
INSERT INTO `meetings` VALUES ('6', 'Agriculture Water Conservation Review', '2026-06-30', '11:30:00', '75', 'Agriculture Office', NULL, 'Offline', 'Rainfall data analysis.\nIrrigation scheme status.\nFarmer compensation disbursement.', 'Agriculture', '2', 'Scheduled', NULL, '2026-06-26 22:14:54', '2026-06-26 22:54:38');
INSERT INTO `meetings` VALUES ('7', 'IT Infrastructure Modernisation Meeting', '2026-07-03', '10:00:00', '60', 'NIC Office', NULL, 'Online', 'e-Governance portal update.\nBiometric attendance rollout.\nData security compliance.', 'IT Department', '2', 'Scheduled', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meetings` VALUES ('8', 'Public Works Department Monthly Review', '2026-07-08', '09:00:00', '120', 'PWD Office', NULL, 'Offline', 'Road repair tender status.\nBridge inspection report.\nRain season maintenance plan.', 'Public Works Department', '2', 'Completed', NULL, '2026-06-26 22:14:54', '2026-06-27 07:30:29');
INSERT INTO `meetings` VALUES ('9', 'Rural Development Schemes Progress', '2026-07-15', '11:00:00', '90', 'Zilla Parishad Hall', '', 'Offline', 'Updated agenda with new action items.', 'Rural Development', '2', 'Cancelled', 'Test', '2026-06-26 22:14:54', '2026-06-27 07:30:17');
INSERT INTO `meetings` VALUES ('10', 'Law and Order Monthly Briefing', '2026-06-26', '08:00:00', '180', 'Police Headquarters', NULL, 'Offline', 'Crime statistics for June.\nSensitive area monitoring.\nPolice personnel deployment.', 'Law & Order', '2', 'Scheduled', NULL, '2026-06-26 22:14:54', '2026-06-27 08:07:26');
INSERT INTO `meetings` VALUES ('14', 'AFter UI change Test', '2026-06-27', '15:30:00', '30', '', 'https://www.mittalbuilders.com/sun-garnet', 'Online', '1', 'Administration', '2', 'Scheduled', NULL, '2026-06-26 22:53:38', '2026-06-26 22:53:38');
INSERT INTO `meetings` VALUES ('15', 'Testuser meeting', '2026-06-27', '12:11:00', '30', '', 'https://www.mittalbuilders.com/sun-garnet', 'Online', '1', 'Administration', '2', 'Scheduled', NULL, '2026-06-26 23:23:22', '2026-06-26 23:23:22');

-- --------------------------------------------------------
-- Table: `attendance`
-- --------------------------------------------------------
TRUNCATE TABLE `attendance`;
INSERT INTO `attendance` VALUES ('1', '1', '3', 'Present', '09:55:00', 'On time');
INSERT INTO `attendance` VALUES ('2', '1', '4', 'Present', '10:02:00', '');
INSERT INTO `attendance` VALUES ('3', '1', '5', 'Absent', NULL, 'On leave');
INSERT INTO `attendance` VALUES ('4', '1', '6', 'Present', '10:00:00', '');
INSERT INTO `attendance` VALUES ('5', '1', '7', 'Present', '09:58:00', '');
INSERT INTO `attendance` VALUES ('6', '2', '3', 'Present', '09:25:00', '');
INSERT INTO `attendance` VALUES ('7', '2', '4', 'Present', '09:30:00', '');
INSERT INTO `attendance` VALUES ('8', '2', '6', 'Absent', NULL, 'Field duty');
INSERT INTO `attendance` VALUES ('9', '2', '7', 'Present', '09:28:00', 'On time');
INSERT INTO `attendance` VALUES ('10', '3', '3', 'Present', '10:55:00', '');
INSERT INTO `attendance` VALUES ('11', '3', '4', 'Absent', NULL, 'Sick leave');
INSERT INTO `attendance` VALUES ('12', '3', '5', 'Present', '11:00:00', '');
INSERT INTO `attendance` VALUES ('13', '3', '7', 'Present', '11:03:00', '');
INSERT INTO `attendance` VALUES ('14', '3', '8', 'Present', '10:58:00', '');
INSERT INTO `attendance` VALUES ('15', '5', '3', '', NULL, '');
INSERT INTO `attendance` VALUES ('16', '5', '4', '', NULL, '');
INSERT INTO `attendance` VALUES ('17', '5', '5', '', NULL, '');
INSERT INTO `attendance` VALUES ('18', '5', '6', '', NULL, '');
INSERT INTO `attendance` VALUES ('19', '5', '7', '', NULL, '');
INSERT INTO `attendance` VALUES ('20', '5', '8', '', NULL, '');
INSERT INTO `attendance` VALUES ('21', '5', '9', '', NULL, '');
INSERT INTO `attendance` VALUES ('22', '5', '10', '', NULL, '');
INSERT INTO `attendance` VALUES ('23', '10', '3', 'Present', '07:55:00', '');
INSERT INTO `attendance` VALUES ('24', '10', '5', 'Present', '08:00:00', '');
INSERT INTO `attendance` VALUES ('25', '10', '6', 'Absent', NULL, 'WFH');
INSERT INTO `attendance` VALUES ('27', '14', '5', 'Present with Late', NULL, '');
INSERT INTO `attendance` VALUES ('28', '15', '11', '', NULL, NULL);

-- --------------------------------------------------------
-- Table: `tasks`
-- --------------------------------------------------------
TRUNCATE TABLE `tasks`;
INSERT INTO `tasks` VALUES ('1', '1', 'Prepare Q2 revenue recovery plan', 'Detail district-wise recovery targets and timeline.', '6', '2026-06-15', 'High', 'Completed', 'Submit to collector by EOD', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('2', '1', 'Compile arrears data from all talukas', 'Include officer-wise breakdown.', '7', '2026-06-12', 'High', 'Completed', '', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('3', '1', 'Send notices to defaulters', 'Coordinate with legal section for notice drafts.', '3', '2026-07-01', 'Medium', 'In Progress', 'Legal notices required', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('4', '2', 'Procure ORS and IV fluids for PHCs', 'Minimum 3-month stock at each PHC.', '7', '2026-06-18', 'High', 'Completed', 'Urgent procurement', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('5', '2', 'Deploy mobile medical teams to affected zones', 'Teams to report daily to district health office.', '4', '2026-06-30', 'High', 'In Progress', '', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('6', '2', 'Submit disease surveillance report', 'Use standard GOI format.', '3', '2026-07-05', 'Medium', 'Pending', 'Weekly report format', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('7', '3', 'Audit school building repair submissions', 'Cross-check contractor submissions against site photos.', '8', '2026-06-25', 'Medium', 'Completed', '', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('8', '3', 'Prepare teacher posting order draft', 'Include rural preference candidates.', '5', '2026-07-02', 'High', 'In Progress', 'NPC approval needed', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('9', '3', 'Mid-day meal compliance verification', 'Random school visits across 3 talukas.', '3', '2026-07-10', 'Low', 'Pending', '', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('10', '5', 'Consolidate weekly department status reports', 'Template shared on group.', '3', '2026-06-28', 'High', 'Pending', 'Due Friday', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('11', '5', 'Update action item tracker', 'Mark completed items green.', '4', '2026-06-28', 'Medium', 'Pending', '', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('12', '5', 'Circulate minutes to all HODs', 'Use official letter format.', '5', '2026-06-29', 'Low', 'Pending', '', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('13', '1', 'Submit land records digitisation progress', 'Monthly report pending.', '6', '2026-06-01', 'High', 'Pending', 'OVERDUE - escalated', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `tasks` VALUES ('14', '2', 'Update GIS mapping data', 'Coordinate with NIC.', '7', '2026-06-10', 'Medium', 'Pending', '', NULL, '2026-06-26 22:14:54', '2026-06-26 22:14:54');

-- --------------------------------------------------------
-- Table: `task_assignments`
-- --------------------------------------------------------
TRUNCATE TABLE `task_assignments`;
INSERT INTO `task_assignments` VALUES ('1', '1', '6', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('2', '2', '7', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('3', '3', '3', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('4', '3', '6', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('5', '4', '7', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('6', '5', '4', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('7', '5', '7', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('8', '6', '3', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('9', '7', '8', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('10', '8', '5', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('11', '8', '9', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('12', '9', '3', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('13', '10', '3', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('14', '10', '4', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('15', '11', '4', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('16', '12', '5', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('17', '13', '6', '2026-06-26 22:14:54');
INSERT INTO `task_assignments` VALUES ('18', '14', '7', '2026-06-26 22:14:54');

-- --------------------------------------------------------
-- Table: `meeting_translations`
-- --------------------------------------------------------
TRUNCATE TABLE `meeting_translations`;

-- --------------------------------------------------------
-- Table: `meeting_attachments`
-- --------------------------------------------------------
TRUNCATE TABLE `meeting_attachments`;

-- --------------------------------------------------------
-- Table: `meeting_notes`
-- --------------------------------------------------------
TRUNCATE TABLE `meeting_notes`;
INSERT INTO `meeting_notes` VALUES ('1', '1', 'Q2 Revenue Target Finalised', 'Meeting resolved to set Q2 target at Rs. 45 crore across the district. Sub-divisional officers to submit ward-wise breakdown by June 15.', 'Revenue', '1', '2', '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meeting_notes` VALUES ('2', '1', 'Defaulter Notice Process Approved', 'Legal section to prepare standard notice templates. First batch of 120 notices to be dispatched by July 1.', 'Revenue', '3', '2', '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meeting_notes` VALUES ('3', '2', 'Emergency Procurement Approved', 'Collector approved emergency procurement of medicines without standard tender process under Rule 19. CMO to process PO within 24 hours.', 'Health', '4', '2', '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meeting_notes` VALUES ('4', '2', 'Mobile Team Deployment Schedule', 'Three mobile teams deployed — Team A to Nilanga, Team B to Udgir, Team C to Ausa. Daily reporting to DHMO mandatory.', 'Health', '5', '2', '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meeting_notes` VALUES ('5', '3', 'School Repair Fund Released', 'Education department fund of Rs. 1.2 crore released for 15 school building repairs. Audit by June 25.', 'Education', '7', '2', '2026-06-26 22:14:54', '2026-06-26 22:14:54');
INSERT INTO `meeting_notes` VALUES ('6', '5', 'Weekly Meeting Outcomes', 'All departments to submit pending action items by June 28. Finance to clear pending bills before month end. Revenue to expedite defaulter notices.', 'Administration', '10', '2', '2026-06-26 22:14:54', '2026-06-26 22:14:54');

-- --------------------------------------------------------
-- Table: `task_attachments`
-- --------------------------------------------------------
TRUNCATE TABLE `task_attachments`;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- End of backup
-- ============================================================
