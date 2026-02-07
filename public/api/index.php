<?php

/**
 * API Router - redirects all API requests to the main routes.php
 * This file should be placed at public/api/index.php
 */

// Forward the request to the main routes.php
require_once __DIR__ . '/../../routes.php';
