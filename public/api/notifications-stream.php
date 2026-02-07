<?php

/**
 * Server-Sent Events (SSE) endpoint for real-time notifications
 * เรียกใช้: /api/notifications/stream
 */

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Start output buffering for SSE
ob_start();

// Helper function to safely flush output
function safeFlush()
{
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// Start session (READ ONLY for SSE)
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
if (function_exists('startSessionReadOnly')) {
    startSessionReadOnly();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}
// CRITICAL: Force close session writing to release lock immediately
// This prevents this long-running script from blocking other requests (like Approve)
session_write_close();

if (!isset($_SESSION['user']['id'])) {
    echo "event: error\n";
    echo "data: {\"error\": \"Unauthorized\"}\n\n";
    exit;
}

$userId = $_SESSION['user']['id'];

require_once __DIR__ . '/../../core/Services/NotificationService.php';

// Send initial unread count
$unreadCount = NotificationService::getUnreadCount($userId);
echo "event: init\n";
echo "data: " . json_encode(['unread_count' => $unreadCount]) . "\n\n";
safeFlush();

// Keep connection alive and check for new notifications
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// If last_id is 0 (fresh connection), do NOT replay all unread messages.
// Start from the current latest ID.
if ($lastId === 0) {
    $lastId = NotificationService::getLastId($userId);
}

$checkInterval = 3; // Check every 3 seconds
$maxRuntime = 30; // Max 30 seconds per connection (reconnect after)

$startTime = time();

while ((time() - $startTime) < $maxRuntime) {
    // Check for new notifications
    $newNotifications = NotificationService::getNewSince($userId, $lastId);

    if (!empty($newNotifications)) {
        foreach ($newNotifications as $notification) {
            echo "event: notification\n";
            echo "data: " . json_encode($notification) . "\n\n";
            $lastId = max($lastId, $notification['id']);
        }
        safeFlush();
    }

    // Send heartbeat to keep connection alive
    echo ": heartbeat\n\n";
    safeFlush();

    // Sleep before next check
    sleep($checkInterval);

    // Check if client disconnected
    if (connection_aborted()) {
        break;
    }
}

// Tell client to reconnect
echo "event: reconnect\n";
echo "data: {\"last_id\": $lastId}\n\n";
