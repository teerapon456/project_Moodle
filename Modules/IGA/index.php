<?php
// Modules/IGA/main.php

// 1. Session & Config
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Load Core Database
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';

// 3. Create Connection
try {
    $db = new \Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// 4. Routing Logic
$page = $_GET['page'] ?? 'login'; // Default to login
$action = $_GET['action'] ?? 'index';

// 5. Front Controller Switch
switch ($page) {
    case 'login':
        require_once __DIR__ . '/Controllers/AuthController.php';
        $controller = new \IGA\Controllers\AuthController($conn);
        if ($action === 'check') {
            $controller->login();
        } elseif ($action === 'logout') {
            $controller->logout();
        } else {
            $controller->showLogin();
        }
        break;

    case 'dashboard':
        // Ensure logged in
        if (!isset($_SESSION['iga_user_id'])) {
            header("Location: ?page=login");
            exit;
        }
        require_once __DIR__ . '/Views/dashboard.php';
        break;

    case 'register':
        require_once __DIR__ . '/Controllers/AuthController.php';
        $controller = new \IGA\Controllers\AuthController($conn);
        if ($action === 'submit') {
            $controller->register();
        } else {
            $controller->showRegister();
        }
        break;

    case 'activate':
        require_once __DIR__ . '/Controllers/AuthController.php';
        $controller = new \IGA\Controllers\AuthController($conn);
        $controller->activate();
        break;

    default:
        // 404
        echo "Page not found.";
        break;
}
