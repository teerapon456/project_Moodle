<?php
// Modules/CarBooking/Scripts/fix_status_enum.php

$corePath = __DIR__ . '/../../../core';
require_once $corePath . '/Config/Env.php';
require_once $corePath . '/Database/Database.php';

echo "Attempting to fix 'status' enum in cb_bookings...\n";

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Check current column definition
    $stmt = $pdo->query("SHOW COLUMNS FROM cb_bookings LIKE 'status'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current definition: " . ($col['Type'] ?? 'Unknown') . "\n";

    // Update column
    // Adding pending_manager, rejected_supervisor, rejected_manager, etc.
    $sql = "ALTER TABLE cb_bookings 
            MODIFY COLUMN status 
            ENUM('pending_supervisor', 'pending_manager', 'approved', 'in_use', 'pending_return', 'completed', 'rejected_supervisor', 'rejected_manager', 'cancelled', 'revoked') 
            NOT NULL DEFAULT 'pending_supervisor'";

    $pdo->exec($sql);
    echo "[SUCCESS] Updated status column definition.\n";
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
