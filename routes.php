<?php
header("Access-Control-Allow-Origin: http://localhost"); // Must be specific for credentials
header("Access-Control-Allow-Credentials: true"); // Required for cookies/sessions
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ช่วงพัฒนา: เปิด error reporting เต็มเพื่อช่วย debug (ควรปิดใน production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

file_put_contents('/srv/myhr/dev/logs/route_debug.log', date('Y-m-d H:i:s') . " - START\nURI: " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);

// Use optimized session configuration (fixes Antivirus slowdown)
require_once __DIR__ . '/core/Config/SessionConfig.php';
require_once __DIR__ . '/core/Config/Env.php';
require_once __DIR__ . '/core/Security/SecureSession.php';
require_once __DIR__ . '/core/Security/CSRFMiddleware.php';
require_once __DIR__ . '/core/Security/RateLimiter.php';

SecureSession::start();

// Read session data into local variable for later use
$sessionUser = $_SESSION['user'] ?? null;

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Rate Limiting - ENABLED WITH NEW SECURITY SYSTEM
$endpoint = $_GET['resource'] ?? 'default';

// Skip rate limiting for auth endpoints to avoid conflicts with SecureSession
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$isAuthRequest = strpos($requestUri, '/auth/') !== false;

if (!$isAuthRequest) {
    RateLimiter::protect('api');
}

// CRITICAL: Release session lock for concurrent requests AFTER RateLimiter writes
session_write_close();

// CSRF Protection for state-changing requests (POST, PUT, DELETE)
$csrfExemptRoutes = ['auth/login', 'auth/microsoft', 'hrnews/published', 'api/auth', 'auth/rate-limits', 'auth/clear-rate-limit', 'auth/clear-all-rate-limits']; // Routes that don't need CSRF

// Better route detection for API endpoints
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$pathInfo = parse_url($requestUri, PHP_URL_PATH);
$pathSegments = explode('/', trim($pathInfo, '/'));

// Check if this is an API auth request
$isApiAuthRequest = (count($pathSegments) >= 2 && $pathSegments[0] === 'api' && $pathSegments[1] === 'auth');

// For non-API requests, use the old method
if (!$isApiAuthRequest) {
    // Extract first two meaningful segments for route detection
    $cleanSegments = array_values(array_filter($pathSegments, function ($s) {
        return $s !== '' && $s !== 'api';
    }));
    $currentRoute = implode('/', array_slice($cleanSegments, 0, 2));
} else {
    $currentRoute = 'api/auth';
}

if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    $isExempt = false;

    // Check for exact match first
    if (in_array($currentRoute, $csrfExemptRoutes)) {
        $isExempt = true;
    } else {
        // Check for partial match
        foreach ($csrfExemptRoutes as $route) {
            if (strpos($currentRoute, $route) !== false) {
                $isExempt = true;
                break;
            }
        }
    }

    if (!$isExempt) {
        // Use new CSRF middleware
        if (!CSRFMiddleware::validateRequest()) {
            // Response already sent by validateRequest()
            exit;
        }
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

// Remove /api/ prefix if present and find the resource
$resourceIndex = 0;
$validResources = ['auth', 'bookings', 'cars', 'settings', 'users', 'dashboard', 'pdf', 'reports', 'email_logs', 'approval', 'fleetcards', 'modules', 'hrnews', 'dormitory', 'dorm', 'permissions', 'notifications', 'activity', 'scheduled_reports', 'yearlyactivity', 'sso', 'employees'];
// Remove empty segments and find resource
$cleanUri = array_values(array_filter($uri, function ($segment) {
    return $segment !== '' && $segment !== 'api';
}));



foreach ($cleanUri as $index => $segment) {
    if (in_array($segment, $validResources)) {
        $resource = $segment;
        $resourceIndex = $index;
        $segment = $cleanUri[$index + 1] ?? null;
        break;
    }
}

// Fallback: Support query parameter routing for servers without mod_rewrite (e.g., Android)
// URL format: routes.php?resource=auth&controller=microsoft&action=callback
if (!isset($resource) && isset($_GET['resource']) && in_array($_GET['resource'], $validResources)) {
    $resource = $_GET['resource'];
    $segment = $_GET['controller'] ?? $_GET['action'] ?? null;
    $resourceIndex = 0;
    // For Microsoft OAuth, set action from query param
    if (isset($_GET['action'])) {
        $_GET['action'] = $_GET['action'];
    }
}

if (isset($resource)) {
    // Map URL segment to action if not already set in query string
    if ($segment && !isset($_GET['action'])) {
        $_GET['action'] = $segment;
    }

    switch ($resource) {
        case 'auth':
            // Check if this is a Microsoft OAuth request (/auth/microsoft/*)
            $isMicrosoftOAuth = ($segment === 'microsoft');
            $isMoodleSSO = ($segment === 'moodle-sso');

            if ($isMicrosoftOAuth) {
                $oauthAction = $cleanUri[$resourceIndex + 2] ?? 'login';
                $_GET['action'] = $oauthAction;

                include_once __DIR__ . '/core/Auth/MicrosoftAuthController.php';
                $controller = new MicrosoftAuthController();
                $controller->processRequest();
            } elseif ($isMoodleSSO) {
                $_GET['action'] = 'moodle';
                include_once __DIR__ . '/core/Controllers/SSOController.php';
                $controller = new SSOController();
                $controller->processRequest();
            } else {
                include_once __DIR__ . '/core/Auth/AuthController.php';
                $controller = new AuthController();
                $controller->processRequest();
            }
            break;
        case 'bookings':
            include_once __DIR__ . '/modules/CarBooking/Controllers/BookingController.php';
            $controller = new BookingController();
            $controller->processRequest();
            break;
        case 'cars':
            include_once __DIR__ . '/modules/CarBooking/Controllers/CarController.php';
            $controller = new CarController();
            $controller->processRequest();
            break;
        /*
        case 'settings':
            include_once __DIR__ . '/modules/CarBooking/Controllers/SettingController.php';
            $controller = new SettingController();
            $controller->processRequest();
            break;
        case 'workflow':
            include_once __DIR__ . '/modules/CarBooking/Controllers/WorkflowController.php';
            $controller = new WorkflowController();
            $controller->processRequest();
            break;
        case 'users':
            include_once __DIR__ . '/modules/CarBooking/Controllers/UserController.php';
            $controller = new UserController();
            $controller->processRequest();
            break;
        case 'approval':
            include_once __DIR__ . '/modules/CarBooking/Controllers/ApprovalController.php';
            $controller = new ApprovalController();
            $controller->processRequest();
            break;
        case 'pdf':
            include_once __DIR__ . '/modules/CarBooking/Controllers/PDFController.php';
            $controller = new PDFController();
            $controller->processRequest();
            break;
        case 'dashboard':
            include_once __DIR__ . '/modules/CarBooking/Controllers/DashboardController.php';
            $controller = new DashboardController();
            $controller->processRequest();
            break;
        case 'reports':
            include_once __DIR__ . '/modules/CarBooking/Controllers/ReportController.php';
            $controller = new ReportController();
            $controller->generateUsageReport();
            break;
        */
        case 'modules':
            include_once __DIR__ . '/Modules/HRServices/Controllers/HRServicesController.php';
            $controller = new ModuleController();
            $controller->processRequest();
            break;
        case 'permissions':
            include_once __DIR__ . '/Modules/PermissionManagement/Controllers/PermissionController.php';
            $controller = new PermissionController();
            $controller->processRequest();
            break;
        case 'employees':
            include_once __DIR__ . '/Modules/PermissionManagement/Controllers/PermissionController.php';
            $controller = new PermissionController();
            $controller->processRequest();
            break;
        case 'dormitory':
        case 'dorm':
            // Dormitory Module - route to the appropriate controller based on ?controller= param or URL segment
            $dormController = $_GET['controller'] ?? $cleanUri[$resourceIndex + 1] ?? null;

            // If controller came from URL, the next segment is the action (if not already set)
            if (isset($cleanUri[$resourceIndex + 1]) && !isset($_GET['action'])) {
                $_GET['action'] = $cleanUri[$resourceIndex + 2] ?? 'index';
            }
            if (!$dormController) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Controller not specified']);
                break;
            }
            $controllerMap = [
                'dashboard' => 'DashboardController',
                'buildings' => 'BuildingController',
                'rooms' => 'RoomController',
                'billing' => 'BillingController',
                'dashboard' => 'DashboardController',
                'buildings' => 'BuildingController',
                'rooms' => 'RoomController',
                'billing' => 'BillingController',
                'maintenance' => 'MaintenanceController',
                'request' => 'BookingController'
            ];
            if (!isset($controllerMap[$dormController])) {
                $basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
                if ($basePath === '') {
                    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                    $basePath = $scriptDir;
                    if (basename($basePath) === 'api') {
                        $basePath = dirname($basePath);
                    }
                }
                header("Location: " . $basePath . "/404.php");
                exit;
            }
            $controllerClass = $controllerMap[$dormController];
            include_once __DIR__ . "/Modules/Dormitory/Controllers/{$controllerClass}.php";
            $controller = new $controllerClass();
            $controller->processRequest();
            break;
        case 'hrnews':
            include_once __DIR__ . '/Modules/HRNews/Controllers/NewsController.php';
            $controller = new NewsController();
            $controller->processRequest();
            break;
        case 'notifications':
            include_once __DIR__ . '/core/Controllers/NotificationController.php';
            $controller = new NotificationController();
            $controller->processRequest();
            break;
        case 'activity':
            include_once __DIR__ . '/core/Controllers/ActivityController.php';
            $controller = new ActivityController();
            $controller->processRequest();
            break;
        case 'email_logs':
            include_once __DIR__ . '/core/Controllers/EmailLogController.php';
            $controller = new EmailLogController();
            $controller->processRequest();
            break;
        case 'sso':
            include_once __DIR__ . '/core/Controllers/SSOController.php';
            $controller = new SSOController();
            $controller->processRequest();
            break;
        case 'scheduled_reports':
            include_once __DIR__ . '/core/Controllers/ScheduledReportController.php';
            $controller = new ScheduledReportController();
            $controller->processRequest();
            break;

        case 'yearlyactivity':
            // Simple Router for Yearly Activity Module
            $controllerName = $_GET['controller'] ?? 'dashboard';
            $action = $_GET['action'] ?? 'index';

            // Map controller names to classes
            $map = [
                'dashboard' => 'Modules/YearlyActivity/Controllers/DashboardController.php',
                'calendar'  => 'Modules/YearlyActivity/Controllers/CalendarController.php',
                'activity'  => 'Modules/YearlyActivity/Controllers/ActivityController.php',
            ];

            if (array_key_exists($controllerName, $map)) {
                require_once __DIR__ . '/' . $map[$controllerName];

                // Convention: dashboard -> DashboardController
                $className = ucfirst($controllerName) . 'Controller';
                if (class_exists($className)) {
                    $controller = new $className();

                    // Call the action
                    if (method_exists($controller, $action)) {
                        $controller->$action();
                    } else {
                        // Fallback or Error
                        echo "Action not found";
                    }
                } else {
                    echo "Controller Class not found";
                }
            } else {
                header("Location: /yearlyactivity"); // Reset to dashboard
            }
            // Retain control, do not break to default 404
            exit;
            break;
        /*
        case 'fleetcards':
            include_once __DIR__ . '/modules/CarBooking/Controllers/FleetCardController.php';
            $controller = new FleetCardController();
            $controller->processRequest();
            break;
        */

        default:
            $basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
            if ($basePath === '') {
                $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                $basePath = $scriptDir;
                if (basename($basePath) === 'api') {
                    $basePath = dirname($basePath);
                }
            }
            header("Location: " . $basePath . "/404.php");
            exit;
            break;
    }
} else {
    // Fallback for debugging or root access
    $basePathEnv = rtrim(Env::get('APP_BASE_PATH', ''), '/');
    $basePath = $basePathEnv ? $basePathEnv : '';
    header("Location: " . $basePath . "/404.php");
    exit;
}
