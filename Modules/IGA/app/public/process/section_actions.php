<?php
// process/section-actions  -> map มาที่ไฟล์นี้ (เช่น section_actions.php)
// timezone
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// safety: db timezone
if (isset($conn) && $conn) {
    $conn->query("SET time_zone = '+07:00'");
}

// ---- Auth: ให้สอดคล้องกับหน้าเพจ (admin หรือ editor) ----
require_login();
$is_Super_user_Recruitment = has_role('Super_user_Recruitment');
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
  set_alert(get_text('alert_no_admin_permission'), "danger");
  header("Location: /login");
  exit();
}

// ติดตาม user_id (สำหรับ trigger/audit ถ้ามี)
if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (string)($_SESSION['user_id'] ?? '');
    $uid = $conn->real_escape_string($current_user_id);
    $conn->query("SET @user_id = '{$uid}'"); // ครอบ quote เสมอ
    // หรือถ้าไม่ได้ใช้ @user_id ที่อื่นจริงๆ ให้ลบทั้งบรรทัดนี้ทิ้งได้เลย

} else {
    if ($conn) $conn->query("SET @user_id = NULL");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = "Invalid request method.";
    echo json_encode($response);
    exit();
}

// ---- CSRF ----
if (!verify_csrf_token()) {
    $response['message'] = get_text('security_error_csrf');
    echo json_encode($response);
    exit();
}

// ---- Inputs ----
$action          = $_POST['action']        ?? '';
$test_id         = filter_var($_POST['test_id']        ?? null, FILTER_VALIDATE_INT);
$section_id      = filter_var($_POST['section_id']     ?? null, FILTER_VALIDATE_INT);
$section_name    = trim($_POST['section_name'] ?? '');
$description     = trim($_POST['description']  ?? '');
$section_order   = filter_var($_POST['section_order']  ?? null, FILTER_VALIDATE_INT);
$duration_minutes = filter_var($_POST['duration_minutes'] ?? 0, FILTER_VALIDATE_INT);

// sanitize
if ($test_id === false || $test_id <= 0) {
    $response['message'] = get_text('alert_invalid_test_id');
    echo json_encode($response);
    exit();
}
if ($duration_minutes === false || $duration_minutes < 0) {
    $duration_minutes = 0;
}
if ($section_order === false || $section_order === null || $section_order < 1) {
    // ใน add/edit จะเช็กอีกครั้ง แต่เผื่อไว้
    $section_order = 1;
}

