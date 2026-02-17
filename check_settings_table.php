<?php
require_once __DIR__ . '/core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->query("SHOW TABLES LIKE 'system_settings'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "Table system_settings exists.\n";
        $stmt = $conn->query("DESCRIBE system_settings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
    } else {
        echo "Table system_settings does NOT exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
