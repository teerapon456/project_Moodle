<?php

/**
 * IGA Module - Entry Point
 * Handles direct access via /Modules/IGA/?mid=5
 * Supports both Employee (SSO) and Applicant (Local Login) access
 */

// Use optimized session configuration
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
startOptimizedSession();

// Load UrlHelper for dynamic paths
require_once __DIR__ . '/../../core/Helpers/UrlHelper.php';

use Core\Helpers\UrlHelper;

$basePath = UrlHelper::getBasePath();

// Load dependencies
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Helpers/PermissionHelper.php';

// Create single database connection
$db = new Database();
$pdo = $db->getConnection();

// Determine authentication state
$isApplicant = isset($_SESSION['applicant_id']);
$isEmployee = isset($_SESSION['user']) && !$isApplicant;

// Handle Standalone Actions (Login, Register, Verify)
$action = $_GET['action'] ?? '';
if (in_array($action, ['login', 'register', 'register_process', 'authenticate', 'verify'])) {
    require_once __DIR__ . '/Controllers/ApplicantAuthController.php';
    $auth = new ApplicantAuthController();
    $auth->processRequest();
    exit;
}

// Get current page
$page = $_GET['page'] ?? 'dashboard';

// If not logged in as either, restrict to login pages
if (!$isApplicant && !$isEmployee) {
    if (!in_array($page, ['login', 'register', 'verify'])) {
        $page = 'login';
    }
}

// Copy session data
$user = $_SESSION['user'] ?? [];

// Check permissions for employees
$canView = true;
$canEdit = false;
$canManage = false;

if ($isEmployee) {
    $perms = userHasModuleAccess('IGA', (int)($user['role_id'] ?? 0), $pdo);
    $canView = !empty($perms['can_view']);
    $canEdit = !empty($perms['can_edit']);
    $canManage = !empty($perms['can_manage']);

    if (!$canView) {
        echo '<div style="text-align:center;padding:60px;font-family:sans-serif;">';
        echo '<h2>ไม่มีสิทธิ์เข้าถึง</h2>';
        echo '<p>คุณไม่มีสิทธิ์เข้าถึงโมดูล IGA</p>';
        echo '<a href="' . $basePath . '/Modules/HRServices/public/index.php">กลับหน้าหลัก</a>';
        echo '</div>';
        exit;
    }
}

// Applicant has limited pages
if ($isApplicant) {
    $user = [
        'id' => 'APP_' . $_SESSION['applicant_id'],
        'fullname' => $_SESSION['user']['fullname'] ?? 'Applicant',
        'email' => $_SESSION['user']['email'] ?? '',
        'is_applicant' => true,
        'emptype' => 'applicant'
    ];
}

// Ensure employee type is present for SSO users
if ($isEmployee && !isset($user['emptype'])) {
    try {
        $stmtTyp = $pdo->prepare("SELECT EmpType FROM users WHERE id = ?");
        $stmtTyp->execute([$user['id']]);
        $user['emptype'] = $stmtTyp->fetchColumn() ?: 'employee';
        $_SESSION['user']['emptype'] = $user['emptype'];
    } catch (Exception $e) {
        $user['emptype'] = 'employee';
    }
}

// Valid pages definition
$adminPages = ['dashboard', 'tests', 'take_test', 'history', 'results', 'categories', 'questions', 'reports', 'settings', 'edit_test', 'test_overview'];
$employeePages = ['dashboard', 'tests', 'take_test', 'history', 'results'];

$applicantPages = ['dashboard', 'tests', 'take_test', 'history', 'results'];
$loginPages = ['login', 'register', 'verify'];

if ($canManage) {
    $validPages = $adminPages;
} elseif ($isEmployee) {
    $validPages = $employeePages;
} elseif ($isApplicant) {
    $validPages = $applicantPages;
} else {
    $validPages = $loginPages;
}

if (!in_array($page, $validPages)) {
    $page = ($isApplicant || $isEmployee) ? 'dashboard' : 'login';
}

// Base URLs
$baseUrl = rtrim($basePath, '/') . '/Modules/IGA/';
$assetBase = UrlHelper::getAssetBase();
$publicUrl = rtrim($assetBase, '/');

