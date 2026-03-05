<?php

/**
 * Microsoft SSO Failover Handler for Yearly Activity
 * This script can be called by Nginx when the main portal container is down.
 */

require_once __DIR__ . '/../../core/Auth/MicrosoftAuthController.php';

if (!isset($_GET['action']) || empty($_GET['action'])) {
    $_GET['action'] = 'login';
}

$controller = new MicrosoftAuthController();
$controller->processRequest();
