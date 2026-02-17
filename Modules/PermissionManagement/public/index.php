<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use optimized session configuration (fixes Antivirus slowdown)
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
// startOptimizedSession(); // Moved to Middleware

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';

$db = new Database();
$conn = $db->getConnection();

// $user setup removed, waiting for linkBase

// Base paths (prefer .env APP_BASE_PATH)
$basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/Modules/PermissionManagement/public$#i', '', $scriptDir);
}
if ($basePath === '') {
    $basePath = '/';
}
$baseRoot = rtrim($basePath, '/');

// Determine asset base: check if DocumentRoot points to public/ folder (Docker) or htdocs (XAMPP)
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot && is_dir($docRoot . '/assets')) {
    $assetBase = ($baseRoot ? $baseRoot : '') . '/';
} else {
    $assetBase = ($baseRoot ? $baseRoot : '') . '/public/';
}
$linkBase = ($baseRoot ? $baseRoot . '/' : '/');

$user = AuthMiddleware::checkLogin($linkBase);
$roleId = $user['role_id'] ?? null;
$roleActive = $user['role_active'] ?? 1;

require_once __DIR__ . '/../../../core/Helpers/PermissionHelper.php';

function getModulePermissionByCode($conn, $moduleCode, $roleId)

{
    $sql = "
SELECT cm.id,
COALESCE(p.can_view, 0) as can_view,
COALESCE(p.can_edit, 0) as can_edit,
COALESCE(p.can_delete, 0) as can_delete,
COALESCE(p.can_manage, 0) as can_manage
FROM core_modules cm
LEFT JOIN core_module_permissions p
ON p.module_id = cm.id AND p.role_id = :role_id
WHERE cm.code = :code
LIMIT 1
";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
    $stmt->bindValue(':code', $moduleCode);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function resolveCurrentModuleCode($conn, $path, $fallback)
{
    try {
        if (!$conn) return $fallback;
        $normalized = rtrim($path, '/');
        $sql = "SELECT code FROM core_modules WHERE :p LIKE CONCAT(path, '%') ORDER BY LENGTH(path) DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':p', $normalized);
        $stmt->execute();
        return $stmt->fetchColumn() ?: $fallback;
    } catch (Exception $e) {
        return $fallback;
    }
}

// module code for permission management page (resolve by path, fallback env)
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$moduleCode = resolveCurrentModuleCode($conn, $currentPath, Env::get('PERMISSION_MODULE_CODE', 'PERMISSION_MANAGEMENT'));

// Redirect Helper
// Redirect logic handled by Middleware

// Guard: must be logged in and role active and have view access
if (!$user || !$roleId || !$conn || !$roleActive) {
    AuthMiddleware::redirectToLogin($linkBase, 'no_permission');
    exit;
}

try {
    $perm = getModulePermissionByCode($conn, $moduleCode, (int)$roleId);
    $canView = $perm ? (bool)$perm['can_view'] : false;
    $canEdit = $perm ? ((bool)$perm['can_edit'] || (bool)$perm['can_manage']) : false;
    $canManage = $perm ? (bool)$perm['can_manage'] : false;
    // ถ้ามีสิทธิ์ manage ที่ PERMISSION module => full manage ทุกส่วนในหน้านี้
    if ($canManage) {
        $canView = true;
        $canEdit = true;
    }
} catch (Exception $e) {
    $canView = false;
    $canEdit = false;
    $canManage = false;
}

// ต้องมี can_view เพื่อดูหน้าได้
if (!$canView) {
    header('Location: ' . $redirectBase . '?error=no_permission');
    exit;
}

$userPerms = userHasModuleAccess('HR_SERVICES', (int)$roleId);
$hrNewsPerm = userHasModuleAccess('HR_NEWS', (int)$roleId);
$activityPerm = userHasModuleAccess('ACTIVITY_DASHBOARD', (int)$roleId);
$emailLogPerm = userHasModuleAccess('EMAIL_LOGS', (int)$roleId);
$scheduledPerm = userHasModuleAccess('SCHEDULED_REPORTS', (int)$roleId);
$permManage = ['can_view' => 1, 'can_manage' => $canManage ? 1 : 0];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสิทธิ์ & Module</title>
    <link rel="icon" type="image/png" href="<?php echo $assetBase; ?>assets/images/brand/inteqc-logo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="<?php echo $assetBase; ?>assets/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo $assetBase; ?>assets/js/global-notifications.js"></script>
    <style type="text/tailwindcss">
        @layer utilities {
            .btn-icon {
                @apply inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-200 border;
            }
            .btn-edit {
                @apply border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 hover:border-blue-300 transition-colors;
            }
            .btn-danger {
                @apply border-transparent bg-red-600 hover:bg-red-700 text-white transition-colors shadow-sm;
            }
            .btn-primary {
                @apply border-transparent bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition-colors;
            }
            .btn-secondary {
                @apply border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors;
            }
            .btn-success {
                @apply border-transparent bg-green-600 text-white hover:bg-green-700 shadow-sm;
            }
            .card {
                @apply bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden;
            }
            .form-input {
                @apply block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm px-3 py-2 transition-all;
            }
            .form-select {
                @apply block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm px-3 py-2 transition-all;
            }
            .tab-btn {
                @apply px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200 flex items-center gap-2;
            }
            .tab-btn.active {
                @apply bg-blue-600 text-white shadow-sm;
            }
            .tab-btn:not(.active) {
                @apply text-gray-600 hover:bg-gray-100;
            }
        }
    </style>
    <?php
    // Generate CSRF token
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];
    ?>
    <script>
        window.APP_BASE_PATH = <?php echo json_encode($basePath ?? ''); ?>;
        window.CSRF_TOKEN = <?php echo json_encode($csrfToken ?? ''); ?>;
    </script>
</head>

