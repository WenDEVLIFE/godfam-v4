-- ============================================================
-- Dummy Data SQL Import File for Church Management System (`churchgods`)
-- Database: `churchgods`
-- Generated: 2026-09-16
-- ============================================================

USE `churchgods`;

-- --------------------------------------------------------
-- 1. Roles
-- --------------------------------------------------------
INSERT IGNORE INTO `roles` (`role_id`, `role_name`, `role_slug`, `description`, `is_system`) VALUES
(1, 'Administrator', 'administrator', 'System Administrator with full access', 1),
(2, 'Pastor', 'pastor', 'Pastoral leadership', 1),
(3, 'Staff', 'staff', 'Operational church staff', 1),
(4, 'Member', 'member', 'Standard church member', 1),
(5, 'Secretary', 'secretary', 'Administrative secretary', 1),
(6, 'Committee Head', 'committee_head', 'Committee department leader', 1),
(9, 'Treasurer', 'treasurer', 'Finance and collections controller', 1);

-- --------------------------------------------------------
-- 2. Dummy Members
-- --------------------------------------------------------
INSERT IGNORE INTO `members` (`member_id`, `full_name`, `email`, `phone`, `contact_info`, `birthday`, `birthdate`, `gender`, `address`, `status`, `role_id`, `qr_token`) VALUES
(1, 'Camille Jane Madrid', 'camillejanemadrid629@gmail.com', '+639171234567', '+639171234567', '1995-06-29', '1995-06-29', 'Female', '123 Main St, Cabanatuan City', 'active', 1, '4a8f9c1e2b3d4f5a6b7c8d9e0f1a2b3c'),
(2, 'Rev. Benjamin Santos', 'pastor.benjamin@church.org', '+639182345678', '+639182345678', '1978-04-12', '1978-04-12', 'Male', '45 Church Compound, Gapan City', 'active', 2, 'b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8'),
(3, 'Maria Clara Dela Cruz', 'maria.delacruz@gmail.com', '+639193456789', '+639193456789', '1992-09-16', '1992-09-16', 'Female', '78 Rizal St, San Jose City', 'active', 5, 'c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9'),
(4, 'Juan Dela Cruz', 'juan.delacruz@yahoo.com', '+639204567890', '+639204567890', '1988-11-20', '1988-11-20', 'Male', '12 Mabini Ave, Talavera', 'active', 3, 'd5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0'),
(5, 'Grace Gonzales', 'grace.gonzales@gmail.com', '+639215678901', '+639215678901', '2001-03-15', '2001-03-15', 'Female', '56 Luna St, Santa Rosa', 'active', 4, 'e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1'),
(6, 'Joshua Ramos', 'joshua.ramos@outlook.com', '+639226789012', '+639226789012', '1999-08-04', '1999-08-04', 'Male', '89 Bonifacio St, Cabanatuan City', 'active', 4, 'f7a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2'),
(7, 'Angelica Reyes', 'angelica.reyes@gmail.com', '+639237890123', '+639237890123', '1996-12-10', '1996-12-10', 'Female', '34 Quezon Blvd, Gapan City', 'active', 4, 'a8b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3'),
(8, 'Daniel Padilla', 'daniel.padilla@gmail.com', '+639248901234', '+639248901234', '1994-04-26', '1994-04-26', 'Male', '90 Del Pilar St, Cabanatuan', 'active', 4, 'b9c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4'),
(9, 'Kathryn Bernardo', 'kathryn.bernardo@gmail.com', '+639259012345', '+639259012345', '1996-03-26', '1996-03-26', 'Female', '15 Roxas St, San Leonardo', 'active', 4, 'c0d1e2f3a4b5c6d7e8f9a0b1c2d3e4f5'),
(10, 'Ricardo Dalisay', 'cardo.dalisay@gmail.com', '+639260123456', '+639260123456', '1985-01-14', '1985-01-14', 'Male', '77 Zamora St, Cabanatuan City', 'active', 4, 'd1e2f3a4b5c6d7e8f9a0b1c2d3e4f5a6');

