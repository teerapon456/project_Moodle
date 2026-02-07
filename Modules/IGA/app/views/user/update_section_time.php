<?php
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// AuthZ: require logged-in associate/applicant only
require_login();
if (!has_role('associate') && !has_role('applicant')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}


// กำหนดพาธสำหรับ error log (ควรอยู่นอก public_html หรือ web root เพื่อความปลอดภัย)

// ตั้งค่าการรายงานข้อผิดพลาดและการบันทึก
ini_set('display_errors', 0); // ไม่แสดง error บนหน้าเว็บจริงเพื่อความปลอดภัย
ini_set('log_errors', 1);     // เปิดใช้งานการบันทึก error
ini_set('error_log', LOG_FILE); // กำหนดไฟล์สำหรับบันทึก error

$conn->query("SET time_zone = '+07:00'");

$response = ['success' => false, 'message' => ''];

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
    $time_spent_seconds = $input['time_spent_seconds'] ?? null;

    if ($attempt_id === null || $section_id === null || $time_spent_seconds === null) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วน';
        echo json_encode($response);
        exit();
    }

    try {
        // ตรวจสอบว่ามี record นี้อยู่แล้วหรือไม่
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM user_section_times WHERE attempt_id = ? AND section_id = ?");
        $stmt_check->bind_param("ii", $attempt_id, $section_id);
        $stmt_check->execute();
        $row_count = $stmt_check->get_result()->fetch_row()[0];
        $stmt_check->close();

        if ($row_count > 0) {
            // อัพเดทเวลาที่ใช้ไป
            $stmt_update = $conn->prepare("UPDATE user_section_times SET time_spent_seconds = ? WHERE attempt_id = ? AND section_id = ?");
            $stmt_update->bind_param("iii", $time_spent_seconds, $attempt_id, $section_id);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // สร้าง record ใหม่ (กรณีที่อาจจะยังไม่มี record นี้ใน DB เช่น ถ้าเพิ่งเข้า Section ใหม่และ AJAX ถูกส่งก่อน DB จะมี)
            $stmt_insert = $conn->prepare("INSERT INTO user_section_times (attempt_id, section_id, time_spent_seconds) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("iii", $attempt_id, $section_id, $time_spent_seconds);
            $stmt_insert->execute();
            $stmt_insert->close();
        }

        $response['success'] = true;
        $response['message'] = 'บันทึกเวลา Section สำเร็จ';

    } catch (Exception $e) {
        $response['message'] = 'เกิดข้อผิดพลาดในการบันทึกเวลา Section: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>