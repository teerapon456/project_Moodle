<?php
// Modules/CarBooking/Scripts/verify_cleanup.php

$corePath = __DIR__ . '/../../../core';
require_once $corePath . '/Config/Env.php';
require_once $corePath . '/Database/Database.php';

$db = new Database();
$pdo = $db->getConnection();

echo "Verifying Car Booking Data for Cleanup...\n";

// 1. Check Backup Table
$stmt = $pdo->query("SHOW TABLES LIKE 'cb_bookings_backup_%'");
$backups = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($backups)) {
    echo "[ERROR] No backup table found!\n";
} else {
    $latestBackup = end($backups);
    echo "[OK] Found backup table: $latestBackup\n";

    // Check row counts
    $stmt = $pdo->query("SELECT COUNT(*) FROM cb_bookings");
    $currentCount = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM `$latestBackup`");
    $backupCount = $stmt->fetchColumn();

    echo "  - Current Rows: $currentCount\n";
    echo "  - Backup Rows:  $backupCount\n";
    if ($currentCount != $backupCount) {
        echo "  [WARNING] Row count mismatch!\n";
    }
}

// 2. Check for missing IDs (potential data loss)
echo "\nChecking for Missing IDs (Data present in old column but missing in new ID column):\n";

$check = function ($label, $oldCol, $newCol) use ($pdo) {
    if ($newCol === 'driver_user_id') {
        // Special handling for driver: check if name/email exists but ID is null
        $sql = "SELECT COUNT(*) FROM cb_bookings WHERE ($oldCol IS NOT NULL AND $oldCol != '') AND $newCol IS NULL";
    } else {
        $sql = "SELECT COUNT(*) FROM cb_bookings WHERE ($oldCol IS NOT NULL AND $oldCol != '') AND $newCol IS NULL";
    }
    $stmt = $pdo->query($sql);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        echo "  [WARNING] $label: $count rows have $oldCol but NO $newCol. Deleting $oldCol will lose data!\n";
    } else {
        echo "  [OK] $label: All clean.\n";
    }
};

$check('Approver', 'approver_email', 'approver_user_id');
$check('Supervisor Approved By', 'supervisor_approved_by', 'supervisor_approved_user_id');
$check('Manager Approved By', 'manager_approved_by', 'manager_approved_user_id');
$check('Driver Email', 'driver_email', 'driver_user_id');
$check('Driver Name', 'driver_name', 'driver_user_id');

echo "\nVerification Complete.\n";
