<?php

/**
 * Dormitory Module - API Router
 * จัดการ routing สำหรับ API endpoints ของ module หอพัก
 */

header("Content-Type: application/json; charset=UTF-8");

// Use optimized session configuration (fixes Antivirus slowdown)
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
startOptimizedSession();
// Note: Don't call session_write_close() here - ModuleController needs $_SESSION

$action = $_GET['action'] ?? null;
$controller = $_GET['controller'] ?? null;

// Centralized User Search Routes (No controller required)
if ($action === 'searchManager') {
    require_once __DIR__ . '/../../core/Services/UserSearchService.php';
    echo json_encode(UserSearchService::searchManager($_GET['query'] ?? ''));
    exit;
}

if ($action === 'searchEmployee') {
    require_once __DIR__ . '/../../core/Services/UserSearchService.php';
    echo json_encode(UserSearchService::searchEmployee($_GET['query'] ?? ''));
    exit;
}

if ($action === 'searchEmail') {
    require_once __DIR__ . '/../../core/Services/UserSearchService.php';
    echo json_encode(UserSearchService::searchEmail($_GET['query'] ?? ''));
    exit;
}

if (!$controller) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Controller not specified']);
    exit;
}

try {
    switch ($controller) {
        case 'dashboard':
            require_once __DIR__ . '/Controllers/DashboardController.php';
            $ctrl = new DashboardController();
            break;

        case 'buildings':
            require_once __DIR__ . '/Controllers/BuildingController.php';
            $ctrl = new BuildingController();
            break;

        case 'rooms':
            require_once __DIR__ . '/Controllers/RoomController.php';
            $ctrl = new RoomController();
            break;

        case 'billing':
            require_once __DIR__ . '/Controllers/BillingController.php';
            $ctrl = new BillingController();
            break;

        case 'maintenance':
            require_once __DIR__ . '/Controllers/MaintenanceController.php';
            $ctrl = new MaintenanceController();
            break;

        case 'settings':
            require_once __DIR__ . '/Controllers/SettingsController.php';
            $ctrl = new SettingsController();
            break;

        case 'base':
            require_once __DIR__ . '/Controllers/BaseController.php';
            $ctrl = new DormBaseController();
            break;

        case 'booking':
        case 'request':
            require_once __DIR__ . '/Controllers/BookingController.php';
            $ctrl = new BookingController();
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Controller '$controller' not found"]);
            exit;
    }

    $ctrl->processRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
