-- Migration: Reduce system roles from 7 to 4
-- Keeps Administrator, Member, Secretary, Treasurer.
-- Removes Pastor, Staff, Committee Head and reassigns affected accounts.
-- Run this in phpMyAdmin or via database/migrate_reduce_roles.php against `churchgods`.

USE `churchgods`;

-- --------------------------------------------------------
-- 1. Reassign Rev. Benjamin Santos (Pastor) -> Administrator
-- --------------------------------------------------------
UPDATE `users`
    SET `role_id` = 1
    WHERE `email` = 'pastor.benjamin@church.org';

UPDATE `members`
    SET `role_id` = 1
    WHERE `email` = 'pastor.benjamin@church.org';

-- --------------------------------------------------------
-- 2. Reassign Juan Dela Cruz (Staff) -> Member
-- --------------------------------------------------------
UPDATE `users`
    SET `role_id` = 4
    WHERE `email` = 'juan.delacruz@yahoo.com';

UPDATE `members`
    SET `role_id` = 4
    WHERE `email` = 'juan.delacruz@yahoo.com';

-- --------------------------------------------------------
-- 3. Report any remaining references to roles being removed
--    (should be zero before the DELETE below)
-- --------------------------------------------------------
SELECT 'users' AS scope, r.`role_slug`, COUNT(*) AS total
FROM `users` u
JOIN `roles` r ON u.`role_id` = r.`role_id`
WHERE r.`role_slug` IN ('pastor', 'staff', 'committee_head')
GROUP BY r.`role_slug`;

SELECT 'members' AS scope, r.`role_slug`, COUNT(*) AS total
FROM `members` m
JOIN `roles` r ON m.`role_id` = r.`role_id`
WHERE r.`role_slug` IN ('pastor', 'staff', 'committee_head')
GROUP BY r.`role_slug`;

-- --------------------------------------------------------
-- 4. Remove the deprecated roles (fails safely if still referenced)
-- --------------------------------------------------------
DELETE FROM `roles` WHERE `role_slug` IN ('pastor', 'staff', 'committee_head');

-- --------------------------------------------------------
-- 5. Confirm the final role list
-- --------------------------------------------------------
SELECT `role_id`, `role_name`, `role_slug` FROM `roles` ORDER BY `role_id`;
