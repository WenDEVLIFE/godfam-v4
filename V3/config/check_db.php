<?php
$db_path = __DIR__ . '/database.sqlite';
try {
    $pdo = new PDO("sqlite:$db_path");
    $stmt = $pdo->query("PRAGMA table_info(members)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo $col['name'] . " (" . $col['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