try {
    // ----- Validate เฉพาะ add/edit -----
    if ($action === 'add' || $action === 'edit') {
        if ($section_name === '') {
            $response['message'] = get_text('alert_section_name_required');
            echo json_encode($response);
            exit();
        }
        if ($section_order < 1) {
            $response['message'] = get_text('alert_invalid_section_order');
            echo json_encode($response);
            exit();
        }

        // ป้องกัน order ซ้ำใน test เดียวกัน (ยกเว้นตัวเองตอน edit)
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM iga_sections WHERE test_id = ? AND section_order = ? AND section_id != ?");
        $section_id_check = ($action === 'edit' && $section_id) ? $section_id : 0;
        $stmt_check->bind_param("iii", $test_id, $section_order, $section_id_check);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            $response['message'] = get_text('alert_section_order_exists');
            echo json_encode($response);
            exit();
        }

        // ---- จำกัดเวลารวมเฉพาะกรณี test มีการกำหนด duration (ไม่ใช่ NULL) ----
        $stmt_test = $conn->prepare("SELECT duration_minutes FROM iga_tests WHERE test_id = ?");
        $stmt_test->bind_param("i", $test_id);
        $stmt_test->execute();
        $stmt_test->bind_result($test_total_duration);
        $stmt_test->fetch();
        $stmt_test->close();

        // ถ้า test_total_duration เป็นตัวเลข (ไม่ใช่ NULL) จึงบังคับรวมเวลา
        if ($test_total_duration !== null) {
            $stmt_duration = $conn->prepare("SELECT SUM(duration_minutes) FROM iga_sections WHERE test_id = ? AND section_id != ?");
            $stmt_duration->bind_param("ii", $test_id, $section_id_check);
            $stmt_duration->execute();
            $stmt_duration->bind_result($current_total_duration);
            $stmt_duration->fetch();
            $stmt_duration->close();

            $current_total_duration = $current_total_duration ?? 0;
            $new_total_duration = $current_total_duration + $duration_minutes;

            if ($new_total_duration > (int)$test_total_duration) {
                $response['message'] = sprintf(get_text('alert_total_duration_exceeds'), (int)$test_total_duration);
                echo json_encode($response);
                exit();
            }
        }
    }

    // ----- ทำงานหลัก -----
    $conn->begin_transaction();

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO iga_sections (test_id, section_name, description, section_order, duration_minutes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issii", $test_id, $section_name, $description, $section_order, $duration_minutes);

        if (!$stmt->execute()) {
            throw new Exception(get_text('alert_error_add_section') . ": " . $stmt->error);
        }
        $stmt->close();

        $response['success'] = true;
        $response['message'] = get_text('alert_section_added_success');
    } elseif ($action === 'edit') {
        if ($section_id === false || $section_id <= 0) {
            throw new Exception(get_text('alert_invalid_section_id'));
        }

        $stmt = $conn->prepare("UPDATE iga_sections SET section_name = ?, description = ?, section_order = ?, duration_minutes = ? WHERE section_id = ? AND test_id = ?");
        $stmt->bind_param("ssisii", $section_name, $description, $section_order, $duration_minutes, $section_id, $test_id);

        if (!$stmt->execute()) {
            throw new Exception(get_text('alert_error_edit_section') . ": " . $stmt->error);
        }

        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = get_text('alert_section_updated_success');
        } else {
            // ไม่เปลี่ยนแปลงค่าใด ๆ
            $response['success'] = true; // ถือว่าไม่ error
            $response['message'] = get_text('alert_no_change_section');
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        if ($section_id === false || $section_id <= 0) {
            throw new Exception(get_text('alert_invalid_section_id'));
        }

        $stmt = $conn->prepare("DELETE FROM iga_sections WHERE section_id = ? AND test_id = ?");
        $stmt->bind_param("ii", $section_id, $test_id);

        if (!$stmt->execute()) {
            throw new Exception(get_text('alert_error_delete_section') . ": " . $stmt->error);
        }
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = get_text('alert_section_deleted_success');
        } else {
            $response['message'] = get_text('alert_section_not_found');
        }
        $stmt->close();
    } else {
        throw new Exception(get_text('alert_invalid_action'));
    }

    $conn->commit();

    // ----- สรุปเวลา (สำหรับข้อความแจ้งเตือนเสริม) -----
    $stmt_test = $conn->prepare("SELECT duration_minutes FROM iga_tests WHERE test_id = ?");
    $stmt_test->bind_param("i", $test_id);
    $stmt_test->execute();
    $stmt_test->bind_result($test_total_duration);
    $stmt_test->fetch();
    $stmt_test->close();

    $stmt_duration = $conn->prepare("SELECT SUM(duration_minutes) FROM iga_sections WHERE test_id = ?");
    $stmt_duration->bind_param("i", $test_id);
    $stmt_duration->execute();
    $stmt_duration->bind_result($current_total_duration);
    $stmt_duration->fetch();
    $stmt_duration->close();

    $current_total_duration = $current_total_duration ?? 0;

    // ถ้า test กำหนดเวลา เราถึงคำนวณ remaining
    if ($test_total_duration !== null) {
        $remaining_duration = (int)$test_total_duration - (int)$current_total_duration;
    } else {
        $remaining_duration = null; // ไม่มีลิมิต
    }

    $response['total_test_duration']  = $test_total_duration;
    $response['total_used_duration']  = (int)$current_total_duration;
    $response['remaining_duration']   = $remaining_duration;

    // ปรับข้อความสรุป (ถ้า test มีลิมิต)
    if ($test_total_duration !== null) {
        if ($action === 'add') {
            $response['message'] = sprintf(get_text('alert_section_added_with_duration'),  (int)$current_total_duration, (int)$remaining_duration, (int)$test_total_duration);
        } elseif ($action === 'edit') {
            $response['message'] = sprintf(get_text('alert_section_updated_with_duration'), (int)$current_total_duration, (int)$remaining_duration, (int)$test_total_duration);
        } elseif ($action === 'delete') {
            $response['message'] = sprintf(get_text('alert_section_deleted_with_duration'), (int)$current_total_duration, (int)$remaining_duration, (int)$test_total_duration);
        }
    }
} catch (Exception $e) {
    if ($conn && $conn->errno === 0) {
        // ถ้าเริ่ม transaction ไปแล้วให้ rollback
        $conn->rollback();
    }
    error_log("section-actions error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
if ($conn) {
    $conn->close();
}
