<?php
// Modules/YearlyActivity/index.php
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
\startOptimizedSession();

require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';

// Auth Check
$user = $_SESSION['user'] ?? null;
if (!$user) {
    require_once __DIR__ . '/../../core/Helpers/UrlHelper.php';
    $redirectTo = urlencode(\Core\Helpers\UrlHelper::getCurrentUrl());
    header("Location: login.php?redirect_to=$redirectTo");
    exit;
}

// Base paths
$startDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$basePath = preg_replace('#/Modules/YearlyActivity$#i', '', $startDir);
if ($basePath === '') $basePath = '/';
$baseRoot = rtrim($basePath, '/');

// Determine asset base correctly for Docker environment
if (file_exists(__DIR__ . '/../../core/Helpers/UrlHelper.php')) {
    require_once __DIR__ . '/../../core/Helpers/UrlHelper.php';
    $assetBase = \Core\Helpers\UrlHelper::getAssetBase();
    $linkBase = \Core\Helpers\UrlHelper::getLinkBase();
} else {
    // Fallback logic
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    if ($docRoot && (is_dir($docRoot . '/assets') || is_dir($docRoot . '/public/assets'))) {
        $assetBase = '/';
    } else {
        $assetBase = $baseRoot . '/public/';
    }
    $linkBase = $baseRoot . '/';
}
$moduleAssets = $baseRoot . '/Modules/YearlyActivity/public/assets/';


// Page Router
$page = $_GET['page'] ?? null;

// Default Page Logic (if no page param)
if (!$page) {
    // Try to get user preference
    $defaultPage = 'dashboard';
    if (isset($_SESSION['user']['id'])) {
        require_once __DIR__ . '/Controllers/SettingsController.php';
        $settingsCtrl = new SettingsController();
        $mySettings = $settingsCtrl->getSettings($_SESSION['user']['id']);
        if (!empty($mySettings['start_page'])) {
            $defaultPage = $mySettings['start_page'];
        }
    }
    $page = $defaultPage;
}

// Pre-check for pages requiring Redirects (Fix: Headers already sent)
if (($page === 'calendar' || $page === 'calendar_settings') && empty($_GET['id'])) {
    header('Location: ?page=dashboard');
    exit;
}

