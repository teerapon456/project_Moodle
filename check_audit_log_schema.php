<?php
require_once __DIR__ . '/core/Database/Database.php';
putenv("DB_HOST=127.0.0.1");
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->query("DESCRIBE news_attachments");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query("SELECT * FROM news_attachments LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
