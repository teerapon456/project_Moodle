<?php

/**
 * Microsoft SSO Failover Handler
 * This script is called by Nginx when the main portal container is down.
 * Nginx rewrites /auth/microsoft/{action} to this script with ?action={action}.
 */

// Include the standard Microsoft Auth Controller from the core directory
require_once __DIR__ . '/core/Auth/MicrosoftAuthController.php';

// Ensure the action is set, defaulting to 'login' if missing
if (!isset($_GET['action']) || empty($_GET['action'])) {
    $_GET['action'] = 'login';
}

// Instantiate and process the request using the same logic as the main portal
$controller = new MicrosoftAuthController();
$controller->processRequest();
