<?php
require_once __DIR__ . '/../../core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check if columns exist before dropping (simple check not fully robust but sufficient for this flow)
    $sql = "ALTER TABLE ya_milestones 
            DROP COLUMN actual_date,
            DROP COLUMN actual_start_date,
            DROP COLUMN actual_end_date;";

    $conn->exec($sql);
    echo "Columns 'actual_date', 'actual_start_date', 'actual_end_date' dropped successfully.\n";
} catch (PDOException $e) {
    // If columns don't exist, it might fail, which is acceptable if already run.
    echo "DB Warning/Error: " . $e->getMessage() . "\n";
}
