<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure correct path to database connection and functions
require_once __DIR__ . '/../includes/functions.php'; // include config + autoload + $conn

header('Content-Type: application/json');

$user_type = $_GET['user_type'] ?? 'all';

$sql = "
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS count 
    FROM users
    WHERE role_id IN (
        SELECT role_id 
        FROM roles
        WHERE 
            (role_name = 'associate' AND ?) OR 
            (role_name = 'applicant' AND ?) OR
            (TRUE AND ?)
    )
    GROUP BY month 
    ORDER BY month
";

$params = [];
$types = "";

if ($user_type === 'associate') {
    $params = [1, 0, 0];
    $types = "iii";
} elseif ($user_type === 'applicant') {
    $params = [0, 1, 0];
    $types = "iii";
} else { // 'all'
    $params = [1, 1, 1];
    $types = "iii";
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
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