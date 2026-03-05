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

// Base URL for assets
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

        /* Disable transitions on initial load to prevent flash */
        .sidebar.no-transition,
        .main-wrapper.no-transition {
            transition: none !important;
        }

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
            const KEY = 'carbooking_sidebar_collapsed';
            if (window.innerWidth > 768 && localStorage.getItem(KEY) === 'true') {
                document.write('<style id="sidebar-instant-style">#sidebar{width:70px;overflow:hidden}#sidebar .sidebar-text,#sidebar .nav-section,#sidebar .user-details,#sidebar .logo span{display:none}#sidebar nav{overflow:hidden}#mainContent{margin-left:70px}</style>');
            }
        })();
    </script>
    <!-- Mobile Overlay -->
    <div id="sidebar-overlay" onclick="closeSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity backdrop-blur-sm"></div>

    <!-- Sidebar -->
    <aside class="sidebar no-transition fixed top-0 left-0 h-full w-[260px] bg-white border-r border-gray-200 flex flex-col z-50" id="sidebar">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 h-16 border-b border-gray-100">
            <div class="logo flex items-center gap-3 text-primary font-semibold text-xl">
                <i class="ri-car-line text-2xl sidebar-text"></i>
                <span class="sidebar-text">ระบบจองรถ</span>
            </div>
            <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-lg" id="sidebarToggle">
                <i class="ri-menu-line text-lg"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
                <li>
                    <a href="?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'dashboard' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-home-4-line text-lg"></i>
                        <span class="sidebar-text">หน้าหลัก</span>
                    </a>
                </li>

                <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">การจอง</li>
                <li>
                    <a href="?page=bookings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'bookings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-file-list-3-line text-lg"></i>
                        <span class="sidebar-text">รายการคำขอ</span>
                    </a>
                </li>
                <li>
                    <a href="?page=calendar" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'calendar' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-calendar-line text-lg"></i>
                        <span class="sidebar-text">ปฏิทิน</span>
                    </a>
                </li>

                <?php if ($canApprove && !$canManage): ?>
                    <li>
                        <a href="?page=manage" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'manage' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-checkbox-circle-line text-lg"></i>
                            <span class="sidebar-text">คำขออนุมัติ</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($canManage): ?>
                    <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">จัดการระบบ</li>
                    <li>
                        <a href="?page=manage" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'manage' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-checkbox-circle-line text-lg"></i>
                            <span class="sidebar-text">อนุมัติคำขอ</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=in-use" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'in-use' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-car-line text-lg"></i>
                            <span class="sidebar-text">รถที่ยังไม่คืน</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=cars" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'cars' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-roadster-line text-lg"></i>
                            <span class="sidebar-text">จัดการรถ</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=fleet-cards" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'fleet-cards' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-bank-card-line text-lg"></i>
                            <span class="sidebar-text">Fleet Card</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=reports" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'reports' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-bar-chart-box-line text-lg"></i>
                            <span class="sidebar-text">รายงาน</span>
                        </a>
                    </li>
                    <li>
                        <a href="?page=settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'settings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                            <i class="ri-settings-3-line text-lg"></i>
                            <span class="sidebar-text">ตั้งค่า</span>
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
                    <?= mb_substr($user['fullname'] ?? $user['username'] ?? 'U', 0, 1) ?>
                </div>
                <div class="user-details flex-1 min-w-0">
                    <div class="font-medium text-gray-900 truncate text-sm"><?= htmlspecialchars($user['fullname'] ?? $user['username'] ?? 'ผู้ใช้') ?></div>
                    <div class="text-xs text-gray-500"><?= $canManage ? 'ผู้จัดการ' : 'พนักงาน' ?></div>
                </div>
            </div>
            <a href="<?= $basePath ?>/Modules/HRServices/public/index.php" class="flex items-center gap-2 px-3 py-2 text-gray-600 hover:text-primary hover:bg-gray-50 rounded-lg text-sm transition-colors">
                <i class="ri-arrow-left-line"></i>
                <span class="sidebar-text">กลับสู่ระบบหลัก</span>
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
                    echo $titles[$page] ?? 'ระบบจองรถ';
                    ?>
                </h1>
            </div>
            <div class="flex items-center gap-2 text-gray-500 text-sm">
                <i class="ri-calendar-line"></i>
                <span id="currentDate"></span>
            </div>
        </header>

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

    <!-- Scripts -->
    <script>
        document.getElementById('currentDate').textContent = new Date().toLocaleDateString('th-TH', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const menuToggle = document.getElementById('menuToggle');
        const SIDEBAR_STATE_KEY = 'carbooking_sidebar_collapsed';

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
            const overlay = document.getElementById('sidebar-overlay');
            if (window.innerWidth <= 768) {
                const isOpen = sidebar.classList.toggle('show');
                if (isOpen) {
                    overlay.classList.remove('hidden');
                } else {
                    overlay.classList.add('hidden');
                }
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                localStorage.setItem(SIDEBAR_STATE_KEY, sidebar.classList.contains('collapsed'));
            }
        }

        function closeSidebar() {
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.remove('show');
            overlay.classList.add('hidden');
        }

        restoreSidebarState();
        sidebarToggle?.addEventListener('click', toggleSidebar);
        menuToggle?.addEventListener('click', toggleSidebar);

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

        let confirmResolve = null;

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

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('confirmModal').classList.contains('active')) {
                handleConfirm(false);
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
</body>

</html>