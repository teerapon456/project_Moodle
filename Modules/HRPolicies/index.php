<?php
/**
 * HR Policy Module - Entry Point
 * Refactored to match Car Booking Architecture
 */

// Use optimized session configuration
require_once __DIR__ . '/../../core/Config/SessionConfig.php';
startOptimizedSession();

// Load UrlHelper for dynamic paths
require_once __DIR__ . '/../../core/Helpers/UrlHelper.php';
use Core\Helpers\UrlHelper;

$basePath = UrlHelper::getBasePath();
$linkBase = UrlHelper::getLinkBase();
$assetBase = UrlHelper::getAssetBase();

// Check authentication
require_once __DIR__ . '/../../core/Security/AuthMiddleware.php';
$user = AuthMiddleware::checkLogin($linkBase);

// Release session lock
session_write_close();

// Dependencies
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Helpers/PermissionHelper.php';

// Check permissions
if ((isset($user['user_active']) && !$user['user_active']) || (isset($user['role_active']) && !$user['role_active'])) {
    header('Location: ' . $linkBase . 'public/index.php?error=role_inactive');
    exit;
}

$modulePerm = userHasModuleAccess('HR_POLICIES', (int)$user['role_id']);
$canManage = !empty($modulePerm['can_manage']) || !empty($modulePerm['can_edit']) || userHasModuleAccess('PERMISSION_MANAGEMENT', (int)$user['role_id'])['can_manage'];
$profilePic = $user['profile_picture'] ?? null;

$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Policies & Regulations</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= $assetBase ?>assets/css/tailwind.css">
    <script>
        window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
    </script>
    <script src="<?= $assetBase ?>assets/js/i18n.js"></script>
    <style>
        body { font-family: 'Kanit', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php include __DIR__ . '/../../public/includes/header.php'; ?>

    <?php 
    // Route to appropriate view
    switch ($page) {
        case 'dashboard':
        default:
            include __DIR__ . '/Views/dashboard.php';
            break;
    }
    ?>

    <script src="<?= $assetBase ?>assets/js/csrf.js"></script>
    <script src="<?= $assetBase ?>assets/js/notifications.js"></script>
</body>
</html>
