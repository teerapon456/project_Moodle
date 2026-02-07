<?php
require_once __DIR__ . '/../../core/Database/Database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS ya_user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        setting_key VARCHAR(50) NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_setting (user_id, setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sql);
    echo "Table 'ya_user_settings' created successfully.";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
