-- Migration: Add member_type category to members
-- Run this in phpMyAdmin against the `churchgods` database.

USE `churchgods`;

-- --------------------------------------------------------
-- 1. Add member_type column to members, positioned after full_name
-- Allowed values: Member, Youth Member, Child, Senior (default 'Member')
-- --------------------------------------------------------
ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `member_type` VARCHAR(50) NOT NULL DEFAULT 'Member' AFTER `full_name`;