// Handle AJAX requests BEFORE any HTML output
// If action parameter is present, include the view to handle AJAX and exit before HTML
if (isset($_GET['action'])) {
    // Schema Update Action
    if ($_GET['action'] === 'update_schema') {
        $db = new \Database();
        $conn = $db->getConnection();
        try {
            // Existing schema updates
            // Check/Add key_person_id
            $columns = $conn->query("SHOW COLUMNS FROM ya_activities LIKE 'key_person_id'")->fetchAll();
            if (empty($columns)) {
                $sql = "ALTER TABLE ya_activities ADD COLUMN key_person_id INT NULL AFTER location";
                $conn->exec($sql);
                echo "<p>Added key_person_id column.</p>";
            }

            $columns = $conn->query("SHOW COLUMNS FROM ya_activities LIKE 'is_synced'")->fetchAll();
            if (empty($columns)) {
                $sql = "ALTER TABLE ya_activities ADD COLUMN is_synced TINYINT(1) DEFAULT 0";
                $conn->exec($sql);
                echo "<p>Added is_synced column.</p>";
            }

            // Create ya_user_settings table
            $sql = "CREATE TABLE IF NOT EXISTS ya_user_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                setting_key VARCHAR(50) NOT NULL,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_setting (user_id, setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $conn->exec($sql);
            echo "<p>Table 'ya_user_settings' check/created.</p>";

            echo "<h1>Schema Updated!</h1><p><a href='?page=dashboard'>Go to Dashboard</a></p>";
        } catch (PDOException $e) {
            echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
        }
        // Also try to add FK if separate
        try {
            $sql = "ALTER TABLE ya_activities ADD CONSTRAINT fk_ya_activities_key_person FOREIGN KEY (key_person_id) REFERENCES users(id) ON DELETE SET NULL";
            $conn->exec($sql);
        } catch (Exception $e) {
        }
        exit;
    }

    // Save Settings Action
    if ($_GET['action'] === 'save_setting') {
        require_once __DIR__ . '/Controllers/SettingsController.php';
        $settingsController = new SettingsController();
        $settingsController->saveSetting();
        exit;
    }

    // Seed Data Action
    if ($_GET['action'] === 'seed_data') {
        require_once __DIR__ . '/seed_data.php';
        exit;
    }

    // Handle notification actions at module level
    if ($_GET['action'] === 'mark_read' && isset($_GET['notif_id'])) {
        require_once __DIR__ . '/Controllers/NotificationController.php';
        $notifController = new NotificationController();
        $notifController->markAsRead($_GET['notif_id']);
        exit;
    }
    if ($_GET['action'] === 'mark_all_read') {
        require_once __DIR__ . '/Controllers/NotificationController.php';
        $notifController = new NotificationController();
        $notifController->markAllAsRead();
        exit;
    }

    // Export actions
    if (strpos($_GET['action'], 'export_') === 0) {
        require_once __DIR__ . '/Controllers/ExportController.php';
        $exportController = new ExportController();

        switch ($_GET['action']) {
            case 'export_activities':
                $exportController->exportActivitiesCSV();
                exit;
            case 'export_rasci':
                $exportController->exportRasciCSV();
                exit;
            case 'export_risks':
                $exportController->exportRisksCSV();
                exit;
            case 'export_report':
                $exportController->exportReportCSV();
                exit;
        }
    }

    // Import action
    if ($_GET['action'] === 'import_activities' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/Controllers/ExportController.php';
        $exportController = new ExportController();
        $calendarId = $_POST['calendar_id'] ?? null;

        header('Content-Type: application/json');

        if (empty($calendarId)) {
            echo json_encode(['success' => false, 'message' => 'Calendar ID missing']);
            exit;
        }

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $result = $exportController->importActivitiesFromExcel($_FILES['file']['tmp_name'], $calendarId);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        }
        exit;
    }

    if ($_GET['action'] === 'download_template') {
        require_once __DIR__ . '/Controllers/ExportController.php';
        $exportController = new ExportController();
        $exportController->downloadTemplate();
        exit;
    }

    // PDF Report actions
    if (strpos($_GET['action'], 'pdf_') === 0) {
        require_once __DIR__ . '/Controllers/PDFController.php';
        $pdfController = new PDFController();

        switch ($_GET['action']) {
            case 'pdf_activities':
                $pdfController->outputReport('activities');
                exit;
            case 'pdf_rasci':
                $pdfController->outputReport('rasci');
                exit;
            case 'pdf_risks':
                $pdfController->outputReport('risks');
                exit;
        }
    }

    // ICS Calendar export
    if (strpos($_GET['action'], 'ics_') === 0) {
        require_once __DIR__ . '/Controllers/CalendarSyncController.php';
        $calController = new CalendarSyncController();

        if ($_GET['action'] === 'ics_all') {
            $calController->exportAllToICS();
            exit;
        }
        if ($_GET['action'] === 'ics_single' && isset($_GET['id'])) {
            $calController->downloadICS($_GET['id']);
            exit;
        }
    }

    // Calendar sync actions
    if ($_GET['action'] === 'calendar_connect') {
        require_once __DIR__ . '/Controllers/CalendarSyncController.php';
        $calController = new CalendarSyncController();
        $provider = $_GET['provider'] ?? 'outlook';

        // Construct Redirect URI (Same script, callback action)
        $protocol = "http";
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            $protocol = "https";
        }

        // Force https as requested by user for Microsoft OAuth alignment
        $protocol = "https";

        $host = $_SERVER['HTTP_HOST'];
        $path = $_SERVER['SCRIPT_NAME'];
        $redirectUri = "$protocol://$host$path?action=calendar_callback";

        $authUrl = $calController->getAuthUrl($provider, $redirectUri);
        header("Location: $authUrl");
        exit;
    }

    if ($_GET['action'] === 'calendar_callback') {
        require_once __DIR__ . '/Controllers/CalendarSyncController.php';
        $calController = new CalendarSyncController();

        // Reconstruct Redirect URI for verification
        $protocol = "http";
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            $protocol = "https";
        }

        // Force https as requested by user for Microsoft OAuth alignment
        $protocol = "https";

        $host = $_SERVER['HTTP_HOST'];
        $path = $_SERVER['SCRIPT_NAME'];
        $redirectUri = "$protocol://$host$path?action=calendar_callback";

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';

        $result = $calController->handleCallback($code, $state, $redirectUri);

        if ($result['success']) {
            header('Location: ?page=settings&msg=connected');
        } else {
            header('Location: ?page=settings&error=' . urlencode($result['message']));
        }
        exit;
    }

    if ($_GET['action'] === 'calendar_disconnect') {
        require_once __DIR__ . '/Controllers/CalendarSyncController.php';
        $calController = new CalendarSyncController();
        $calController->disconnect();
        header('Location: ?page=settings');
        exit;
    }
    if ($_GET['action'] === 'calendar_sync') {
        require_once __DIR__ . '/Controllers/CalendarSyncController.php';
        $calController = new CalendarSyncController();
        $result = $calController->syncActivities();
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Calendar Actions
    // Calendar Actions (Routed to Controller)
    $calendarActions = ['update_calendar', 'delete_calendar', 'add_member', 'remove_member'];
    if (in_array($_GET['action'], $calendarActions) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/Controllers/CalendarController.php';
        $calController = new CalendarController();

        switch ($_GET['action']) {
            case 'update_calendar':
                $calController->update();
                break;
            case 'delete_calendar':
                $calController->delete($_GET['id'] ?? 0);
                break;
            case 'add_member':
                $calController->addMember();
                break;
            case 'remove_member':
                $calController->removeMember();
                break;
        }
        exit;
    }

    // Delete Calendar via GET (Link)
    if ($_GET['action'] === 'delete_calendar' && isset($_GET['id'])) {
        require_once __DIR__ . '/Controllers/CalendarController.php';
        $calController = new CalendarController();
        $calController->delete($_GET['id']);
        exit;
    }

    if ($_REQUEST['action'] === 'create_calendar') { // Support POST/GET
        require_once __DIR__ . '/Controllers/CalendarController.php';
        $controller = new CalendarController();
        $controller->store();
    }

    // Activity Actions
    if ($_GET['action'] === 'delete_activity' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $actController = new ActivityController();
        $id = $_POST['id'] ?? 0;
        if ($actController->deleteActivity($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete or access denied']);
        }
        exit;
    }

    if ($_GET['action'] === 'search_users') {
        $db = new \Database();
        $conn = $db->getConnection();
        $q = $_GET['q'] ?? '';
        $stmt = $conn->prepare("SELECT id, fullname, email, department FROM users WHERE fullname LIKE ? OR email LIKE ? LIMIT 10");
        $term = "%$q%";
        $stmt->execute([$term, $term]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($_GET['action'] === 'search_employee') {
        require_once __DIR__ . '/../../core/Services/UserSearchService.php';
        $query = $_GET['query'] ?? '';
        header('Content-Type: application/json');
        echo json_encode(UserSearchService::searchEmployee($query));
        exit;
    }

    // Activity Wizard Actions
    if ($_REQUEST['action'] === 'save_wizard') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->saveWizard();
        exit;
    }
    if ($_REQUEST['action'] === 'change_activity_status') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->changeActivityStatus();
        exit;
    }
    if ($_REQUEST['action'] === 'rate_activity') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->rateActivity();
        exit;
    }

    // Activity API Actions (Milestones)
    if ($_GET['action'] === 'get_milestones' && isset($_GET['activity_id'])) {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->getMilestones($_GET['activity_id']);
        exit;
    }
    if ($_REQUEST['action'] === 'add_milestone') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->addMilestone();
        exit;
    }
    if ($_REQUEST['action'] === 'update_milestone_status') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->updateMilestoneStatus();
        exit;
    }
    if ($_REQUEST['action'] === 'update_milestone') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->updateMilestone();
        exit;
    }
    if ($_REQUEST['action'] === 'remove_milestone') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->removeMilestone();
        exit;
    }

    // RASCI Actions
    if ($_GET['action'] === 'get_calendar_members') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->getCalendarMembers();
        exit;
    }
    if ($_GET['action'] === 'get_rasci') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->getRasci();
        exit;
    }
    if ($_GET['action'] === 'get_all_rasci') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->getAllRasci();
        exit;
    }
    if ($_REQUEST['action'] === 'add_rasci') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->addRasci();
        exit;
    }
    if ($_REQUEST['action'] === 'remove_rasci') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->removeRasci();
        exit;
    }

    // Resource Actions
    if ($_GET['action'] === 'get_resources') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->getResources();
        exit;
    }
    if ($_REQUEST['action'] === 'add_resource') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->addResource();
        exit;
    }
    if ($_REQUEST['action'] === 'remove_resource') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->removeResource();
        exit;
    }

    // Risk Actions
    if ($_GET['action'] === 'get_risks') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->getRisks();
        exit;
    }
    if ($_REQUEST['action'] === 'add_risk') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->addRisk();
        exit;
    }
    if ($_REQUEST['action'] === 'remove_risk') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->removeRisk();
        exit;
    }

    // Attachment Actions
    if ($_REQUEST['action'] === 'upload_attachment') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->uploadAttachment();
        exit;
    }
    if ($_REQUEST['action'] === 'delete_attachment') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->deleteAttachment();
        exit;
    }

    // Comment Actions
    if ($_REQUEST['action'] === 'add_comment') {
        require_once __DIR__ . '/Controllers/ActivityController.php';
        $controller = new ActivityController();
        $controller->addComment();
        exit;
    }

    $viewPath = __DIR__ . "/Views/$page.php";
    if (file_exists($viewPath)) {
        include $viewPath;
        // If we reach here, the view didn't handle the action - just exit
        exit;
    }
}

