<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure correct path to database connection and functions
require_once __DIR__ . '/../../includes/functions.php'; // include config + autoload + $conn

header('Content-Type: application/json');

$test_id = $_GET['test_id'] ?? 'all';

$sql = "
    SELECT t.test_name, AVG(uta.total_score) AS average_score, t.test_no
    FROM iga_user_test_attempts uta
    JOIN iga_tests t ON uta.test_id = t.test_id
    WHERE uta.is_completed = 1
";
$params = [];
$types = "";

if ($test_id !== 'all') {
    $sql .= " AND t.test_no = ?";
    $params[] = $test_id;
    $types = "s";
}

$sql .= " GROUP BY t.test_id ORDER BY t.test_no ASC";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>