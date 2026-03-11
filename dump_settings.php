<?php
require_once __DIR__ . '/core/Database/Database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ?");
$stmt->execute([2]);
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
