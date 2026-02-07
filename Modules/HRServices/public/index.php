<?php
// HR Services page (Landing page after login)
// Migrated to Tailwind CSS

// Use optimized session configuration (fixes Antivirus slowdown)
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
// startOptimizedSession(); // Moved to AuthMiddleware

require_once '../../../core/Database/Database.php';
require_once '../../../core/Config/Env.php';
require_once '../../../core/Security/AuthMiddleware.php';

// Base Paths Calculation First (Needed for Auth Redirect)
$basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/Modules/HRServices/public$#i', '', $scriptDir);
}
if ($basePath === '') $basePath = '/';
$baseRoot = rtrim($basePath, '/');
$linkBase = ($baseRoot ? $baseRoot . '/' : '/');

// Check Login using Middleware
$user = AuthMiddleware::checkLogin($linkBase);
$isLoggedIn = !empty($user);

// Determine asset base: check if DocumentRoot points to public/ folder (Docker) or htdocs (XAMPP)
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot && is_dir($docRoot . '/assets')) {
    // Docker: DocumentRoot is public/, assets are at /assets/
    $assetBase = ($baseRoot ? $baseRoot : '') . '/';
} else {
    // XAMPP: DocumentRoot is htdocs, assets are at /public/assets/
    $assetBase = ($baseRoot ? $baseRoot : '') . '/public/';
}
// $linkBase calculated above

require_once __DIR__ . '/../../../core/Helpers/PermissionHelper.php';

function getPermissionModuleCode()

{
    try {
        $db = new Database();
        $conn = $db->getConnection();
        if ($conn) {
            $sql = "SELECT code FROM core_modules WHERE path LIKE '%modules/manage.php%' OR name LIKE '%permission%' OR code LIKE 'PERMISSION%' ORDER BY id ASC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $code = $stmt->fetchColumn();
            if ($code) return $code;
        }
    } catch (Exception $e) {
    }
    return Env::get('PERMISSION_MODULE_CODE', 'PERMISSION_MANAGEMENT');
}

function normalizePathForMatch($path)
{
    global $basePath;
    if (!$path) return '';
    $p = rtrim($path, '/');
    $p = preg_replace('#^https?://[^/]+#', '', $p);
    $baseClean = rtrim($basePath, '/');
    if ($baseClean !== '') $p = preg_replace('#^' . preg_quote($baseClean, '#') . '#', '', $p);
    $p = preg_replace('#/public$#', '', $p);
    $p = preg_replace('#/+#', '/', $p);
    if ($p === '') $p = '/';
    return $p;
}

