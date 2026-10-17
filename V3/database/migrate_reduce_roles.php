<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';

try {
    // Reassign Rev. Benjamin Santos (Pastor) -> Administrator
    $stmt = $pdo->prepare("UPDATE users SET role_id = 1 WHERE email = ?");
    $stmt->execute(['pastor.benjamin@church.org']);
    $stmt = $pdo->prepare("UPDATE members SET role_id = 1 WHERE email = ?");
    $stmt->execute(['pastor.benjamin@church.org']);

    // Reassign Juan Dela Cruz (Staff) -> Member
    $stmt = $pdo->prepare("UPDATE users SET role_id = 4 WHERE email = ?");
    $stmt->execute(['juan.delacruz@yahoo.com']);
    $stmt = $pdo->prepare("UPDATE members SET role_id = 4 WHERE email = ?");
    $stmt->execute(['juan.delacruz@yahoo.com']);

    // Print counts before deleting
    $remaining = $pdo->query(
        "SELECT r.role_slug,
                (SELECT COUNT(*) FROM users u WHERE u.role_id = r.role_id)   AS user_count,
                (SELECT COUNT(*) FROM members m WHERE m.role_id = r.role_id) AS member_count
         FROM roles r
         WHERE r.role_slug IN ('pastor', 'staff', 'committee_head')"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($remaining as $row) {
        echo sprintf(
            "References before delete: %-14s users=%d members=%d\n",
            $row['role_slug'],
            $row['user_count'],
            $row['member_count']
        );
        if ((int)$row['user_count'] > 0 || (int)$row['member_count'] > 0) {
            throw new Exception("Cannot delete role '{$row['role_slug']}': it still has assigned users/members.");
        }
    }

    // Delete deprecated roles
    $deleted = $pdo->exec("DELETE FROM roles WHERE role_slug IN ('pastor', 'staff', 'committee_head')");
    echo "Deleted roles: {$deleted}\n";

    // Confirm final role list
    $roles = $pdo->query("SELECT role_id, role_name FROM roles ORDER BY role_id")->fetchAll(PDO::FETCH_ASSOC);
    echo "Final roles:\n";
    foreach ($roles as $r) {
        echo sprintf("  %d = %s\n", $r['role_id'], $r['role_name']);
    }

    echo "Migration Successful: reduced system roles to 4.\n";
} catch (Throwable $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