-- --------------------------------------------------------
-- 3. Dummy Users
-- Password for all accounts: Admin123!
-- --------------------------------------------------------
INSERT IGNORE INTO `users` (`user_id`, `role_id`, `member_id`, `name`, `email`, `password`, `status`) VALUES
(1, 1, 1, 'Camille Jane Madrid', 'camillejanemadrid629@gmail.com', '$2y$10$ht3egELlhO4SI9M1o4jrIewEtNQDhR5O3RHg98gWJ4l/CCllg4XM6', 'active'),
(2, 2, 2, 'Rev. Benjamin Santos', 'pastor.benjamin@church.org', '$2y$10$ht3egELlhO4SI9M1o4jrIewEtNQDhR5O3RHg98gWJ4l/CCllg4XM6', 'active'),
(3, 5, 3, 'Maria Clara Dela Cruz', 'maria.delacruz@gmail.com', '$2y$10$ht3egELlhO4SI9M1o4jrIewEtNQDhR5O3RHg98gWJ4l/CCllg4XM6', 'active'),
(4, 3, 4, 'Juan Dela Cruz', 'juan.delacruz@yahoo.com', '$2y$10$ht3egELlhO4SI9M1o4jrIewEtNQDhR5O3RHg98gWJ4l/CCllg4XM6', 'active'),
(5, 4, 5, 'Grace Gonzales', 'grace.gonzales@gmail.com', '$2y$10$ht3egELlhO4SI9M1o4jrIewEtNQDhR5O3RHg98gWJ4l/CCllg4XM6', 'active');

-- --------------------------------------------------------
-- 4. Dummy Events
-- --------------------------------------------------------
INSERT IGNORE INTO `events` (`event_id`, `title`, `description`, `date`, `time`, `location`, `status`) VALUES
(1, 'Sunday Worship & Holy Communion', 'Weekly Sunday morning worship service & celebration.', CURDATE() - INTERVAL 14 DAY, '09:00:00', 'Main Sanctuary', 'completed'),
(2, 'Midweek Prayer & Bible Study', 'Wednesday evening prayer meeting and Bible study.', CURDATE() - INTERVAL 10 DAY, '18:30:00', 'Fellowship Hall', 'completed'),
(3, 'Sunday Praise & Thanksgiving', 'Sunday morning praise and sermon service.', CURDATE() - INTERVAL 7 DAY, '09:00:00', 'Main Sanctuary', 'completed'),
(4, 'Youth Fellowship Night', 'Youth fellowship, music worship, and games.', CURDATE() - INTERVAL 3 DAY, '17:00:00', 'Youth Center', 'completed'),
(5, 'Sunday Celebration Service', 'Upcoming Sunday morning service.', CURDATE(), '09:00:00', 'Main Sanctuary', 'upcoming'),
(6, 'Church Officers Meeting', 'Monthly administrative and pastoral planning.', CURDATE() + INTERVAL 3 DAY, '14:00:00', 'Conference Room', 'upcoming'),
(7, 'Annual Church Thanksgiving', 'Grand annual thanksgiving service & fellowship.', CURDATE() + INTERVAL 14 DAY, '08:30:00', 'Main Sanctuary', 'upcoming');

-- --------------------------------------------------------
-- 5. Dummy Attendance Logs
-- --------------------------------------------------------
INSERT IGNORE INTO `attendance` (`attendance_id`, `member_id`, `event_id`, `date`, `status`) VALUES
(1, 1, 1, CURDATE() - INTERVAL 14 DAY, 'Present'),
(2, 2, 1, CURDATE() - INTERVAL 14 DAY, 'Present'),
(3, 3, 1, CURDATE() - INTERVAL 14 DAY, 'Present'),
(4, 4, 1, CURDATE() - INTERVAL 14 DAY, 'Present'),
(5, 5, 1, CURDATE() - INTERVAL 14 DAY, 'Present'),
(6, 1, 2, CURDATE() - INTERVAL 10 DAY, 'Present'),
(7, 2, 2, CURDATE() - INTERVAL 10 DAY, 'Present'),
(8, 3, 2, CURDATE() - INTERVAL 10 DAY, 'Present'),
(9, 1, 3, CURDATE() - INTERVAL 7 DAY, 'Present'),
(10, 2, 3, CURDATE() - INTERVAL 7 DAY, 'Present'),
(11, 4, 3, CURDATE() - INTERVAL 7 DAY, 'Present'),
(12, 5, 3, CURDATE() - INTERVAL 7 DAY, 'Present');

