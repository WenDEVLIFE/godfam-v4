-- Migration: Add birthday to members + collections table
-- Run this in phpMyAdmin against the `churchgods` database.

USE `churchgods`;

-- --------------------------------------------------------
-- 1. Add birthday column to members
-- --------------------------------------------------------
ALTER TABLE `members`
    ADD COLUMN IF NOT EXISTS `birthday` DATE NULL AFTER `address`;

-- --------------------------------------------------------
-- 2. Collections table (tithes, offerings, special giving)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `collections` (
    `collection_id`   INT(11)        NOT NULL AUTO_INCREMENT,
    `member_id`       INT(11)        NULL DEFAULT NULL COMMENT 'NULL = anonymous/walk-in',
    `amount`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `category`        VARCHAR(50)    NOT NULL,
    `payment_method`  VARCHAR(50)    NOT NULL DEFAULT 'Cash',
    `collection_date` DATE           NOT NULL,
    `recorded_by`     INT(11)        NOT NULL COMMENT 'user_id of staff who recorded it',
    `remarks`         TEXT           NULL DEFAULT NULL,
    `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`collection_id`),
    KEY `idx_collection_date` (`collection_date`),
    KEY `idx_collection_category` (`category`),
    KEY `member_id` (`member_id`),
    KEY `recorded_by` (`recorded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign keys
ALTER TABLE `collections`
    ADD CONSTRAINT `col_fk_member`   FOREIGN KEY (`member_id`)   REFERENCES `members` (`member_id`) ON DELETE SET NULL,
    ADD CONSTRAINT `col_fk_recorder` FOREIGN KEY (`recorded_by`) REFERENCES `users`   (`user_id`)   ON DELETE RESTRICT;
