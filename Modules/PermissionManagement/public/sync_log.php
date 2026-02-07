<?php

/*************************************************
 * sync_log.php — View Sync Error Log
 * แสดง Log การ Sync ที่เกิดข้อผิดพลาด
 *************************************************/
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';

// Check authentication
AuthMiddleware::checkLogin();

// MySQL config
$MYSQL_HOST = Env::get('DB_HOST', 'localhost');
$MYSQL_DB = Env::get('DB_NAME') ?? Env::get('DB_DATABASE', 'myhr');
$MYSQL_UID = Env::get('DB_USER') ?? Env::get('DB_USERNAME', 'root');
$MYSQL_PWD = Env::get('DB_PASS') ?? Env::get('DB_PASSWORD', '');
$MYSQL_PORT = Env::get('DB_PORT', 3306);

try {
    $pdo = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8mb4;port=$MYSQL_PORT", $MYSQL_UID, $MYSQL_PWD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Get filter
$filter = $_GET['filter'] ?? 'error';
$limit = (int)($_GET['limit'] ?? 100);

// Build query
if ($filter === 'all') {
    $sql = "SELECT * FROM user_sync_log ORDER BY synced_at DESC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $sql = "SELECT * FROM user_sync_log WHERE field_name = 'ERROR' ORDER BY synced_at DESC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
}

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$statsStmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN field_name = 'ERROR' THEN 1 ELSE 0 END) as errors,
        SUM(CASE WHEN action = 'insert' AND field_name != 'ERROR' THEN 1 ELSE 0 END) as inserts,
        SUM(CASE WHEN action = 'update' AND field_name != 'ERROR' THEN 1 ELSE 0 END) as updates
    FROM user_sync_log
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Log - Permission Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto py-8 px-4">
        <!-- Header -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
                <i class="ri-arrow-left-line"></i>
                กลับไปหน้า Permission Management
            </a>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <i class="ri-file-list-3-line text-primary"></i>
                Sync Log
            </h1>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="text-sm text-gray-500">รายการทั้งหมด</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-emerald-600"><?= number_format($stats['inserts'] ?? 0) ?></div>
                <div class="text-sm text-gray-500">Insert</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-blue-600"><?= number_format($stats['updates'] ?? 0) ?></div>
                <div class="text-sm text-gray-500">Update</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="text-2xl font-bold text-red-600"><?= number_format($stats['errors'] ?? 0) ?></div>
                <div class="text-sm text-gray-500">Error</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">แสดง:</span>
                <a href="?filter=error" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter === 'error' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                    <i class="ri-error-warning-line mr-1"></i> เฉพาะข้อผิดพลาด
                </a>
                <a href="?filter=all" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filter === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                    <i class="ri-list-check mr-1"></i> ทั้งหมด
                </a>
            </div>
        </div>

        <!-- Log Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <?php if (empty($logs)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="ri-inbox-line text-4xl mb-2"></i>
                    <p>ไม่พบข้อมูล Log</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">เวลา</th>
                                <th class="px-4 py-3 font-medium">Action</th>
                                <th class="px-4 py-3 font-medium">Person ID</th>
                                <th class="px-4 py-3 font-medium">ชื่อ</th>
                                <th class="px-4 py-3 font-medium">Field</th>
                                <th class="px-4 py-3 font-medium">รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($logs as $log): ?>
                                <tr class="hover:bg-gray-50 <?= $log['field_name'] === 'ERROR' ? 'bg-red-50' : '' ?>">
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        <?= date('d/m/Y H:i:s', strtotime($log['synced_at'])) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($log['field_name'] === 'ERROR'): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                <i class="ri-error-warning-line"></i> Error
                                            </span>
                                        <?php elseif ($log['action'] === 'insert'): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                <i class="ri-add-line"></i> Insert
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                <i class="ri-edit-line"></i> Update
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600">
                                        <?= htmlspecialchars(substr($log['person_id'], 0, 8)) ?>...
                                    </td>
                                    <td class="px-4 py-3 text-gray-800">
                                        <?= htmlspecialchars($log['fullname'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <?= htmlspecialchars($log['field_name'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($log['field_name'] === 'ERROR'): ?>
                                            <span class="text-red-600"><?= htmlspecialchars($log['new_value'] ?? '') ?></span>
                                        <?php elseif ($log['field_name'] === 'NEW_USER'): ?>
                                            <span class="text-emerald-600"><?= htmlspecialchars($log['new_value'] ?? '') ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400"><?= htmlspecialchars($log['old_value'] ?? 'null') ?></span>
                                            <i class="ri-arrow-right-line mx-1 text-gray-400"></i>
                                            <span class="text-gray-800"><?= htmlspecialchars($log['new_value'] ?? 'null') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>