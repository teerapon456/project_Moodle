<?php
require_once __DIR__ . '/core/Config/Env.php';
require_once __DIR__ . '/core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "Connected to database.\n";

    function tryMigration($conn, $sql, $message)
    {
        try {
            $conn->exec($sql);
            echo "SUCCESS: $message\n";
        } catch (PDOException $e) {
            if (
                strpos($e->getMessage(), "Duplicate column name") !== false ||
                strpos($e->getMessage(), "Duplicate key name") !== false ||
                strpos($e->getMessage(), "already exists") !== false
            ) {
                echo "SKIP: $message (Already applied)\n";
            } else {
                echo "ERROR: $message - " . $e->getMessage() . "\n";
            }
        }
    }

    // Latitude/Longitude in user_logins
    tryMigration($conn, "ALTER TABLE user_logins ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER client_version", "Add latitude to user_logins");
    tryMigration($conn, "ALTER TABLE user_logins ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude", "Add longitude to user_logins");

    // system_settings columns
    tryMigration($conn, "ALTER TABLE system_settings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP", "Add created_at to system_settings");
    tryMigration($conn, "ALTER TABLE system_settings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", "Add updated_at to system_settings");

    // module_id nullable
    tryMigration($conn, "ALTER TABLE system_settings MODIFY COLUMN module_id INT NULL", "Make module_id nullable in system_settings");

    echo "Schema migration completed.\n";
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