// Variables for Sidebar/Topbar
$pageTitle = 'Yearly Activities';
if ($page === 'dashboard') $pageTitle = 'Dashboard';
elseif ($page === 'my_calendars') $pageTitle = 'My Calendars';
elseif ($page === 'reports') $pageTitle = 'Reports';

$baseRoot = $basePath;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - MyHR Portal</title>

    <!-- Google Fonts - Kanit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Local) -->
    <link href="<?= $assetBase ?>assets/css/tailwind.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- RemixIcon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.1/fonts/remixicon.css" rel="stylesheet">

    <!-- Module CSS -->
    <link href="<?= $moduleAssets ?>css/style.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php
    // Sidebar Configuration
    $sidebarConfig = [
        'app_key' => 'yearlyactivity',
        'title' => 'Yearly Activities',
        'icon' => 'ri-calendar-check-fill',
        'home_link' => $linkBase . 'Modules/HRServices/public/index.php',
        'home_text' => 'กลับสู่หน้าหลัก',
        'user' => [
            'initial' => mb_substr($user['fullname'] ?? $user['name'] ?? $user['username'] ?? 'U', 0, 1),
            'name' => htmlspecialchars($user['fullname'] ?? $user['name'] ?? $user['username']),
            'role' => $user['role_name'] ?? 'User'
        ],
        'nav_groups' => [
            [
                'title' => null,
                'items' => [
                    ['id' => 'dashboard', 'link' => '?page=dashboard', 'icon' => 'ri-dashboard-3-line', 'text' => 'Dashboard'],
                    ['id' => 'my_calendars', 'link' => '?page=my_calendars', 'icon' => 'ri-calendar-todo-line', 'text' => 'My Calendars'],
                    ['id' => 'reports', 'link' => '?page=reports', 'icon' => 'ri-file-chart-line', 'text' => 'Reports']
                ]
            ],
            [
                'title' => 'ตั้งค่า',
                'items' => [
                    ['id' => 'settings', 'link' => '?page=settings', 'icon' => 'ri-settings-3-line', 'text' => 'ตั้งค่าส่วนตัว']
                ]
            ]
        ]
    ];

    // Map sub-pages to parent sidebar items
    $parentPageMap = [
        'calendar' => 'my_calendars',
        'calendar_settings' => 'my_calendars',
        'activity_detail' => 'dashboard',
        'summary_5w2h' => 'dashboard',
        'activity_wizard' => 'dashboard',
        'form_5w2h' => 'dashboard',
        'settings' => 'settings'
    ];

    include dirname(__DIR__, 2) . '/core/Views/components/sidebar.php';
    ?>

    <!-- Main Content Wrapper -->
    <main class="main-wrapper no-transition min-h-screen" id="mainContent" style="padding-left: 0;">
        <!-- Topbar -->
        <?php include dirname(__DIR__, 2) . '/core/Views/components/topbar.php'; ?>

        <!-- Content Area -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
            <?php
            // Clean Controller Routing
            // This replaces the need for "dispatcher view files"
            switch ($page) {
                case 'calendar':
                    require_once __DIR__ . '/Controllers/CalendarController.php';
                    (new CalendarController())->show($_GET['id'] ?? 0);
                    break;

                case 'calendar_settings':
                    require_once __DIR__ . '/Controllers/CalendarController.php';
                    (new CalendarController())->settings($_GET['id'] ?? 0);
                    break;

                case 'activity_detail':
                case 'summary_5w2h':
                    require_once __DIR__ . '/Controllers/ActivityController.php';
                    (new ActivityController())->summary5w2h($_GET['id'] ?? 0);
                    break;

                case 'activity_wizard':
                    require_once __DIR__ . '/Controllers/ActivityController.php';
                    (new ActivityController())->wizard();
                    break;

                default:
                    // Fallback for simple views (dashboard, my_calendars, reports, settings)
                    $viewPath = __DIR__ . "/Views/$page.php";
                    if (file_exists($viewPath)) {
                        include $viewPath;
                    } else {
                        echo "
                        <div class='flex flex-col items-center justify-center py-20 text-center bg-white rounded-xl shadow-sm border border-gray-100'>
                            <div class='w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4'>
                                <i class='ri-tools-line text-3xl text-gray-400'></i>
                            </div>
                            <h2 class='text-2xl font-bold text-gray-900 mb-2'>Under Development</h2>
                            <p class='text-gray-500 max-w-md'>The page '$page' is currently being built. Check back soon!</p>
                            <a href='?page=dashboard' class='mt-6 px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors'>Return to Dashboard</a>
                        </div>";
                    }
                    break;
            }
            ?>
        </div>
    </main>

    <!-- Global Variables for JS -->
    <script>
        window.APP_BASE_PATH = '<?= $baseRoot ?>';
        window.ASSET_BASE = '<?= $assetBase ?>';
    </script>
</body>

</html>