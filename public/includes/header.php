<?php

/**
 * Shared Header/Navbar Component
 * Include this file in all module pages for consistent navigation
 * 
 * Required variables before including:
 * - $basePath, $baseRoot, $assetBase, $linkBase (path helpers)
 * - $user (session user)
 * - $userPerms (HR_SERVICES permissions)
 * - $hrNewsPerm (HR_NEWS permissions)
 * - $permManage (PERMISSION_MANAGEMENT permissions)
 */

$profilePic = $user['profile_picture'] ?? null;
?>

<!-- Tailwind CSS (Local) -->
<link rel="stylesheet" href="<?= $assetBase ?>assets/css/tailwind.css">

<script>
    // Global Config
    // Use PHP's $basePath directly.
    // If running in Docker (root public), basePath is empty string ''.
    // If running in XAMPP, basePath is '/MyHR-Portal-Dev'.
    window.APP_BASE_PATH = '<?= $basePath ?>';
    window.BASE_URL = window.APP_BASE_PATH; // Alias for compatibility
    window.ASSET_BASE = '<?= $assetBase ?>'; // Add explicit asset base from PHP logic

    // User Info for AI Copilot
    window.USER = {
        id: '<?= htmlspecialchars($user['id'] ?? '') ?>',
        name: '<?= htmlspecialchars($user['fullname'] ?? $user['username'] ?? 'Guest') ?>',
        department: '<?= htmlspecialchars($user['Level3Name'] ?? $user['department'] ?? '') ?>',
        permissions: {
            hrServices: <?= json_encode($userPerms ?? []) ?>,
            hrNews: <?= json_encode($hrNewsPerm ?? []) ?>,
            permissionManagement: <?= json_encode($permManage ?? []) ?>
        }
    };

    // CSRF Token
    <?php
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    ?>
    window.CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';
</script>
<script src="<?= $assetBase ?>assets/js/csrf.js"></script>

