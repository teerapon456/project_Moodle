<?php

/**
 * Dormitory Module - Entry Point
 * หน้าหลักของระบบหอพัก - Migrated to Tailwind CSS
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

// Copy session data and release lock ASAP
$user = $_SESSION['user'];
$page = $_GET['page'] ?? 'dashboard';
session_write_close(); // Release session lock immediately

// Base URL for assets and navigation
$baseUrl = $basePath . '/Modules/Dormitory';
// Detect clean URL (/Dormitory/ instead of /Modules/Dormitory/)
if (strpos($_SERVER['REQUEST_URI'], $basePath . '/Dormitory') === 0) {
    $baseUrl = $basePath . '/Dormitory';
}

$assetBase = UrlHelper::getAssetBase();
// Remove trailing slash for compatibility
$publicUrl = rtrim($assetBase, '/');

// Link base for navigation links (used by sidebar)
$linkBase = UrlHelper::getLinkBase();

// Load database for permission check
require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Helpers/PermissionHelper.php';

// Create single database connection for all operations
$db = new Database();
$pdo = $db->getConnection();

// Helper function to check module permissions from DB (uses shared connection)
function getDormitoryPermissions($roleId, $conn)
{
    if (!$conn) return ['can_view' => 0, 'can_edit' => 0, 'can_manage' => 0, 'can_delete' => 0];

    try {
        // Get module ID for DORMITORY
        $stmt = $conn->prepare("
            SELECT cm.id,
                   COALESCE(p.can_view, 0) as can_view,
                   COALESCE(p.can_edit, 0) as can_edit,
                   COALESCE(p.can_manage, 0) as can_manage,
                   COALESCE(p.can_delete, 0) as can_delete
            FROM core_modules cm
            LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = ?
            WHERE cm.code = 'DORMITORY'
            LIMIT 1
        ");
        $stmt->execute([$roleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: ['can_view' => 0, 'can_edit' => 0, 'can_manage' => 0, 'can_delete' => 0];
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return ['can_view' => 0, 'can_edit' => 0, 'can_manage' => 0, 'can_delete' => 0];
    }
}

// RBAC - Check permissions from DB (uses shared $pdo)
$perms = getDormitoryPermissions((int)($user['role_id'] ?? 0), $pdo);
$canView = !empty($perms['can_view']) || !empty($perms['can_edit']) || !empty($perms['can_manage']);
$canEdit = !empty($perms['can_edit']) || !empty($perms['can_manage']);
$isAdmin = !empty($perms['can_manage']);

// Check if user is L06+ (can approve)
$canApprove = false;
try {
    if (isset($user['id']) && $pdo) {
        $stmtTmp = $pdo->prepare("SELECT emplevel_id FROM users WHERE id = ?");
        $stmtTmp->execute([$user['id']]);
        $row = $stmtTmp->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['emplevel_id']) && (int)$row['emplevel_id'] >= 7) {
            $canApprove = true;
        }
    }
} catch (Exception $e) {
}

// Block users without view permission
if (!$canView) {
    header('Location: ' . $assetBase . 'index.php?error=no_permission');
    exit;
}

// Valid pages based on role
$adminPages = ['dashboard', 'buildings', 'rooms', 'billing', 'meter-reading', 'invoices', 'payments', 'maintenance', 'maintenance-form', 'settings', 'my-room', 'history', 'audit-log', 'booking_form', 'booking_manage', 'request_history', 'layout-designer', 'layout-display'];
$userPages = ['my-room', 'invoices', 'payments', 'request_history', 'layout-display'];

if ($canEdit) {
    $userPages[] = 'booking_form';
}
if ($canApprove) {
    $userPages[] = 'booking_manage';
}
$userPages[] = 'maintenance-form'; // Allow all users to access maintenance form

$validPages = $isAdmin ? $adminPages : $userPages;

if (!$isAdmin && $page === 'dashboard') {
    $page = 'my-room';
}

if (!in_array($page, $validPages)) {
    $page = $isAdmin ? 'dashboard' : 'my-room';
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบหอพัก - MyHR Services</title>

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
                            'username' => $user['EmpCode'] ?? $user['username'] ?? '',
                            'email' => $user['email'] ?? '',
                            'role' => $isAdmin ? 'admin' : 'user',
                            'role_id' => $user['role_id'] ?? null,
                            'permissions' => $perms,
                            'employee_id' => $user['EmpCode'] ?? $user['username'] ?? null
                        ]) ?>;
        const isAdmin = <?= json_encode($isAdmin) ?>;
        const canEdit = <?= json_encode($canEdit) ?>;
        const canApprove = <?= json_encode($canApprove) ?>;
    </script>

    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        /* Toast */
        .toast {
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php
    $navGroups = [];

    if ($isAdmin) {
        $navGroups[] = [
            'title' => null,
            'items' => [
                ['id' => 'dashboard', 'link' => '?page=dashboard', 'icon' => 'ri-home-4-line', 'text' => 'หน้าหลัก']
            ]
        ];
    }

    $residentGroupItems = [
        ['id' => 'my-room', 'link' => '?page=my-room', 'icon' => 'ri-user-heart-line', 'text' => 'ห้องของฉัน']
    ];
    if ($canEdit) {
        $residentGroupItems[] = ['id' => 'booking_form', 'link' => '?page=booking_form', 'icon' => 'ri-hotel-bed-line', 'text' => 'ขอเข้าพัก/ย้าย'];
    }
    $residentGroupItems[] = ['id' => 'request_history', 'link' => '?page=request_history', 'icon' => 'ri-history-line', 'text' => 'ประวัติคำขอ'];

    $navGroups[] = [
        'title' => 'ผู้พักอาศัย',
        'items' => $residentGroupItems
    ];

    if ($isAdmin || $canApprove) {
        $manageGroupItems = [];
        if ($isAdmin) {
            $manageGroupItems[] = ['id' => 'buildings', 'link' => '?page=buildings', 'icon' => 'ri-building-line', 'text' => 'อาคาร'];
        }
        if ($isAdmin || $canApprove) {
            $manageGroupItems[] = ['id' => 'booking_manage', 'link' => '?page=booking_manage', 'icon' => 'ri-file-list-3-line', 'text' => 'จัดการคำขอ'];
        }
        if ($isAdmin) {
            $manageGroupItems[] = ['id' => 'rooms', 'link' => '?page=rooms', 'icon' => 'ri-door-open-line', 'text' => 'ห้องพัก'];
            $manageGroupItems[] = ['id' => 'history', 'link' => '?page=history', 'icon' => 'ri-history-line', 'text' => 'ประวัติการเข้าพัก'];
        }

        $navGroups[] = [
            'title' => 'จัดการห้องพัก',
            'items' => $manageGroupItems
        ];
    }

    if ($isAdmin) {
        $navGroups[] = [
            'title' => 'ระบบบิล',
            'items' => [
                ['id' => 'meter-reading', 'link' => '?page=meter-reading', 'icon' => 'ri-dashboard-3-line', 'text' => 'บันทึกมิเตอร์'],
                ['id' => 'invoices', 'link' => '?page=invoices', 'icon' => 'ri-file-list-3-line', 'text' => 'ใบแจ้งหนี้'],
                ['id' => 'payments', 'link' => '?page=payments', 'icon' => 'ri-money-dollar-circle-line', 'text' => 'การชำระเงิน']
            ]
        ];
    }

    $maintenanceGroupItems = [
        ['id' => 'maintenance-form', 'link' => '?page=maintenance-form', 'icon' => 'ri-add-circle-line', 'text' => 'แจ้งซ่อมใหม่']
    ];
    if ($isAdmin) {
        $maintenanceGroupItems[] = ['id' => 'maintenance', 'link' => '?page=maintenance', 'icon' => 'ri-tools-line', 'text' => 'รายการแจ้งซ่อม'];
    }

    $navGroups[] = [
        'title' => 'ระบบแจ้งซ่อม',
        'items' => $maintenanceGroupItems
    ];

    if ($isAdmin) {
        $navGroups[] = [
            'title' => 'ตั้งค่า',
            'items' => [
                ['id' => 'settings', 'link' => '?page=settings', 'icon' => 'ri-settings-3-line', 'text' => 'ตั้งค่าระบบ'],
                ['id' => 'audit-log', 'link' => '?page=audit-log', 'icon' => 'ri-file-list-2-line', 'text' => 'Audit Log']
            ]
        ];
    }

    $sidebarConfig = [
        'app_key' => 'dormitory',
        'title' => 'ระบบหอพัก',
        'icon' => 'ri-building-2-line',
        'home_link' => $linkBase . 'Modules/HRServices/public/index.php',
        'home_text' => 'กลับสู่หน้าหลัก',
        'user' => [
            'initial' => mb_substr($user['fullname'] ?? $user['name'] ?? $user['username'] ?? 'U', 0, 1),
            'name' => htmlspecialchars($user['fullname'] ?? $user['name'] ?? $user['username']),
            'role' => $user['role_name'] ?? 'User'
        ],
        'nav_groups' => $navGroups
    ];

    include dirname(__DIR__, 2) . '/core/Views/components/sidebar.php';
    ?>

    <!-- Main Content -->
    <main class="main-wrapper no-transition min-h-screen" id="mainContent">
        <?php
        $titles = [
            'dashboard' => 'หน้าหลัก',
            'buildings' => 'จัดการอาคาร',
            'rooms' => 'จัดการห้องพัก',
            'meter-reading' => 'บันทึกมิเตอร์',
            'invoices' => 'ใบแจ้งหนี้',
            'payments' => 'การชำระเงิน',
            'maintenance' => 'รายการแจ้งซ่อม',
            'maintenance-form' => 'แจ้งซ่อมใหม่',
            'settings' => 'ตั้งค่าระบบ',
            'history' => 'ประวัติการเข้าพัก',
            'audit-log' => 'Audit Log',
            'my-room' => 'ห้องของฉัน',
            'booking_form' => 'ขอเข้าพัก/ย้าย',
            'booking_manage' => 'จัดการคำขอเข้าพัก',
            'request_history' => 'ประวัติคำขอ'
        ];
        $pageTitle = $titles[$page] ?? 'ระบบหอพัก';
        include dirname(__DIR__, 2) . '/core/Views/components/topbar.php';
        ?>

        <!-- Content -->
        <div class="p-6" id="contentBody">
            <?php
            $viewFile = __DIR__ . "/Views/{$page}.php";
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo '<div class="text-center py-12 text-gray-500"><i class="ri-error-warning-line text-4xl mb-2 block"></i>ไม่พบหน้าที่ต้องการ</div>';
            }
            ?>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 space-y-3"></div>

    <!-- Scripts -->
    <script src="<?= $baseRoot ?>/public/assets/js/shared-modals.js"></script>
    <script>
        // No need for sidebar toggle scripts here as they are included in the shared component

        // Toast notification
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

        // API helper
        async function apiCall(controller, action, data = null, method = 'GET') {
            try {
                const url = new URL(API_BASE, window.location.origin);
                url.searchParams.set('controller', controller);
                url.searchParams.set('action', action);

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

                if (!result.success) throw new Error(result.message || 'เกิดข้อผิดพลาด');
                return result;
            } catch (error) {
                showToast(error.message, 'error');
                throw error;
            }
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('th-TH').format(num);
        }

        function formatCurrency(num) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB'
            }).format(num);
        }

        // Redirect old modal calls to new MyHRModal system
        function showConfirm(message, title = 'ยืนยัน') {
            return MyHRModal.confirm({
                message,
                title
            });
        }

        function showPrompt(message, title = 'กรุณากรอกข้อมูล', defaultValue = '') {
            return MyHRModal.prompt({
                message,
                title,
                defaultValue
            });
        }
    </script>

    <?php include dirname(__DIR__, 2) . '/core/Views/components/shared_modals.php'; ?>

    <!-- Page-specific scripts -->
    <?php if (file_exists(__DIR__ . "/public/js/{$page}.js")): ?>
        <script src="<?= $baseUrl ?>/public/js/<?= $page ?>.js"></script>
    <?php endif; ?>
</body>

</html>