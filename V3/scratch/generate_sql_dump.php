<?php
require_once __DIR__ . '/../config/database.php';

$tables = [
    'roles',
    'members',
    'users',
    'events',
    'attendance',
    'announcements',
    'announcement_notifications',
    'password_resets',
    'settings',
    'collections',
    'expenses',
    'notifications',
    'audit_logs',
    'permissions',
    'role_permissions'
];

$sql = "-- Full Database Export for Church Management System\n";
$sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sql .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql .= "START TRANSACTION;\n";
$sql .= "SET time_zone = \"+00:00\";\n\n";
$sql .= "CREATE DATABASE IF NOT EXISTS `churchgods`;\n";
$sql .= "USE `churchgods`;\n\n";

foreach ($tables as $table) {
    $sql .= "-- --------------------------------------------------------\n";
    $sql .= "-- Table structure for table `$table`\n";
    $sql .= "-- --------------------------------------------------------\n";
    
    $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
    $createRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $createTableSql = $createRow['Create Table'];
    
    // Ensure CREATE TABLE IF NOT EXISTS
    $createTableSql = preg_replace('/CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createTableSql, 1);
    $sql .= $createTableSql . ";\n\n";
}

// Check if role_permission_matrix is a VIEW
$views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($views as $view) {
    $sql .= "-- --------------------------------------------------------\n";
    $sql .= "-- View structure for `$view`\n";
    $sql .= "-- --------------------------------------------------------\n";
    $stmt = $pdo->query("SHOW CREATE VIEW `$view`");
    $createRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $sql .= "DROP VIEW IF EXISTS `$view`;\n";
    $sql .= $createRow['Create View'] . ";\n\n";
}

$sql .= "-- --------------------------------------------------------\n";
$sql .= "-- Seed Data for `roles`\n";
$sql .= "-- --------------------------------------------------------\n";
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($roles)) {
    $sql .= "INSERT IGNORE INTO `roles` (`role_id`, `role_name`, `role_slug`, `description`, `is_system`) VALUES\n";
    $roleRows = [];
    foreach ($roles as $r) {
        $roleRows[] = sprintf(
            "(%d, %s, %s, %s, %d)",
            $r['role_id'],
            $pdo->quote($r['role_name']),
            $r['role_slug'] !== null ? $pdo->quote($r['role_slug']) : "NULL",
            $r['description'] !== null ? $pdo->quote($r['description']) : "NULL",
            $r['is_system'] ?? 0
        );
    }
    $sql .= implode(",\n", $roleRows) . ";\n\n";
}

$sql .= "-- --------------------------------------------------------\n";
$sql .= "-- Seed Default Admin User\n";
$sql .= "-- --------------------------------------------------------\n";
$sql .= "INSERT IGNORE INTO `users` (`user_id`, `role_id`, `name`, `email`, `password`) VALUES\n";
$sql .= "(1, 1, 'System Admin', 'admin@church.com', '\$2y\$10\$da7FAxna.U8VuLth.3Clde8Jr73F8JuEa7JrSy4QZh5Hgv9ZJGB0G');\n\n";

$sql .= "-- --------------------------------------------------------\n";
$sql .= "-- Seed Default System Settings\n";
$sql .= "-- --------------------------------------------------------\n";
$settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_ASSOC);
if (!empty($settings)) {
    $sql .= "INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES\n";
    $settingRows = [];
    foreach ($settings as $s) {
        $settingRows[] = sprintf(
            "(%s, %s)",
            $pdo->quote($s['setting_key']),
            $pdo->quote($s['setting_value'])
        );
    }
    $sql .= implode(",\n", $settingRows) . ";\n\n";
}

$sql .= "COMMIT;\n";

file_put_contents(__DIR__ . '/test_out.sql', $sql);
echo "Export generated: " . strlen($sql) . " bytes\n";
