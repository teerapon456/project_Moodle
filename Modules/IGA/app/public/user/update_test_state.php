<?php
// ไฟล์นี้สำหรับบันทึกสถานะการทำแบบทดสอบของผู้ใช้ผ่าน AJAX (เช่น ตำแหน่งข้อปัจจุบัน, เวลาที่ใช้ไป)
date_default_timezone_set('Asia/Bangkok');

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/functions.php';
$conn->query("SET time_zone = '+07:00'");

// กำหนดพาธสำหรับ error log (ควรอยู่นอก public_html หรือ web root เพื่อความปลอดภัย)
// Error handling configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

// ตรวจสอบว่าผู้ใช้ล็อกอินและมีสิทธิ์เป็น 'user'
require_login();
if (!has_role('associate') && !has_role('applicant')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// ตรวจสอบว่าเป็น request แบบ POST และเป็น JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    // CSRF verification for JSON endpoints
    $header_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $body_token = is_array($data) ? ($data['_csrf_token'] ?? null) : null;
    $csrf_token = $header_token ?: $body_token;
    if (!verify_csrf_token_value($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
        exit();
    }

    // Validate JSON and required fields
    if (json_last_error() !== JSON_ERROR_NONE ||
        !isset($data['attempt_id'], $data['current_question_index'], $data['time_spent_seconds'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request. Missing data or invalid JSON.']);
        exit();
    }

    $attempt_id = $data['attempt_id'];
    $current_question_index = $data['current_question_index'];
    $time_spent_seconds_overall = $data['time_spent_seconds']; // เปลี่ยนชื่อตัวแปรให้ชัดเจนว่าเป็นเวลารวม

// **NEW:** รับข้อมูล section_id และ time_spent_in_section
$section_id = $data['section_id'] ?? null;
$time_spent_in_section = $data['time_spent_in_section'] ?? null;

$user_id = $_SESSION['user_id'];

try {
    // 1. ตรวจสอบให้แน่ใจว่า attempt_id เป็นของผู้ใช้ปัจจุบันและยังไม่เสร็จสิ้น
    $stmt = $conn->prepare("SELECT attempt_id FROM iga_user_test_attempts WHERE attempt_id = ? AND user_id = ? AND is_completed = 0");
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    $stmt->bind_param("is", $attempt_id, $user_id);
    $stmt->execute();
    $existing_attempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing_attempt) {
        echo json_encode(['success' => false, 'message' => 'Invalid attempt or test already completed.']);
        exit();
    }

    // 2. ตรวจสอบค่าเวลาที่ส่งมาและเวลาที่มีอยู่ เพื่อป้องกันการอัพเดทเวลาย้อนหลัง
    $check_stmt = $conn->prepare("SELECT time_spent_seconds FROM iga_user_test_attempts WHERE attempt_id = ?");
    $check_stmt->bind_param("i", $attempt_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    // ใช้ค่าที่มากกว่าระหว่างเวลาที่ส่งมากับเวลาที่บันทึกไว้ (ป้องกันการอัพเดทย้อนหลัง)
    $final_time_spent = $time_spent_seconds_overall;
    if ($check_result && isset($check_result['time_spent_seconds'])) {
        $final_time_spent = max($time_spent_seconds_overall, (int)$check_result['time_spent_seconds']);
    }
    
    // 2. อัพเดท current_question_index และ time_spent_seconds (Overall Test Time) ในตาราง user_test_attempts
    $stmt = $conn->prepare("
        UPDATE iga_user_test_attempts
        SET
            current_question_index = ?,
            time_spent_seconds = ?,
            updated_at = CURRENT_TIMESTAMP()
        WHERE attempt_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Database error occurred");
    }
    $stmt->bind_param("iii", $current_question_index, $final_time_spent, $attempt_id);
    $stmt->execute();
    $stmt->close(); // ปิด statement แรกก่อนเริ่ม statement ใหม่

    // **NEW LOGIC START:** 3. จัดการเวลาที่ใช้ไปในแต่ละ Section (บันทึกลงตาราง user_section_times)
    if (!is_null($section_id) && !is_null($time_spent_in_section)) {
        // ตรวจสอบว่ามีบันทึกเวลาสำหรับ Section นี้ใน attempt นี้อยู่แล้วหรือไม่
        $stmt_check_section_time = $conn->prepare("SELECT section_time_id FROM iga_user_section_times WHERE attempt_id = ? AND section_id = ?");
        if (!$stmt_check_section_time) {
            throw new Exception("Database error occurred");
        }
        $stmt_check_section_time->bind_param("ii", $attempt_id, $section_id);
        $stmt_check_section_time->execute();
        $result_check = $stmt_check_section_time->get_result();

        if ($result_check->num_rows > 0) {
            // ถ้ามีอยู่แล้ว ให้อัพเดทเวลาที่ใช้ไป
            $stmt_update_section_time = $conn->prepare("UPDATE iga_user_section_times SET time_spent_seconds = ? WHERE attempt_id = ? AND section_id = ?");
            if (!$stmt_update_section_time) {
                throw new Exception("Database error occurred");
            }
            $stmt_update_section_time->bind_param("iii", $time_spent_in_section, $attempt_id, $section_id);
            $stmt_update_section_time->execute();
            $stmt_update_section_time->close();
        } else {
            // ถ้ายังไม่มี ให้เพิ่มบันทึกใหม่
            $stmt_insert_section_time = $conn->prepare("INSERT INTO iga_user_section_times (attempt_id, section_id, time_spent_seconds) VALUES (?, ?, ?)");
            if (!$stmt_insert_section_time) {
                throw new Exception("Database error occurred");
            }
            $stmt_insert_section_time->bind_param("iii", $attempt_id, $section_id, $time_spent_in_section);
            $stmt_insert_section_time->execute();
            $stmt_insert_section_time->close();
        }
        $stmt_check_section_time->close(); // ปิด statement ตรวจสอบ
    }
    // **NEW LOGIC END**
    
    // 4. อัพเดทใน session (ไม่จำเป็นต้องอัพเดท session สำหรับ section_times_data โดยตรงที่นี่)
    $_SESSION['time_spent_at_resume'] = $time_spent_seconds_overall;
    $_SESSION['current_question_index'] = $current_question_index;

    echo json_encode(['success' => true, 'message' => 'สถานะแบบทดสอบและเวลาส่วนอัพเดทแล้ว']);

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
} // close POST check