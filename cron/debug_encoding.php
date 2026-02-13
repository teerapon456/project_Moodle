<?php
require_once __DIR__ . '/../core/Database/Database.php';

$db = new Database();
$conn = $db->getConnection();

// Force UTF8 just in case
$conn->exec("SET NAMES 'utf8mb4'");

$stmt = $conn->prepare("SELECT fullname FROM users WHERE fullname REGEXP '[[:alpha:]]' LIMIT 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Fetched Name: " . $user['fullname'] . "\n";
echo "Hex: " . bin2hex($user['fullname']) . "\n";
