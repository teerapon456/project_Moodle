<?php
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');



ini_set('display_errors', 0); 
ini_set('log_errors', 1);     
ini_set('error_log', LOG_FILE); 

$conn->query("SET time_zone = '+07:00'");

$response = ['success' => false, 'message' => ''];

// AuthZ: require logged-in associate/applicant only
require_login();
if (!has_role('associate') && !has_role('applicant')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    // CSRF verification: token in header or JSON body
    $header_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $body_token = is_array($input) ? ($input['_csrf_token'] ?? null) : null;
    $csrf_token = $header_token ?: $body_token;
    if (!verify_csrf_token_value($csrf_token)) {
        $response['message'] = 'Invalid CSRF token';
        echo json_encode($response);
        exit();
    }

    $attempt_id = $input['attempt_id'] ?? null;
    $section_id = $input['section_id'] ?? null;
    $start_timestamp_js = $input['start_timestamp'] ?? null; 

    if ($attempt_id === null || $section_id === null || $start_timestamp_js === null) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วน';
        echo json_encode($response);
        exit();
    }

    $start_timestamp_db = date('Y-m-d H:i:s', $start_timestamp_js);

    try {
        $stmt = $conn->prepare("
            INSERT INTO iga_user_section_times (attempt_id, section_id, start_timestamp)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE start_timestamp = VALUES(start_timestamp)
        ");
        $stmt->bind_param("iis", $attempt_id, $section_id, $start_timestamp_db);
        $stmt->execute();
        $stmt->close();

        $response['success'] = true;
        $response['message'] = 'บันทึก start_timestamp ของ Section สำเร็จ';

    } catch (Exception $e) {
        $response['message'] = 'เกิดข้อผิดพลาดในการบันทึก start_timestamp ของ Section: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>