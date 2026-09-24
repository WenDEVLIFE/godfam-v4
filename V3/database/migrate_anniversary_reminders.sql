-- Migration: Add wedding_anniversary to members table & verify notifications index
-- Database: churchgods

USE `churchgods`;

-- 1. Add wedding_anniversary column to members if not exists
ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `wedding_anniversary` DATE NULL AFTER `birthday`;

-- 2. Ensure indexes on notifications table for performance
ALTER TABLE `notifications`
    ADD INDEX IF NOT EXISTS `idx_notification_type` (`type`),
    ADD INDEX IF NOT EXISTS `idx_reminder_date` (`reminder_date`);
