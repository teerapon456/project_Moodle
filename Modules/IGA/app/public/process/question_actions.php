<?php
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/functions.php';
header('Content-Type: application/json');

// --- ตั้งค่า Logging ---
ini_set('display_errors', 0);
ini_set('log_errors', 1);
if (!defined('LOG_FILE')) {
    // กันกรณีไม่ได้ประกาศ LOG_FILE ในระบบหลัก
    ini_set('error_log', __DIR__ . '/../../logs/php-error.log');
} else {
    ini_set('error_log', LOG_FILE);
}
// ----------------------

if (isset($conn) && $conn) {
    $conn->query("SET time_zone = '+07:00'");
}

$response = ['success' => false, 'message' => ''];

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (ให้ editor ใช้ได้ด้วยถ้าต้องการ)
require_login();
$is_Super_user_Recruitment = has_role('Super_user_Recruitment');
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
  set_alert(get_text('alert_no_admin_permission'), "danger");
  header("Location: /login");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['message'] = get_text('error_invalid_request_method');
    error_log("Invalid Method: Received a non-POST request.");
    echo json_encode($response);
    exit();
}

try {
    // ---- CSRF ----
    if (!verify_csrf_token($_POST['_csrf_token'] ?? '')) {
        throw new Exception((get_text('error_invalid_csrf') ?: 'Invalid CSRF token'));
    }

    $action          = $_POST['action'] ?? '';
    $test_id         = $_POST['test_id'] ?? null;
    $section_id      = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT);
    $question_id     = filter_var($_POST['question_id'] ?? null, FILTER_VALIDATE_INT);
    $question_text   = trim((string)($_POST['question_text'] ?? ''));
    $question_type   = (string)($_POST['question_type'] ?? '');
    $score           = filter_var($_POST['score'] ?? 1, FILTER_VALIDATE_INT);
    $question_order  = filter_var($_POST['question_order'] ?? 1, FILTER_VALIDATE_INT);
    $is_critical     = filter_var($_POST['is_critical'] ?? 0, FILTER_VALIDATE_INT);

    // ⭐ รับค่า category_id (ยอมให้ว่าง/NULL)
    $category_id_raw = $_POST['category_id'] ?? '';
    $category_id     = ($category_id_raw === '' || $category_id_raw === null) ? null : (int)$category_id_raw;

    // ตัวเลือกคำตอบ
    $submitted_options        = $_POST['options'] ?? [];
    $is_correct_option_index  = $_POST['is_correct_option'] ?? null;

    if ($section_id === false || $section_id <= 0) {
        throw new Exception((get_text('alert_invalid_section_id') ?: 'Invalid section_id'));
    }

    if ($action === 'add' || $action === 'edit') {
        // คำถามประเภท accept ให้คะแนนเป็น 0
        if ($question_type === 'accept') {
            $score = 0;
        }

        if (
            $question_text === '' || $question_type === '' ||
            $score === false || $score < 0 ||
            $question_order === false || $question_order < 0
        ) {
            throw new Exception((get_text('alert_missing_required_fields') ?: 'Missing required fields'));
        }

        // ตรวจสอบความซ้ำซ้อนของ question_order ใน section เดียวกัน
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM iga_questions WHERE section_id = ? AND question_order = ? AND question_id != ?");
        $qid_for_check = ($action === 'edit' && $question_id) ? $question_id : 0;
        $stmt_check->bind_param("iii", $section_id, $question_order, $qid_for_check);
        $stmt_check->execute();
        $stmt_check->bind_result($countDup);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($countDup > 0) {
            throw new Exception((get_text('alert_question_order_exists') ?: 'This question order already exists in the section.'));
        }

        // ----- เริ่มทรานแซคชัน -----
        $conn->begin_transaction();

        // user-defined variable สำหรับ trigger/log
        if (isset($_SESSION['user_id']) && $conn) {
            $current_user_id = (string)($_SESSION['user_id'] ?? '');
            $uid = $conn->real_escape_string($current_user_id);
            $conn->query("SET @user_id = '{$uid}'"); // ครอบ quote เสมอ
            // หรือถ้าไม่ได้ใช้ @user_id ที่อื่นจริงๆ ให้ลบทั้งบรรทัดนี้ทิ้งได้เลย

        } else {
            $conn->query("SET @user_id = NULL");
        }

        if ($action === 'add') {
            // ✅ FIX: เพิ่ม category_id และชนิดเป็น i (รองรับ NULL)
            $stmt = $conn->prepare("
                INSERT INTO questions
                (section_id, question_text, question_type, score, question_order, is_critical, category_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            // types: i s s i i i i  (7 ตัว)
            $stmt->bind_param(
                "issiiii",
                $section_id,
                $question_text,
                $question_type,
                $score,
                $question_order,
                $is_critical,
                $category_id
            );
            error_log("DB Action: INSERT question (section_id={$section_id})");
        } else {
            if ($question_id === false || $question_id <= 0) {
                throw new Exception((get_text('alert_question_id_missing_for_edit') ?: 'Missing question_id for edit'));
            }
            // ✅ FIX: UPDATE พร้อม category_id และชนิดเป็น i
            $stmt = $conn->prepare("
                UPDATE questions
                SET question_text = ?, question_type = ?, score = ?, question_order = ?, is_critical = ?, category_id = ?
                WHERE question_id = ? AND section_id = ?
            ");
            // types: s s i i i i i i  (8 ตัว)
            $stmt->bind_param(
                "ssiiiiii",
                $question_text,
                $question_type,
                $score,
                $question_order,
                $is_critical,
                $category_id,
                $question_id,
                $section_id
            );
            error_log("DB Action: UPDATE question_id={$question_id} (section_id={$section_id})");
        }

        if (!$stmt->execute()) {
            throw new Exception("Error saving question: " . $stmt->error);
        }

        $current_question_id = ($action === 'add') ? (int)$conn->insert_id : $question_id;
        $stmt->close();

        // จัดการตัวเลือกคำตอบ
        if ($question_type === 'multiple_choice' || $question_type === 'true_false') {
            if (
                empty($submitted_options) ||
                ($question_type === 'multiple_choice' && count($submitted_options) < 2) ||
                ($question_type === 'true_false' && count($submitted_options) != 2)
            ) {
                throw new Exception((get_text('alert_invalid_options_for_type') ?: 'Invalid options for this question type'));
            }

            if ($question_type === 'multiple_choice' && $is_correct_option_index === null) {
                throw new Exception((get_text('alert_select_correct_answer') ?: 'Please select a correct answer.'));
            }

            // โหลด options เดิม (เฉพาะ edit)
            $existing_options_map = [];
            if ($action === 'edit') {
                $stmt_fetch = $conn->prepare("SELECT option_id, option_text, is_correct FROM iga_question_options WHERE question_id = ?");
                $stmt_fetch->bind_param("i", $current_question_id);
                $stmt_fetch->execute();
                $res_opts = $stmt_fetch->get_result();
                while ($row = $res_opts->fetch_assoc()) {
                    $existing_options_map[(int)$row['option_id']] = $row;
                }
                $stmt_fetch->close();
            }

            $options_to_delete = array_keys($existing_options_map);
            $options_to_update = [];
            $options_to_insert = [];

            foreach ($submitted_options as $idx => $opt) {
                $option_id   = isset($opt['option_id']) && $opt['option_id'] !== '' ? (int)$opt['option_id'] : null;
                $option_text = trim((string)($opt['text'] ?? ''));
                $is_correct  = ($idx == $is_correct_option_index) ? 1 : 0;

                if ($option_text === '') {
                    throw new Exception((get_text('alert_option_cannot_be_empty') ?: 'Option text cannot be empty.'));
                }

                if ($option_id && isset($existing_options_map[$option_id])) {
                    $old = $existing_options_map[$option_id];
                    if ($old['option_text'] !== $option_text || (int)$old['is_correct'] !== $is_correct) {
                        $options_to_update[] = ['option_id' => $option_id, 'option_text' => $option_text, 'is_correct' => $is_correct];
                    }
                    // เอาออกจากรายการที่จะลบ
                    $key = array_search($option_id, $options_to_delete, true);
                    if ($key !== false) unset($options_to_delete[$key]);
                } else {
                    $options_to_insert[] = ['question_id' => $current_question_id, 'option_text' => $option_text, 'is_correct' => $is_correct];
                }
            }

            // ลบ options ที่หายไป
            if (!empty($options_to_delete)) {
                $placeholders = implode(',', array_fill(0, count($options_to_delete), '?'));
                $types = str_repeat('i', count($options_to_delete));
                $stmt_del = $conn->prepare("DELETE FROM iga_question_options WHERE option_id IN ($placeholders)");
                $stmt_del->bind_param($types, ...$options_to_delete);
                if (!$stmt_del->execute()) {
                    throw new Exception("Error deleting old options: " . $stmt_del->error);
                }
                $stmt_del->close();
                error_log("DB Action: Deleted " . count($options_to_delete) . " options (question_id={$current_question_id})");
            }

            // อัปเดต options ที่แก้ไข
            if (!empty($options_to_update)) {
                $stmt_upd = $conn->prepare("UPDATE iga_question_options SET option_text = ?, is_correct = ? WHERE option_id = ?");
                foreach ($options_to_update as $op) {
                    $stmt_upd->bind_param("sii", $op['option_text'], $op['is_correct'], $op['option_id']);
                    if (!$stmt_upd->execute()) {
                        throw new Exception("Error updating option: " . $stmt_upd->error);
                    }
                }
                $stmt_upd->close();
                error_log("DB Action: Updated " . count($options_to_update) . " options (question_id={$current_question_id})");
            }

            // แทรก options ใหม่
            if (!empty($options_to_insert)) {
                $stmt_ins = $conn->prepare("INSERT INTO iga_question_options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                foreach ($options_to_insert as $op) {
                    $stmt_ins->bind_param("isi", $op['question_id'], $op['option_text'], $op['is_correct']);
                    if (!$stmt_ins->execute()) {
                        throw new Exception("Error saving new option: " . $stmt_ins->error);
                    }
                }
                $stmt_ins->close();
                error_log("DB Action: Inserted " . count($options_to_insert) . " options (question_id={$current_question_id})");
            }
        } else {
            // ถ้าเปลี่ยนจากประเภทมีตัวเลือก -> เป็นประเภทที่ไม่มีตัวเลือก
            // ลบ options เดิมทิ้ง (กรณี edit)
            if ($action === 'edit') {
                $stmt_del_opt = $conn->prepare("DELETE FROM iga_question_options WHERE question_id = ?");
                $stmt_del_opt->bind_param("i", $current_question_id);
                $stmt_del_opt->execute();
                $stmt_del_opt->close();
            }
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = ($action === 'add'
            ? (get_text('alert_question_added_successfully') ?: 'Question added')
            : (get_text('alert_question_updated_successfully') ?: 'Question updated'));
        echo json_encode($response);
        exit();
    }

    if ($action === 'delete') {
        if ($question_id === false || $question_id <= 0) {
            throw new Exception((get_text('alert_question_id_missing_for_delete') ?: 'Missing question_id for delete'));
        }

        // ลบ options ก่อน (ถ้าไม่มี FK cascade)
        $stmt_del_opt = $conn->prepare("DELETE FROM iga_question_options WHERE question_id = ?");
        $stmt_del_opt->bind_param("i", $question_id);
        $stmt_del_opt->execute();
        $stmt_del_opt->close();

        $stmt = $conn->prepare("DELETE FROM iga_questions WHERE question_id = ? AND section_id = ?");
        $stmt->bind_param("ii", $question_id, $section_id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting question: " . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            $response['success'] = true;
            $response['message'] = (get_text('alert_question_deleted_successfully') ?: 'Question deleted');
        } else {
            $response['message'] = (get_text('error_question_not_found') ?: 'Question not found');
        }

        echo json_encode($response);
        exit();
    }

    throw new Exception((get_text('error_invalid_action') ?: 'Invalid action'));
} catch (Throwable $e) {
    if (isset($conn) && $conn && $conn->errno === 0) {
        @$conn->rollback();
    }
    error_log("question-actions error: " . $e->getMessage());
    $msg = (get_text('error_database_operation') ?: 'Database error');
    echo json_encode(['success' => false, 'message' => $msg . ': ' . $e->getMessage()]);
    exit();
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
