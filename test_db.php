<?php
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'myhr_portal';
$user = getenv('DB_USER') ?: 'myhr_user';
$pass = getenv('DB_PASS') ?: 'MyHR_S3cur3_P@ss_2026!';

echo "Testing connection to $host -> $db with user $user\n";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    echo "Connection successful!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