function getRoleModulePermissions($roleId)
{
    try {
        $db = new Database();
        $conn = $db->getConnection();
        if (!$conn) return [];
        $sql = "SELECT cm.id, cm.code, cm.path, cm.name, cm.is_active, COALESCE(p.can_view, 0) as can_view, COALESCE(p.can_edit, 0) as can_edit, COALESCE(p.can_delete, 0) as can_delete, COALESCE(p.can_manage, 0) as can_manage FROM core_modules cm LEFT JOIN core_module_permissions p ON p.module_id = cm.id AND p.role_id = :role_id WHERE cm.is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

$currentModuleCode = 'HR_SERVICES';
$permModuleCode = getPermissionModuleCode();

if (!$isLoggedIn || empty($user['role_id'])) {
    // Don't destroy session, just redirect
    header('Location: ' . $linkBase . 'public/index.php?error=no_permission');
    exit;
}
if (isset($user['user_active']) && !$user['user_active']) {
    // Don't destroy session, just redirect
    header('Location: ' . $linkBase . 'public/index.php?error=role_inactive');
    exit;
}
if (isset($user['role_active']) && !$user['role_active']) {
    // Don't destroy session, just redirect
    header('Location: ' . $linkBase . 'public/index.php?error=role_inactive');
    exit;
}

$userPerms = userHasModuleAccess($currentModuleCode, (int)$user['role_id']);
if (empty($userPerms['can_view'])) {
    // Don't destroy session, just redirect
    header('Location: ' . $linkBase . 'public/index.php?error=no_permission');
    exit;
}

$permManage = userHasModuleAccess($permModuleCode, (int)$user['role_id']);
$hrNewsPerm = userHasModuleAccess('HR_NEWS', (int)$user['role_id']);
$roleModulePerms = getRoleModulePermissions((int)$user['role_id']);
$profilePic = $user['profile_picture'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My HR Services</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="<?= $assetBase ?>assets/css/tailwind.css">

    <script>
        window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
    </script>
    <!-- i18n Module -->
    <script src="<?= $assetBase ?>assets/js/i18n.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        .side-nav {
            transform: translateX(-100%);
            transition: transform 0.25s ease;
        }

        .side-nav.open {
            transform: translateX(0);
        }

        .service-modal {
            display: none;
        }

        .service-modal.show {
            display: flex;
        }

        .profile-modal-overlay {
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .profile-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .edit-mode .add-service-btn,
        .edit-mode .exit-edit-btn {
            display: inline-flex;
        }

        .edit-mode .card-actions {
            display: flex;
        }

        .edit-mode .service-card {
            pointer-events: none;
            border-style: dashed;
        }

        .edit-mode .card-actions,
        .edit-mode .card-actions * {
            pointer-events: auto;
        }

        .service-card.coming-soon {
            opacity: 0.5;
        }

        .service-card.maintenance {
            opacity: 0.4;
            filter: grayscale(0.2);
        }

        /* Delete Modal */
        .delete-modal {
            display: none;
        }

        .delete-modal.show {
            display: flex;
        }

        /* Thin scrollbar for icon grid */
        #icon-grid::-webkit-scrollbar {
            width: 4px;
        }

        #icon-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #icon-grid::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        #icon-grid::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">

    <!-- Use shared header component -->
    <?php include __DIR__ . '/../../../public/includes/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-8 py-10">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900" data-i18n="hrservices.title">My HR Services</h1>
            <?php if (!empty($userPerms['can_edit']) || !empty($userPerms['can_manage'])): ?>
                <div class="flex items-center gap-3">
                    <button class="hidden items-center gap-2 px-4 py-2.5 rounded-lg border border-dashed border-primary bg-red-50 text-primary font-bold cursor-pointer add-service-btn" id="add-service-btn">+ <span data-i18n="hrservices.add_service">Add Service</span></button>
                    <button class="hidden items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-400 bg-gray-50 text-gray-700 font-bold cursor-pointer exit-edit-btn" id="exit-edit-btn" data-i18n="hrservices.exit_edit">Exit Edit</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="relative mb-6 service-search-container">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="ri-search-line text-gray-400"></i>
            </span>
            <input type="text" id="service-search" class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm" placeholder="Search" data-i18n-placeholder="hrservices.search_placeholder">
        </div>
        <div id="modules-container"></div>
    </div>

    <!-- Toast -->
    <div id="notification" class="fixed right-5 bottom-5 bg-white px-6 py-4 rounded-lg shadow-lg hidden items-center gap-3 z-[400]"></div>

    <!-- Service Edit Modal -->
    <div class="service-modal fixed inset-0 bg-black/45 items-center justify-center z-[300] overflow-y-auto p-4" id="service-modal">
        <div class="bg-white w-full max-w-2xl rounded-xl shadow-xl p-6 flex flex-col gap-4 my-auto max-h-[90vh] overflow-y-auto">
            <h3 id="service-modal-title" class="font-semibold text-lg border-b pb-3">เพิ่มบริการ</h3>
            <input type="hidden" id="service-id">

            <!-- 2-column layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Left Column -->
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm text-gray-600" data-i18n="hrservices.label_core_module">Core Module</label>
                        <select id="service-module" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                            <option value="" data-i18n="hrservices.label_no_permission">(ไม่ผูกสิทธิ์)</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm text-gray-600"><span data-i18n="hrservices.label_name_en">ชื่อบริการ (English)</span> <span class="text-red-500">*</span></label>
                        <input type="text" id="service-name-en" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm" placeholder="e.g. Request for Uniform">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm text-gray-600" data-i18n="hrservices.label_name_th">ชื่อบริการ (ภาษาไทย)</label>
                        <input type="text" id="service-name-th" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm" placeholder="เช่น ขอเบิกยูนิฟอร์ม">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm text-gray-600" data-i18n="hrservices.label_name_mm">ชื่อบริการ (မြန်မာဘာသာ)</label>
                        <input type="text" id="service-name-mm" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm" placeholder="ဝန်ဆောင်မှု">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm text-gray-600" data-i18n="hrservices.label_category">หมวด</label>
                            <input type="text" id="service-category" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm" placeholder="Facilities">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm text-gray-600" data-i18n="hrservices.label_status">สถานะ</label>
                            <select id="service-status" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                                <option value="ready" data-i18n="hrservices.status_ready">พร้อมใช้</option>
                                <option value="soon" data-i18n="hrservices.status_soon">เร็วๆนี้</option>
                                <option value="maintenance" data-i18n="hrservices.status_maintenance">ปิดปรับปรุง</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm text-gray-600" data-i18n="hrservices.label_path">ลิงก์ (path)</label>
                        <input type="text" id="service-path" class="px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm" placeholder="/path หรือ #">
                    </div>
                </div>

                <!-- Right Column: Icon Picker -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm text-gray-600" data-i18n="hrservices.label_icon">ไอคอน</label>
                    <div class="border border-gray-200 rounded-lg overflow-hidden flex-1">
                        <div class="flex border-b border-gray-200">
                            <button type="button" class="icon-tab flex-1 px-3 py-2 text-sm font-medium bg-blue-50 text-blue-600 border-b-2 border-blue-500" data-tab="pick">
                                <i class="ri-apps-line mr-1"></i> เลือก
                            </button>
                            <button type="button" class="icon-tab flex-1 px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50" data-tab="upload">
                                <i class="ri-upload-line mr-1"></i> อัพโหลด
                            </button>
                        </div>
                        <div class="icon-tab-content p-2" id="icon-tab-pick">
                            <!-- Color Picker -->
                            <div class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-100">
                                <span class="text-xs text-gray-500">สี:</span>
                                <div class="flex gap-1 flex-wrap" id="icon-color-picker"></div>
                            </div>
                            <div class="grid grid-cols-6 gap-1.5 max-h-[280px] overflow-y-auto" id="icon-grid"></div>
                        </div>
                        <div class="icon-tab-content p-3 hidden" id="icon-tab-upload">
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-14 bg-gray-100 rounded-lg flex items-center justify-center border border-dashed border-gray-300" id="icon-upload-preview">
                                    <i class="ri-image-add-line text-2xl text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <input type="file" id="service-icon-file" accept="image/*" class="hidden">
                                    <button type="button" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200" onclick="document.getElementById('service-icon-file').click()">เลือกไฟล์</button>
                                    <p class="text-xs text-gray-400 mt-1">PNG, JPG, SVG (max 200KB)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="service-icon" value="">
                    <input type="hidden" id="service-icon-type" value="remix">
                    <input type="hidden" id="service-icon-color" value="#3B82F6">
                    <input type="hidden" id="service-custom-icon-path" value="">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-2.5 justify-end pt-3 border-t">
                <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium" id="service-cancel" data-i18n="common.cancel">ยกเลิก</button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium" id="service-save" data-i18n="common.save">บันทึก</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-modal fixed inset-0 bg-black/45 items-center justify-center z-[350]" id="delete-modal">
        <div class="bg-white w-[90%] max-w-sm rounded-xl shadow-xl p-6 flex flex-col items-center gap-4 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                <i class="ri-delete-bin-line text-3xl text-red-500"></i>
            </div>
            <h3 class="font-semibold text-lg text-gray-900" data-i18n="hrservices.confirm_delete">ยืนยันลบบริการ</h3>
            <p class="text-gray-600 text-sm" id="delete-modal-message">คุณต้องการลบบริการนี้หรือไม่?</p>
            <input type="hidden" id="delete-service-id">
            <div class="flex gap-3 w-full mt-2">
                <button class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold" id="delete-cancel" data-i18n="common.cancel">ยกเลิก</button>
                <button class="flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold" id="delete-confirm" data-i18n="common.delete">ลบ</button>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['login_success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showNotification('เข้าสู่ระบบสำเร็จ!', 'success');
                history.replaceState({}, document.title, location.pathname);
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'no_permission'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showNotification('คุณไม่มีสิทธิ์เข้าถึงบริการนี้', 'error');
                history.replaceState({}, document.title, location.pathname);
            });
        </script>
    <?php endif; ?>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php';

        function showNotification(msg, type = 'info') {
            const el = document.getElementById('notification');
            if (!el) return;
            el.textContent = msg;
            el.className = 'fixed right-5 bottom-5 bg-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 z-[400] border-l-4 ' + (type === 'error' ? 'border-red-500' : 'border-emerald-500');
            setTimeout(() => el.className = 'fixed right-5 bottom-5 bg-white px-6 py-4 rounded-lg shadow-lg hidden items-center gap-3 z-[400]', 3000);
        }

        // Module rendering
        const CAN_EDIT = <?= !empty($userPerms['can_edit']) ? 'true' : 'false' ?>;
        const CAN_DELETE = <?= !empty($userPerms['can_delete']) ? 'true' : 'false' ?>;
        const CAN_MANAGE = <?= !empty($userPerms['can_manage']) ? 'true' : 'false' ?>;
        const ACCESSIBLE_MODULES = <?= json_encode($roleModulePerms) ?>;

        const ICON_COLORS = {
            'ri-shirt-line': '#c0392b',
            'ri-hotel-bed-line': '#1e90ff',
            'ri-contacts-book-2-line': '#009688',
            'ri-macbook-line': '#f5a524',
            'ri-parking-box-line': '#7f8c8d',
            'ri-pencil-ruler-2-line': '#f39c12',
            'ri-tools-line': '#16a085',
            'ri-community-line': '#2ecc71',
            'ri-truck-line': '#3867d6',
            'ri-bank-card-line': '#2980b9',
            'ri-hammer-line': '#d35400',
            'ri-cpu-line': '#00bcd4',
            'ri-map-pin-user-line': '#c0392b',
            'ri-money-dollar-circle-line': '#27ae60',
            'ri-book-read-line': '#8e44ad',
            'ri-voiceprint-line': '#d81b60',
            'ri-team-line': '#2d98da',
            'ri-bar-chart-2-line': '#2980b9',
            'ri-profile-line': '#7f8c8d',
            'ri-car-line': '#10b981'
        };
        const getIconColor = (icon) => ICON_COLORS[icon] || '#3867d6';

        // Popular Remix Icons for picker (expanded list)
        const POPULAR_ICONS = [
            // Apps & UI
            'ri-apps-line', 'ri-apps-2-line', 'ri-dashboard-line', 'ri-layout-line', 'ri-window-line',
            'ri-menu-line', 'ri-more-line', 'ri-function-line', 'ri-command-line', 'ri-keyboard-line',
            // Navigation
            'ri-home-line', 'ri-home-4-line', 'ri-building-line', 'ri-building-4-line', 'ri-store-line',
            'ri-hotel-line', 'ri-hospital-line', 'ri-bank-line', 'ri-government-line', 'ri-community-line',
            // Users
            'ri-user-line', 'ri-user-3-line', 'ri-team-line', 'ri-group-line', 'ri-contacts-line',
            'ri-account-circle-line', 'ri-profile-line', 'ri-user-settings-line', 'ri-admin-line', 'ri-spy-line',
            // Communication
            'ri-mail-line', 'ri-mail-open-line', 'ri-chat-1-line', 'ri-message-line', 'ri-phone-line',
            'ri-customer-service-line', 'ri-service-line', 'ri-questionnaire-line', 'ri-feedback-line', 'ri-survey-line',
            // Files
            'ri-file-line', 'ri-file-list-line', 'ri-file-text-line', 'ri-file-copy-line', 'ri-folder-line',
            'ri-folder-open-line', 'ri-clipboard-line', 'ri-article-line', 'ri-book-line', 'ri-newspaper-line',
            // Time & Calendar
            'ri-calendar-line', 'ri-calendar-event-line', 'ri-calendar-check-line', 'ri-time-line', 'ri-timer-line',
            'ri-alarm-line', 'ri-history-line', 'ri-24-hours-line', 'ri-hourglass-line', 'ri-schedule-line',
            // Finance
            'ri-money-dollar-circle-line', 'ri-money-dollar-box-line', 'ri-wallet-line', 'ri-wallet-3-line', 'ri-bank-card-line',
            'ri-coupon-line', 'ri-gift-line', 'ri-vip-diamond-line', 'ri-coin-line', 'ri-hand-coin-line',
            // Transport
            'ri-car-line', 'ri-car-fill', 'ri-bus-line', 'ri-truck-line', 'ri-taxi-line',
            'ri-plane-line', 'ri-ship-line', 'ri-bike-line', 'ri-walk-line', 'ri-parking-line',
            // Items
            'ri-shirt-line', 't-shirt-line', 'ri-briefcase-line', 'ri-suitcase-line', 'ri-handbag-line',
            'ri-shopping-cart-line', 'ri-shopping-bag-line', 'ri-box-1-line', 'ri-archive-line', 'ri-inbox-line',
            // Settings
            'ri-settings-line', 'ri-settings-3-line', 'ri-tools-line', 'ri-hammer-line', 'ri-wrench-line',
            'ri-key-line', 'ri-lock-line', 'ri-lock-unlock-line', 'ri-shield-line', 'ri-shield-check-line',
            // Charts
            'ri-bar-chart-line', 'ri-bar-chart-box-line', 'ri-line-chart-line', 'ri-pie-chart-line', 'ri-bubble-chart-line',
            'ri-pulse-line', 'ri-stock-line', 'ri-funds-line', 'ri-increase-decrease-line', 'ri-exchange-line',
            // Media
            'ri-image-line', 'ri-gallery-line', 'ri-camera-line', 'ri-video-line', 'ri-film-line',
            'ri-music-line', 'ri-headphone-line', 'ri-mic-line', 'ri-speaker-line', 'ri-volume-up-line',
            // Nature
            'ri-sun-line', 'ri-moon-line', 'ri-cloud-line', 'ri-umbrella-line', 'ri-fire-line',
            'ri-leaf-line', 'ri-plant-line', 'ri-seedling-line', 'ri-flower-line', 'ri-tree-line',
            // Misc
            'ri-lightbulb-line', 'ri-flashlight-line', 'ri-flag-line', 'ri-bookmark-line', 'ri-star-line',
            'ri-heart-line', 'ri-trophy-line', 'ri-medal-line', 'ri-rocket-line', 'ri-compass-line'
        ];

        // Color palette for icons
        const ICON_COLOR_PALETTE = [
            '#3B82F6', '#0EA5E9', '#06B6D4', '#14B8A6', '#10B981', '#22C55E', // Blues & Greens
            '#84CC16', '#EAB308', '#F59E0B', '#F97316', '#EF4444', '#DC2626', // Yellows & Reds
            '#EC4899', '#D946EF', '#A855F7', '#8B5CF6', '#6366F1', '#6B7280' // Pinks & Purples & Gray
        ];

        let selectedIconColor = '#3B82F6';

        let applyEditControls = () => {};

        // Store all modules for client-side filtering
        window.ALL_MODULES = [];

        async function fetchModules() {
            const container = document.getElementById('modules-container');
            if (container) container.innerHTML = '<div class="text-center py-8 text-gray-500"><i class="ri-loader-4-line animate-spin text-2xl"></i></div>';

            try {
                const res = await fetch(`${API_BASE_URL}/modules/list`, {
                    credentials: 'include'
                });
                if (!res.ok) throw new Error('Failed');
                const modules = await res.json();
                window.ALL_MODULES = Array.isArray(modules) ? modules : [];
                renderModules(window.ALL_MODULES);
            } catch (err) {
                if (container) container.innerHTML = '<div class="text-red-500 py-4">โหลดบริการไม่สำเร็จ</div>';
            }
        }

        function renderModules(modules) {
            const container = document.getElementById('modules-container');
            if (!container) return;

            if (modules.length === 0) {
                container.innerHTML = '<div class="text-gray-500 py-4 text-center">ไม่พบบริการที่ค้นหา</div>';
                return;
            }

            const groups = modules.reduce((acc, item) => {
                const cat = item.category || 'Other';
                if (!acc[cat]) acc[cat] = [];
                acc[cat].push(item);
                return acc;
            }, {});

            container.innerHTML = Object.entries(groups).map(([category, items]) => {
                const cards = items.map(item => {
                    const status = item.status || 'ready';
                    const statusClass = status === 'soon' ? 'coming-soon' : status === 'maintenance' ? 'maintenance' : '';
                    const isReady = status === 'ready';
                    const href = item.path && item.path !== '' ? item.path : '#';
                    const icon = item.icon || 'ri-apps-line';
                    const color = item.icon_color || getIconColor(icon);
                    const modId = item.module_id ? parseInt(item.module_id, 10) : null;

                    // Get localized name
                    const currentLocale = I18n.getLocale();
                    let translations = {};
                    try {
                        translations = item.name_translations ? JSON.parse(item.name_translations) : {};
                    } catch (e) {
                        translations = {};
                    }
                    // Fallback: current locale -> en -> original name
                    const displayName = translations[currentLocale] || translations['en'] || item.name;
                    const translationsJson = JSON.stringify(translations).replace(/"/g, '&quot;');

                    const allowed = ACCESSIBLE_MODULES.some(m => {
                        if (!m) return false;
                        if (modId && m.id && parseInt(m.id, 10) === modId) return !!(m.can_view || m.can_edit || m.can_manage);
                        const modPath = normalizePathJs(m.path || '');
                        const cardPath = normalizePathJs(item.path || '');
                        if (!modPath || modPath === '/' || !cardPath) return false;
                        return !!(m.can_view || m.can_edit || m.can_manage) && (cardPath === modPath || cardPath.startsWith(modPath));
                    });

                    // Append mid (Module ID) to URL if available
                    let finalHref = href;
                    if (modId) {
                        const separator = finalHref.includes('?') ? '&' : '?';
                        finalHref += `${separator}mid=${modId}`;
                    }

                    // Allow admins/managers to see all cards in edit mode, but disable link if no permission
                    // Regular usage: disable link if not ready or not allowed
                    const disableLink = !isReady || !allowed;
                    const hrefAttr = disableLink ? 'javascript:void(0)' : finalHref;
                    const targetAttr = disableLink ? '' : 'target="_blank" rel="noopener"';

                    // Status badge with i18n
                    let statusBadge = '';
                    if (status === 'soon') {
                        statusBadge = `<span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 rounded-full">${I18n.t('hrservices.coming_soon')}</span>`;
                    } else if (status === 'maintenance') {
                        statusBadge = `<span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">${I18n.t('hrservices.maintenance')}</span>`;
                    }

                    return `
                        <a href="${hrefAttr}" ${targetAttr} class="service-card bg-white border border-gray-200 rounded-xl p-6 flex flex-col items-center gap-3 relative cursor-pointer transition-all hover:border-primary hover:shadow-md hover:-translate-y-0.5 no-underline text-inherit min-h-[120px] ${statusClass} ${disableLink ? 'cursor-not-allowed' : ''}" data-id="${item.id}" data-module-id="${modId ?? ''}" data-status="${status}" data-category="${category}" data-allowed="${allowed ? '1' : '0'}" data-translations="${translationsJson}">
                            ${statusBadge}
                            ${statusBadge}
                            <div class="w-12 h-12 flex items-center justify-center text-3xl">
                                ${(icon && (icon.includes('/') || icon.includes('.'))) 
                                    ? `<img src="${BASE_URL.replace(/\/$/, '')}/${icon.replace(/^\//, '')}" class="w-full h-full object-contain">`
                                    : `<i class="${icon}" style="color:${color}"></i>`
                                }
                            </div>
                            <div class="text-center"><div class="text-sm text-gray-700">${displayName}</div></div>
                            <div class="card-actions hidden absolute top-2 right-2 items-center gap-1">
                                ${CAN_EDIT ? '<button class="action-btn edit w-7 h-7 flex items-center justify-center border border-blue-500 text-blue-600 bg-white rounded-lg hover:bg-blue-50" title="Edit"><i class="ri-pencil-line text-sm"></i></button>' : ''}
                                ${CAN_DELETE ? '<button class="action-btn delete w-7 h-7 flex items-center justify-center border border-red-500 text-red-600 bg-white rounded-lg hover:bg-red-50" title="Delete"><i class="ri-delete-bin-line text-sm"></i></button>' : ''}
                            </div>
                        </a>
                    `;
                }).join('');
                return `
                    <div class="text-base font-semibold text-gray-800 mb-5 pl-3.5 border-l-4 border-primary">${category}</div>
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-5 mb-12">${cards}</div>
                `;
            }).join('');

            if (document.body.classList.contains('edit-mode')) applyEditControls();
        }

        // Initialize icon grid - Updated size to w-9 h-9
        function initIconGrid() {
            const grid = document.getElementById('icon-grid');
            if (!grid) return;

            const currentIcon = document.getElementById('service-icon').value;
            grid.innerHTML = POPULAR_ICONS.map(icon => `
                <button type="button" class="icon-item w-9 h-9 flex items-center justify-center rounded-lg hover:bg-blue-100 transition-all ${currentIcon === icon ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-50 hover:scale-110'}" data-icon="${icon}" title="${icon}">
                    <i class="${icon} text-xl" style="color: ${selectedIconColor}"></i>
                </button>
            `).join('');

            // Icon selection handler
            grid.querySelectorAll('.icon-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    grid.querySelectorAll('.icon-item').forEach(b => b.classList.remove('bg-blue-100', 'ring-2', 'ring-blue-500'));
                    btn.classList.add('bg-blue-100', 'ring-2', 'ring-blue-500');
                    document.getElementById('service-icon').value = btn.dataset.icon;
                    document.getElementById('service-icon-type').value = 'remix';
                    document.getElementById('service-custom-icon-path').value = '';
                    document.getElementById('service-icon-color').value = selectedIconColor;
                });
            });
        }

        // Initialize color picker
        function initColorPicker() {
            const colorContainer = document.getElementById('icon-color-picker');
            if (!colorContainer) return;

            colorContainer.innerHTML = ICON_COLOR_PALETTE.map(color => `
                <button type="button" class="color-item w-6 h-6 rounded-full border-2 hover:scale-110 transition-all ${selectedIconColor === color ? 'border-gray-800 ring-2 ring-offset-1 ring-gray-400' : 'border-white'}" data-color="${color}" style="background-color: ${color}"></button>
            `).join('');

            colorContainer.querySelectorAll('.color-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    selectedIconColor = btn.dataset.color;
                    colorContainer.querySelectorAll('.color-item').forEach(b => {
                        b.classList.remove('border-gray-800', 'ring-2', 'ring-offset-1', 'ring-gray-400');
                        b.classList.add('border-white');
                    });
                    btn.classList.add('border-gray-800', 'ring-2', 'ring-offset-1', 'ring-gray-400');
                    btn.classList.remove('border-white');
                    document.getElementById('service-icon-color').value = selectedIconColor;
                    // Re-render icons with new color
                    initIconGrid();
                });
            });
        }

        // Tab switching
        document.querySelectorAll('.icon-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.icon-tab').forEach(t => {
                    t.classList.remove('bg-blue-50', 'text-blue-600', 'border-b-2', 'border-blue-500');
                    t.classList.add('text-gray-500');
                });
                tab.classList.add('bg-blue-50', 'text-blue-600', 'border-b-2', 'border-blue-500');
                tab.classList.remove('text-gray-500');

                document.querySelectorAll('.icon-tab-content').forEach(c => c.classList.add('hidden'));
                document.getElementById(`icon-tab-${tab.dataset.tab}`).classList.remove('hidden');
            });
        });

        // File upload preview
        document.getElementById('service-icon-file')?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 200 * 1024) {
                alert('ไฟล์ใหญ่เกินไป (max 200KB)');
                e.target.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = (ev) => {
                const preview = document.getElementById('icon-upload-preview');
                preview.innerHTML = `<img src="${ev.target.result}" class="w-full h-full object-contain rounded-lg">`;
                document.getElementById('service-icon-type').value = 'custom';
                document.getElementById('service-icon').value = '';
            };
            reader.readAsDataURL(file);
        });

        const normalizePathJs = (p) => {
            if (!p) return '';
            let np = p.replace(/^https?:\/\/[^/]+/, '');
            if (BASE_PATH) {
                np = np.replace(BASE_PATH + '/public', '').replace(BASE_PATH, '');
            }
            np = np.replace(/^\.{1,2}\//, '/').replace(/\/+/g, '/').replace(/\/$/, '');
            return np || '/';
        };

        // Setup Search
        const searchInput = document.getElementById('service-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.toLowerCase().trim();
                searchTimeout = setTimeout(() => {
                    const filtered = window.ALL_MODULES.filter(m => {
                        // Check main name
                        if ((m.name || '').toLowerCase().includes(query)) return true;
                        // Check translations
                        let t = {};
                        try {
                            t = JSON.parse(m.name_translations || '{}');
                        } catch (_) {}
                        if ((t.th || '').toLowerCase().includes(query)) return true;
                        if ((t.en || '').toLowerCase().includes(query)) return true;
                        if ((t.mm || '').toLowerCase().includes(query)) return true;
                        return false;
                    });
                    renderModules(filtered);
                }, 300);
            });
        }

        // Click guard
        document.addEventListener('click', (e) => {
            const card = e.target.closest('.service-card');
            if (!card) return;
            const allowed = card.getAttribute('data-allowed') === '1';
            const status = card.getAttribute('data-status') || 'ready';
            const name = card.querySelector('.text-sm')?.innerText || 'บริการนี้';

            // Allow actions in edit mode
            if (document.body.classList.contains('edit-mode')) return;

            if (status !== 'ready') {
                e.preventDefault();
                e.stopPropagation();
                showNotification(status === 'maintenance' ? `${name} ปิดปรับปรุง` : `${name} ยังไม่เปิดใช้งาน`, 'error');
                return;
            }
            if (!allowed) {
                e.preventDefault();
                e.stopPropagation();
                showNotification(`ไม่มีสิทธิ์เข้าถึง ${name}`, 'error');
            }
        });

        // Edit mode
        const setEditMode = (on) => {
            if (!CAN_MANAGE) return;
            document.body.classList.toggle('edit-mode', on);
            if (on) {
                // Close side nav if open
                document.getElementById('side-nav')?.classList.remove('open');
                document.getElementById('side-nav-close')?.click();
                applyEditControls();
            }
            document.getElementById('exit-edit-btn').style.display = on ? 'inline-flex' : 'none';
            document.getElementById('add-service-btn').style.display = on ? 'inline-flex' : 'none';
        };

        applyEditControls = () => {
            if (!CAN_MANAGE) return;
            document.querySelectorAll('.service-card').forEach(card => {
                card.querySelectorAll('.action-btn').forEach(btn => {
                    btn.addEventListener('click', async (ev) => {
                        ev.preventDefault();
                        ev.stopPropagation();
                        const isEdit = btn.classList.contains('edit');
                        const isDelete = btn.classList.contains('delete');
                        const id = card.getAttribute('data-id');
                        const name = card.querySelector('.text-sm')?.innerText || '';
                        if (isDelete) {
                            // Show custom delete modal
                            document.getElementById('delete-service-id').value = id;
                            document.getElementById('delete-modal-message').textContent = `"${name}"`;
                            document.getElementById('delete-modal').classList.add('show');
                            I18n.apply();
                            return;
                        }
                        if (isEdit) promptServiceData(card);
                    });
                });
            });
        };

        const promptServiceData = (card) => {
            const modal = document.getElementById('service-modal');
            document.getElementById('service-id').value = card.getAttribute('data-id') || '';

            // Handle translations from data attribute
            const translations = card.getAttribute('data-translations');
            let nameEn = '',
                nameTh = '',
                nameMm = '';
            if (translations) {
                try {
                    const t = JSON.parse(translations);
                    nameEn = t.en || '';
                    nameTh = t.th || '';
                    nameMm = t.mm || '';
                } catch (e) {
                    nameEn = card.querySelector('.text-sm')?.innerText || '';
                    nameTh = '';
                    nameMm = '';
                }
            } else {
                nameEn = card.querySelector('.text-sm')?.innerText || '';
            }
            document.getElementById('service-name-en').value = nameEn;
            document.getElementById('service-name-th').value = nameTh;
            document.getElementById('service-name-mm').value = nameMm;

            document.getElementById('service-category').value = card.getAttribute('data-category') || '';
            document.getElementById('service-category').value = card.getAttribute('data-category') || '';
            const iconElement = card.querySelector('i');
            const iconClass = (iconElement?.className || '').split(' ').find(c => c.startsWith('ri-')) || '';

            // Extract color from inline style
            let currentColor = '#3B82F6'; // Default blue
            if (iconElement && iconElement.style.color) {
                // Convert RGB to Hex if necessary (browsers often return style.color as rgb(...))
                const rgb = iconElement.style.color;
                if (rgb.startsWith('#')) {
                    currentColor = rgb;
                } else if (rgb.startsWith('rgb')) {
                    const rgbPart = rgb.match(/\d+/g);
                    if (rgbPart && rgbPart.length >= 3) {
                        currentColor = "#" +
                            ((1 << 24) + (parseInt(rgbPart[0]) << 16) + (parseInt(rgbPart[1]) << 8) + parseInt(rgbPart[2])).toString(16).slice(1).toUpperCase();
                    }
                }
            }

            document.getElementById('service-icon').value = iconClass;
            document.getElementById('service-icon-color').value = currentColor;
            document.getElementById('service-icon-type').value = 'remix';
            document.getElementById('service-custom-icon-path').value = '';
            document.getElementById('service-status').value = card.getAttribute('data-status') || 'ready';
            document.getElementById('service-status').value = card.getAttribute('data-status') || 'ready';

            // Fix: Strip recursively appended parameters (e.g. ?mid=...) before showing in input
            let rawPath = card.getAttribute('href') || '#';
            if (rawPath !== '#') {
                // Remove existing mid param to prevent accumulation
                rawPath = rawPath.replace(/(\?|&)mid=\d+/g, '');
                // Clean up trailing ? or & if left behind
                if (rawPath.endsWith('?') || rawPath.endsWith('&')) {
                    rawPath = rawPath.slice(0, -1);
                }
            }
            document.getElementById('service-path').value = rawPath;
            document.getElementById('service-module').value = card.getAttribute('data-module-id') || '';

            // Reset upload preview
            document.getElementById('icon-upload-preview').innerHTML = '<i class="ri-image-add-line text-2xl text-gray-400"></i>';
            document.getElementById('service-icon-file').value = '';

            // Initialize icon grid with current selection
            initColorPicker();

            // Fix: Visually select the color in the picker
            selectedIconColor = document.getElementById('service-icon-color').value || '#3B82F6';
            const colorContainer = document.getElementById('icon-color-picker');
            if (colorContainer) {
                // Remove existing selection classes
                colorContainer.querySelectorAll('.color-item').forEach(b => {
                    b.classList.remove('border-gray-800', 'ring-2', 'ring-offset-1', 'ring-gray-400');
                    b.classList.add('border-white');
                });
                // Find and highlight the matching color button
                const activeColorBtn = colorContainer.querySelector(`.color-item[data-color="${selectedIconColor}"]`);
                if (activeColorBtn) {
                    activeColorBtn.classList.add('border-gray-800', 'ring-2', 'ring-offset-1', 'ring-gray-400');
                    activeColorBtn.classList.remove('border-white');
                }
            }

            initIconGrid();

            // Use i18n for modal title
            const isEdit = !!card.getAttribute('data-id');
            document.getElementById('service-modal-title').textContent = I18n.t(isEdit ? 'hrservices.modal_edit_title' : 'hrservices.modal_add_title');
            modal.classList.add('show');
            // Apply translations to modal elements
            I18n.apply();
        };

        const saveService = async (payload) => {
            const res = await fetch(`${API_BASE_URL}/modules/${payload.delete ? 'delete_service' : 'save_service'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(payload)
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
        };

        document.getElementById('manage-service-link')?.addEventListener('click', (e) => {
            if (location.pathname.toLowerCase().includes('modules/hrservices')) {
                e.preventDefault();
                setEditMode(!document.body.classList.contains('edit-mode'));
            }
        });
        document.getElementById('exit-edit-btn')?.addEventListener('click', () => setEditMode(false));
        document.getElementById('add-service-btn')?.addEventListener('click', () => {
            if (!CAN_MANAGE) return;
            const fake = document.createElement('div');
            fake.setAttribute('data-id', '');
            fake.setAttribute('data-category', '');
            fake.setAttribute('data-status', 'ready');
            fake.setAttribute('href', '#');
            fake.innerHTML = '<div class="text-sm"></div><i class="ri-apps-line"></i>';
            promptServiceData(fake);
        });

        const modal = document.getElementById('service-modal');
        document.getElementById('service-save')?.addEventListener('click', async () => {
            const id = document.getElementById('service-id').value;
            const moduleId = document.getElementById('service-module').value;
            const nameEn = document.getElementById('service-name-en').value.trim();
            const nameTh = document.getElementById('service-name-th').value.trim();
            const nameMm = document.getElementById('service-name-mm').value.trim();
            const category = document.getElementById('service-category').value.trim();
            const icon = document.getElementById('service-icon').value.trim();
            const iconColor = document.getElementById('service-icon-color').value || '#3B82F6';
            const status = document.getElementById('service-status').value;
            const path = document.getElementById('service-path').value.trim() || '#';

            // English name is required
            if (!nameEn || !category) {
                alert('กรอกชื่อ (English) และหมวดด้วย');
                return;
            }

            // Use EN as fallback if other languages are empty
            const name_translations = {
                en: nameEn,
                th: nameTh || nameEn,
                mm: nameMm || nameEn
            };

            try {
                await saveService({
                    id: id ? parseInt(id) : undefined,
                    module_id: moduleId ? parseInt(moduleId) : null,
                    name: nameEn, // Use EN as primary name
                    name_translations,
                    category,
                    icon,
                    custom_icon: customIconBase64,
                    icon_color: iconColor,
                    status,
                    path
                });
                modal.classList.remove('show');
                fetchModules();
            } catch (err) {
                alert(err.message || 'บันทึกไม่สำเร็จ');
            }
        });
        document.getElementById('service-cancel')?.addEventListener('click', () => modal.classList.remove('show'));
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('show');
        });

        if (new URLSearchParams(location.search).get('edit') === '1') setEditMode(true);

        // Load core modules for select
        (async () => {
            try {
                const res = await fetch(`${API_BASE_URL}/permissions/core_modules`, {
                    credentials: 'include'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data)) {
                    const sel = document.getElementById('service-module');
                    if (sel) sel.innerHTML = '<option value="">(ไม่ผูกสิทธิ์)</option>' + data.filter(m => m.is_active == 1).map(m => `<option value="${m.id}">${m.name}</option>`).join('');
                }
            } catch (_) {}
        })();

        // File Input Handler
        let customIconBase64 = null;
        document.getElementById('service-icon-file')?.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    customIconBase64 = ev.target.result;
                    // Preview (optional: update input to show 'File Selected')
                    document.getElementById('service-icon').value = '[Custom Image Selected]';
                };
                reader.readAsDataURL(file);
            }
        });

        // Reset base64 when modal opens or closes
        const resetFileUpload = () => {
            const fileInput = document.getElementById('service-icon-file');
            if (fileInput) fileInput.value = '';
            customIconBase64 = null;
        };
        // Hook into existing cancel/close events if possible or just rely on 'change' overwriting it.

        // Delete Modal Handlers
        const deleteModal = document.getElementById('delete-modal');
        document.getElementById('delete-cancel')?.addEventListener('click', () => {
            deleteModal.classList.remove('show');
        });
        deleteModal?.addEventListener('click', (e) => {
            if (e.target === deleteModal) deleteModal.classList.remove('show');
        });
        document.getElementById('delete-confirm')?.addEventListener('click', async () => {
            const id = document.getElementById('delete-service-id').value;
            if (!id) return;
            try {
                await saveService({
                    id: parseInt(id),
                    delete: true
                });
                deleteModal.classList.remove('show');
                showNotification(I18n.t('hrservices.delete_success'), 'success');
                fetchModules();
            } catch (err) {
                showNotification(err.message || I18n.t('common.error'), 'error');
            }
        });

        // Initial load
        fetchModules();

        // Listen for language changes from header
        window.addEventListener('language-changed', () => {
            fetchModules();
        });
    </script>
</body>

</html>