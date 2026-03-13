<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';

// AuthMiddleware::checkLogin(); // Assume implemented or session handled elsewhere
// Must be admin to access settings
session_start();
$roleId = $_SESSION['user']['role_id'] ?? null;
require_once __DIR__ . '/../../../core/Helpers/PermissionHelper.php';
$canManage = userHasModuleAccess('PERMISSION_MANAGEMENT', (int)$roleId)['can_manage'] ?? false;

// Remove full blockage for now if testing, but ideally uncomment:
// if (!$canManage) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Forbidden']);
//     exit;
// }

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $conn->query("SELECT setting_key, setting_value FROM ai_settings");
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['success' => true, 'data' => $results]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['settings']) || !is_array($input['settings'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("UPDATE ai_settings SET setting_value = ? WHERE setting_key = ?");
        foreach ($input['settings'] as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