<body class="bg-gray-50 font-kanit min-h-screen">
    <?php include __DIR__ . '/../../../public/includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="ri-shield-keyhole-line text-primary"></i> จัดการสิทธิ์ระบบ
            </h1>
            <p class="mt-1 text-sm text-gray-500">กำหนดสิทธิ์การใช้งาน, จัดการ Role และผู้ใช้งาน</p>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden min-h-[600px] flex flex-col">

            <!-- Tab Navigation Header -->
            <div class="bg-gray-50/50 border-b border-gray-200 px-6 pt-4">
                <div class="flex items-center gap-1 overflow-x-auto no-scrollbar" id="tab-nav">
                    <button class="nav-tab active group relative pb-4 px-4 min-w-[120px] text-center" data-tab="perm">
                        <div class="flex items-center justify-center gap-2 text-sm font-semibold transition-colors duration-200 text-primary">
                            <i class="ri-shield-keyhole-line text-lg"></i>
                            <span>Permissions</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-primary transform scale-x-100 transition-transform duration-200 origin-left"></div>
                    </button>

                    <button class="nav-tab group relative pb-4 px-4 min-w-[120px] text-center" data-tab="role">
                        <div class="flex items-center justify-center gap-2 text-sm font-semibold transition-colors duration-200 text-gray-500 hover:text-gray-700">
                            <i class="ri-shield-user-line text-lg"></i>
                            <span>Roles</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-primary transform scale-x-0 transition-transform duration-200 origin-left group-hover:scale-x-100 opacity-50"></div>
                    </button>

                    <button class="nav-tab group relative pb-4 px-4 min-w-[120px] text-center" data-tab="user">
                        <div class="flex items-center justify-center gap-2 text-sm font-semibold transition-colors duration-200 text-gray-500 hover:text-gray-700">
                            <i class="ri-group-line text-lg"></i>
                            <span>Users</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-primary transform scale-x-0 transition-transform duration-200 origin-left group-hover:scale-x-100 opacity-50"></div>
                    </button>

                    <button class="nav-tab group relative pb-4 px-4 min-w-[120px] text-center" data-tab="rate-limits">
                        <div class="flex items-center justify-center gap-2 text-sm font-semibold transition-colors duration-200 text-gray-500 hover:text-gray-700">
                            <i class="ri-shield-check-line text-lg"></i>
                            <span>Rate Limits</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-primary transform scale-x-0 transition-transform duration-200 origin-left group-hover:scale-x-100 opacity-50"></div>
                    </button>

                    <button class="nav-tab group relative pb-4 px-4 min-w-[120px] text-center" data-tab="system-security">
                        <div class="flex items-center justify-center gap-2 text-sm font-semibold transition-colors duration-200 text-gray-500 hover:text-gray-700">
                            <i class="ri-settings-4-line text-lg"></i>
                            <span>System & Security</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-primary transform scale-x-0 transition-transform duration-200 origin-left group-hover:scale-x-100 opacity-50"></div>
                    </button>

                    <button class="nav-tab group relative pb-4 px-4 min-w-[120px] text-center" data-tab="audit-log">
                        <div class="flex items-center justify-center gap-2 text-sm font-semibold transition-colors duration-200 text-gray-500 hover:text-gray-700">
                            <i class="ri-history-line text-lg"></i>
                            <span>ประวัติการแก้ไข</span>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-0.5 bg-primary transform scale-x-0 transition-transform duration-200 origin-left group-hover:scale-x-100 opacity-50"></div>
                    </button>
                </div>
            </div>

            <!-- Tab Content Area -->
            <div class="p-0 flex-1 bg-white relative">

                <!-- Permissions Tab -->
                <div class="tab-panel animate-fade-in" data-tab="perm">
                    <!-- Role Selector Toolbar -->
                    <div class="px-6 py-5 border-b border-gray-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-10 bg-white/95 backdrop-blur-sm">
                        <div class="flex items-center gap-4">
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-user-star-line text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                </div>
                                <select id="perm-role" class="form-select pl-10 pr-10 py-2.5 bg-gray-50 border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-medium text-gray-700 min-w-[240px]">
                                    <option value="">-- เลือก Role เพื่อจัดการ --</option>
                                </select>
                            </div>
                            <div id="perm-loading" class="hidden text-primary">
                                <i class="ri-loader-4-line animate-spin text-xl"></i>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="h-8 px-3 rounded-lg bg-gray-50 border border-gray-200 flex items-center gap-2 text-xs font-medium text-gray-600">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Access: <?= ($canManage ? 'Full' : ($canEdit ? 'Edit' : 'View')) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Matrix Table -->
                    <div class="overflow-x-auto min-h-[400px]">
                        <table class="w-full" id="perm-table">
                            <thead>
                                <tr class="bg-gray-50 border-y border-gray-100">
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-[40%]">Module Name</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-[15%]">View</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-[15%]">Edit</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-[15%]">Delete</th>
                                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-[15%]">Manage</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr>
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center justify-center opacity-50">
                                            <i class="ri-shield-keyhole-line text-5xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 font-medium">กรุณาเลือก Role ที่ต้องการจัดการสิทธิ์</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Roles Tab -->
                <div class="tab-panel hidden animate-fade-in" data-tab="role">
                    <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="relative max-w-xs w-full group">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                            <input type="text" id="role-search" class="form-input pl-10 py-2.5 rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-gray-400" placeholder="ค้นหา Role...">
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($canManage): ?>
                                <button id="add-role-btn" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm shadow-red-200 hover:shadow-red-300 transition-all transform active:scale-95">
                                    <i class="ri-shield-user-line"></i> Add Role
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full" id="role-table">
                            <thead>
                                <tr class="bg-gray-50 border-y border-gray-100">
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Role Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50"></tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                        <span id="role-page-label" class="text-xs text-gray-500 font-medium"></span>
                        <div class="flex items-center gap-2">
                            <button id="role-prev" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"><i class="ri-arrow-left-s-line"></i></button>
                            <button id="role-next" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"><i class="ri-arrow-right-s-line"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Users Tab -->
                <div class="tab-panel hidden animate-fade-in" data-tab="user">
                    <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="relative max-w-xs w-full group">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                <input type="text" id="user-search" class="form-input pl-10 py-2.5 rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-gray-400" placeholder="Search users...">
                            </div>
                        </div>
                        <?php if ($canManage): ?>
                            <button id="add-user-btn" class="btn-primary flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm shadow-red-200 hover:shadow-red-300 transition-all transform active:scale-95">
                                <i class="ri-user-add-line"></i> Add User
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full" id="user-table">
                            <thead>
                                <tr class="bg-gray-50 border-y border-gray-100">
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">User Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50"></tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                        <span id="user-page-label" class="text-xs text-gray-500 font-medium"></span>
                        <div class="flex items-center gap-2">
                            <button id="user-prev" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"><i class="ri-arrow-left-s-line"></i></button>
                            <button id="user-next" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"><i class="ri-arrow-right-s-line"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Rate Limits Tab -->
                <div class="tab-panel hidden animate-fade-in" data-tab="rate-limits">
                    <?php if ($canManage): ?>
                        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex items-center gap-2">
                                    <i class="ri-information-line text-yellow-600"></i>
                                    <div class="text-sm text-yellow-800">
                                        <strong>Rate Limit Management:</strong> Users are locked after 5 failed login attempts within 15 minutes.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="loadRateLimits()" class="btn-secondary flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium">
                                    <i class="ri-refresh-line"></i> Refresh
                                </button>
                                <button onclick="clearAllRateLimits()" class="btn-danger flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium">
                                    <i class="ri-delete-bin-line"></i> Clear All
                                </button>
                            </div>
                        </div>

                        <div id="rate-limits-container" class="p-6">
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-loader-4-line animate-spin text-2xl"></i>
                                <p class="mt-2">Loading rate limits...</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto">
                                <i class="ri-lock-line text-red-600 text-3xl mb-3"></i>
                                <h3 class="text-lg font-semibold text-red-800 mb-2">Access Denied</h3>
                                <p class="text-red-600 text-sm">
                                    You need <strong>Manage</strong> permissions to access Rate Limit Management.
                                    Please contact your administrator for access.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- System & Security Tab -->
                <div id="tab-system-security" class="tab-panel hidden animate-fade-in" data-tab="system-security">
                    <?php if (!empty($canManage)): ?>
                        <div class="max-w-5xl mx-auto py-8 px-6 space-y-10">

                            <!-- Unified System & Security Settings -->
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
                                <!-- Geolocation Section -->
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-100 pb-6 mb-6">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-800 text-lg">บังคับระบุตำแหน่งก่อน Login</h4>
                                        <p class="text-gray-500 text-sm mt-1">ผู้ใช้ต้องอนุญาตตำแหน่งที่ตั้งเพื่อเข้าใช้งานระบบ (สำหรับ Standard & Microsoft Login)</p>
                                        <div class="flex items-center gap-4 mt-3">
                                            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider flex items-center gap-1.5">
                                                <i class="ri-time-line"></i> <span id="geo-last-update">Last update: -</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" id="geo-toggle" class="sr-only peer" onchange="toggleGeoSetting(this)">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-500"></div>
                                        </label>
                                    </div>
                                </div>
                                <div id="security-loading" class="hidden text-center py-2 mb-4">
                                    <i class="ri-loader-4-line animate-spin text-primary"></i> <span class="text-xs text-gray-400">Saving...</span>
                                </div>

                                <!-- User Sync Section -->
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-lg">Automatic Schedule</h4>
                                        <p class="text-gray-500 text-sm mt-1">Configure daily automatic synchronization from HRIS (SQL Server)</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-medium text-gray-700">Enable Auto Sync</span>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="auto_sync_enabled" id="auto_sync_enabled" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-500"></div>
                                        </label>
                                    </div>
                                </div>

                                <form id="sync-settings-form" class="space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Sync Time (Daily)</label>
                                            <div class="relative">
                                                <input type="time" name="auto_sync_time" id="auto_sync_time" class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 shadow-sm text-gray-900 bg-white" value="02:00">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                    <i class="ri-time-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Notification Email</label>
                                            <div class="relative">
                                                <input type="email" name="notification_email" id="notification_email" class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 shadow-sm text-gray-900 bg-white" placeholder="admin@example.com">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                    <i class="ri-mail-line"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>
                </div>
                <div id="sync-loading" class="hidden text-center py-2">
                    <i class="ri-loader-4-line animate-spin text-primary"></i> <span class="text-xs text-gray-400">Saving changes...</span>
                </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="p-12 text-center text-gray-500">
            <i class="ri-lock-line text-4xl mb-4 block text-red-200"></i>
            Access Denied: You need permission to manage system settings.
        </div>
    <?php endif; ?>
    </div>

    <!-- End Tab Content -->
    </div>
    </div>
    </div>
    </div>

    <style>
        /* Custom Toggle Switch */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #10b981;
        }

        .toggle-checkbox:checked+.toggle-label {
            background-color: #10b981;
        }

        .toggle-checkbox {
            right: 0;
            transition: all 0.2s cubic-bezier(0.4, 0.0, 0.2, 1);
        }

        /* Fix right position when unchecked */
        .toggle-checkbox:not(:checked) {
            right: calc(100% - 1.5rem);
            /* 1.5rem = w-6 */
        }

        /* Specific Toggle Colors - Enforce Teal for Consistency */
        #geo-toggle:checked+div,
        #auto_sync_enabled:checked+div {
            background-color: #14b8a6;
            /* Teal-500 */
        }

        #geo-toggle:focus+div,
        #auto_sync_enabled:focus+div {
            box-shadow: 0 0 0 4px #ccfbf1;
            /* Teal-100 ring */
        }
    </style>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php';
        const CAN_EDIT = <?= !empty($canEdit) ? 'true' : 'false' ?>;
        const CAN_MANAGE = <?= !empty($canManage) ? 'true' : 'false' ?>;
        const LOGIN_URL = '<?= $linkBase ?>'; // Go to root
        window.currentModuleId = 3; // Permission Management Module ID

        // --- Security Settings Logic (Global) ---
        window.loadSecuritySettings = async function() {
            const loader = document.getElementById('security-loading');
            if (loader) loader.classList.remove('hidden');

            try {
                const res = await fetch(`${API_BASE_URL}/permissions/get_settings?module_id=3`, {
                    credentials: 'include'
                });
                if (!res.ok) throw new Error('Failed to load settings');
                const settings = await res.json();

                const geoSetting = settings.find(s => s.setting_key === 'mandatory_geolocation');
                if (geoSetting) {
                    const toggle = document.getElementById('geo-toggle');
                    if (toggle) toggle.checked = (geoSetting.setting_value === '1');
                    const updateLabel = document.getElementById('geo-last-update');
                    if (updateLabel) updateLabel.textContent = 'Last update: ' + (geoSetting.updated_at || '-');
                }
            } catch (e) {
                console.error(e);
                if (window.notify) window.notify('Could not load security settings', 'error');
            } finally {
                if (loader) loader.classList.add('hidden');
            }
        };

        window.toggleGeoSetting = async function(checkbox) {
            const loader = document.getElementById('security-loading');
            if (loader) loader.classList.remove('hidden');

            const newValue = checkbox.checked ? '1' : '0';

            try {
                const res = await fetch(`${API_BASE_URL}/permissions/save_setting`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        _csrf_token: window.CSRF_TOKEN,
                        setting_key: 'mandatory_geolocation',
                        setting_value: newValue,
                        module_id: 3
                    })
                });

                if (!res.ok) throw new Error('Failed to save');
                const data = await res.json();

                if (window.notify) window.notify('Security setting updated', 'success');

                // Refresh last update time
                loadSecuritySettings();
            } catch (e) {
                console.error(e);
                checkbox.checked = !checkbox.checked; // Revert
                if (window.notify) window.notify('Error saving setting: ' + e.message, 'error');
            } finally {
                if (loader) loader.classList.add('hidden');
            }
        };

        // --- Sync Settings Logic (Global) ---
        window.loadSyncSettings = async function() {
            const autoSyncUrl = window.location.pathname.replace(/\/index\.php$/, '') + '/sync_users.php';
            try {
                let url = autoSyncUrl + '?action=get_settings';
                if (window.currentModuleId) {
                    url += '&module_id=' + window.currentModuleId;
                }
                const res = await fetch(url);
                if (!res.ok) return;
                const data = await res.json();

                const syncToggle = document.getElementById('auto_sync_enabled');
                if (syncToggle) syncToggle.checked = (data.auto_sync_enabled === '1');

                const syncTime = document.getElementById('auto_sync_time');
                if (syncTime) syncTime.value = data.auto_sync_time || '02:00';

                const notifEmail = document.getElementById('notification_email');
                if (notifEmail) notifEmail.value = data.notification_email || '';
            } catch (e) {
                console.error('Failed to load settings', e);
            }
        };

        // Initialize Sync Settings Form Listener
        document.addEventListener('DOMContentLoaded', () => {
            const settingsForm = document.getElementById('sync-settings-form');
            if (settingsForm) {
                const autoSyncUrl = window.location.pathname.replace(/\/index\.php$/, '') + '/sync_users.php';
                const syncParams = ['auto_sync_enabled', 'auto_sync_time', 'notification_email'];

                async function saveSyncSettings() {
                    const loader = document.getElementById('sync-loading');
                    if (loader) loader.classList.remove('hidden');

                    try {
                        const formData = new FormData(settingsForm);
                        formData.append('action', 'save_settings');
                        if (window.currentModuleId) {
                            formData.append('module_id', window.currentModuleId);
                        }

                        // Handle checkbox specifically if unchecked (FormData doesn't include it)
                        if (!document.getElementById('auto_sync_enabled').checked) {
                            formData.set('auto_sync_enabled', '0');
                        } else {
                            formData.set('auto_sync_enabled', '1');
                        }

                        const res = await fetch(autoSyncUrl, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await res.json();
                        if (result.success) {
                            if (window.notify) window.notify('บันทึกการตั้งค่าเรียบร้อยแล้ว', 'success');
                        } else {
                            throw new Error(result.error || 'Unknown error');
                        }
                    } catch (e) {
                        console.error(e);
                        if (window.notify) window.notify('บันทึกไม่สำเร็จ: ' + e.message, 'error');
                    } finally {
                        if (loader) loader.classList.add('hidden');
                    }
                }

                // Attach Auto-Save Listeners
                const toggle = document.getElementById('auto_sync_enabled');
                const timeInput = document.getElementById('auto_sync_time');
                const emailInput = document.getElementById('notification_email');

                if (toggle) toggle.addEventListener('change', saveSyncSettings);
                if (timeInput) {
                    timeInput.addEventListener('change', saveSyncSettings);
                    timeInput.addEventListener('blur', saveSyncSettings);
                }
                if (emailInput) {
                    emailInput.addEventListener('change', saveSyncSettings);
                    emailInput.addEventListener('blur', saveSyncSettings);
                }
            }
            // Initial load
            loadSyncSettings();
        });

        // Global window.notify is now defined in global-notifications.js

        async function logout() {
            try {
                await fetch(`${API_BASE_URL}/auth/logout`, {
                    method: 'POST',
                    credentials: 'include'
                });
            } catch (e) {
                console.error('Logout error', e);
            } finally {
                window.location.href = LOGIN_URL;
            }
        }

        // Side nav toggler wrapper logic if header exists (reused from hr_services but header handles most)
        // (Assuming header.php handles side nav toggling internally or provides IDs)

        const STATUS_TEXT = {
            ready: 'พร้อมใช้',
            soon: 'เร็วๆนี้',
            maintenance: 'ปิดปรับปรุง'
        };

        const STATUS_CLASS = {
            ready: 'status-ready',
            soon: 'status-soon',
            maintenance: 'status-maintenance'
        };

        let rolesCache = null;
        let rolesPromise = null;
        let usersCacheGlobal = [];
        let userPage = 1;
        const USER_PAGE_SIZE = 10;
        let userSearchTerm = '';
        let rolePage = 1;
        let roleSearchTerm = '';
        const ROLE_PAGE_SIZE = 10;

        const getFilteredRoles = () => {
            const roles = rolesCache || [];
            const term = roleSearchTerm.trim().toLowerCase();
            if (!term) return roles;
            return roles.filter(r => (`${r.name || ''} ${r.description || ''}`).toLowerCase().includes(term));
        };

        function fillRoleSelect(selectEl, roles) {
            if (!selectEl) return;
            selectEl.innerHTML = '<option value="">-- เลือก role --</option>' + roles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
        }

        async function getRoles() {
            if (rolesCache) return rolesCache;
            if (rolesPromise) return rolesPromise;
            rolesPromise = (async () => {
                const res = await fetch(`${API_BASE_URL}/permissions/roles`, {
                    credentials: 'include'
                });
                if (!res.ok) throw new Error('roles failed');
                rolesCache = await res.json();
                return rolesCache;
            })();
            return rolesPromise;
        }

        async function loadCoreModules() {
            const res = await fetch(`${API_BASE_URL}/permissions/core_modules`, {
                credentials: 'include'
            });
            if (!res.ok) throw new Error('core modules failed');
            return await res.json();
        }

        async function loadPermissions(roleId) {
            const res = await fetch(`${API_BASE_URL}/permissions/permissions?role_id=${roleId}`, {
                credentials: 'include'
            });
            if (!res.ok) throw new Error('permissions failed');
            return await res.json();
        }

        async function savePermission(roleId, moduleId, perms) {
            const res = await fetch(`${API_BASE_URL}/permissions/save_permission`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    _csrf_token: window.CSRF_TOKEN,
                    role_id: roleId,
                    module_id: moduleId,
                    ...perms
                })
            });
            if (!res.ok) {
                let msg = 'ไม่มีสิทธิ์';
                try {
                    const data = await res.json();
                    if (data.message) msg = data.message;
                } catch (_) {}
                throw new Error(msg);
            }
            return true;
        }

        function renderPermissionTable(modules, perms, roleId) {
            const tbody = document.querySelector('#perm-table tbody');
            if (!modules.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-16 text-center text-gray-500">ยังไม่มี core_modules</td></tr>';
                return;
            }

            const permMap = {};
            perms.forEach(p => permMap[p.module_id] = p);

            // Sort modules alphabetically by name
            const sortedModules = [...modules].sort((a, b) =>
                (a.name || '').localeCompare(b.name || '', 'th')
            );

            const createToggle = (checked, className, disabled) => {
                const checkedClass = checked ? 'bg-emerald-500' : 'bg-gray-300';
                const dotPosition = checked ? 'translate-x-5' : 'translate-x-0';
                const disabledAttr = disabled ? 'pointer-events-none opacity-50' : 'cursor-pointer';
                return `
                    <label class="relative inline-flex items-center ${disabledAttr}">
                        <input type="checkbox" class="${className} sr-only peer" ${checked ? 'checked' : ''} ${disabled ? 'disabled' : ''}>
                        <div class="w-10 h-5 ${checkedClass} rounded-full peer peer-checked:bg-emerald-500 transition-colors duration-200"></div>
                        <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transform ${dotPosition} peer-checked:translate-x-5 transition-transform duration-200"></div>
                    </label>
                `;
            };

            // Module icon mapping
            let html = '';

            sortedModules.forEach((m, rowIndex) => {
                const p = permMap[m.id] || {};
                const rowBg = rowIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50/50';
                const disableToggle = !CAN_EDIT;

                // Determine icon HTML
                let iconContent;
                if (m.custom_icon_path) {
                    // Ensure valid path concatenation
                    const iconPath = m.custom_icon_path.startsWith('/') ? m.custom_icon_path.substring(1) : m.custom_icon_path;
                    iconContent = `<img src="${APP_BASE_PATH}${iconPath}" alt="icon" class="w-6 h-6 object-contain">`;
                } else {
                    const iconClass = m.icon || 'ri-puzzle-line';
                    const iconColor = m.icon_color || '#9ca3af'; // Default gray-400
                    iconContent = `<i class="${iconClass} transition-colors" style="color: ${iconColor}; font-size: 1.2rem;"></i>`;
                }

                html += `
                    <tr data-module="${m.id}" class="${rowBg} hover:bg-blue-50/50 transition-colors group">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-gray-50 border border-gray-100 group-hover:bg-white group-hover:border-blue-200 flex items-center justify-center transition-all overflow-hidden shadow-sm">
                                    ${iconContent}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">${m.name || '-'}</div>
                                    <code class="text-xs text-gray-400">${m.code || '-'}</code>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">${createToggle(p.can_view, 'perm-view', disableToggle)}</td>
                        <td class="px-4 py-3 text-center">${createToggle(p.can_edit, 'perm-edit', disableToggle)}</td>
                        <td class="px-4 py-3 text-center">${createToggle(p.can_delete, 'perm-delete', disableToggle)}</td>
                        <td class="px-4 py-3 text-center">${createToggle(p.can_manage, 'perm-manage', disableToggle)}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            if (CAN_EDIT) {
                tbody.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.addEventListener('change', async (e) => {
                        const row = e.target.closest('tr');
                        const moduleId = row.getAttribute('data-module');
                        const toggle = e.target.closest('label');
                        const dot = toggle.querySelector('div:last-child');
                        const bg = toggle.querySelector('div:first-of-type');

                        // Immediate visual feedback
                        if (e.target.checked) {
                            bg.classList.remove('bg-gray-300');
                            bg.classList.add('bg-emerald-500');
                            dot.classList.remove('translate-x-0');
                            dot.classList.add('translate-x-5');
                        } else {
                            bg.classList.remove('bg-emerald-500');
                            bg.classList.add('bg-gray-300');
                            dot.classList.remove('translate-x-5');
                            dot.classList.add('translate-x-0');
                        }

                        const permsPayload = {
                            can_view: row.querySelector('.perm-view').checked ? 1 : 0,
                            can_edit: row.querySelector('.perm-edit').checked ? 1 : 0,
                            can_delete: row.querySelector('.perm-delete').checked ? 1 : 0,
                            can_manage: row.querySelector('.perm-manage').checked ? 1 : 0
                        };
                        try {
                            await savePermission(roleId, moduleId, permsPayload);
                            // Show success indicator
                            row.classList.add('ring-2', 'ring-emerald-200');
                            setTimeout(() => row.classList.remove('ring-2', 'ring-emerald-200'), 500);
                        } catch (err) {
                            alert(err.message || 'ไม่มีสิทธิ์แก้ไข');
                            // Revert toggle
                            e.target.checked = !e.target.checked;
                            if (e.target.checked) {
                                bg.classList.remove('bg-gray-300');
                                bg.classList.add('bg-emerald-500');
                                dot.classList.remove('translate-x-0');
                                dot.classList.add('translate-x-5');
                            } else {
                                bg.classList.remove('bg-emerald-500');
                                bg.classList.add('bg-gray-300');
                                dot.classList.remove('translate-x-5');
                                dot.classList.add('translate-x-0');
                            }
                        }
                    });
                });
            }
        }

        function renderRoleTable(roles, error = false) {
            const tbody = document.querySelector('#role-table tbody');
            const pageLabel = document.getElementById('role-page-label');
            const prevBtn = document.getElementById('role-prev');
            const nextBtn = document.getElementById('role-next');
            if (error) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-red-500">โหลด role ไม่สำเร็จ</td></tr>';
                if (pageLabel) pageLabel.textContent = '';
                return;
            }
            const filtered = getFilteredRoles();
            if (!filtered.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">ยังไม่มี role</td></tr>';
                if (pageLabel) pageLabel.textContent = '';
                return;
            }
            const totalPages = Math.ceil(filtered.length / ROLE_PAGE_SIZE) || 1;
            if (rolePage > totalPages) rolePage = totalPages;
            const start = (rolePage - 1) * ROLE_PAGE_SIZE;
            const rows = filtered.slice(start, start + ROLE_PAGE_SIZE);
            tbody.innerHTML = rows.map(r => `
                <tr data-role="${r.id}" class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${r.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${r.description || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-center"><span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full ${r.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${r.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                        <button class="btn-icon btn-edit role-edit" data-role="${r.id}" ${CAN_EDIT ? '' : 'disabled'}><i class="ri-edit-line"></i> แก้ไข</button>
                    </td>
                </tr>
            `).join('');
            if (pageLabel) pageLabel.textContent = `แสดง ${start + 1}-${start + rows.length} จาก ${filtered.length}`;
            if (prevBtn) prevBtn.disabled = rolePage <= 1;
            if (nextBtn) nextBtn.disabled = rolePage >= totalPages;

            document.querySelectorAll('.role-edit').forEach(btn => {
                btn.addEventListener('click', () => {
                    const rid = parseInt(btn.getAttribute('data-role'), 10);
                    const role = (rolesCache || []).find(r => parseInt(r.id, 10) === rid);
                    if (role) roleModal.open(role);
                });
            });
        }

        async function saveRole(payload) {
            const payloadWithToken = {
                ...payload,
                _csrf_token: window.CSRF_TOKEN
            };
            const res = await fetch(`${API_BASE_URL}/permissions/save_role`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(payloadWithToken)
            });
            if (!res.ok) {
                let msg = 'บันทึกไม่สำเร็จ';
                try {
                    const data = await res.json();
                    if (data.message) msg = data.message;
                } catch (_) {}
                throw new Error(msg);
            }
            return true;
        }

        async function initPermissions() {
            const roleSelect = document.getElementById('perm-role');
            const roleSearch = document.getElementById('role-search');
            const rolePrev = document.getElementById('role-prev');
            const roleNext = document.getElementById('role-next');
            let coreModules = [];
            try {
                const roles = await getRoles();
                fillRoleSelect(roleSelect, roles);
                coreModules = await loadCoreModules();
                renderRoleTable(roles);
            } catch (err) {
                document.querySelector('#perm-table tbody').innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">โหลดข้อมูลไม่สำเร็จ</td></tr>';
                renderRoleTable([], true);
                return;
            }

            if (roleSearch) {
                roleSearch.addEventListener('input', () => {
                    roleSearchTerm = roleSearch.value;
                    rolePage = 1;
                    renderRoleTable(rolesCache);
                });
            }
            if (rolePrev) {
                rolePrev.addEventListener('click', () => {
                    const totalPages = Math.ceil(getFilteredRoles().length / ROLE_PAGE_SIZE) || 1;
                    rolePage = Math.max(1, rolePage - 1);
                    renderRoleTable(rolesCache);
                });
            }
            if (roleNext) {
                roleNext.addEventListener('click', () => {
                    const totalPages = Math.ceil(getFilteredRoles().length / ROLE_PAGE_SIZE) || 1;
                    rolePage = Math.min(totalPages, rolePage + 1);
                    renderRoleTable(rolesCache);
                });
            }

            roleSelect.addEventListener('change', async (e) => {
                const roleId = e.target.value;
                if (!roleId) {
                    document.querySelector('#perm-table tbody').innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500"><div class="flex flex-col items-center justify-center"><i class="ri-list-check-2 text-4xl text-gray-300 mb-3"></i><span>เลือก Role ก่อนเพื่อดูรายการสิทธิ์</span></div></td></tr>';
                    return;
                }
                try {
                    const perms = await loadPermissions(roleId);
                    renderPermissionTable(coreModules, perms, roleId);
                } catch (err) {
                    document.querySelector('#perm-table tbody').innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">โหลดสิทธิ์ไม่สำเร็จ</td></tr>';
                }
            });
        }

        async function loadUsers() {
            const res = await fetch(`${API_BASE_URL}/permissions/users?t=${new Date().getTime()}`, {
                credentials: 'include'
            });
            if (!res.ok) throw new Error('users failed');
            return await res.json();
        }

        async function saveUser(payload) {
            const payloadWithToken = {
                ...payload,
                _csrf_token: window.CSRF_TOKEN
            };
            const res = await fetch(`${API_BASE_URL}/permissions/save_user`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(payloadWithToken)
            });
            if (!res.ok) {
                let msg = 'บันทึกไม่สำเร็จ';
                try {
                    const data = await res.json();
                    if (data.message) msg = data.message;
                } catch (_) {}
                throw new Error(msg);
            }
            return true;
        }

        function renderUserTable() {
            const tbody = document.querySelector('#user-table tbody');
            const pageLabel = document.getElementById('user-page-label');
            if (!tbody) return;
            const filtered = (usersCacheGlobal || []).filter(u => {
                const term = userSearchTerm.trim().toLowerCase();
                if (!term) return true;
                const hay = `${u.username || ''} ${u.email || ''} ${u.role || ''}`.toLowerCase();
                return hay.includes(term);
            });
            if (!filtered.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">ยังไม่มีผู้ใช้</td></tr>';
                if (pageLabel) pageLabel.textContent = '';
                return;
            }
            const totalPages = Math.ceil(filtered.length / USER_PAGE_SIZE) || 1;
            if (userPage > totalPages) userPage = totalPages;
            const start = (userPage - 1) * USER_PAGE_SIZE;
            const rows = filtered.slice(start, start + USER_PAGE_SIZE);
            tbody.innerHTML = rows.map(u => {
                const active = (u.role_active ?? 1) && (u.user_active ?? 1);
                const canEdit = CAN_EDIT;
                return `
                    <tr data-id="${u.id}" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${u.username || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${u.email || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full bg-blue-100 text-blue-800">${u.role || '-'}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center"><span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full ${active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${active ? 'Active' : 'Inactive'}</span></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <button class="btn-icon btn-edit user-edit" data-user="${u.id}" ${canEdit ? '' : 'disabled'}><i class="ri-edit-line"></i> แก้ไข</button>
                        </td>
                    </tr>
                `;
            }).join('');
            const showingStart = start + 1;
            const showingEnd = start + rows.length;
            if (pageLabel) pageLabel.textContent = `แสดง ${showingStart}-${showingEnd} จาก ${filtered.length}`;
        }

        async function assignRole(userId, roleId, userActive = null) {
            const res = await fetch(`${API_BASE_URL}/permissions/assign_role`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    _csrf_token: window.CSRF_TOKEN,
                    user_id: userId,
                    role_id: roleId
                })
            });
            if (!res.ok) {
                let msg = 'ไม่มีสิทธิ์';
                try {
                    const data = await res.json();
                    if (data.message) msg = data.message;
                } catch (_) {}
                throw new Error(msg);
            }
            return true;
        }

        async function initUserRoles() {
            const statusEl = document.getElementById('assign-status');
            const prevBtn = document.getElementById('user-prev');
            const nextBtn = document.getElementById('user-next');

            const showStatus = (msg, ok = true) => {
                if (window.notify) {
                    window.notify(msg, ok ? 'success' : 'error');
                }
            };

            try {
                const roles = await getRoles();
                rolesCache = roles;
                usersCacheGlobal = await loadUsers();
                renderUserTable();

                if (prevBtn && nextBtn) {
                    prevBtn.addEventListener('click', () => {
                        userPage = Math.max(1, userPage - 1);
                        renderUserTable();
                    });
                    nextBtn.addEventListener('click', () => {
                        const totalPages = Math.ceil((usersCacheGlobal?.length || 0) / USER_PAGE_SIZE) || 1;
                        userPage = Math.min(totalPages, userPage + 1);
                        renderUserTable();
                    });
                }
                const userSearch = document.getElementById('user-search');
                if (userSearch) {
                    userSearch.addEventListener('input', () => {
                        userSearchTerm = userSearch.value;
                        userPage = 1;
                        renderUserTable();
                    });
                }
            } catch (err) {
                if (window.notify) window.notify('โหลดข้อมูลไม่สำเร็จ', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', initPermissions);
        document.addEventListener('DOMContentLoaded', initUserRoles);

        // Tabs
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.nav-tab');
            const panels = document.querySelectorAll('.tab-panel');

            tabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tab = btn.getAttribute('data-tab');

                    // Update tab state
                    tabs.forEach(b => {
                        if (b === btn) {
                            b.classList.add('active');
                            b.querySelector('.bg-primary').classList.remove('scale-x-0', 'opacity-50');
                            b.querySelector('.bg-primary').classList.add('scale-x-100');
                            b.querySelector('div:first-child').classList.remove('text-gray-500');
                            b.querySelector('div:first-child').classList.add('text-primary');
                        } else {
                            b.classList.remove('active');
                            b.querySelector('.bg-primary').classList.add('scale-x-0', 'opacity-50');
                            b.querySelector('.bg-primary').classList.remove('scale-x-100');
                            b.querySelector('div:first-child').classList.add('text-gray-500');
                            b.querySelector('div:first-child').classList.remove('text-primary');
                        }
                    });

                    // Update panel state
                    panels.forEach(p => {
                        if (p.getAttribute('data-tab') === tab) {
                            p.classList.remove('hidden');
                        } else {
                            p.classList.add('hidden');
                        }
                    });

                    // Load Security Settings & Sync Settings if needed
                    if (tab === 'system-security') {
                        loadSecuritySettings();
                        if (typeof loadSyncSettings === 'function') {
                            loadSyncSettings();
                        }
                    }

                    // Load Audit Logs if needed
                    if (tab === 'audit-log') {
                        loadAuditLogs();
                    }
                });
            });
        });

        // Role Modal (Add/Edit)
        const roleModal = (() => {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center hidden';
            modal.id = 'role-modal';
            modal.innerHTML = `
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" id="role-modal-overlay"></div>
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl transform transition-all z-10 m-4 overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-20">
                            <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                                    <i class="ri-shield-star-line text-2xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span id="role-modal-title">จัดการ Role</span>
                                    <span class="text-sm font-normal text-gray-500" id="role-modal-subtitle">เพิ่มหรือแก้ไขข้อมูลบทบาทในระบบ</span>
                                </div>
                            </h3>
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors" id="role-modal-close">
                                <i class="ri-close-line text-2xl"></i>
                            </button>
                        </div>

                        <div class="p-8 overflow-y-auto custom-scrollbar">
                            <input type="hidden" id="role-edit-id">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Section: Role Info -->
                                <div class="md:col-span-2">
                                    <h4 class="text-base font-semibold text-gray-900 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                                        <i class="ri-information-line text-primary text-lg"></i> ข้อมูลบทบาท
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">Role Name <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-shield-user-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <input type="text" id="role-edit-name" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all text-base shadow-sm" style="padding-left: 3.5rem;" placeholder="e.g. Manager">
                                            </div>
                                        </div>

                                        <div class="group flex items-end">
                                             <div class="w-full bg-gray-50 p-3 rounded-md border border-gray-200 flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 border border-gray-200">
                                                        <i class="ri-toggle-line text-xl"></i>
                                                    </div>
                                                    <div>
                                                        <span class="block text-sm font-medium text-gray-900">สถานะการใช้งาน</span>
                                                        <span class="block text-xs text-gray-500">เปิด/ปิด การใช้งาน Role นี้</span>
                                                    </div>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" id="role-edit-active" class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="group md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">คำอธิบาย</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-file-text-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <input type="text" id="role-edit-desc" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all text-base shadow-sm" style="padding-left: 3.5rem;" placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับ Role นี้">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-20">
                            <button class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors" id="role-modal-cancel">ยกเลิก</button>
                            <button class="px-6 py-3 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition-all transform hover:-translate-y-0.5 flex items-center gap-2" id="role-modal-save">
                                <i class="ri-save-line text-lg"></i> บันทึก
                            </button>
                        </div>
                    </div>
                `;
            document.body.appendChild(modal);
            const close = () => modal.classList.add('hidden');
            const open = () => modal.classList.remove('hidden');

            modal.querySelector('#role-modal-overlay').addEventListener('click', close);
            modal.querySelector('#role-modal-close').addEventListener('click', close);
            modal.querySelector('#role-modal-cancel').addEventListener('click', close);

            return {
                open(role = null) {
                    open();
                    const titleEl = modal.querySelector('#role-modal-title');
                    const subtitleEl = modal.querySelector('#role-modal-subtitle');

                    if (role) {
                        // Edit Mode
                        titleEl.textContent = 'แก้ไข Role';
                        subtitleEl.textContent = 'แก้ไขชื่อและรายละเอียดของ Role';
                        modal.querySelector('#role-edit-id').value = role.id || '';
                        modal.querySelector('#role-edit-name').value = role.name || '';
                        modal.querySelector('#role-edit-desc').value = role.description || '';
                        modal.querySelector('#role-edit-active').checked = role.is_active ? true : false;
                    } else {
                        // Add Mode
                        titleEl.textContent = 'เพิ่ม Role ใหม่';
                        subtitleEl.textContent = 'สร้าง Role ใหม่และกำหนดข้อมูลเบื้องต้น';
                        modal.querySelector('#role-edit-id').value = '';
                        modal.querySelector('#role-edit-name').value = '';
                        modal.querySelector('#role-edit-desc').value = '';
                        modal.querySelector('#role-edit-active').checked = true; // Default active
                    }
                },
                close,
                async save() {
                    const id = modal.querySelector('#role-edit-id').value ? parseInt(modal.querySelector('#role-edit-id').value, 10) : null;
                    const name = modal.querySelector('#role-edit-name').value.trim();
                    const desc = modal.querySelector('#role-edit-desc').value.trim();
                    const active = modal.querySelector('#role-edit-active').checked ? 1 : 0;
                    if (!name) throw new Error('กรอกชื่อ Role');
                    await saveRole({
                        id, // if null, backend handles insert
                        name,
                        description: desc,
                        is_active: active
                    });
                    rolesCache = null;
                    rolesPromise = null;
                    rolesCache = await getRoles();
                    // Also update select options if on perm tab?
                    const roleSelect = document.getElementById('perm-role');
                    if (roleSelect) fillRoleSelect(roleSelect, rolesCache); // Update potential select
                    renderRoleTable(rolesCache);
                    close();
                }
            };
        })();

        // Add Role Button Listener
        document.addEventListener('DOMContentLoaded', () => {
            const addRoleBtn = document.getElementById('add-role-btn');
            if (addRoleBtn) {
                addRoleBtn.addEventListener('click', () => {
                    roleModal.open(); // No arg = Add mode
                });
            }
        });

        // Edit User Modal
        const userModal = (() => {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center hidden';
            modal.id = 'user-modal';
            modal.innerHTML = `
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" id="user-modal-overlay"></div>
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl transform transition-all z-10 m-4 overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-20">
                            <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                                    <i class="ri-user-settings-line text-2xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span>แก้ไขผู้ใช้</span>
                                    <span class="text-sm font-normal text-gray-500">ปรับปรุงข้อมูลและสิทธิ์การใช้งานของผู้ใช้</span>
                                </div>
                            </h3>
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors" id="user-modal-close">
                                <i class="ri-close-line text-2xl"></i>
                            </button>
                        </div>

                        <div class="p-8 overflow-y-auto custom-scrollbar">
                            <input type="hidden" id="user-edit-id">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Section: Account Info -->
                                <div class="md:col-span-2">
                                    <h4 class="text-base font-semibold text-gray-900 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                                        <i class="ri-shield-user-line text-primary text-lg"></i> ข้อมูลบัญชี
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">Username</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-user-3-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <input type="text" id="user-edit-name" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed text-base shadow-sm" readonly>
                                            </div>
                                        </div>

                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">Email</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-mail-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <input type="text" id="user-edit-email" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed text-base shadow-sm" readonly>
                                            </div>
                                        </div>

                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">Role <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-vip-crown-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <select id="user-edit-role" class="form-select pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all cursor-pointer appearance-none bg-no-repeat bg-[right_1rem_center] text-base shadow-sm"></select>
                                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                                                    <i class="ri-arrow-down-s-line text-xl"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="group flex items-end">
                                             <div class="w-full bg-gray-50 p-3 rounded-md border border-gray-200 flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 border border-gray-200">
                                                        <i class="ri-toggle-line text-xl"></i>
                                                    </div>
                                                    <div>
                                                        <span class="block text-sm font-medium text-gray-900">สถานะการใช้งาน</span>
                                                        <span class="block text-xs text-gray-500">เปิด/ปิด การเข้าถึงระบบของผู้ใช้</span>
                                                    </div>
                                                </div>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" id="user-edit-active" class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-20">
                            <button class="px-6 py-3 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors" id="user-modal-cancel">ยกเลิก</button>
                            <button class="px-6 py-3 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition-all transform hover:-translate-y-0.5 flex items-center gap-2" id="user-modal-save">
                                <i class="ri-save-line text-lg"></i> บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </div>
                `;
            document.body.appendChild(modal);
            const close = () => modal.classList.add('hidden');
            const open = () => modal.classList.remove('hidden');

            modal.querySelector('#user-modal-overlay').addEventListener('click', close);
            modal.querySelector('#user-modal-close').addEventListener('click', close);
            modal.querySelector('#user-modal-cancel').addEventListener('click', close);

            return {
                open(user) {
                    open();
                    modal.querySelector('#user-edit-id').value = user.id || '';
                    modal.querySelector('#user-edit-name').value = user.username || '';
                    modal.querySelector('#user-edit-email').value = user.email || '';
                    const sel = modal.querySelector('#user-edit-role');
                    if (rolesCache) {
                        sel.innerHTML = rolesCache.map(r => `<option value="${r.id}" ${user.role_id == r.id ? 'selected' : ''}>${r.name}</option>`).join('');
                    } else {
                        sel.innerHTML = '';
                    }
                    modal.querySelector('#user-edit-active').checked = (user.user_active ?? 1) ? true : false;
                },
                close,
                save: async () => {
                    const id = parseInt(modal.querySelector('#user-edit-id').value, 10);
                    const roleId = parseInt(modal.querySelector('#user-edit-role').value, 10);
                    // Fix: Checkbox logic was inverted in previous context, ensure it sends 1 for true
                    const active = modal.querySelector('#user-edit-active').checked ? 1 : 0;
                    if (!id || !roleId) throw new Error('กรอกข้อมูลให้ครบ');
                    await saveUser({
                        user_id: id,
                        role_id: roleId,
                        user_active: active
                    });
                    close();
                }
            };
        })();

        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.user-edit');
            if (editBtn) {
                const uid = parseInt(editBtn.getAttribute('data-user'), 10);
                const u = (usersCacheGlobal || []).find(x => parseInt(x.id, 10) === uid);
                if (u) {
                    userModal.open(u);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const roleSaveBtn = document.getElementById('role-modal-save');
            if (roleSaveBtn) {
                roleSaveBtn.addEventListener('click', async () => {
                    try {
                        await roleModal.save();
                        if (window.notify) window.notify('บันทึก Role สำเร็จ', 'success');
                    } catch (err) {
                        if (window.notify) window.notify(err.message || 'บันทึกไม่สำเร็จ', 'error');
                    }
                });
            }

            const saveBtn = document.getElementById('user-modal-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', async () => {
                    try {
                        await userModal.save();
                        usersCacheGlobal = await loadUsers();
                        renderUserTable();
                        if (window.notify) window.notify('บันทึกผู้ใช้สำเร็จ', 'success');
                    } catch (err) {
                        if (window.notify) window.notify(err.message || 'บันทึกไม่สำเร็จ', 'error');
                    }
                });
            }

            // Add User Modal
            const addUserModal = (() => {
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 z-50 flex items-center justify-center hidden';
                modal.id = 'add-user-modal';
                modal.innerHTML = `
                    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" id="add-user-modal-overlay"></div>
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl transform transition-all z-10 m-4 overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-20">
                            <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 border border-blue-100">
                                    <i class="ri-user-add-line text-2xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span>เพิ่มผู้ใช้ใหม่</span>
                                    <span class="text-sm font-normal text-gray-500">สร้างบัญชีผู้ใช้และกำหนดสิทธิ์การใช้งานในระบบ</span>
                                </div>
                            </h3>
                            <button class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors" id="add-user-modal-close">
                                <i class="ri-close-line text-2xl"></i>
                            </button>
                        </div>
                        
                        <div class="p-8 overflow-y-auto custom-scrollbar">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Section: Account Info -->
                                <div class="md:col-span-2">
                                    <h4 class="text-base font-semibold text-gray-900 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                                        <i class="ri-shield-user-line text-primary text-lg"></i> ข้อมูลบัญชี
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">Username <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-user-3-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <input type="text" id="add-user-username" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all text-base shadow-sm" style="padding-left: 3.5rem;" placeholder="username">
                                            </div>
                                        </div>

                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">Email <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-mail-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <input type="email" id="add-user-email" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all text-base shadow-sm" style="padding-left: 3.5rem;" placeholder="user@example.com">
                                            </div>
                                        </div>

                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">รหัสผ่าน <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-lock-password-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <input type="password" id="add-user-password" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all text-base shadow-sm" style="padding-left: 3.5rem;" placeholder="••••••••">
                                            </div>
                                        </div>

                                        <div class="group">
                                            <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">Role <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <i class="ri-vip-crown-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                                </div>
                                                <select id="add-user-role" class="form-select pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all cursor-pointer appearance-none bg-no-repeat bg-[right_1rem_center] text-base shadow-sm" style="padding-left: 3.5rem;"></select>
                                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-gray-400">
                                                    <i class="ri-arrow-down-s-line text-xl"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section: Personal Info -->
                                <div class="md:col-span-2 mt-2">
                                    <h4 class="text-base font-semibold text-gray-900 mb-6 flex items-center gap-2 pb-2 border-b border-gray-100">
                                        <i class="ri-id-card-line text-primary text-lg"></i> ข้อมูลส่วนตัว
                                    </h4>
                                    <div class="group">
                                        <label class="block text-sm font-medium text-gray-700 mb-2 ml-1">ชื่อ-นามสกุล</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <i class="ri-user-smile-line text-xl text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                            </div>
                                            <input type="text" id="add-user-fullname" class="form-input pl-14 py-3 w-full rounded-md border-gray-300 focus:border-primary focus:ring focus:ring-primary/20 transition-all text-base shadow-sm" style="padding-left: 3.5rem;" placeholder="ชื่อ นามสกุล">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-20">
                            <button class="px-6 py-3 rounded-md border border-gray-300 text-gray-700 font-medium hover:bg-gray-100 hover:text-gray-900 transition-colors focus:ring-2 focus:ring-gray-200" id="add-user-modal-cancel">ยกเลิก</button>
                            <button class="px-6 py-3 rounded-md bg-emerald-600 text-white font-medium hover:bg-emerald-700 shadow-sm shadow-emerald-200 transition-all transform hover:-translate-y-0.5 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 flex items-center gap-2" id="add-user-modal-save">
                                <i class="ri-check-line text-lg"></i> สร้างผู้ใช้
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                const close = () => {
                    modal.classList.add('hidden');
                    // Clear form
                    modal.querySelector('#add-user-username').value = '';
                    modal.querySelector('#add-user-email').value = '';
                    modal.querySelector('#add-user-fullname').value = '';
                    modal.querySelector('#add-user-password').value = '';
                };
                const open = () => modal.classList.remove('hidden');

                modal.querySelector('#add-user-modal-overlay').addEventListener('click', close);
                modal.querySelector('#add-user-modal-close').addEventListener('click', close);
                modal.querySelector('#add-user-modal-cancel').addEventListener('click', close);

                modal.querySelector('#add-user-modal-save').addEventListener('click', async () => {
                    const username = modal.querySelector('#add-user-username').value.trim();
                    const email = modal.querySelector('#add-user-email').value.trim();
                    const fullname = modal.querySelector('#add-user-fullname').value.trim();
                    const password = modal.querySelector('#add-user-password').value;
                    const roleId = parseInt(modal.querySelector('#add-user-role').value, 10);

                    if (!username || !email || !password || !roleId) {
                        if (window.notify) window.notify('กรุณากรอกข้อมูลที่จำเป็น', 'error');
                        return;
                    }

                    try {
                        const res = await fetch(`${API_BASE_URL}/permissions/create_user`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            credentials: 'include',
                            body: JSON.stringify({
                                _csrf_token: window.CSRF_TOKEN,
                                username,
                                email,
                                fullname,
                                password,
                                role_id: roleId
                            })
                        });
                        if (!res.ok) {
                            const data = await res.json();
                            throw new Error(data.message || 'สร้างผู้ใช้ไม่สำเร็จ');
                        }
                        close();
                        usersCacheGlobal = await loadUsers();
                        renderUserTable();
                        if (window.notify) window.notify('สร้างผู้ใช้สำเร็จ', 'success');
                    } catch (err) {
                        if (window.notify) window.notify(err.message || 'สร้างผู้ใช้ไม่สำเร็จ', 'error');
                    }
                });

                return {
                    async open() {
                        // Fill role options
                        const roles = await getRoles();
                        const roleSelect = modal.querySelector('#add-user-role');
                        roleSelect.innerHTML = '<option value="">-- เลือก role --</option>' +
                            roles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
                        open();
                    },
                    close
                };
            })();

            // Add User Button click handler
            const addUserBtn = document.getElementById('add-user-btn');
            if (addUserBtn) {
                addUserBtn.addEventListener('click', () => {
                    addUserModal.open();
                });
            }

            // Rate Limits Management
            window.loadRateLimits = async function() {
                if (!CAN_MANAGE) {
                    if (window.notify) window.notify('Access denied. Manage permissions required.', 'error');
                    return;
                }

                try {
                    const response = await fetch(`${API_BASE_URL}/auth/rate-limits`, {
                        method: 'GET',
                        credentials: 'include'
                    });

                    if (!response.ok) {
                        throw new Error('Failed to load rate limits');
                    }

                    const data = await response.json();
                    renderRateLimits(data.rate_limits || []);
                } catch (error) {
                    console.error('Error loading rate limits:', error);
                    document.getElementById('rate-limits-container').innerHTML = `
                        <div class="text-center py-8 text-red-500">
                            <i class="ri-error-warning-line text-2xl"></i>
                            <p class="mt-2">Failed to load rate limits: ${error.message}</p>
                        </div>
                    `;
                }
            };

            function renderRateLimits(limits) {
                const container = document.getElementById('rate-limits-container');

                if (limits.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="ri-shield-check-line text-2xl"></i>
                            <p class="mt-2">No active rate limits found</p>
                        </div>
                    `;
                    return;
                }

                const tableHTML = `
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-y border-gray-100">
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Identifier</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Attempts</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">First Attempt</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Remaining Time</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                ${limits.map(limit => `
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-medium">${limit.identifier}</div>
                                            <div class="text-xs text-gray-500 font-mono">${limit.session_key}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full ${limit.attempts >= 5 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'}">
                                                ${limit.attempts}/5
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${limit.first_attempt}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            ${limit.remaining_time > 0 ? Math.ceil(limit.remaining_time / 60) + ' min' : 'Expired'}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <button onclick="clearRateLimit('${limit.session_key}', '${limit.identifier}')" class="text-red-600 hover:text-red-900 flex items-center gap-1 mx-auto">
                                                <i class="ri-unlock-line"></i> Unlock
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-sm text-gray-500 text-center">
                        Total: ${limits.length} rate limit(s) active
                    </div>
                `;

                container.innerHTML = tableHTML;
            }

            window.clearRateLimit = async function(sessionKey, identifier) {
                if (!CAN_MANAGE) {
                    if (window.notify) window.notify('Access denied. Manage permissions required.', 'error');
                    return;
                }

                showConfirmModal(
                    'Unlock User',
                    `Are you sure you want to unlock rate limit for <strong>${identifier}</strong>?<br><br>This will allow the user to attempt login again immediately.`,
                    async () => {
                        try {
                            const response = await fetch(`${API_BASE_URL}/auth/clear-rate-limit`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                credentials: 'include',
                                body: JSON.stringify({
                                    session_key: sessionKey
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                if (window.notify) window.notify(data.message, 'success');
                                loadRateLimits();
                            } else {
                                if (window.notify) window.notify(data.message || 'Failed to clear rate limit', 'error');
                            }
                        } catch (error) {
                            console.error('Error clearing rate limit:', error);
                            if (window.notify) window.notify('Error: ' + error.message, 'error');
                        }
                    }
                );
            };

            window.clearAllRateLimits = async function() {
                if (!CAN_MANAGE) {
                    if (window.notify) window.notify('Access denied. Manage permissions required.', 'error');
                    return;
                }

                showConfirmModal(
                    'Clear All Rate Limits',
                    'Are you sure you want to <strong>clear ALL rate limits</strong>?<br><br>This will allow all locked users to login again immediately. This action cannot be undone.',
                    async () => {
                        try {
                            const response = await fetch(`${API_BASE_URL}/auth/clear-all-rate-limits`, {
                                method: 'POST',
                                credentials: 'include'
                            });

                            const data = await response.json();

                            if (data.success) {
                                if (window.notify) window.notify(data.message, 'success');
                                loadRateLimits();
                            } else {
                                if (window.notify) window.notify(data.message || 'Failed to clear rate limits', 'error');
                            }
                        } catch (error) {
                            console.error('Error clearing all rate limits:', error);
                            if (window.notify) window.notify('Error: ' + error.message, 'error');
                        }
                    }
                );
            };

            // Load rate limits when tab is activated
            document.addEventListener('DOMContentLoaded', function() {
                const rateLimitsTab = document.querySelector('[data-tab="rate-limits"]');
                if (rateLimitsTab) {
                    rateLimitsTab.addEventListener('click', function() {
                        setTimeout(loadRateLimits, 100); // Small delay to ensure tab is visible
                    });
                }
            });

            // Modal Confirm Functions
            function showConfirmModal(title, message, onConfirm, onCancel = null) {
                // Create modal overlay
                const modalOverlay = document.createElement('div');
                modalOverlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 animate-fade-in';
                modalOverlay.id = 'confirm-modal';

                // Create modal content
                const modalContent = document.createElement('div');
                modalContent.className = 'bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all';
                modalContent.innerHTML = `
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="ri-alert-line text-yellow-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">${title}</h3>
                        </div>
                        <p class="text-gray-600 mb-6">${message}</p>
                        <div class="flex gap-3 justify-end">
                            <button onclick="closeConfirmModal(false)" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors font-medium">
                                Cancel
                            </button>
                            <button onclick="closeConfirmModal(true)" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium">
                                Confirm
                            </button>
                        </div>
                    </div>
                `;

                modalOverlay.appendChild(modalContent);
                document.body.appendChild(modalOverlay);

                // Store callbacks
                window.confirmModalCallbacks = {
                    onConfirm,
                    onCancel
                };

                // Close on overlay click
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) {
                        closeConfirmModal(false);
                    }
                });

                // Close on Escape key
                const handleEscape = function(e) {
                    if (e.key === 'Escape') {
                        closeConfirmModal(false);
                        document.removeEventListener('keydown', handleEscape);
                    }
                };
                document.addEventListener('keydown', handleEscape);
            }

            window.closeConfirmModal = function(confirmed) {
                const modal = document.getElementById('confirm-modal');
                if (modal) {
                    modal.remove();
                }

                // Execute callbacks
                if (window.confirmModalCallbacks) {
                    if (confirmed && window.confirmModalCallbacks.onConfirm) {
                        window.confirmModalCallbacks.onConfirm();
                    } else if (!confirmed && window.confirmModalCallbacks.onCancel) {
                        window.confirmModalCallbacks.onCancel();
                    }
                    window.confirmModalCallbacks = null;
                }
            };
        });

        // Sync Users Functionality
        // Inject Module ID from PHP
        window.currentModuleId = <?= isset($perm['id']) ? json_encode($perm['id']) : 'null' ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const syncBtn = document.getElementById('sync-users-btn');
            if (!syncBtn) return; // Only init if button exists (canManage is true)

            const syncIcon = document.getElementById('sync-icon');
            const syncBtnText = document.getElementById('sync-btn-text');
            const syncStatus = document.getElementById('sync-status');
            const syncProgressBar = document.getElementById('sync-progress-bar');
            const syncProgressPct = document.getElementById('sync-progress-pct');
            const syncProgressCnt = document.getElementById('sync-progress-cnt');
            const syncMessage = document.getElementById('sync-message');
            const syncResults = document.getElementById('sync-results');
            const syncInsertCount = document.getElementById('sync-insert-count');
            const syncUpdateCount = document.getElementById('sync-update-count');
            const syncUnchangedCount = document.getElementById('sync-unchanged-count');
            const syncErrorCount = document.getElementById('sync-error-count');
            const syncErrorBox = document.getElementById('sync-error-box');
            const syncLogSection = document.getElementById('sync-log-section');
            const viewSyncLogBtn = document.getElementById('view-sync-log-btn');
            const syncError = document.getElementById('sync-error');
            const syncErrorMessage = document.getElementById('sync-error-message');
            const lastSyncTime = document.getElementById('last-sync-time');

            let isSyncing = false;

            // Load last sync time from Server
            async function loadLastSyncTime() {
                try {
                    const savedLastSync = localStorage.getItem('lastSyncTime');
                    if (savedLastSync) lastSyncTime.textContent = savedLastSync; // Immediate feedback

                    const res = await fetch(window.location.pathname.replace(/\/index\.php$/, '') + '/sync_users.php?action=get_last_sync');
                    if (res.ok) {
                        const data = await res.json();
                        if (data.last_sync) {
                            lastSyncTime.textContent = data.last_sync;
                            localStorage.setItem('lastSyncTime', data.last_sync);
                        }
                    }
                } catch (e) {
                    console.error(e);
                }
            }
            loadLastSyncTime(); // Load on init

            // View Log button handler
            if (viewSyncLogBtn) {
                viewSyncLogBtn.addEventListener('click', function() {
                    const logUrl = window.location.pathname.replace(/\/index\.php$/, '') + '/sync_log.php';
                    window.open(logUrl, '_blank');
                });
            }

            function resetSyncUI() {
                syncProgressBar.style.width = '0%';
                syncProgressPct.textContent = '0%';
                syncProgressCnt.textContent = '0/0';
                syncMessage.textContent = '';
                syncResults.classList.add('hidden');
                syncError.classList.add('hidden');
                syncLogSection.classList.add('hidden');
                syncInsertCount.textContent = '0';
                syncUpdateCount.textContent = '0';
                syncUnchangedCount.textContent = '0';
                syncErrorCount.textContent = '0';
                syncErrorBox.classList.remove('bg-red-50');
                syncErrorBox.classList.add('bg-white');
            }

            function setSyncRunning(running) {
                isSyncing = running;
                syncBtn.disabled = running;
                if (running) {
                    syncBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    syncIcon.classList.add('animate-spin');
                    syncBtnText.textContent = 'กำลัง Sync...';
                } else {
                    syncBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    syncIcon.classList.remove('animate-spin');
                    syncBtnText.textContent = 'เริ่ม Sync';
                }
            }

            syncBtn.addEventListener('click', function() {
                if (isSyncing) return;

                resetSyncUI();
                setSyncRunning(true);
                syncStatus.textContent = 'กำลังเริ่ม...';

                // Get the sync endpoint URL - use the sync_users.php from the same directory
                const syncUrl = window.location.pathname.replace(/\/index\.php$/, '') + '/sync_users.php?action=start';

                const source = new EventSource(syncUrl);

                source.onmessage = function(event) {
                    try {
                        const d = JSON.parse(event.data);
                        const total = d.total || 0;
                        const done = d.done || 0;
                        const p = total > 0 ? Math.floor(done * 100 / total) : (d.status === 'running' ? 0 : 100);

                        syncProgressBar.style.width = p + '%';
                        syncProgressPct.textContent = p + '%';
                        syncProgressCnt.textContent = done + '/' + total;

                        // Update status text
                        if (d.status === 'running') {
                            syncStatus.textContent = 'กำลังดำเนินการ';
                            syncStatus.classList.remove('text-emerald-600', 'text-red-600');
                            syncStatus.classList.add('text-blue-600');
                        } else if (d.status === 'finished') {
                            syncStatus.textContent = 'สำเร็จ';
                            syncStatus.classList.remove('text-blue-600', 'text-red-600');
                            syncStatus.classList.add('text-emerald-600');
                        } else if (d.status === 'error') {
                            syncStatus.textContent = 'ข้อผิดพลาด';
                            syncStatus.classList.remove('text-blue-600', 'text-emerald-600');
                            syncStatus.classList.add('text-red-600');
                        }

                        syncMessage.textContent = d.message || '';

                        if (d.status === 'finished' || d.status === 'error') {
                            source.close();
                            setSyncRunning(false);

                            if (d.status === 'finished') {
                                syncResults.classList.remove('hidden');
                                syncInsertCount.textContent = (d.inserted_count || 0).toLocaleString();
                                syncUpdateCount.textContent = (d.updated_count || 0).toLocaleString();
                                syncUnchangedCount.textContent = (d.unchanged_count || 0).toLocaleString();

                                // Show error count
                                const errorCount = d.error_count || 0;
                                syncErrorCount.textContent = errorCount.toLocaleString();

                                // Highlight error box if there are errors
                                if (errorCount > 0) {
                                    syncErrorBox.classList.remove('bg-white', 'border-emerald-100');
                                    syncErrorBox.classList.add('bg-red-50', 'border-red-200');
                                    syncLogSection.classList.remove('hidden');
                                }

                                if (window.notify) {
                                    let msg = `Sync สำเร็จ! เพิ่ม ${d.inserted_count || 0} รายการ, อัปเดต ${d.updated_count || 0} รายการ`;
                                    if (errorCount > 0) {
                                        msg += `, ข้อผิดพลาด ${errorCount} รายการ`;
                                    }
                                    window.notify(msg, errorCount > 0 ? 'warning' : 'success');
                                }

                                // Save last sync time
                                const now = new Date();
                                const syncTimeStr = now.toLocaleDateString('th-TH', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric'
                                    }) +
                                    ' ' + now.toLocaleTimeString('th-TH', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                localStorage.setItem('lastSyncTime', syncTimeStr);
                                lastSyncTime.textContent = syncTimeStr;

                                // Refresh the users table in case we're on the users tab later
                                usersCacheGlobal = [];
                                loadUsers().then(data => {
                                    usersCacheGlobal = data;
                                }).catch(() => {});
                            } else {
                                syncMessage.textContent = ''; // Clear message to avoid duplicate
                                syncError.classList.remove('hidden');
                                syncErrorMessage.textContent = d.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ';

                                if (window.notify) {
                                    window.notify('Sync ล้มเหลว: ' + (d.message || 'Unknown error'), 'error');
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Failed to parse sync JSON:', e);
                        source.close();
                        setSyncRunning(false);
                        syncStatus.textContent = 'ข้อผิดพลาด';
                        syncStatus.classList.add('text-red-600');
                        syncError.classList.remove('hidden');
                        syncErrorMessage.textContent = 'ไม่สามารถอ่านข้อมูลจากเซิร์ฟเวอร์ได้';
                    }
                };

                source.onerror = function() {
                    console.error('EventSource failed for sync.');
                    source.close();
                    setSyncRunning(false);
                    syncStatus.textContent = 'ข้อผิดพลาด';
                    syncStatus.classList.remove('text-blue-600');
                    syncStatus.classList.add('text-red-600');
                    syncError.classList.remove('hidden');
                    syncErrorMessage.textContent = 'การเชื่อมต่อมีปัญหา กรุณาลองใหม่อีกครั้ง';

                    if (window.notify) {
                        window.notify('การเชื่อมต่อ Sync มีปัญหา', 'error');
                    }
                };
            });





            function escapeHtml(text) {
                if (!text) return '';
                return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            }

            function formatDateTime(dateStr) {
                if (!dateStr) return '-';
                try {
                    const d = new Date(dateStr.replace(/-/g, '/'));
                    return d.toLocaleString('th-TH', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (e) {
                    return dateStr;
                }
            }

            // --- Audit Log Logic ---
            window.loadAuditLogs = async function(page = 1) {
                const tbody = document.querySelector('#audit-log-table tbody');
                if (!tbody) return;

                try {
                    const res = await fetch(`${API_BASE_URL}/permissions/get_audit_logs?page=${page}&limit=20`, {
                        credentials: 'include'
                    });
                    if (!res.ok) throw new Error('Failed to load audit logs');
                    const data = await res.json();

                    renderAuditLogs(data.logs || []);
                    renderAuditLogPagination(data);
                } catch (e) {
                    console.error(e);
                    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center text-red-500">เกิดข้อผิดพลาด: ${e.message}</td></tr>`;
                }
            };

            function renderAuditLogs(logs) {
                const tbody = document.querySelector('#audit-log-table tbody');
                if (logs.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-20 text-center text-gray-400">ไม่พบประวัติการแก้ไข</td></tr>`;
                    return;
                }

                tbody.innerHTML = logs.map(log => `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-xs text-gray-600 whitespace-nowrap">
                            ${formatDateTime(log.performed_at)}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-[10px] font-bold">
                                    ${log.performed_by.charAt(0).toUpperCase()}
                                </div>
                                <span class="text-sm font-medium text-gray-900">${escapeHtml(log.performed_by)}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700 font-mono bg-gray-100 px-1.5 py-0.5 rounded">${escapeHtml(log.column_name)}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            ${formatValue(log.old_value)}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                            ${formatValue(log.new_value)}
                        </td>
                    </tr>
                `).join('');
            }

            function formatValue(val) {
                if (val === '1' || val === 'true') return '<span class="text-emerald-600 flex items-center gap-1"><i class="ri-checkbox-circle-line"></i> Enabled</span>';
                if (val === '0' || val === 'false') return '<span class="text-gray-400 flex items-center gap-1"><i class="ri-close-circle-line"></i> Disabled</span>';
                if (val === null || val === '') return '<span class="text-gray-300">empty</span>';
                return escapeHtml(val);
            }

            function renderAuditLogPagination(data) {
                const info = document.getElementById('audit-log-info');
                const nav = document.getElementById('audit-log-pagination');
                if (!info || !nav) return;

                info.textContent = `หน้า ${data.page} จาก ${data.total_pages} (ทั้งหมด ${data.total} รายการ)`;

                let html = '';
                if (data.page > 1) {
                    html += `
                        <button onclick="loadAuditLogs(${data.page - 1})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors">
                            <i class="ri-arrow-left-s-line"></i>
                        </button>`;
                }
                if (data.page < data.total_pages) {
                    html += `
                        <button onclick="loadAuditLogs(${data.page + 1})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition-colors">
                            <i class="ri-arrow-right-s-line"></i>
                        </button>`;
                }
                nav.innerHTML = html;
            }
        });
    </script>
</body>

</html>