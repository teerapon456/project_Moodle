<?php
// Modules/CarBooking/Scripts/backfill_approver_ids.php

// Adjust path to find core/
$corePath = __DIR__ . '/../../../core';

require_once $corePath . '/Config/Env.php';
require_once $corePath . '/Database/Database.php';

// Database class is in global namespace
$db = new Database();
$pdo = $db->getConnection();

echo "Starting Backfill of Car Booking Approver IDs...\n";

// 0. Backup Table
$backupTable = 'cb_bookings_backup_' . date('YmdHis');
$pdo->exec("CREATE TABLE `$backupTable` LIKE cb_bookings");
$pdo->exec("INSERT INTO `$backupTable` SELECT * FROM cb_bookings");
echo "Backup created: $backupTable\n";

// 1. Fetch all bookings
$stmt = $pdo->query("
    SELECT id, 
           approver_email, approver_user_id,
           supervisor_approved_by, supervisor_approved_user_id,
           manager_approved_by, manager_approved_user_id,
           driver_email, driver_user_id
    FROM cb_bookings
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($bookings);
$updatedCount = 0;

foreach ($bookings as $booking) {
    $updates = [];
    $params = ['id' => $booking['id']];
    $hasUpdate = false;

    // Helper to find ID by email
    $findId = function ($email) use ($pdo) {
        if (empty($email)) return null;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn();
    };

    // Approver
    if (!empty($booking['approver_email']) && empty($booking['approver_user_id'])) {
        $uid = $findId($booking['approver_email']);
        if ($uid) {
            $updates[] = "approver_user_id = :auid";
            $params['auid'] = $uid;
            $hasUpdate = true;
            echo "Booking #{$booking['id']}: Found Approver ID $uid for {$booking['approver_email']}\n";
        }
    }

    // Supervisor Approved By
    if (!empty($booking['supervisor_approved_by']) && empty($booking['supervisor_approved_user_id'])) {
        $uid = $findId($booking['supervisor_approved_by']);
        if ($uid) {
            $updates[] = "supervisor_approved_user_id = :suid";
            $params['suid'] = $uid;
            $hasUpdate = true;
            echo "Booking #{$booking['id']}: Found Supervisor ID $uid for {$booking['supervisor_approved_by']}\n";
        }
    }

    // Manager Approved By
    if (!empty($booking['manager_approved_by']) && empty($booking['manager_approved_user_id'])) {
        $uid = $findId($booking['manager_approved_by']);
        if ($uid) {
            $updates[] = "manager_approved_user_id = :muid";
            $params['muid'] = $uid;
            $hasUpdate = true;
            echo "Booking #{$booking['id']}: Found Manager ID $uid for {$booking['manager_approved_by']}\n";
        }
    }

    // Driver
    if (!empty($booking['driver_email']) && empty($booking['driver_user_id'])) {
        $uid = $findId($booking['driver_email']);
        if ($uid) {
            $updates[] = "driver_user_id = :duid";
            $params['duid'] = $uid;
            $hasUpdate = true;
            echo "Booking #{$booking['id']}: Found Driver ID $uid for {$booking['driver_email']}\n";
        }
    }

    if ($hasUpdate) {
        $sql = "UPDATE cb_bookings SET " . implode(', ', $updates) . " WHERE id = :id";
        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);
        $updatedCount++;
    }
}

echo "Backfill Complete. Updated $updatedCount out of $total bookings.\n";
