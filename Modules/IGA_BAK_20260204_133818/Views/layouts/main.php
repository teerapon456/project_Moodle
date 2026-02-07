<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'ระบบทดสอบออนไลน์' ?> - MyHR Services</title>

    <!-- Google Fonts - Kanit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="../../public/assets/css/tailwind.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 font-kanit text-gray-800 antialiased selection:bg-primary/20 selection:text-primary">

    <div class="flex h-screen overflow-hidden">

        <!-- Mobile Overlay -->
        <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity backdrop-blur-sm"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 flex flex-col transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl md:shadow-none">
            <!-- Logo -->
            <div class="h-16 flex items-center gap-3 px-6 border-b border-gray-100 bg-white">
                <div class="w-8 h-8 rounded-lg bg-primary text-white flex items-center justify-center text-xl font-bold shadow-sm shadow-primary/40">
                    <i class="ri-shield-check-line"></i>
                </div>
                <div>
                    <h1 class="font-bold text-gray-800 leading-none text-lg">IGA Portal</h1>
                    <p class="text-[10px] text-gray-400 font-medium tracking-wider uppercase mt-0.5">Integrity Assessment</p>
                </div>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-1 scrollbar-hide">
                <?php
                // $currentUser is passed from IGABaseController::render
                if (!isset($currentUser)) {
                    $currentUser = $_SESSION['user'] ?? ($_SESSION['iga_applicant'] ?? []);
                }

                $controller = $_GET['controller'] ?? 'exam';
                $action = $_GET['action'] ?? 'index';

                // Permission Check
                // Only Employees (with role_id) can be admins
                $roleId = $currentUser['role_id'] ?? 0;
                $isAdmin = false;

                if ($roleId > 0 && isset($GLOBALS['pdo'])) {
                    $perm = getIGAPermissions($roleId, $GLOBALS['pdo']);
                    $isAdmin = $perm['can_manage'] || $perm['can_edit'];
                }
                ?>

                <!-- User Menu -->
                <div class="pb-2">
                    <div class="uppercase text-[10px] font-bold text-gray-400 mb-2 px-2 tracking-wider">เมนูผู้ใช้งาน</div>
                    <a href="index.php?controller=exam&action=index" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all group <?= ($controller == 'exam') ? 'bg-primary/5 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="<?= ($controller == 'exam') ? 'ri-file-list-fill' : 'ri-file-list-line' ?> text-lg group-hover:scale-110 transition-transform"></i>
                        <span>แบบทดสอบของฉัน</span>
                    </a>
                </div>

                <!-- Admin Menu -->
                <?php if ($isAdmin): ?>
                    <div class="pt-4 pb-2 border-t border-gray-100 mt-2">
                        <div class="uppercase text-[10px] font-bold text-gray-400 mb-2 px-2 tracking-wider">ผู้ดูแลระบบ</div>
                        <a href="index.php?controller=admin&action=index" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all group <?= ($controller == 'admin') ? 'bg-primary/5 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="<?= ($controller == 'admin') ? 'ri-dashboard-fill' : 'ri-dashboard-line' ?> text-lg group-hover:scale-110 transition-transform"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="index.php?controller=test&action=index" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all group <?= ($controller == 'test' || $controller == 'section' || $controller == 'question') ? 'bg-primary/5 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="<?= ($controller == 'test') ? 'ri-survey-fill' : 'ri-survey-line' ?> text-lg group-hover:scale-110 transition-transform"></i>
                            <span>จัดการแบบทดสอบ</span>
                        </a>
                        <a href="index.php?controller=report&action=index" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all group <?= ($controller == 'report') ? 'bg-primary/5 text-primary' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="<?= ($controller == 'report') ? 'ri-bar-chart-box-fill' : 'ri-bar-chart-box-line' ?> text-lg group-hover:scale-110 transition-transform"></i>
                            <span>รายงานผลสอบ</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>

            <!-- User Profile (Footer) -->
            <div class="border-t border-gray-100 p-4 bg-gray-50/50">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-primary-light text-white flex items-center justify-center font-bold shadow-md shadow-primary/20 text-sm ring-2 ring-white">
                        <?= mb_substr($currentUser['fullname'] ?? 'U', 0, 1) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-gray-900 truncate text-sm"><?= htmlspecialchars($currentUser['fullname'] ?? 'User') ?></div>
                        <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars($currentUser['email'] ?? ($currentUser['role'] ?? 'Applicant')) ?></div>
                    </div>
                </div>

                <!-- Footer Helper Actions -->
                <div class="grid grid-cols-1 gap-2">
                    <?php if (isset($currentUser['role']) && $currentUser['role'] === 'applicant'): ?>
                        <a href="index.php?controller=auth&action=logout" class="flex items-center justify-center gap-2 w-full px-3 py-2 border border-red-200 bg-white text-red-600 rounded-lg text-sm hover:bg-red-50 transition-colors shadow-sm">
                            <i class="ri-logout-box-line"></i>
                            <span>ออกจากระบบ</span>
                        </a>
                    <?php else: ?>
                        <!-- Employee Actions -->
                        <a href="../../Modules/HRServices/public/index.php" class="flex items-center justify-center gap-2 w-full px-3 py-2 border border-gray-200 bg-white rounded-lg text-gray-600 hover:bg-gray-50 hover:text-primary text-sm transition-colors shadow-sm">
                            <i class="ri-arrow-left-line"></i>
                            <span>กลับ Portal</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">

            <!-- Mobile Header -->
            <header class="bg-white border-b border-gray-100 px-4 py-3 flex items-center justify-between md:hidden sticky top-0 z-30 shadow-sm">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 active:scale-95 transition-all">
                        <i class="ri-menu-4-line text-xl"></i>
                    </button>
                    <span class="font-bold text-gray-800">IGA Portal</span>
                </div>
                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">
                    <?= mb_substr($currentUser['fullname'] ?? 'U', 0, 1) ?>
                </div>
            </header>

            <!-- Desktop Header & Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 md:p-8 relative">
                <!-- Desktop Header (Title + Date) -->
                <header class="hidden md:flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 tracking-tight"><?= $title ?? 'Dashboard' ?></h2>
                        <p class="text-gray-500 text-sm mt-1">
                            <?= date('l, d F Y') ?>
                        </p>
                    </div>
                </header>

                <!-- Page Content -->
                <div class="animate-fade-in-up">
                    <?php
                    // Render View
                    if (file_exists($viewPath)) {
                        require $viewPath;
                    } else {
                        echo "<div class='p-4 bg-red-50 text-red-600 rounded-lg'>Error: View not found ($viewPath)</div>";
                    }
                    ?>
                </div>

                <!-- Footer Credit -->
                <div class="mt-12 pt-6 border-t border-gray-100 text-center">
                    <p class="text-xs text-gray-400">© <?= date('Y') ?> HR Portal Services. All rights reserved.</p>
                </div>
            </main>
        </div>

    </div>

    <!-- Scripts -->
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (sidebar.classList.contains('-translate-x-full')) {
                // Open
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                // Close
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
    </script>
</body>

</html>