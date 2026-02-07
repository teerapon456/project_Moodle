<?php
/**
 * Proxy router for environments where the webserver docroot is `public/`.
 *
 * This file simply includes the main `routes.php` in project root so
 * requests like `/public/routes.php/hrnews/published` will be handled
 * by the existing router without duplicating logic.
 */

// Ensure working directory and include path are correct
require_once __DIR__ . '/../routes.php';
