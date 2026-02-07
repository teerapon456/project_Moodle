<?php

/**
 * Car Booking Module - API Router
 * จัดการ routing สำหรับ API endpoints ของ module จองรถ
 * (โครงสร้างเหมือน Dormitory Module)
 */

header("Content-Type: application/json; charset=UTF-8");

// Use optimized session configuration (fixes Antivirus slowdown)
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
startOptimizedSession();
// Note: Don't call session_write_close() here - controllers need $_SESSION

$action = $_GET['action'] ?? null;
$controller = $_GET['controller'] ?? null;

if (!$controller) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Controller not specified']);
    exit;
}

// Create user object from session (session still open for controllers)
$user = $_SESSION['user'] ?? null;

try {
    switch ($controller) {
        case 'bookings':
            require_once __DIR__ . '/Controllers/BookingController.php';
            $ctrl = new BookingController($user);
            break;

        case 'cars':
            require_once __DIR__ . '/Controllers/CarController.php';
            $ctrl = new CarController($user);
            break;

        case 'fleet-cards':
        case 'fleetcards':
            require_once __DIR__ . '/Controllers/FleetCardController.php';
            $ctrl = new FleetCardController($user);
            break;

        case 'dashboard':
            require_once __DIR__ . '/Controllers/DashboardController.php';
            $ctrl = new CBDashboardController($user);
            break;

        case 'reports':
            require_once __DIR__ . '/Controllers/ReportController.php';
            $ctrl = new CBReportController($user);
            break;

        case 'settings':
            require_once __DIR__ . '/Controllers/SettingsController.php';
            $ctrl = new CBSettingsController($user);
            break;

        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Controller '$controller' not found"]);
            exit;
    }

    // Handle searchEmployee specially (doesn't use processRequest)
    if ($controller === 'bookings' && $action === 'searchEmployee') {
        $query = $_GET['query'] ?? '';
        $result = $ctrl->searchEmployee($query);
        echo json_encode($result);
        exit;
    }

    // Handle listAuditLogs (Admin only)
    if ($controller === 'bookings' && $action === 'listAuditLogs') {
        $result = $ctrl->listAuditLogs();
        echo json_encode($result);
        exit;
    }

    // Handle revoke (cancel approved booking)
    if ($controller === 'bookings' && $action === 'revoke') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $input['booking_id'] ?? 0;
        $reason = $input['reason'] ?? '';
        $result = $ctrl->revoke($id, $reason);
        echo json_encode($result);
        exit;
    }

    // Handle updateApproved (edit approved booking)
    if ($controller === 'bookings' && $action === 'updateApproved') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $input['booking_id'] ?? 0;
        $result = $ctrl->updateApproved($id, $input);
        echo json_encode($result);
        exit;
    }
    // Handle saveDefaultSupervisor
    if ($controller === 'bookings' && $action === 'saveDefaultSupervisor') {
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $ctrl->saveDefaultSupervisor($input);
        echo json_encode($result);
        exit;
    }

    // Handle getAvailableAssets
    if ($controller === 'bookings' && $action === 'getAvailableAssets') {
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';
        $exclude = $_GET['exclude_id'] ?? 0;

        $result = $ctrl->getAvailableAssets($start, $end, $exclude);
        echo json_encode($result);
        exit;
    }

    // Handle resend email
    if ($controller === 'bookings' && $action === 'resendEmail') {
        $input = json_decode(file_get_contents('php://input'), true);
        $bookingId = $input['id'] ?? $input['booking_id'] ?? $_GET['id'] ?? 0;
        $result = $ctrl->resendEmails($bookingId);
        echo json_encode($result);
        exit;
    }

    // ======================================
    // CAR RETURN FEATURE API ROUTES
    // ======================================

    // List bookings currently in use (for IPCD)
    if ($controller === 'bookings' && $action === 'listInUse') {
        $result = $ctrl->listInUse();
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    // List bookings pending return confirmation
    if ($controller === 'bookings' && $action === 'listPendingReturn') {
        $result = $ctrl->listPendingReturn();
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }

    // User reports car return
    if ($controller === 'bookings' && $action === 'reportReturn') {
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $ctrl->reportReturn($input);
        echo json_encode($result);
        exit;
    }

    // IPCD confirms car return
    if ($controller === 'bookings' && $action === 'confirmReturn') {
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $ctrl->confirmReturn($input);
        echo json_encode($result);
        exit;
    }

    // Handle cancel (User cancels own request)
    if ($controller === 'bookings' && $action === 'cancel') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $reason = $input['reason'] ?? '';
        $result = $ctrl->cancel($id, $reason);
        echo json_encode($result);
        exit;
    }

    // Handle reject (Manager/IPCD rejects request)
    if ($controller === 'bookings' && $action === 'reject') {
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $ctrl->reject($input);
        echo json_encode($result);
        exit;
    }


    // Handle token actions (Public - No session required)
    if ($action === 'get_token_details') {
        require_once __DIR__ . '/Controllers/BookingController.php';
        $ctrl = new BookingController(null); // No user session needed
        $token = $_GET['token'] ?? '';
        echo json_encode($ctrl->getDetailsByToken($token));
        exit;
    }

    if ($action === 'approve_token') {
        require_once __DIR__ . '/Controllers/BookingController.php';
        $ctrl = new BookingController(null);
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        echo json_encode($ctrl->approveByToken($token));
        exit;
    }

    if ($action === 'reject_token') {
        require_once __DIR__ . '/Controllers/BookingController.php';
        $ctrl = new BookingController(null);
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        $reason = $input['reason'] ?? '';
        echo json_encode($ctrl->rejectByToken($token, $reason));
        exit;
    }

    if ($action === 'sendEmailNotification') {
        echo json_encode($ctrl->sendEmailNotification());
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
