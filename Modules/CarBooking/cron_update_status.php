<?php

/**
 * CRON Job - Update Booking Status to In-Use
 * 
 * รันทุก 5 นาที เพื่อเปลี่ยนสถานะ approved -> in_use เมื่อถึง start_time
 * 
 * Setup:
 * - Windows Task Scheduler: php "C:\xampp\htdocs\MyHR Portal\Modules\CarBooking\cron_update_status.php"
 * - Linux Cron: (every 5 min) php /path/to/cron_update_status.php
 * - หรือเรียกผ่าน API: ?controller=cron&action=updateStatus
 */

// CLI mode check
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Called via HTTP - check for secret key
    $providedKey = $_GET['key'] ?? $_POST['key'] ?? '';
    $secretKey = 'CARBOOKING_CRON_SECRET_2024'; // ควรเก็บใน config

    if ($providedKey !== $secretKey) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }
}

require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Services/NotificationService.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    $now = date('Y-m-d H:i:s');
    $updated = 0;

    // 1. Get bookings that will become in_use (before update) for notifications
    $stmtSelect = $pdo->prepare("
        SELECT id, user_id FROM cb_bookings
        WHERE status = 'approved' AND start_time <= :now
    ");
    $stmtSelect->execute([':now' => $now]);
    $toNotify = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    // 2. Change approved -> in_use when start_time is reached
    $stmt = $pdo->prepare("
        UPDATE cb_bookings 
        SET status = 'in_use', 
            in_use_at = NOW()
        WHERE status = 'approved' 
        AND start_time <= :now
    ");
    $stmt->execute([':now' => $now]);
    $updated = $stmt->rowCount();

    // 3. Send in-app notification to each requester
    foreach ($toNotify as $row) {
        $bookingId = (int)$row['id'];
        $userId = (int)$row['user_id'];
        if ($userId > 0) {
            try {
                NotificationService::create(
                    $userId,
                    'info',
                    'เริ่มใช้งานจองรถได้แล้ว',
                    "คำขอจองรถ #{$bookingId} ถึงเวลาแล้ว สามารถเริ่มใช้งานได้",
                    ['booking_id' => $bookingId],
                    'Modules/CarBooking/?page=request_history'
                );
            } catch (Exception $e) {
                error_log("Cron in_use notification failed (booking #{$bookingId}): " . $e->getMessage());
            }
        }
    }

    $result = [
        'success' => true,
        'message' => "Updated $updated booking(s) to in_use status",
        'updated_count' => $updated,
        'executed_at' => $now
    ];

    if ($isCli) {
        echo "[" . $now . "] CRON: Updated $updated booking(s) to in_use\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
} catch (Exception $e) {
    $error = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];

    if ($isCli) {
        echo "[ERROR] " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode($error);
    }
}