-- --------------------------------------------------------
-- 6. Dummy Collections
-- --------------------------------------------------------
INSERT IGNORE INTO `collections` (`collection_id`, `member_id`, `amount`, `category`, `payment_method`, `collection_date`, `recorded_by`, `remarks`) VALUES
(1, 1, 1500.00, 'Tithe', 'Cash', CURDATE() - INTERVAL 14 DAY, 1, 'Weekly Tithe'),
(2, 2, 2500.00, 'Offering', 'GCash', CURDATE() - INTERVAL 14 DAY, 1, 'Sunday Offering'),
(3, 3, 1000.00, 'Tithe', 'Cash', CURDATE() - INTERVAL 10 DAY, 1, 'Midweek Tithe'),
(4, 4, 3000.00, 'Donation', 'Bank', CURDATE() - INTERVAL 7 DAY, 1, 'Building Fund Giving'),
(5, 5, 500.00, 'Offering', 'Cash', CURDATE() - INTERVAL 7 DAY, 1, 'Sunday Offering'),
(6, 6, 2000.00, 'Tithe', 'GCash', CURDATE() - INTERVAL 3 DAY, 1, 'Youth Fellowship Tithe'),
(7, NULL, 1250.00, 'Offering', 'Cash', CURDATE(), 1, 'Anonymous Visitor Offering'),
(8, 7, 5000.00, 'Donation', 'GCash', CURDATE(), 1, 'Missionary Support Donation');

-- --------------------------------------------------------
-- 7. Dummy Expenses
-- --------------------------------------------------------
INSERT IGNORE INTO `expenses` (`expense_id`, `amount`, `category`, `expense_date`, `description`, `recorded_by`) VALUES
(1, 1200.00, 'Utilities', CURDATE() - INTERVAL 12 DAY, 'Electricity bill payment', 1),
(2, 800.00, 'Maintenance', CURDATE() - INTERVAL 8 DAY, 'Sanctuary aircon cleaning', 1),
(3, 500.00, 'Ministry', CURDATE() - INTERVAL 5 DAY, 'Sunday school materials', 1),
(4, 1500.00, 'Honorarium', CURDATE() - INTERVAL 2 DAY, 'Guest speaker honorarium', 1);

-- --------------------------------------------------------
-- 8. Dummy Announcements
-- --------------------------------------------------------
INSERT IGNORE INTO `announcements` (`announcement_id`, `title`, `content`, `created_by`) VALUES
(1, 'Sunday Worship Schedule', 'Please be reminded that our Sunday Service starts promptly at 9:00 AM. See you with your family!', 1),
(2, 'Youth Fellowship Camp 2026', 'Registration is now open for the upcoming Summer Youth Camp. Contact the youth coordinator for details.', 1),
(3, 'Annual Thanksgiving Preparation', 'We invite all church committees to join the coordination meeting this coming Saturday.', 1);

-- --------------------------------------------------------
-- 9. Dummy Notifications
-- --------------------------------------------------------
INSERT IGNORE INTO `notifications` (`notification_id`, `member_id`, `type`, `title`, `message`, `reminder_date`) VALUES
(1, 3, 'birthday', '🎂 Birthday Celebrants Today', 'Celebrating Maria Clara Dela Cruz birthday today!', CURDATE()),
(2, 4, 'event', '📅 Upcoming Service', 'Reminder: Sunday Worship Service tomorrow at 9:00 AM', CURDATE() + INTERVAL 1 DAY);

-- --------------------------------------------------------
-- 10. Dummy Audit Logs
-- --------------------------------------------------------
INSERT IGNORE INTO `audit_logs` (`log_id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`) VALUES
(1, 1, 'LOGIN', 'Admin logged into dashboard', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(2, 1, 'RECORD_COLLECTION', 'Recorded collection ₱1,500.00 (Tithe)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(3, 1, 'CREATE_EVENT', 'Created new event: Youth Fellowship Night', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'),
(4, 1, 'SCAN_ATTENDANCE', 'Scanned QR attendance for member ID 1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
