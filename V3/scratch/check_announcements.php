<?php
require_once __DIR__ . '/../config/database.php';

echo "--- LATEST ANNOUNCEMENTS ---\n";
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($announcements);

echo "\n--- MEMBERS TABLE ---\n";
$members = $pdo->query("SELECT member_id, full_name, email FROM members")->fetchAll(PDO::FETCH_ASSOC);
echo "Total members: " . count($members) . "\n";
foreach ($members as $m) {
    echo $m['member_id'] . " | " . $m['full_name'] . " | " . $m['email'] . "\n";
}