<style>
    /* Shared navbar styles */
    .side-nav {
        transform: translateX(-100%);
        transition: transform 0.25s ease;
    }

    .side-nav.open {
        transform: translateX(0);
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
</style>

<!-- Header -->
<header class="bg-white h-16 flex items-center justify-between px-8 shadow-sm sticky top-0 z-50" style="font-family:'Kanit',sans-serif;">
    <div class="flex items-center gap-5">
        <i class="ri-menu-line text-2xl cursor-pointer text-gray-700 hover:text-primary" id="nav-toggle" style="color:#A21D21;"></i>
        <a href="<?= $linkBase ?>Modules/HRServices/public/index.php" class="flex items-center">
            <img src="<?= $assetBase ?>assets/images/brand/inteqc-logo.png" alt="Logo" class="h-9">
        </a>
    </div>
    <div class="flex items-center gap-5">
        <!-- Language Dropdown -->
        <div class="relative">
            <button id="lang-toggle-btn" class="flex items-center gap-1 text-gray-500 text-sm cursor-pointer hover:text-primary">
                <i class="ri-global-line"></i>
                <span id="lang-current-label">TH</span>
                <i class="ri-arrow-down-s-fill"></i>
            </button>
            <div id="lang-dropdown" class="hidden absolute right-0 mt-2 w-36 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                <button class="lang-option w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2" data-lang="th">
                    <span class="text-lg">🇹🇭</span> ไทย
                </button>
                <button class="lang-option w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2" data-lang="en">
                    <span class="text-lg">🇬🇧</span> English
                </button>
                <button class="lang-option w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2" data-lang="mm">
                    <span class="text-lg">🇲🇲</span> မြန်မာ
                </button>
            </div>
        </div>

        <!-- AI Copilot CSS -->
        <link rel="stylesheet" href="<?= $assetBase ?>assets/css/copilot.css">

        <!-- Notification Bell -->
        <div class="relative" id="notification-container">
            <button id="notification-bell" class="relative p-2 text-gray-500 hover:text-primary cursor-pointer transition-colors">
                <i class="ri-notification-3-line text-xl"></i>
                <span id="notification-badge" class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center px-1">0</span>
            </button>
            <!-- Notification Dropdown -->
            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 max-h-[400px] bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-50">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <h4 class="font-semibold text-gray-900">การแจ้งเตือน</h4>
                    <button id="mark-all-read-btn" class="text-xs text-primary hover:underline">อ่านทั้งหมด</button>
                </div>
                <div id="notification-list" class="max-h-[320px] overflow-y-auto">
                    <div class="text-center py-8 text-gray-400 text-sm">ไม่มีการแจ้งเตือน</div>
                </div>
            </div>
        </div>

        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary" onclick="toggleProfileModal()" style="--tw-ring-color:#A21D21;">
            <?php if ($profilePic): ?>
                <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" class="w-full h-full object-cover rounded-full">
            <?php else: ?>
                <i class="ri-user-fill text-xl text-gray-600"></i>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Profile Modal -->
<div id="profile-modal" class="profile-modal-overlay fixed inset-0 bg-black/35 backdrop-blur-sm z-[200] flex items-start justify-end pt-[70px] pr-5" onclick="closeProfileModal(event)">
    <div class="bg-white w-80 rounded-xl shadow-lg border border-gray-200 overflow-hidden transform -translate-y-2.5 transition-transform" style="font-family:'Kanit',sans-serif;">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Profile</h3>
            <button class="text-gray-400 hover:text-gray-600 text-xl" onclick="toggleProfileModal()"><i class="ri-close-line"></i></button>
        </div>
        <div class="p-6 flex flex-col items-center text-center gap-4">
            <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center overflow-hidden">
                <?php if ($profilePic): ?>
                    <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="ri-user-fill text-3xl text-gray-500"></i>
                <?php endif; ?>
            </div>
            <div>
                <div class="font-semibold text-gray-900"><?= htmlspecialchars($user['fullname'] ?? $user['username']) ?></div>
                <div class="text-sm text-gray-500"><?= htmlspecialchars($user['Level3Name'] ?? $user['department'] ?? '-') ?></div>
                <div class="text-sm text-gray-400"><?= htmlspecialchars($user['email'] ?? '-') ?></div>
            </div>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-100">
            <button class="w-full py-2.5 text-white rounded-lg font-medium flex items-center justify-center gap-2" style="background:#A21D21;" onclick="logout()">
                <i class="ri-logout-box-r-line"></i> Log Out
            </button>
        </div>
    </div>
</div>

<!-- Side Nav Overlay -->
<div class="fixed inset-0 bg-black/35 backdrop-blur-sm z-[110] opacity-0 invisible transition-all" id="side-nav-overlay"></div>

<!-- Side Nav -->
<aside class="side-nav fixed top-0 left-0 h-screen w-80 bg-white shadow-xl z-[120] flex flex-col p-5" id="side-nav" style="font-family:'Kanit',sans-serif;">
    <div class="flex items-center justify-between font-bold text-gray-900 mb-4">
        <span>Menu</span>
        <i class="ri-close-line text-xl text-gray-500 cursor-pointer hover:text-primary" id="side-nav-close" style="--hover-color:#A21D21;"></i>
    </div>
    <div class="text-xs uppercase tracking-wide text-gray-400 mt-2 mb-1">Navigation</div>
    <a href="<?= $linkBase ?>Modules/HRServices/public/index.php" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-inherit no-underline">
        <i class="ri-home-5-line text-xl mt-0.5" style="color:#A21D21;"></i>
        <div>
            <div class="font-bold text-sm text-gray-900">Dashboard</div>
            <div class="text-xs text-gray-500">กลับสู่หน้าหลัก</div>
        </div>
    </a>
    <?php
    $canShowServiceSettings = !empty($userPerms['can_edit']) ||
        (!empty($hrNewsPerm['can_manage']) || !empty($hrNewsPerm['can_edit'])) ||
        !empty($permManage['can_view']) ||
        !empty($activityPerm['can_view']) ||
        !empty($emailLogPerm['can_view']) ||
        !empty($scheduledPerm['can_view']);
    ?>

    <?php if ($canShowServiceSettings): ?>
        <div class="text-xs uppercase tracking-wide text-gray-400 mt-4 mb-1">Service Settings</div>
        <?php if (!empty($userPerms['can_edit'])): ?>
            <a href="<?= $linkBase ?>Modules/HRServices/public/index.php?edit=1" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-inherit no-underline">
                <i class="ri-database-2-line text-xl mt-0.5" style="color:#A21D21;"></i>
                <div>
                    <div class="font-bold text-sm text-gray-900">จัดการ Service</div>
                    <div class="text-xs text-gray-500">สลับเข้า Edit Mode</div>
                </div>
            </a>
        <?php endif; ?>
        <?php if (!empty($hrNewsPerm['can_manage']) || !empty($hrNewsPerm['can_edit'])): ?>
            <a href="<?= $linkBase ?>Modules/HRNews/public/index.php" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-inherit no-underline">
                <i class="ri-newspaper-line text-xl mt-0.5" style="color:#A21D21;"></i>
                <div>
                    <div class="font-bold text-sm text-gray-900">จัดการ HR News</div>
                    <div class="text-xs text-gray-500">โพสต์/ปักหมุด ข่าวสาร</div>
                </div>
            </a>
        <?php endif; ?>
        <?php if (!empty($permManage['can_view'])): ?>
            <a href="<?= $linkBase ?>Modules/PermissionManagement/public/index.php" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-inherit no-underline">
                <i class="ri-shield-user-line text-xl mt-0.5" style="color:#A21D21;"></i>
                <div>
                    <div class="font-bold text-sm text-gray-900">จัดการสิทธิ์</div>
                    <div class="text-xs text-gray-500">ตั้งสิทธิ์การเข้าถึง</div>
                </div>
            </a>
        <?php endif; ?>
        <?php if (!empty($activityPerm['can_view'])): ?>
            <a href="<?= $linkBase ?>Modules/ActivityLog/public/index.php" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-inherit no-underline">
                <i class="ri-bar-chart-line text-xl mt-0.5" style="color:#A21D21;"></i>
                <div>
                    <div class="font-bold text-sm text-gray-900">Activity Dashboard</div>
                    <div class="text-xs text-gray-500">ดูประวัติการใช้งาน</div>
                </div>
            </a>
        <?php endif; ?>
        <?php if (!empty($emailLogPerm['can_view'])): ?>
            <a href="<?= $linkBase ?>Modules/EmailLogs/public/index.php" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-inherit no-underline">
                <i class="ri-mail-send-line text-xl mt-0.5" style="color:#A21D21;"></i>
                <div>
                    <div class="font-bold text-sm text-gray-900">Email Logs</div>
                    <div class="text-xs text-gray-500">ประวัติการส่งอีเมล</div>
                </div>
            </a>
        <?php endif; ?>
        <?php if (!empty($scheduledPerm['can_view'])): ?>
            <a href="<?= $linkBase ?>Modules/ScheduledReports/public/index.php" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 text-inherit no-underline">
                <i class="ri-calendar-check-line text-xl mt-0.5" style="color:#A21D21;"></i>
                <div>
                    <div class="font-bold text-sm text-gray-900">Scheduled Reports</div>
                    <div class="text-xs text-gray-500">รายงานอัตโนมัติ</div>
                </div>
            </a>
        <?php endif; ?>
    <?php endif; ?>

</aside>

<script>
    // Profile modal
    function toggleProfileModal() {
        document.getElementById('profile-modal').classList.toggle('show');
    }

    function closeProfileModal(e) {
        if (e.target.id === 'profile-modal') toggleProfileModal();
    }

    // Side nav
    (function initSideNav() {
        const toggle = document.getElementById('nav-toggle');
        const sideNav = document.getElementById('side-nav');
        const overlay = document.getElementById('side-nav-overlay');
        const closeBtn = document.getElementById('side-nav-close');
        if (!toggle || !sideNav || !overlay || !closeBtn) return;

        const openNav = () => {
            sideNav.classList.add('open');
            overlay.classList.add('opacity-100', 'visible');
            overlay.classList.remove('opacity-0', 'invisible');
        };
        const closeNav = () => {
            sideNav.classList.remove('open');
            overlay.classList.remove('opacity-100', 'visible');
            overlay.classList.add('opacity-0', 'invisible');
        };

        toggle.addEventListener('click', openNav);
        closeBtn.addEventListener('click', closeNav);
        overlay.addEventListener('click', closeNav);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeNav();
        });
    })();

    // Logout function
    async function logout() {
        const basePath = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        try {
            const apiUrl = basePath ? `${basePath}/routes.php/auth/logout` : '/routes.php/auth/logout';
            await fetch(apiUrl, {
                method: 'POST',
                credentials: 'include'
            });
        } catch (e) {
            console.error('Logout error', e);
        } finally {
            // Redirect to base path (root) for a cleaner URL
            if (basePath === '' || basePath === '/') {
                window.location.href = '/';
            } else {
                // If in subfolder, redirect to that folder
                window.location.href = basePath.endsWith('/') ? basePath : `${basePath}/`;
            }
        }
    }

    // Language dropdown handlers
    (function initLangDropdown() {
        const langBtn = document.getElementById('lang-toggle-btn');
        const langDropdown = document.getElementById('lang-dropdown');
        const langLabel = document.getElementById('lang-current-label');
        if (!langBtn || !langDropdown) return;

        // Update label to current locale on load
        const savedLocale = localStorage.getItem('i18n_locale') || 'th';
        if (langLabel) langLabel.textContent = savedLocale.toUpperCase();

        // Toggle dropdown
        langBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            langDropdown.classList.toggle('hidden');
        });

        // Close when clicking outside
        document.addEventListener('click', () => langDropdown.classList.add('hidden'));

        // Language selection
        langDropdown.querySelectorAll('.lang-option').forEach(option => {
            option.addEventListener('click', async (e) => {
                e.stopPropagation();
                const lang = option.dataset.lang;

                // Update localStorage
                localStorage.setItem('i18n_locale', lang);

                // Update label
                if (langLabel) langLabel.textContent = lang.toUpperCase();

                // Hide dropdown
                langDropdown.classList.add('hidden');

                // If I18n is available, use it to apply translations
                if (typeof I18n !== 'undefined') {
                    await I18n.setLocale(lang);
                    I18n.apply();
                } else {
                    location.reload();
                }
            });
        });

        // Initialize i18n if available
        if (typeof I18n !== 'undefined') {
            const savedLocale = localStorage.getItem('i18n_locale') || 'th';
            const basePath = (window.APP_BASE_PATH || '').replace(/\/$/, '');
            I18n.init(savedLocale, basePath).then(() => {
                if (langLabel) langLabel.textContent = savedLocale.toUpperCase();
            });
        }
    })();

    // Notification system
    // Notification system (SSE)
</script>
<script src="<?= $assetBase ?>assets/js/header-notifications.js"></script>

<!-- AI Copilot JS -->
<script src="<?= $assetBase ?>assets/js/copilot.js"></script>