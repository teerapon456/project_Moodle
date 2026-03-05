<?php
require_once __DIR__ . '/core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = file_get_contents(__DIR__ . '/database/migrations/2026_03_02_create_iga_test_users.sql');
    $conn->exec($sql);

    echo "Migration successful: iga_test_users table created.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
