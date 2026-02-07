<?php
// ไฟล์นี้สำหรับจัดการการส่งแบบทดสอบของผู้ใช้
date_default_timezone_set('Asia/Bangkok');

// ไม่จำเป็นต้องกำหนด Header เป็น JSON อีกต่อไป
require_once __DIR__ . '/../../includes/functions.php';
$conn->query("SET time_zone = '+07:00'");

// ตั้งค่าการรายงานข้อผิดพลาดและการบันทึก
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

// ตรวจสอบว่าผู้ใช้ล็อกอินและมีสิทธิ์เป็น 'user'
require_login();
if (!has_role('associate') && !has_role('applicant')) {
    set_alert(get_text('error_unauthorized_access'), "danger");
    redirect_to('/user/');
    exit();
}

// ตรวจสอบว่าเป็น request แบบ POST และมี attempt_id
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['attempt_id'])) {
    set_alert(get_text('invalid_request'), "danger");
    redirect_to('/user/');
    exit();
}

// CSRF verification from POST form
if (!verify_csrf_token()) {
    set_alert(get_text('invalid_csrf_token') ?: 'Invalid CSRF token', 'danger');
    redirect_to('/user/');
    exit();
}

// ในกรณีที่ไม่มีการใช้ JSON ให้เปลี่ยนมาใช้ $_POST แทน
$attempt_id = $_POST['attempt_id'];
$user_id = $_SESSION['user_id'];
$end_time = date('Y-m-d H:i:s');

try {
    // 1. ตรวจสอบสถานะการทำแบบทดสอบ
    $stmt = $conn->prepare("SELECT uta.test_id, uta.is_completed, uta.start_time, uta.time_spent_seconds, t.duration_minutes, t.show_result_immediately
                           FROM iga_user_test_attempts uta
                           JOIN iga_tests t ON uta.test_id = t.test_id
                           WHERE uta.attempt_id = ? AND uta.user_id = ?");
    $stmt->bind_param("is", $attempt_id, $user_id);
    $stmt->execute();
    $attempt_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$attempt_info) {
        set_alert(get_text('invalid_attempt_id'), "danger");
        redirect_to('/user');
        exit();
    }

    if ($attempt_info['is_completed'] == 1) {
        set_alert(get_text('test_already_submitted'), "warning");
        redirect_to('/user');
        exit();
    }

    $test_id = $attempt_info['test_id'];
    $test_duration_minutes = $attempt_info['duration_minutes'];
    $show_result_immediately = $attempt_info['show_result_immediately'];

    // 2. คำนวณคะแนนสุดท้าย
    $stmt = $conn->prepare("
        SELECT SUM(score_earned) AS final_score
        FROM iga_user_answers
        WHERE attempt_id = ? AND is_correct IS NOT NULL
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result_score = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $final_score = $result_score['final_score'] ?? 0.00;

    // 3. คำนวณเวลาที่ใช้ไป
    $time_spent_actual = 0;
    if ($attempt_info['time_spent_seconds'] !== null) {
        $time_spent_actual = $attempt_info['time_spent_seconds'];
    } else {
        $start_timestamp = strtotime($attempt_info['start_time']);
        $end_timestamp = strtotime($end_time);
        $time_spent_actual = $end_timestamp - $start_timestamp;
    }

    if ($test_duration_minutes > 0) {
        $max_duration_seconds = $test_duration_minutes * 60;
        if ($time_spent_actual > $max_duration_seconds) {
            $time_spent_actual = $max_duration_seconds;
        }
    }


    // 4. อัพเดทสถานะการทำแบบทดสอบให้เสร็จสิ้น
    $stmt = $conn->prepare("
        UPDATE iga_user_test_attempts
        SET
            end_time = ?,
            total_score = ?,
            is_completed = 1,
            time_spent_seconds = ?
        WHERE attempt_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ssiis", $end_time, $final_score, $time_spent_actual, $attempt_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // ล้างข้อมูล session ที่เกี่ยวข้องกับการทำแบบทดสอบนี้
        unset($_SESSION['current_attempt_id']);
        unset($_SESSION['attempt_start_time']);
        unset($_SESSION['time_spent_at_resume']);
        unset($_SESSION['test_answers'][$attempt_id]);

        set_alert(get_text("test_completed_alert"), "success");

        if ($show_result_immediately) {
            // ใช้ POST form redirect ที่เร็ว
            $_SESSION['view_result_attempt_id'] = $attempt_id;
            
            // Clear any previous output and set proper headers
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: text/html; charset=UTF-8');
            
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
            echo '<form id="redirect_form" action="/user/results" method="post">';
            echo '<input type="hidden" name="attempt_id" value="' . htmlspecialchars($attempt_id) . '">';
            echo '</form>';
            echo '<script>document.getElementById("redirect_form").submit();</script>';
            echo '</body></html>';
            exit();
        } else {
            redirect_to('/user');
        }
    } else {
        $stmt->close();
        set_alert(get_text('submit_test_error'), "danger");
        redirect_to('/user');
    }
} catch (Exception $e) {
    error_log("Database error in submit_test.php: " . $e->getMessage());
    set_alert(get_text('generic_error'), "danger");
    redirect_to('/user');
}