// Standalone pages usually handled via index.php?page=xxx if not using action
$standalonePages = ['login', 'register', 'verify'];
if (in_array($page, $standalonePages)) {
    // Redirect to consistent action-based URL
    $redirectParam = '';
    if (isset($_GET['redirect_to'])) {
        $redirectParam = '&redirect_to=' . urlencode($_GET['redirect_to']);
    } elseif (!in_array($action, $standalonePages)) {
        // Only auto-gen if we're coming from a protected page, not if already on login
        $redirectParam = '&redirect_to=' . urlencode(UrlHelper::getCurrentUrl());
    }

    header("Location: /Modules/IGA/?action={$page}" . $redirectParam);
    exit;
}

// Intercept AJAX requests BEFORE HTML output
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    $viewFile = __DIR__ . "/Views/{$page}.php";
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Page not found']);
    }
    exit;
}

// Handle POST actions before HTML output (for redirects)
$viewIncluded = false;
$postOutput = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $viewFile = __DIR__ . "/Views/{$page}.php";
    if (file_exists($viewFile)) {
        ob_start();
        include $viewFile;
        $postOutput = ob_get_clean();
        $viewIncluded = true;
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IGA - MyHR Portal</title>
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">
    <!-- Google Fonts - Kanit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <!-- Tailwind CSS (CDN) - Keeping for development/flexibility as requested, but optimized -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#b91c1c', // Maroon Red
                        maroon: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                            950: '#450a0a',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        /* Sidebar - White Modern Style */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-text,
        .sidebar.collapsed .sidebar-section,
        .sidebar.collapsed .user-details-text {
            display: none;
        }

        .sidebar-section {
            padding: 1.5rem 1.5rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            margin: 0.25rem 1rem;
            color: #64748b;
            font-weight: 500;
            border-radius: 0.75rem;
            transition: all 0.2s;
            text-decoration: none !important;
        }

        .nav-link:hover {
            color: #b91c1c;
            background: #fef2f2;
        }

        .nav-link.active {
            color: #ffffff !important;
            background: #b91c1c;
            box-shadow: 0 4px 12px rgba(185, 28, 28, 0.2);
        }

        .nav-link i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        /* Top Bar */
        .top-bar {
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        /* Mobile */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
                box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
            }

            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>
    <?php if ($isApplicant || $isEmployee): ?>
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 h-16 border-b border-gray-100">
                <div class="flex items-center gap-3 text-primary font-bold text-xl overflow-hidden">
                    <i class="ri-graduation-cap-fill text-2xl shrink-0"></i>
                    <span class="sidebar-text truncate">IGA Assessment</span>
                </div>
                <button class="lg:hidden w-8 h-8 flex items-center justify-center text-gray-400 hover:bg-gray-100 rounded-lg" onclick="document.getElementById('sidebar').classList.remove('show')">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-grow overflow-y-auto py-4">
                <a href="?page=dashboard<?= isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '' ?>" class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="ri-home-4-line"></i> <span class="sidebar-text">หน้าหลัก</span>
                </a>
                <a href="?page=history<?= isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '' ?>" class="nav-link <?= $page === 'history' ? 'active' : '' ?>">
                    <i class="ri-history-line"></i> <span class="sidebar-text">ประวัติการสอบ</span>
                </a>

                <?php if ($canManage): ?>
                    <div class="sidebar-section">จัดการระบบ</div>
                    <a href="?page=tests<?= isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '' ?>" class="nav-link <?= $page === 'tests' ? 'active' : '' ?>">
                        <i class="ri-file-list-3-line"></i> <span class="sidebar-text">แบบทดสอบ</span>
                    </a>
                    <a href="?page=categories<?= isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '' ?>" class="nav-link <?= $page === 'categories' ? 'active' : '' ?>">
                        <i class="ri-price-tag-3-line"></i> <span class="sidebar-text">หมวดหมู่</span>
                    </a>
                    <a href="?page=reports<?= isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '' ?>" class="nav-link <?= $page === 'reports' ? 'active' : '' ?>">
                        <i class="ri-bar-chart-line"></i> <span class="sidebar-text">รายงาน</span>
                    </a>
                    <a href="?page=settings<?= isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '' ?>" class="nav-link <?= $page === 'settings' ? 'active' : '' ?>">
                        <i class="ri-settings-4-line"></i> <span class="sidebar-text">ตั้งค่า</span>
                    </a>
                <?php endif; ?>
            </nav>

            <!-- Footer / User Profile -->
            <div class="border-t border-gray-100 p-4">
                <div class="flex items-center gap-3 mb-3 px-2">
                    <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-sm">
                        <?= mb_substr($user['fullname'] ?? 'U', 0, 1) ?>
                    </div>
                    <div class="user-details-text min-w-0 flex-grow">
                        <div class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($user['fullname'] ?? 'User') ?></div>
                        <div class="text-[11px] text-gray-500 truncate"><?= $canManage ? 'ผู้ดูแลระบบ' : ($isApplicant ? 'ผู้สมัคร' : 'พนักงาน') ?></div>
                    </div>
                </div>
                <?php if ($isApplicant): ?>
                    <a href="/iga/applicant?action=logout" class="flex items-center gap-2 px-3 py-2 text-gray-500 hover:text-primary hover:bg-gray-50 rounded-lg text-sm transition-colors">
                        <i class="ri-logout-box-r-line"></i> <span class="sidebar-text">ออกจากระบบ</span>
                    </a>
                <?php else: ?>
                    <a href="<?= $basePath ?>/Modules/HRServices/public/index.php" class="flex items-center gap-2 px-3 py-2 text-gray-500 hover:text-primary hover:bg-gray-50 rounded-lg text-sm transition-colors">
                        <i class="ri-arrow-left-line"></i> <span class="sidebar-text">กลับสู่ระบบหลัก</span>
                    </a>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content <?= (!$isApplicant && !$isEmployee) ? 'expanded' : '' ?>" style="<?= (!$isApplicant && !$isEmployee) ? 'margin-left: 0;' : '' ?>" id="mainContent">
            <?php if ($isApplicant || $isEmployee): ?>
                <!-- Top Bar -->
                <header class="top-bar">
                    <div class="flex items-center gap-4">
                        <button class="lg:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors" onclick="document.getElementById('sidebar').classList.toggle('show')">
                            <i class="ri-menu-2-line text-2xl"></i>
                        </button>
                        <div class="flex flex-col">
                            <h1 class="text-lg font-bold text-gray-900 leading-none">
                                <?php
                                $titles = [
                                    'dashboard' => 'หน้าหลัก',
                                    'tests' => 'แบบทดสอบ',
                                    'history' => 'ประวัติการสอบ',
                                    'results' => 'ผลสอบ',
                                    'categories' => 'จัดการหมวดหมู่',
                                    'questions' => 'จัดการคำถาม',
                                    'reports' => 'รายงาน',
                                    'settings' => 'ตั้งค่า'
                                ];
                                echo $titles[$page] ?? 'IGA Assessment';
                                ?>
                            </h1>
                            <nav class="flex text-[10px] text-gray-400 mt-1 uppercase tracking-wider font-semibold">
                                <span>IGA</span>
                                <span class="mx-1.5 opacity-50">/</span>
                                <span class="text-primary"><?= $page ?></span>
                            </nav>
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-lg border border-gray-100 text-gray-500 text-xs font-medium">
                        <i class="ri-calendar-line text-primary"></i>
                        <span id="currentDate"></span>
                    </div>
                </header>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="p-4 md:p-8 flex-grow" id="contentBody">
                <?php
                // Map 'login' page to the split login view
                $viewFile = __DIR__ . "/Views/{$page}.php";

                if (file_exists($viewFile)) {
                    if ($viewIncluded) {
                        echo $postOutput;
                    } else {
                        include $viewFile;
                    }
                } else {
                    echo '<div class="flex flex-col items-center justify-center py-20 text-center text-gray-400">';
                    echo '<i class="ri-tools-line text-6xl mb-4"></i>';
                    echo '<h2 class="text-xl font-bold text-gray-600">กำลังพัฒนา</h2>';
                    echo '<p class="text-sm">หน้า "' . htmlspecialchars($page) . '" กำลังอยู่ในระหว่างการพัฒนา</p>';
                    echo '<a href="?page=dashboard" class="mt-6 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium transition-all hover:bg-maroon-800">กลับหน้าหลัก</a>';
                    echo '</div>';
                }
                ?>
            </div>
        </main>
        </main>
    <?php endif; ?>

    <script>
        // Update current date in Thai format
        const dateEl = document.getElementById('currentDate');
        if (dateEl) {
            dateEl.textContent = new Date().toLocaleDateString('th-TH', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Handle Sidebar Toggle on Mobile
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    document.getElementById('sidebar').classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>