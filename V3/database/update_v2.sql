-- Church Management System Update v2
-- Enhanced Member Tracking and Settings

-- Add photo column for member ID cards
ALTER TABLE members ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL AFTER full_name;

-- Add address back into the members table if not properly used
-- (Checked schema, it already exists, so skipping)

-- Create a generic settings table for church configurations
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('church_name', "God's Family United Methodist Church"),
('church_logo', 'assets/images/logo.png'),
('church_tagline', 'Therefore go, grow in love, mission and service.'),
('church_contact', 'contact@godsfamchurch.com'),
('church_address', '123 Church St, City, Country');

-- Index for attendance status searches
CREATE INDEX idx_attendance_status ON attendance(status);
CREATE INDEX idx_attendance_member_status ON attendance(member_id, status);
