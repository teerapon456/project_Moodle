<?php

/**
 * db_connect.php
 * Connects to the shared Portal database using environment variables.
 */

// Explicitly use the Portal's DB credentials from environment
$host    = getenv('DB_HOST')    ?: '127.0.0.1';
$db      = getenv('DB_NAME')    ?: 'myhr_portal';
$user    = getenv('DB_USER')    ?: 'myhr_user';
$pass    = getenv('DB_PASS')    ?: 'MyHR_S3cur3_P@ss_2026!'; // Fallback for dev if env missing
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$port    = (int)(getenv('DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset($charset);
} catch (Exception $e) {
    error_log("[IGA DB Connection Error] " . $e->getMessage());
    die("Database connection failed.");
}
