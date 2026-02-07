<?php
require_once __DIR__ . '/../../core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS ya_milestone_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        milestone_id INT NOT NULL,
        previous_status ENUM('pending', 'in_progress', 'completed', 'on_hold', 'cancelled', 'proposed') NOT NULL,
        new_status ENUM('pending', 'in_progress', 'completed', 'on_hold', 'cancelled', 'proposed') NOT NULL,
        actual_start_date DATETIME NULL,
        actual_end_date DATETIME NULL,
        note TEXT,
        changed_by INT,
        changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (milestone_id) REFERENCES ya_milestones(id) ON DELETE CASCADE,
        FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sql);
    echo "Table 'ya_milestone_logs' created successfully.\n";
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
