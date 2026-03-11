<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';

$db = new Database();
$conn = $db->getConnection();

// Basic security check (already handled by index.php inclusion usually, but good for standalone)
// AuthMiddleware::checkLogin(); 

try {
    // 1. Get Stats Summary
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_requests,
            SUM(tokens_used) as total_tokens,
            COUNT(DISTINCT user_id) as unique_users
        FROM copilot_usage_logs
    ")->fetch(PDO::FETCH_ASSOC);

    // Default values if null
    $stats['total_requests'] = (int)($stats['total_requests'] ?? 0);
    $stats['total_tokens'] = (int)($stats['total_tokens'] ?? 0);
    $stats['unique_users'] = (int)($stats['unique_users'] ?? 0);

    // 2. Get Recent Logs
    $logs = $conn->query("
        SELECT l.*, u.fullname, u.username
        FROM copilot_usage_logs l
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Chart Data (Last 7 Days)
    $chartData = $conn->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as requests,
            SUM(tokens_used) as tokens
        FROM copilot_usage_logs
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'stats' => $stats,
        'logs' => $logs,
        'chartData' => $chartData
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
