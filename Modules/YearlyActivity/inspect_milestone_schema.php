<?php
require_once __DIR__ . '/../../core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "Inspecting ya_milestones schema...\n";
    $stmt = $conn->query("DESCRIBE ya_milestones");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "Column: " . $col['Field'] . " | Type: " . $col['Type'] . " | Null: " . $col['Null'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
