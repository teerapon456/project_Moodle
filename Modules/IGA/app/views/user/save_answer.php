<?php
// ไฟล์นี้สำหรับบันทึกคำตอบของผู้ใช้แต่ละข้อผ่าน AJAX
date_default_timezone_set('Asia/Bangkok');

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/functions.php';
$conn->query("SET time_zone = '+07:00'");

// เปิดใช้งาน Error Reporting ชั่วคราวสำหรับการ Debug (ลบออกเมื่อขึ้น Production)
// กำหนดพาธสำหรับ error log (ควรอยู่นอก public_html หรือ web root เพื่อความปลอดภัย)

// ตั้งค่าการรายงานข้อผิดพลาดและการบันทึก
ini_set('display_errors', 0); // ไม่แสดง error บนหน้าเว็บจริงเพื่อความปลอดภัย
ini_set('log_errors', 1);     // เปิดใช้งานการบันทึก error
ini_set('error_log', LOG_FILE); // กำหนดไฟล์สำหรับบันทึก error


// ตรวจสอบว่าผู้ใช้ล็อกอินและมีสิทธิ์เป็น 'user'
require_login();
if (!has_role('associate') && !has_role('applicant')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// ตรวจสอบว่าเป็น request แบบ POST และเป็น JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// CSRF verification: accept token from header or JSON body
$header_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
$body_token = is_array($data) ? ($data['_csrf_token'] ?? null) : null;
$csrf_token = $header_token ?: $body_token;
if (!verify_csrf_token_value($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit();
}

// ตรวจสอบข้อมูลที่จำเป็น
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || json_last_error() !== JSON_ERROR_NONE || !isset($data['attempt_id'], $data['question_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request. Missing data or invalid JSON. JSON Error: ' . json_last_error_msg() . ' Raw input: ' . $input]);
    exit();
}

$attempt_id = $data['attempt_id'];
$question_id = $data['question_id'];
$user_answer_text = $data['user_answer_text'] ?? ''; // ใช้ค่าว่างถ้าไม่มีการส่งมา (สำหรับกรณี short_answer ที่ยังไม่ได้พิมพ์)
$user_id = $_SESSION['user_id'];

try {
    // 1. ตรวจสอบให้แน่ใจว่า attempt_id และ question_id เป็นของผู้ใช้ปัจจุบันและถูกต้อง
    //    และ attempt นั้นยังไม่เสร็จสิ้น เพื่อป้องกันการส่งข้อมูลปลอม
    $stmt = $conn->prepare("
        SELECT uta.test_id, t.test_name, q.question_type, q.score AS question_max_score
        FROM user_test_attempts uta
        JOIN tests t ON uta.test_id = t.test_id
        JOIN questions q ON q.question_id = ?
        WHERE uta.attempt_id = ? AND uta.user_id = ? AND uta.is_completed = 0
    ");
    if (!$stmt) {
        error_log("save_answer.php: Prepare statement (check attempt/question) failed: " . $conn->error);
        throw new Exception("Database prepare error. Please check server logs.");
    }
    $stmt->bind_param("iii", $question_id, $attempt_id, $user_id);
    $stmt->execute();
    $question_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$question_info) {
        echo json_encode(['success' => false, 'message' => 'Invalid attempt, question, or test already completed. Cannot save answer.']);
        exit();
    }

    $question_type = $question_info['question_type'];
    $score_earned = null; // เริ่มต้นให้เป็น null สำหรับคะแนนที่ได้ (สำหรับอัตนัย)
    $is_correct = null; // เริ่มต้นให้เป็น null สำหรับสถานะความถูกต้อง (สำหรับอัตนัย/ยังไม่ตรวจ)

    // 2. ตรวจสอบประเภทคำถามเพื่อคำนวณคะแนนเบื้องต้น (สำหรับปรนัย/จริง-เท็จ)
    if ($question_type === 'multiple_choice' || $question_type === 'true_false') {
        // ดึงตัวเลือกที่ถูกต้อง
        $correct_option_stmt = $conn->prepare("SELECT option_id FROM question_options WHERE question_id = ? AND is_correct = 1");
        if (!$correct_option_stmt) {
            error_log("save_answer.php: Prepare correct_option_stmt failed: " . $conn->error);
            throw new Exception("Database prepare error for options. Please check server logs.");
        }
        $correct_option_stmt->bind_param("i", $question_id);
        $correct_option_stmt->execute();
        $correct_option_row = $correct_option_stmt->get_result()->fetch_assoc();
        $correct_option_stmt->close();

        $correct_option_id = $correct_option_row ? $correct_option_row['option_id'] : null;

        // เปรียบเทียบคำตอบ
        if ((string)$user_answer_text === (string)$correct_option_id) {
            $is_correct = 1; // ถูกต้อง
            $score_earned = $question_info['question_max_score']; // ได้คะแนนเต็ม
        } else {
            $is_correct = 0; // ผิด
            $score_earned = 0; // ได้ 0 คะแนน
        }
    }
    // สำหรับ short_answer, score_earned และ is_correct จะเป็น null (ต้องตรวจมือ)

    // 3. บันทึกหรืออัพเดทคำตอบของผู้ใช้ในตาราง user_answers
    //    ใช้ INSERT ... ON DUPLICATE KEY UPDATE สำหรับ MySQL เพื่อจัดการทั้ง Insert และ Update ใน Query เดียว
    //    ต้องแน่ใจว่าตาราง user_answers มี UNIQUE KEY (attempt_id, question_id)
    $stmt = $conn->prepare("
        INSERT INTO user_answers (attempt_id, question_id, user_answer_text, is_correct, score_earned)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            user_answer_text = VALUES(user_answer_text),
            is_correct = VALUES(is_correct),
            score_earned = VALUES(score_earned),
            updated_at = CURRENT_TIMESTAMP()
    ");
    if (!$stmt) {
        error_log("save_answer.php: Prepare statement for insert/update failed: " . $conn->error);
        throw new Exception("Database prepare error for user_answers. Please check server logs.");
    }
    // 'iisid' -> i: integer, s: string, d: double (สำหรับ score_earned ที่อาจเป็นทศนิยม)
    $stmt->bind_param("iisid", $attempt_id, $question_id, $user_answer_text, $is_correct, $score_earned);
    $stmt->execute();

    if ($stmt->affected_rows > 0 || $stmt->insert_id > 0) {
        // 4. อัพเดตคะแนนรวมใน user_test_attempts ทันที
        //    (เฉพาะคะแนนที่สามารถตรวจอัตโนมัติได้)
        //    ใช้ COALESCE เพื่อให้แน่ใจว่าได้ 0 ถ้า SUM ไม่มีค่า
        $total_score_update_stmt = $conn->prepare("
            UPDATE user_test_attempts
            SET total_score = (SELECT COALESCE(SUM(score_earned), 0) FROM user_answers WHERE attempt_id = ?)
            WHERE attempt_id = ?
        ");
        if (!$total_score_update_stmt) {
            error_log("save_answer.php: Prepare total_score_update_stmt failed: " . $conn->error);
            throw new Exception("Database prepare error for total score update. Please check server logs.");
        }
        $total_score_update_stmt->bind_param("ii", $attempt_id, $attempt_id);
        $total_score_update_stmt->execute();
        $total_score_update_stmt->close();

        // 5. เก็บคำตอบใน session ด้วย (เพื่อให้ refresh หน้าแล้วข้อมูลไม่หายจาก frontend)
        $_SESSION['test_answers'][$attempt_id] = $_SESSION['test_answers'][$attempt_id] ?? [];
        $_SESSION['test_answers'][$attempt_id][$question_id] = $user_answer_text;

        echo json_encode(['success' => true, 'message' => 'Answer saved successfully. Affected Rows: ' . $stmt->affected_rows . ' Insert ID: ' . $stmt->insert_id]);
    } else {
        echo json_encode(['success' => true, 'message' => 'No changes detected or answer already up-to-date. Affected Rows: ' . $stmt->affected_rows]);
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("save_answer.php: General error: " . $e->getMessage() . " on line " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>