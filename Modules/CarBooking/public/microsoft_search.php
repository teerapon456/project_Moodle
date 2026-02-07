<?php
// Lightweight proxy to MicrosoftAuthController searchUsers for car booking UI
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
if (function_exists('startOptimizedSession')) {
    startOptimizedSession();
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

require_once __DIR__ . '/../../../core/Auth/MicrosoftAuthController.php';

// Force action=search so controller routes correctly
$_GET['action'] = 'search';

$controller = new MicrosoftAuthController();
$controller->processRequest();
