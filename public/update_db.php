<?php
require_once __DIR__ . '/../core/Database/Database.php';

try {
    $db = (new Database())->getConnection();
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM user_logins LIKE 'details'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE user_logins ADD COLUMN details TEXT NULL");
        echo "Column 'details' added successfully.";
    } else {
        echo "Column 'details' already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
