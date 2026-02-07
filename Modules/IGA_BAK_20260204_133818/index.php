<?php

/**
 * IGA Module Entry Point
 */

// Core Config
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Config/SessionConfig.php';

// Enable Errors for Dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start Optimized Session (Matches Main Portal)
startOptimizedSession();


// Autoloader for IGA Module
spl_autoload_register(function ($class) {
    // Determine base directory relative to this file (Modules/IGA)
    $base_dir = __DIR__ . '/';

    // Simple mapping for Controllers
    if (strpos($class, 'Controller') !== false) {
        $file = $base_dir . 'Controllers/' . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Services
    if (strpos($class, 'Service') !== false) {
        $file = $base_dir . 'Services/' . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Database Connection
require_once __DIR__ . '/../../core/Database/Database.php';
$db = new Database();
$pdo = $db->getConnection();

// Helper: Get IGA Permissions
function getIGAPermissions($roleId, $pdo)
{
    if (!$pdo) return ['can_view' => 0, 'can_edit' => 0, 'can_manage' => 0, 'can_delete' => 0];

    // Find Module ID for 'IGA' (assuming code or path)
    // We try to find by path 'Modules/IGA' or code 'IGA'
    $stmt = $pdo->prepare("SELECT id FROM core_modules WHERE path LIKE '%Modules/IGA%' OR code = 'IGA' LIMIT 1");
    $stmt->execute();
    $moduleId = $stmt->fetchColumn();

    if (!$moduleId) return ['can_view' => 0, 'can_edit' => 0, 'can_manage' => 0, 'can_delete' => 0];

    $_SESSION['current_module_id'] = $moduleId; // Set for ModuleController

    $stmt = $pdo->prepare("
        SELECT can_view, can_edit, can_manage, can_delete 
        FROM core_module_permissions 
        WHERE module_id = ? AND role_id = ?
    ");
    $stmt->execute([$moduleId, $roleId]);
    $perms = $stmt->fetch(PDO::FETCH_ASSOC);

    return $perms ?: ['can_view' => 0, 'can_edit' => 0, 'can_manage' => 0, 'can_delete' => 0];
}

// Determine Controller based on Permissions if not specified
$controllerParam = $_GET['controller'] ?? null;
$action = $_GET['action'] ?? 'index';

if ($controllerParam) {
    $controllerName = ucfirst($controllerParam) . 'Controller';
} else {
    // Check Permissions
    $roleId = $_SESSION['user']['role_id'] ?? 0;
    $perms = getIGAPermissions($roleId, $pdo);

    if ($perms['can_manage'] || $perms['can_edit']) {
        $controllerName = 'AdminController';
    } else {
        $controllerName = 'ExamController';
    }
}

// Check if controller exists
if (class_exists($controllerName)) {
    $controller = new $controllerName();
    $controller->processRequest();
} else {
    http_response_code(404);
    die("Controller '$controllerName' not found.");
}
