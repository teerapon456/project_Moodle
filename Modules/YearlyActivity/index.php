<?php
// Modules/YearlyActivity/index.php
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
\startOptimizedSession();

require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';

// Auth Check
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: ../../public/index.php');
    exit;
}

// Base paths
$startDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$basePath = preg_replace('#/Modules/YearlyActivity$#i', '', $startDir);
if ($basePath === '') $basePath = '/';
$baseRoot = rtrim($basePath, '/');

// Determine asset base: check if DocumentRoot points to public/ folder (Docker) or htdocs (XAMPP)
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot && is_dir($docRoot . '/assets')) {
    $assetBase = ($baseRoot ? $baseRoot : '') . '/';
} else {
    $assetBase = $baseRoot . '/public/';
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
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        // Ensure standard port handling if needed, but usually host header is sufficient
        $host = $_SERVER['HTTP_HOST'];
        $path = strtok($_SERVER['REQUEST_URI'], '?'); // Use request uri path without query/params isn't robust if re-written. Use SCRIPT_NAME.
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
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
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

    $viewPath = __DIR__ . "/Views/$page.php";
    if (file_exists($viewPath)) {
        include $viewPath;
        // If we reach here, the view didn't handle the action - just exit
        exit;
    }
}
?>
<!DOCTYPE html>

<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yearly Activities - MyHR Portal</title>

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
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>

<body class="min-h-screen">

    <!-- Module Header -->
    <?php
    require_once __DIR__ . '/Controllers/NotificationController.php';
    $notifController = new NotificationController();
    $unreadCount = $notifController->getUnreadCount();
    $notifications = $notifController->getMyNotifications(5);
    ?>
    <header class="bg-gradient-to-r from-primary to-primary-light text-white px-4 sm:px-6 py-3 sm:py-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
            <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur">
                    <i class="ri-calendar-event-line text-xl sm:text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-bold">Yearly Activities</h1>
                    <p class="text-xs sm:text-sm text-white/80 hidden sm:block">Activity Tracing System</p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-4 w-full sm:w-auto justify-end">
                <!-- Settings -->
                <a href="?page=settings" class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition text-white">
                    <i class="ri-settings-3-line text-xl"></i>
                </a>

                <!-- Notification Bell -->
                <div class="relative" id="notif-container">
                    <button onclick="toggleNotifications()" class="relative p-2 bg-white/10 hover:bg-white/20 rounded-lg transition">
                        <i class="ri-notification-3-line text-xl"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold">
                                <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <!-- Notification Dropdown -->
                    <div id="notif-dropdown" class="absolute right-0 top-12 w-80 bg-white rounded-xl shadow-2xl border border-gray-200 hidden z-50 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800">Notifications</h3>
                            <button onclick="markAllRead()" class="text-xs text-primary hover:underline">Mark all read</button>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <?php if (empty($notifications)): ?>
                                <div class="p-8 text-center text-gray-400">
                                    <i class="ri-notification-off-line text-3xl mb-2"></i>
                                    <p class="text-sm">No notifications</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif):
                                    $iconMap = ['info' => 'ri-information-line text-blue-500', 'success' => 'ri-check-line text-green-500', 'warning' => 'ri-alert-line text-orange-500', 'error' => 'ri-error-warning-line text-red-500'];
                                    $icon = $iconMap[$notif['type']] ?? $iconMap['info'];
                                ?>
                                    <a href="<?= $notif['link'] ?: '#' ?>"
                                        onclick="markAsRead(<?= $notif['id'] ?>)"
                                        class="block p-4 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 <?= $notif['is_read'] ? 'opacity-60' : '' ?>">
                                        <div class="flex gap-3">
                                            <i class="<?= $icon ?> text-xl mt-0.5"></i>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($notif['title']) ?></div>
                                                <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars($notif['message']) ?></div>
                                                <div class="text-xs text-gray-400 mt-1"><?= date('M j, g:i A', strtotime($notif['created_at'])) ?></div>
                                            </div>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <span class="text-xs sm:text-sm text-white/80 hidden md:inline"><?= htmlspecialchars($user['fullname'] ?? $user['email'] ?? 'User') ?></span>
                <a href="<?= $baseRoot ?>/public/index.php" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm transition">
                    <i class="ri-home-4-line mr-1"></i> หน้าหลัก
                </a>
            </div>
        </div>
    </header>
    <script>
        function toggleNotifications() {
            document.getElementById('notif-dropdown').classList.toggle('hidden');
        }

        function markAsRead(id) {
            fetch('?action=mark_read&notif_id=' + id);
        }

        function markAllRead() {
            fetch('?action=mark_all_read').then(() => location.reload());
        }
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#notif-container')) {
                document.getElementById('notif-dropdown').classList.add('hidden');
            }
        });
    </script>

    <!-- Module Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-3 sm:px-6">
            <div class="flex gap-1 py-2 overflow-x-auto scrollbar-hide nav-scroll">
                <?php
                $navItems = [
                    'dashboard' => ['icon' => 'ri-dashboard-3-line', 'label' => 'Dashboard'],
                    'my_calendars' => ['icon' => 'ri-calendar-todo-line', 'label' => 'My Calendars'],
                    'reports' => ['icon' => 'ri-file-chart-line', 'label' => 'Reports']
                ];

                foreach ($navItems as $key => $item) {
                    $isActive = $page === $key;
                    $activeClass = $isActive
                        ? 'bg-primary text-white shadow-md'
                        : 'text-gray-600 hover:bg-gray-100';
                    echo "<a href='?page=$key' class='flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap $activeClass'>";
                    echo "<i class='{$item['icon']}'></i>";
                    echo "<span>{$item['label']}</span>";
                    echo "</a>";
                }
                ?>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6">
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
                // Fallback for simple views (dashboard, my_calendars, reports)
                $viewPath = __DIR__ . "/Views/$page.php";
                if (file_exists($viewPath)) {
                    include $viewPath;
                } else {
                    echo "
                    <div class='flex flex-col items-center justify-center py-20 text-center'>
                        <div class='w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4'>
                            <i class='ri-tools-line text-3xl text-gray-400'></i>
                        </div>
                        <h2 class='text-2xl font-bold text-gray-900 mb-2'>Under Development</h2>
                        <p class='text-gray-500 max-w-md'>The page '$page' is currently being built. Check back soon!</p>
                    </div>";
                }
                break;
        }
        ?>
    </main>
    </main>

</body>

</html>