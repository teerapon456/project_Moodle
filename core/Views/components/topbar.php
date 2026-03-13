<?php
// Shared Topbar Component
// Requires $pageTitle and $baseRoot to be set before calling.

if (!isset($baseRoot)) {
    $baseRoot = '/';
}
$baseRoot = rtrim($baseRoot, '/');

require_once dirname(__DIR__, 2) . '/Services/NotificationService.php';
$userId = $_SESSION['user']['id'] ?? 0;
$unreadCount = NotificationService::getUnreadCount($userId);
$notifications = NotificationService::getAll($userId, 1, 5);

// Get user profile info
$profilePic = $_SESSION['user']['profile_picture'] ?? null;
?>
<header class="bg-white px-6 h-16 flex items-center justify-between sticky top-0 z-5 shadow-sm" style="font-family: 'Kanit', sans-serif;">
    <div class="flex items-center gap-4">
        <button class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-lg md:hidden" id="menuToggle">
            <i class="ri-menu-line text-xl"></i>
        </button>

        <div class="flex items-center gap-4">
            <a href="<?= $baseRoot ?>/public/index.php" class="flex items-center">
                <img src="<?= $baseRoot ?>/public/assets/images/brand/inteqc-logo.png" alt="Logo" class="h-8">
            </a>
            <div class="h-6 w-[1px] bg-gray-200 mx-1 hidden sm:block"></div>
            <h1 class="text-lg font-medium text-gray-700 hidden sm:block">
                <?= htmlspecialchars($pageTitle ?? 'Dashboard') ?>
            </h1>
        </div>
    </div>

    <div class="flex items-center gap-3 sm:gap-5">
        <!-- Language Selector -->
        <div class="relative group">
            <button id="lang-toggle-btn" class="flex items-center gap-1 text-gray-500 text-sm hover:text-primary transition-colors cursor-pointer">
                <i class="ri-global-line text-lg"></i>
                <span id="lang-current-label" class="font-medium">TH</span>
                <i class="ri-arrow-down-s-fill text-xs"></i>
            </button>
            <div id="lang-dropdown" class="hidden absolute right-0 mt-3 w-40 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-50 animate-fade-in">
                <button class="lang-option w-full px-4 py-2.5 text-left text-sm hover:bg-gray-50 flex items-center gap-3 transition-colors" data-lang="th">
                    <span class="text-base">🇹🇭</span>
                    <span class="text-gray-700">ไทย (Thai)</span>
                </button>
                <button class="lang-option w-full px-4 py-2.5 text-left text-sm hover:bg-gray-50 flex items-center gap-3 transition-colors" data-lang="en">
                    <span class="text-base">🇬🇧</span>
                    <span class="text-gray-700">English</span>
                </button>
                <button class="lang-option w-full px-4 py-2.5 text-left text-sm hover:bg-gray-50 flex items-center gap-3 transition-colors" data-lang="mm">
                    <span class="text-base">🇲🇲</span>
                    <span class="text-gray-700">မြန်မာ (Myanmar)</span>
                </button>
            </div>
        </div>

        <!-- Notification Bell -->
        <div class="relative" id="notif-container">
            <button id="notification-bell" class="relative p-2 text-gray-500 hover:text-primary transition-colors cursor-pointer">
                <i class="ri-notification-3-line text-xl"></i>
                <?php if ($unreadCount > 0): ?>
                    <span id="notification-badge" class="absolute top-1 right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 border-2 border-white">
                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                    </span>
                <?php else: ?>
                    <span id="notification-badge" class="hidden absolute top-1 right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 border-2 border-white">0</span>
                <?php endif; ?>
            </button>

            <!-- Notification Dropdown -->
            <div id="notification-dropdown" class="absolute right-0 top-12 w-80 bg-white rounded-xl shadow-2xl border border-gray-200 hidden z-50 overflow-hidden animate-fade-in">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800">การแจ้งเตือน</h3>
                    <button id="mark-all-read-btn" class="text-xs text-primary hover:underline">อ่านทั้งหมด</button>
                </div>
                <div id="notification-list" class="max-h-80 overflow-y-auto">
                    <?php if (empty($notifications)): ?>
                        <div class="p-8 text-center text-gray-400">
                            <i class="ri-notification-off-line text-3xl mb-2"></i>
                            <p class="text-sm">ไม่มีการแจ้งเตือน</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif):
                            $iconMap = [
                                'info' => 'ri-information-line text-blue-500',
                                'success' => 'ri-check-line text-green-500',
                                'warning' => 'ri-alert-line text-orange-500',
                                'error' => 'ri-error-warning-line text-red-500'
                            ];
                            $icon = $iconMap[$notif['type']] ?? $iconMap['info'];
                            $targetUrl = '#';
                            if (!empty($notif['link'])) {
                                $targetUrl = rtrim($baseRoot, '/') . '/' . ltrim($notif['link'], '/');
                            }
                        ?>
                            <div class="notification-item block p-4 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 <?= $notif['is_read'] ? 'opacity-60' : '' ?>"
                                data-id="<?= $notif['id'] ?>"
                                onclick="handleNotificationClick('<?= $targetUrl ?>', event)"
                                style="cursor: pointer;">
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center flex-shrink-0">
                                        <i class="<?= $icon ?> text-base"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-800 text-sm truncate"><?= htmlspecialchars($notif['title']) ?></div>
                                        <div class="text-xs text-gray-500 line-clamp-2"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="text-[10px] text-gray-400 mt-1"><?= date('M j, g:i A', strtotime($notif['created_at'])) ?></div>
                                    </div>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mt-1.5"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Profile -->
        <div class="relative">
            <div id="profile-toggle" class="h-10 w-10 sm:h-11 sm:w-11 bg-gray-200 rounded-full flex items-center justify-center cursor-pointer hover:ring-2 hover:ring-primary/20 transition-all overflow-hidden border border-gray-100">
                <?php if ($profilePic): ?>
                    <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile" class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="ri-user-fill text-xl text-gray-500"></i>
                <?php endif; ?>
            </div>

            <!-- Simple Profile Dropdown -->
            <div id="profile-dropdown" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl border border-gray-100 py-2 z-50 animate-fade-in">
                <div class="px-4 py-2 border-b border-gray-50 mb-1">
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider">บัญชีผู้ใช้</p>
                    <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($_SESSION['user']['first_name_th'] ?? 'User') ?></p>
                </div>
                <a href="<?= $baseRoot ?>/Modules/HRServices/public/profile.php" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                    <i class="ri-user-line text-lg"></i>
                    โปรไฟล์ของฉัน
                </a>
                <a href="<?= $baseRoot ?>/public/logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                    <i class="ri-logout-box-r-line text-lg"></i>
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </div>
</header>

<script>
    if (!window.APP_BASE_PATH) {
        window.APP_BASE_PATH = '<?= $baseRoot ?>';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Language Dropdown
        const langToggle = document.getElementById('lang-toggle-btn');
        const langDropdown = document.getElementById('lang-dropdown');
        if (langToggle && langDropdown) {
            langToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                langDropdown.classList.toggle('hidden');
                document.getElementById('profile-dropdown')?.classList.add('hidden');
                document.getElementById('notification-dropdown')?.classList.add('hidden');
            });
        }

        // Toggle Profile Dropdown
        const profileToggle = document.getElementById('profile-toggle');
        const profileDropdown = document.getElementById('profile-dropdown');
        if (profileToggle && profileDropdown) {
            profileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
                langDropdown?.classList.add('hidden');
                document.getElementById('notification-dropdown')?.classList.add('hidden');
            });
        }

        // Close on outside click
        document.addEventListener('click', function() {
            langDropdown?.classList.add('hidden');
            profileDropdown?.classList.add('hidden');
        });
    });
</script>
<script src="<?= $baseRoot ?>/public/assets/js/header-notifications.js"></script>