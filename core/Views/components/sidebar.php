<?php

/**
 * Shared Sidebar Component
 *
 * Expected variables in scope:
 * @var array $sidebarConfig Configuration array for the sidebar
 * @var string $page The current page identifier for active state highlighting
 * @var array $parentPageMap (Optional) Mapping of sub-pages to parent sidebar items
 *
 * Expected $sidebarConfig structure:
 * [
 *     'app_key' => 'carbooking', // Used for localStorage toggle state key
 *     'title' => 'ระบบจองรถ', // Sidebar title
 *     'icon' => 'ri-car-line', // Sidebar header icon
 *     'home_link' => '/Modules/HRServices/public/index.php', // Footer link URL
 *     'home_text' => 'กลับสู่ระบบหลัก', // Footer link text
 *     'user' => [
 *         'initial' => 'ป', // Avatar initial
 *         'name' => 'นายปรเมศวร์ บัวศรี', // Full name
 *         'role' => 'ผู้จัดการ' // Role text
 *     ],
 *     'nav_groups' => [
 *         [
 *             'title' => null, // Section title (optional)
 *             'items' => [
 *                 [
 *                     'id' => 'dashboard', // Used to match with $page or $parentPageMap
 *                     'link' => '?page=dashboard', // URL
 *                     'icon' => 'ri-home-4-line', // Remix Icon class
 *                     'text' => 'หน้าหลัก' // Link text
 *                 ]
 *             ]
 *         ]
 *     ]
 * ]
 */

// Determine the active sidebar item
$sidebarPage = $page ?? '';
if (isset($parentPageMap) && is_array($parentPageMap) && isset($parentPageMap[$sidebarPage])) {
    $sidebarPage = $parentPageMap[$sidebarPage];
}
?>

<style>
    /* Shared Sidebar CSS */
    .sidebar {
        transition: all 0.3s ease;
        overflow-y: hidden;
        font-family: 'Kanit', sans-serif;
    }

    .sidebar.no-transition {
        transition: none !important;
    }

    .sidebar.collapsed {
        width: 70px !important;
    }

    .sidebar.collapsed .sidebar-text {
        display: none !important;
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

    .main-wrapper.no-transition {
        transition: none !important;
    }

    .main-wrapper.expanded {
        margin-left: 70px;
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
            z-index: 20;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .main-wrapper {
            margin-left: 0 !important;
        }
    }

    /* Custom Scrollbar */
    .sidebar nav::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar nav::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 10px;
    }

    .sidebar nav:hover::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.15);
    }

    .sidebar nav {
        scrollbar-width: thin;
        scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
    }
</style>

<!-- Instant sidebar state restore (before render) -->
<script>
    (function() {
        const KEY = '<?= htmlspecialchars($sidebarConfig['app_key'] ?? 'default') ?>_sidebar_collapsed';
        if (window.innerWidth > 768 && localStorage.getItem(KEY) === 'true') {
            document.write('<style id="sidebar-instant-style">#sidebar{width:70px !important;overflow:hidden}#sidebar .sidebar-text,#sidebar .nav-section,#sidebar .user-details,#sidebar .logo span{display:none}#sidebar nav{overflow:hidden;padding-left:0;padding-right:0}#sidebar nav ul{display:flex;flex-direction:column;align-items:center}#sidebar nav a{width:44px;height:44px;padding:0;justify-content:center;border-radius:8px}#sidebar nav a i{margin:0}#mainContent{margin-left:70px}</style>');
        }
    })();
</script>

<!-- Mobile Overlay -->
<div id="sidebar-overlay" onclick="closeSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity backdrop-blur-sm"></div>

