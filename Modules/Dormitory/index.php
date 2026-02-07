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
    session_write_close(); // Release lock before redirect
    $assetBase = UrlHelper::getAssetBase();
    header('Location: ' . $assetBase . 'index.php');
    exit;
}

// Copy session data and release lock ASAP
$user = $_SESSION['user'];
$page = $_GET['page'] ?? 'dashboard';
session_write_close(); // Release session lock immediately

// Base URL for assets
$baseUrl = $basePath . '/Modules/Dormitory';
$assetBase = UrlHelper::getAssetBase();
// Remove trailing slash for compatibility
$publicUrl = rtrim($assetBase, '/');

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

// Block users without view permission
if (!$canView) {
    header('Location: ' . $assetBase . 'index.php?error=no_permission');
    exit;
}

// Valid pages based on role
$adminPages = ['dashboard', 'buildings', 'rooms', 'billing', 'meter-reading', 'invoices', 'payments', 'maintenance', 'maintenance-form', 'settings', 'my-room', 'history', 'audit-log', 'booking_form', 'booking_manage', 'request_history'];
$userPages = ['my-room', 'invoices', 'payments', 'request_history'];

if ($canEdit) {
    $userPages[] = 'booking_form';
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
                            'username' => $user['username'] ?? '',
                            'email' => $user['email'] ?? '',
                            'role' => $isAdmin ? 'admin' : 'user',
                            'role_id' => $user['role_id'] ?? null,
                            'permissions' => $perms,
                            'employee_id' => $user['employee_id'] ?? null
                        ]) ?>;
        const isAdmin = <?= json_encode($isAdmin) ?>;
        const canEdit = <?= json_encode($canEdit) ?>;
    </script>

    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        /* Disable transitions on initial load to prevent flash */
        .sidebar.no-transition,
        .main-wrapper.no-transition {
            transition: none !important;
        }

        /* Sidebar transitions */
        .sidebar {
            transition: width 0.3s ease, transform 0.3s ease;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-text {
            display: none;
        }

        .sidebar.collapsed .nav-section {
            display: none;
        }

        .sidebar.collapsed .user-details {
            display: none;
        }

        .sidebar.collapsed .logo span {
            display: none;
        }

        /* Hide scrollbar and center icons when collapsed */
        .sidebar.collapsed nav {
            overflow: hidden;
            padding-left: 0;
            padding-right: 0;
        }

        .sidebar.collapsed nav ul {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar.collapsed nav a {
            width: 44px;
            height: 44px;
            padding: 0;
            justify-content: center;
            border-radius: 8px;
        }

        .sidebar.collapsed nav a i {
            margin: 0;
        }

        .main-wrapper {
            transition: margin-left 0.3s ease;
            margin-left: 260px;
        }

        .main-wrapper.expanded {
            margin-left: 70px;
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

        /* Modal */
        .modal-overlay {
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 260px !important;
                z-index: 50;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Instant sidebar state restore (before render) -->
    <script>
        (function() {
            const KEY = 'dormitory_sidebar_collapsed';
            if (window.innerWidth > 768 && localStorage.getItem(KEY) === 'true') {
                document.write('<style id="sidebar-instant-style">#sidebar{width:70px;overflow:hidden}#sidebar .sidebar-text,#sidebar .nav-section,#sidebar .user-details,#sidebar .logo span{display:none}#sidebar nav{overflow:hidden}#mainContent{margin-left:70px}</style>');
            }
        })();
    </script>
    <!-- Sidebar -->
    <aside class="sidebar no-transition fixed top-0 left-0 h-full w-[260px] bg-white border-r border-gray-200 flex flex-col z-40" id="sidebar">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 h-16 border-b border-gray-100">
            <div class="logo flex items-center gap-3 text-primary font-semibold text-xl">
                <i class="ri-building-2-line text-2xl sidebar-text"></i>
                <span class="sidebar-text">ระบบหอพัก</span>
            </div>
            <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-lg" id="sidebarToggle">
                <i class="ri-menu-line text-lg"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
                <?php if ($isAdmin): ?>
                    <li>
                        <a href="?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'dashboard' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-home-4-line text-lg"></i>
                            <span class="sidebar-text">หน้าหลัก</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ผู้พักอาศัย</li>
                <li>
                    <a href="?page=my-room" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'my-room' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-user-heart-line text-lg"></i>
                        <span class="sidebar-text">ห้องของฉัน</span>
                    </a>
                </li>
                <?php if ($canEdit): ?>
                    <li>
                        <a href="?page=booking_form" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'booking_form' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-hotel-bed-line text-lg"></i>
                            <span class="sidebar-text">ขอเข้าพัก/ย้าย</span>
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="?page=request_history" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'request_history' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-history-line text-lg"></i>
                        <span class="sidebar-text">ประวัติคำขอ</span>
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                    <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">จัดการห้องพัก</li>
                    <li>
                        <a href="?page=buildings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'buildings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-building-line text-lg"></i>
                            <span class="sidebar-text">อาคาร</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=booking_manage" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'booking_manage' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-file-list-3-line text-lg"></i>
                            <span class="sidebar-text">จัดการคำขอ</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=rooms" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'rooms' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-door-open-line text-lg"></i>
                            <span class="sidebar-text">ห้องพัก</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=history" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'history' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-history-line text-lg"></i>
                            <span class="sidebar-text">ประวัติการเข้าพัก</span>
                        </a>
                    </li>

                    <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ระบบบิล</li>
                    <li>
                        <a href="?page=meter-reading" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'meter-reading' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-dashboard-3-line text-lg"></i>
                            <span class="sidebar-text">บันทึกมิเตอร์</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=invoices" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'invoices' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-file-list-3-line text-lg"></i>
                            <span class="sidebar-text">ใบแจ้งหนี้</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=payments" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'payments' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-money-dollar-circle-line text-lg"></i>
                            <span class="sidebar-text">การชำระเงิน</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ระบบแจ้งซ่อม</li>
                <!-- Allow all users to see maintenance form -->
                <li>
                    <a href="?page=maintenance-form" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'maintenance-form' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-add-circle-line text-lg"></i>
                        <span class="sidebar-text">แจ้งซ่อมใหม่</span>
                    </a>
                </li>
                <?php if ($isAdmin): ?>
                    <li>
                        <a href="?page=maintenance" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'maintenance' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-tools-line text-lg"></i>
                            <span class="sidebar-text">รายการแจ้งซ่อม</span>
                        </a>
                    </li>

                    <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ตั้งค่า</li>
                    <li>
                        <a href="?page=settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'settings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-settings-3-line text-lg"></i>
                            <span class="sidebar-text">ตั้งค่าระบบ</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=audit-log" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'audit-log' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-file-list-2-line text-lg"></i>
                            <span class="sidebar-text">Audit Log</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Footer -->
        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-medium">
                    <?= mb_substr($user['fullname'] ?? $user['name'] ?? $user['username'] ?? 'U', 0, 1) ?>
                </div>
                <div class="user-details flex-1 min-w-0">
                    <div class="font-medium text-gray-900 truncate text-sm"><?= htmlspecialchars($user['fullname'] ?? $user['name'] ?? $user['username'] ?? 'ผู้ใช้') ?></div>
                    <div class="text-xs text-gray-500"><?= ($user['role'] ?? 'user') === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้พักอาศัย' ?></div>
                </div>
            </div>
            <a href="<?= $assetBase ?>index.php" class="flex items-center gap-2 px-3 py-2 text-gray-600 hover:text-primary hover:bg-gray-50 rounded-lg text-sm transition-colors">
                <i class="ri-arrow-left-line"></i>
                <span class="sidebar-text">กลับหน้าหลัก</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-wrapper no-transition min-h-screen" id="mainContent">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 h-16 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-lg md:hidden" id="menuToggle">
                    <i class="ri-menu-line text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-900">
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
                    echo $titles[$page] ?? 'ระบบหอพัก';
                    ?>
                </h1>
            </div>
            <div class="flex items-center gap-2 text-gray-500 text-sm">
                <i class="ri-calendar-line"></i>
                <span id="currentDate"></span>
            </div>
        </header>

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
    <script>
        document.getElementById('currentDate').textContent = new Date().toLocaleDateString('th-TH', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const menuToggle = document.getElementById('menuToggle');
        const SIDEBAR_STATE_KEY = 'dormitory_sidebar_collapsed';

        function restoreSidebarState() {
            if (window.innerWidth > 768) {
                const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            }
            // Remove instant style and re-enable transitions
            requestAnimationFrame(() => {
                const instantStyle = document.getElementById('sidebar-instant-style');
                if (instantStyle) instantStyle.remove();
                sidebar.classList.remove('no-transition');
                mainContent.classList.remove('no-transition');
            });
        }

        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                localStorage.setItem(SIDEBAR_STATE_KEY, sidebar.classList.contains('collapsed'));
            }
        }

        restoreSidebarState();
        sidebarToggle?.addEventListener('click', toggleSidebar);
        menuToggle?.addEventListener('click', toggleSidebar);

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

        // Custom dialogs
        let confirmResolve = null,
            promptResolve = null;

        function showConfirm(message, title = 'ยืนยัน') {
            return new Promise((resolve) => {
                confirmResolve = resolve;
                document.getElementById('confirmTitle').textContent = title;
                document.getElementById('confirmMessage').textContent = message;
                document.getElementById('confirmModal').classList.add('active');
            });
        }

        function handleConfirm(result) {
            document.getElementById('confirmModal').classList.remove('active');
            if (confirmResolve) {
                confirmResolve(result);
                confirmResolve = null;
            }
        }

        function showPrompt(message, title = 'กรุณากรอกข้อมูล', defaultValue = '') {
            return new Promise((resolve) => {
                promptResolve = resolve;
                document.getElementById('promptTitle').textContent = title;
                document.getElementById('promptMessage').textContent = message;
                document.getElementById('promptInput').value = defaultValue;
                document.getElementById('promptModal').classList.add('active');
                setTimeout(() => document.getElementById('promptInput').focus(), 100);
            });
        }

        function handlePrompt(submit) {
            document.getElementById('promptModal').classList.remove('active');
            if (promptResolve) {
                promptResolve(submit ? document.getElementById('promptInput').value : null);
                promptResolve = null;
            }
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && document.getElementById('promptModal').classList.contains('active')) handlePrompt(true);
            if (e.key === 'Escape') {
                if (document.getElementById('confirmModal').classList.contains('active')) handleConfirm(false);
                if (document.getElementById('promptModal').classList.contains('active')) handlePrompt(false);
            }
        });
    </script>

    <!-- Confirm Modal -->
    <div class="modal-overlay fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5" id="confirmModal">
        <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900" id="confirmTitle">ยืนยัน</h3>
                <button class="text-gray-400 hover:text-gray-600 text-xl" onclick="handleConfirm(false)">&times;</button>
            </div>
            <div class="p-5">
                <p id="confirmMessage" class="text-gray-600"></p>
            </div>
            <div class="flex justify-end gap-3 px-5 py-4 bg-gray-50 rounded-b-xl">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="handleConfirm(false)">ยกเลิก</button>
                <button class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="handleConfirm(true)">ยืนยัน</button>
            </div>
        </div>
    </div>

    <!-- Prompt Modal -->
    <div class="modal-overlay fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5" id="promptModal">
        <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900" id="promptTitle">กรุณากรอกข้อมูล</h3>
                <button class="text-gray-400 hover:text-gray-600 text-xl" onclick="handlePrompt(false)">&times;</button>
            </div>
            <div class="p-5">
                <p id="promptMessage" class="text-gray-600 mb-4"></p>
                <input type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" id="promptInput">
            </div>
            <div class="flex justify-end gap-3 px-5 py-4 bg-gray-50 rounded-b-xl">
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="handlePrompt(false)">ยกเลิก</button>
                <button class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="handlePrompt(true)">ตกลง</button>
            </div>
        </div>
    </div>

    <!-- Page-specific scripts -->
    <?php if (file_exists(__DIR__ . "/public/js/{$page}.js")): ?>
        <script src="<?= $baseUrl ?>/public/js/<?= $page ?>.js"></script>
    <?php endif; ?>
</body>

</html>