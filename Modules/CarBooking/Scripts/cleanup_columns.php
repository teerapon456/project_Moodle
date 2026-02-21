<?php
// Modules/CarBooking/Scripts/cleanup_columns.php

$corePath = __DIR__ . '/../../../core';
require_once $corePath . '/Config/Env.php';
require_once $corePath . '/Database/Database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "Starting Column Cleanup for Car Booking...\n";

try {
    // Drop columns
    $sql = "ALTER TABLE cb_bookings 
            DROP COLUMN approver_email,
            DROP COLUMN supervisor_approved_by,
            DROP COLUMN manager_approved_by,
            DROP COLUMN driver_email,
            DROP COLUMN driver_name";

    $pdo->exec($sql);
    echo "[SUCCESS] Dropped columns: approver_email, supervisor_approved_by, manager_approved_by, driver_email, driver_name\n";
} catch (PDOException $e) {
    echo "[ERROR] Failed to drop columns: " . $e->getMessage() . "\n";
}