<!-- Sidebar -->
<aside class="sidebar no-transition fixed top-0 left-0 h-full w-[260px] bg-white border-r border-gray-200 flex flex-col z-5" id="sidebar">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 h-16">
        <div class="logo flex items-center gap-3 text-primary font-semibold text-xl">
            <i class="<?= htmlspecialchars($sidebarConfig['icon'] ?? 'ri-apps-line') ?> text-2xl sidebar-text"></i>
            <span class="sidebar-text"><?= htmlspecialchars($sidebarConfig['title'] ?? 'Menu') ?></span>
        </div>
        <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-lg" id="sidebarToggle">
            <i class="ri-menu-line text-lg"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <?php foreach ($sidebarConfig['nav_groups'] as $index => $group): ?>
            <?php if ($index > 0): ?>
                <div class="my-2"></div>
            <?php endif; ?>

            <ul class="space-y-1">
                <?php if (!empty($group['title'])): ?>
                    <div class="px-6 pt-4 pb-2 text-xs font-bold text-gray-400 uppercase tracking-wider sidebar-text">
                        <?= htmlspecialchars($group['title']) ?>
                    </div>
                <?php endif; ?>

                <?php foreach ($group['items'] as $item): ?>
                    <?php
                    $isActive = ($sidebarPage === $item['id']);
                    $activeClasses = $isActive ? 'bg-primary text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100';
                    ?>
                    <li>
                        <a href="<?= htmlspecialchars($item['link']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $activeClasses ?>">
                            <i class="<?= htmlspecialchars($item['icon']) ?> text-lg"></i>
                            <span class="sidebar-text"><?= htmlspecialchars($item['text']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="border-t border-gray-100 p-4">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-medium">
                <?= htmlspecialchars($sidebarConfig['user']['initial'] ?? 'U') ?>
            </div>
            <div class="user-details flex-1 min-w-0">
                <div class="font-medium text-gray-900 truncate text-sm"><?= htmlspecialchars($sidebarConfig['user']['name'] ?? 'User') ?></div>
                <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars($sidebarConfig['user']['role'] ?? '') ?></div>
            </div>
        </div>
        <a href="<?= htmlspecialchars($sidebarConfig['home_link'] ?? '#') ?>" class="flex items-center gap-2 px-3 py-2 text-gray-600 hover:text-primary hover:bg-gray-50 rounded-lg text-sm transition-colors">
            <i class="ri-arrow-left-line"></i>
            <span class="sidebar-text"><?= htmlspecialchars($sidebarConfig['home_text'] ?? 'กลับสู่เมนูหลัก') ?></span>
        </a>
    </div>
</aside>

<!-- Shared Sidebar Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        // Define menuToggle globally if not already defined (might be defined by top bar)
        let localMenuToggle = document.getElementById('menuToggle');
        const SIDEBAR_STATE_KEY = '<?= htmlspecialchars($sidebarConfig['app_key'] ?? 'default') ?>_sidebar_collapsed';

        function restoreSidebarState() {
            if (window.innerWidth > 768) {
                const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    if (mainContent) mainContent.classList.add('expanded');
                }
            }
            // Remove instant style and re-enable transitions
            requestAnimationFrame(() => {
                const instantStyle = document.getElementById('sidebar-instant-style');
                if (instantStyle) instantStyle.remove();
                sidebar.classList.remove('no-transition');
                if (mainContent) mainContent.classList.remove('no-transition');
            });
        }

        function toggleSidebar() {
            const overlay = document.getElementById('sidebar-overlay');
            if (window.innerWidth <= 768) {
                const isOpen = sidebar.classList.toggle('show');
                if (isOpen) {
                    if (overlay) overlay.classList.remove('hidden');
                } else {
                    if (overlay) overlay.classList.add('hidden');
                }
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContent) mainContent.classList.toggle('expanded');
                localStorage.setItem(SIDEBAR_STATE_KEY, sidebar.classList.contains('collapsed'));
            }
        }

        window.closeSidebar = function() {
            const overlay = document.getElementById('sidebar-overlay');
            if (sidebar) sidebar.classList.remove('show');
            if (overlay) overlay.classList.add('hidden');
        };

        restoreSidebarState();

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (localMenuToggle) {
            localMenuToggle.addEventListener('click', toggleSidebar);
        } else {
            // Setup a MutationObserver to listen for dynamically added menuToggle
            const observer = new MutationObserver(function(mutations) {
                if (!localMenuToggle) {
                    localMenuToggle = document.getElementById('menuToggle');
                    if (localMenuToggle) {
                        localMenuToggle.addEventListener('click', toggleSidebar);
                        // Once found, we can disconnect if we want, but let's keep it safe
                    }
                }
            });
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });
</script>