<?php
/**
 * HR Policy - API Handler
 */
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
startOptimizedSession();

require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Security/AuthMiddleware.php';
require_once __DIR__ . '/../../core/Security/CSRFMiddleware.php';
require_once __DIR__ . '/Controllers/HRPoliciesController.php';

header('Content-Type: application/json');

// Check authentication
AuthMiddleware::checkLogin();

// Initialize DB connection
try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception("Database connection failed");
    
    // Pass connection to Controller
    $controller = new HRPoliciesController($conn);
    $controller->handleRequest();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "API Error: " . $e->getMessage()]);
    exit;
}
