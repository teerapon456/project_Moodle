<?php

/**
 * Car Booking Module - Entry Point
 * Migrated to Tailwind CSS
 */

// Use optimized session configuration (fixes Antivirus slowdown)
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
startOptimizedSession();

// Load UrlHelper for dynamic paths
require_once __DIR__ . '/../../core/Helpers/UrlHelper.php';

use Core\Helpers\UrlHelper;

$basePath = UrlHelper::getBasePath();

// Check authentication
if (!isset($_SESSION['user'])) {
    $redirectUrl = UrlHelper::getCurrentUrl();
    session_write_close(); // Release lock before redirect
    // Redirect to local login page with redirect_to parameter
    header('Location: login.php?redirect_to=' . urlencode($redirectUrl));
    exit;
}

// Copy session data to local variables and release lock ASAP
$user = $_SESSION['user'];
$page = $_GET['page'] ?? 'dashboard';
session_write_close(); // Release session lock immediately after reading

// Dependencies
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Helpers/PermissionHelper.php';

// Create single database connection for all operations
$db = new Database();
$pdo = $db->getConnection();

// ============================================
// Auto-trigger: Update approved -> in_use
// Runs max once per minute (Poor Man's Cron)
// ============================================
$lastCronCheck = $_SESSION['cb_last_cron'] ?? 0;
if (time() - $lastCronCheck > 60 && $pdo) {
    $_SESSION['cb_last_cron'] = time();
    try {
        $stmt = $pdo->prepare("
            UPDATE cb_bookings 
            SET status = 'in_use', in_use_at = NOW()
            WHERE status = 'approved' AND start_time <= NOW()
        ");
        $stmt->execute();
    } catch (Exception $e) {
        // Silent fail - don't break the page
    }
}
// ============================================

// Base URL for assets and navigation
$baseUrl = $basePath . '/Modules/CarBooking';
$assetBase = UrlHelper::getAssetBase();
// Remove trailing slash for compatibility with existing code that appends it
$publicUrl = rtrim($assetBase, '/');

// Check permissions (reusing $pdo)

$perms = userHasModuleAccess('CAR_BOOKING', (int)$user['role_id'], $pdo);
$canView = !empty($perms['can_view']);
$canEdit = !empty($perms['can_edit']);
$canManage = !empty($perms['can_manage']);

if (!$canView) {
    header('Location: ' . $assetBase . 'index.php?error=no_permission');
    exit;
}


// Refresh supervisor email and check level from DB (reusing $pdo)
// Note: Session is already closed, only update local $user variable
$canApprove = false;
try {
    if ($user && !empty($user['id']) && $pdo) {
        $stmtTmp = $pdo->prepare("
            SELECT u.default_supervisor_id, u.emplevel_id,
                   s.email as default_supervisor_email,
                   s.fullname as default_supervisor_name
            FROM users u
            LEFT JOIN users s ON u.default_supervisor_id = s.id
            WHERE u.id = :uid LIMIT 1
        ");
        $stmtTmp->execute([':uid' => $user['id']]);
        $row = $stmtTmp->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            if (!empty($row['default_supervisor_id'])) {
                $user['default_supervisor_id'] = $row['default_supervisor_id'];
                $user['default_supervisor_email'] = $row['default_supervisor_email'];
                $user['default_supervisor_name'] = $row['default_supervisor_name'];
            }
            // L06 = emplevel_id 7, show approval tab for L06+
            $canApprove = !empty($row['emplevel_id']) && (int)$row['emplevel_id'] >= 7;
        }
    }
} catch (Exception $e) {
    // ignore
}

// Valid pages based on role
$managerPages = ['dashboard', 'bookings', 'manage', 'in-use', 'cars', 'fleet-cards', 'reports', 'calendar', 'settings', 'audit-log'];
$userPages = ['dashboard', 'bookings', 'calendar'];
if ($canApprove) $userPages[] = 'manage';
$validPages = $canManage ? $managerPages : $userPages;

if (!in_array($page, $validPages)) {
    $page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจองรถ - MyHR Services</title>

    <!-- Google Fonts - Kanit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="<?= $publicUrl ?>/assets/css/tailwind.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $publicUrl ?>/assets/images/brand/inteqc-logo.png">

    <!-- Global Variables -->
    <script>
        const BASE_URL = '<?= $basePath ?>';
        const MODULE_URL = '<?= $baseUrl ?>';
        const API_BASE = '<?= $baseUrl ?>/api.php';
        const USER = <?= json_encode([
                            'id' => $user['id'],
                            'username' => $user['username'] ?? '',
                            'email' => $user['email'] ?? '',
                            'fullname' => $user['fullname'] ?? '',
                            'role' => $user['role'] ?? 'user',
                            'role_id' => $user['role_id'] ?? null,
                            'default_supervisor_id' => $user['default_supervisor_id'] ?? null
                        ]) ?>;
        const canManage = <?= json_encode($canManage) ?>;
        const canEdit = <?= json_encode($canEdit) ?>;
    </script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        .toast {
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .modal-overlay {
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php
    // Build nav groups based on permissions
    $navGroups = [];

    // Main group
    $navGroups[] = [
        'title' => null,
        'items' => [
            ['id' => 'dashboard', 'link' => '?page=dashboard', 'icon' => 'ri-home-4-line', 'text' => 'หน้าหลัก']
        ]
    ];

    // Booking group
    $bookingItems = [
        ['id' => 'bookings', 'link' => '?page=bookings', 'icon' => 'ri-file-list-3-line', 'text' => 'รายการคำขอ'],
        ['id' => 'calendar', 'link' => '?page=calendar', 'icon' => 'ri-calendar-line', 'text' => 'ปฏิทิน']
    ];

    if ($canApprove && !$canManage) {
        $bookingItems[] = ['id' => 'manage', 'link' => '?page=manage', 'icon' => 'ri-checkbox-circle-line', 'text' => 'คำขออนุมัติ'];
    }

    $navGroups[] = [
        'title' => 'การจอง',
        'items' => $bookingItems
    ];

    // Manage group
    if ($canManage) {
        $navGroups[] = [
            'title' => 'จัดการระบบ',
            'items' => [
                ['id' => 'manage', 'link' => '?page=manage', 'icon' => 'ri-checkbox-circle-line', 'text' => 'อนุมัติคำขอ'],
                ['id' => 'in-use', 'link' => '?page=in-use', 'icon' => 'ri-car-line', 'text' => 'รถที่ยังไม่คืน'],
                ['id' => 'cars', 'link' => '?page=cars', 'icon' => 'ri-roadster-line', 'text' => 'จัดการรถ'],
                ['id' => 'fleet-cards', 'link' => '?page=fleet-cards', 'icon' => 'ri-bank-card-line', 'text' => 'Fleet Card'],
                ['id' => 'reports', 'link' => '?page=reports', 'icon' => 'ri-bar-chart-box-line', 'text' => 'รายงาน'],
                ['id' => 'settings', 'link' => '?page=settings', 'icon' => 'ri-settings-3-line', 'text' => 'ตั้งค่า'],
                ['id' => 'audit-log', 'link' => '?page=audit-log', 'icon' => 'ri-file-list-2-line', 'text' => 'Audit Log']
            ]
        ];
    }

    $sidebarConfig = [
        'app_key' => 'carbooking',
        'title' => 'ระบบจองรถ',
        'icon' => 'ri-car-line',
        'home_link' => $basePath . '/Modules/HRServices/public/index.php',
        'home_text' => 'กลับสู่หน้าหลัก',
        'user' => [
            'initial' => mb_substr($user['fullname'] ?? $user['username'] ?? 'U', 0, 1),
            'name' => htmlspecialchars($user['fullname'] ?? $user['username'] ?? 'ผู้ใช้'),
            'role' => $canManage ? 'ผู้จัดการ' : 'พนักงาน'
        ],
        'nav_groups' => $navGroups
    ];

    include dirname(__DIR__, 2) . '/core/Views/components/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="main-wrapper no-transition min-h-screen" id="mainContent">
        <!-- Header -->
        <?php
        $titles = [
            'dashboard' => 'หน้าหลัก',
            'bookings' => 'รายการคำขอ',
            'calendar' => 'ปฏิทิน',
            'manage' => ($canManage ? 'อนุมัติคำขอ' : 'คำขออนุมัติ'),
            'in-use' => 'รถที่ยังไม่คืน',
            'cars' => 'จัดการรถ',
            'fleet-cards' => 'Fleet Card',
            'reports' => 'รายงาน',
            'settings' => 'ตั้งค่าระบบ',
            'audit-log' => 'Audit Log'
        ];
        $pageTitle = $titles[$page] ?? 'ระบบจองรถ';
        include dirname(__DIR__, 2) . '/core/Views/components/topbar.php';
        ?>

        <!-- Content -->
        <div class="p-6 bg-gray-50" id="contentBody">
            <?php
            $viewFile = __DIR__ . "/Views/{$page}.php";
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo '<div class="text-center py-12 text-gray-500"><i class="ri-tools-line text-4xl mb-2 block"></i><h3 class="text-lg font-medium">กำลังพัฒนา</h3><p class="text-sm mt-1">หน้านี้กำลังอยู่ในระหว่างการพัฒนา</p></div>';
            }
            ?>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 space-y-3"></div>

    <script src="<?= $assetBase ?>public/assets/js/shared-modals.js"></script>
    <script>
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const colors = {
                success: 'bg-emerald-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-amber-500'
            };
            const icons = {
                success: 'ri-check-line',
                error: 'ri-error-warning-line',
                info: 'ri-information-line',
                warning: 'ri-alert-line'
            };
            const toast = document.createElement('div');
            toast.className = `toast flex items-center gap-3 px-4 py-3 ${colors[type] || colors.info} text-white rounded-lg shadow-lg`;
            toast.innerHTML = `<i class="${icons[type] || icons.info}"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        async function apiCall(endpoint, action, data = null, method = 'GET') {
            try {
                const url = new URL(API_BASE, window.location.origin);
                url.pathname = API_BASE.replace(/\/$/, '') + '/' + endpoint;
                if (action) url.searchParams.set('action', action);

                const options = {
                    method,
                    headers: {}
                };

                if (data && method !== 'GET') {
                    if (data instanceof FormData) {
                        options.body = data;
                    } else {
                        options.headers['Content-Type'] = 'application/json';
                        options.body = JSON.stringify(data);
                    }
                } else if (data && method === 'GET') {
                    Object.keys(data).forEach(key => url.searchParams.set(key, data[key]));
                }

                const response = await fetch(url, options);
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'เกิดข้อผิดพลาด');
                return result;
            } catch (error) {
                showToast(error.message, 'error');
                throw error;
            }
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('th-TH').format(num);
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('th-TH', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }

        function formatDateTime(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleString('th-TH', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getStatusBadge(status) {
            const map = {
                'pending_supervisor': {
                    text: 'รอหัวหน้าอนุมัติ',
                    class: 'bg-amber-100 text-amber-800'
                },
                'pending_manager': {
                    text: 'รอผู้จัดการอนุมัติ',
                    class: 'bg-blue-100 text-blue-800'
                },
                'approved': {
                    text: 'อนุมัติแล้ว',
                    class: 'bg-emerald-100 text-emerald-800'
                },
                'rejected': {
                    text: 'ปฏิเสธ',
                    class: 'bg-red-100 text-red-800'
                },
                'cancelled': {
                    text: 'ยกเลิก',
                    class: 'bg-gray-100 text-gray-600'
                },
                'completed': {
                    text: 'เสร็จสิ้น',
                    class: 'bg-blue-100 text-blue-800'
                }
            };
            return map[status] || {
                text: status,
                class: 'bg-gray-100 text-gray-600'
            };
        }

        // Redirect old modal calls to new MyHRModal system
        function showConfirm(message, title = 'ยืนยัน') {
            return MyHRModal.confirm({
                message,
                title
            });
        }
    </script>

    <?php include dirname(__DIR__, 2) . '/core/Views/components/shared_modals.php'; ?>



</body>

</html>