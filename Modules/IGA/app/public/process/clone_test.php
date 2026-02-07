<?php
// /process/clone_test.php
declare(strict_types=1);

require_login();
$is_Super_user_Recruitment = has_role('Super_user_Recruitment');
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
  set_alert(get_text('alert_no_admin_permission'), "danger");
  header("Location: /login");
  exit();
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');

// ต้องเป็นไฟล์ที่ไม่มีการ echo HTML/Debug ใดๆ
require_once __DIR__ . '/../../includes/functions.php';

try {
    require_login();
    if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
        throw new Exception(get_text('alert_no_admin_permission') ?? 'No permission');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid method');
    }

    $csrf = $_POST['_csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        throw new Exception('Invalid CSRF token');
    }

    if (!isset($_POST['test_id']) || !is_numeric($_POST['test_id'])) {
        throw new Exception('Invalid test_id');
    }
    $old_test_id = (int)$_POST['test_id'];
    if ($old_test_id <= 0) {
        throw new Exception('Invalid test_id');
    }

    // ===== Load original test (ดึงทุกคอลัมน์ที่อาจใช้เป็นค่า default) =====
    $stmt = $conn->prepare("
        SELECT *
        FROM iga_tests
        WHERE test_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $old_test_id);
    $stmt->execute();
    $orig = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$orig) {
        throw new Exception("Test with ID $old_test_id not found");
    }

    $current_user_id  = (string)($_SESSION['user_id'] ?? 0);
    $creation_year    = (int)($orig['creation_year'] ?? (int)date('Y'));
    $language         = (string)($orig['language'] ?? 'th');
    $category_type_id = (int)($orig['category_type_id'] ?? 1);
    $role_id          = (int)($orig['role_id'] ?? 0);

    // ===== Next test_no ภายในภาษาเดียวกัน =====
    $test_no = 1;
    $test_no_stmt = $conn->prepare("
        SELECT COALESCE(MAX(test_no), 0) + 1 AS next_test_no
        FROM iga_tests
        WHERE language = ?
    ");
    $test_no_stmt->bind_param('s', $language);
    $test_no_stmt->execute();
    $test_no_res = $test_no_stmt->get_result();
    if ($rowNo = $test_no_res->fetch_assoc()) {
        $test_no = (int)$rowNo['next_test_no'];
    }
    $test_no_stmt->close();

    // ===== Begin transaction =====
    $conn->begin_transaction();

    // ===== Insert new test (- COPY, force is_published = 0) =====
    $new_name = rtrim((string)$orig['test_name']) . ' - COPY';

    // ไม่ระบุ created_at/updated_at ให้ DB ใส่ DEFAULT เอง
    $stmt = $conn->prepare("
        INSERT INTO iga_tests (
            test_name,
            description,
            duration_minutes,
            show_result_immediately,
            min_passing_score,
            is_published,
            created_by_user_id,
            creation_year,
            language,
            category_type_id,
            role_id,
            test_no
        ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)
    ");

    // bind types: s s i i d i i s i i i  -> "ssiidiisiii"
    $test_name             = (string)$new_name;
    $description           = (string)($orig['description'] ?? '');
    $duration_minutes      = (int)($orig['duration_minutes'] ?? 0);
    $show_result_immediately = (int)($orig['show_result_immediately'] ?? 1);
    $min_passing_score     = (float)($orig['min_passing_score'] ?? 0.00);

    $stmt->bind_param(
        'ssiidsisiii',
        $test_name,
        $description,
        $duration_minutes,
        $show_result_immediately,
        $min_passing_score,
        $current_user_id,
        $creation_year,
        $language,
        $category_type_id,
        $role_id,
        $test_no
    );
    $stmt->execute();
    $new_test_id = (int)$conn->insert_id;
    $stmt->close();

    if ($new_test_id <= 0) {
        throw new Exception('Failed to create new test');
    }

    // ===== Clone sections =====
    $section_map = []; // old_section_id => new_section_id

    $stmt = $conn->prepare("
        SELECT section_id, section_name, description, duration_minutes, section_order
        FROM iga_sections
        WHERE test_id = ?
        ORDER BY section_order ASC, section_id ASC
    ");
    $stmt->bind_param('i', $old_test_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    $ins_sec = $conn->prepare("
        INSERT INTO iga_sections (test_id, section_name, description, duration_minutes, section_order)
        VALUES (?, ?, ?, ?, ?)
    ");

    while ($row = $res->fetch_assoc()) {
        $old_sid = (int)$row['section_id'];
        $name = (string)$row['section_name'];
        $desc = (string)$row['description'];
        $dur  = (int)$row['duration_minutes'];
        $ord  = (int)$row['section_order'];

        $ins_sec->bind_param('issii', $new_test_id, $name, $desc, $dur, $ord);
        $ins_sec->execute();
        $new_sid = (int)$conn->insert_id;
        $section_map[$old_sid] = $new_sid;
    }
    $ins_sec->close();

    // ===== Clone questions + options =====
    $question_map = []; // old_question_id => new_question_id

    $stmt = $conn->prepare("
        SELECT
            q.question_id, q.section_id, q.question_text, q.question_type,
            q.score AS question_max_score, q.question_order, q.category_id, q.is_critical
        FROM iga_questions q
        JOIN iga_sections s ON q.section_id = s.section_id
        WHERE s.test_id = ?
        ORDER BY s.section_order ASC, q.question_order ASC, q.question_id ASC
    ");
    $stmt->bind_param('i', $old_test_id);
    $stmt->execute();
    $resq = $stmt->get_result();
    $stmt->close();

    $ins_q = $conn->prepare("
        INSERT INTO questions
        (section_id, question_text, question_type, score, question_order, category_id, is_critical)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    while ($q = $resq->fetch_assoc()) {
        $old_qid = (int)$q['question_id'];
        $old_sid = (int)$q['section_id'];
        $new_sid = $section_map[$old_sid] ?? null;
        if (!$new_sid) continue;

        $q_text   = (string)$q['question_text'];
        $q_type   = (string)$q['question_type'];
        $q_score  = (int)$q['question_max_score'];
        $q_order  = (int)$q['question_order'];
        $cat_id   = isset($q['category_id']) ? (int)$q['category_id'] : null;
        $critical = (int)($q['is_critical'] ?? 0);

        if (!$cat_id || $cat_id <= 0) { $cat_id = null; }

        $ins_q->bind_param('issiiis', $new_sid, $q_text, $q_type, $q_score, $q_order, $cat_id, $critical);
        $ins_q->execute();
        $new_qid = (int)$conn->insert_id;
        $question_map[$old_qid] = $new_qid;

        // clone options
        $stmtOpt = $conn->prepare("
            SELECT option_id, option_text, is_correct
            FROM iga_question_options
            WHERE question_id = ?
            ORDER BY option_id ASC
        ");
        $stmtOpt->bind_param('i', $old_qid);
        $stmtOpt->execute();
        $optRes = $stmtOpt->get_result();
        $stmtOpt->close();

        if ($optRes && $optRes->num_rows > 0) {
            $insOpt = $conn->prepare("
                INSERT INTO iga_question_options (question_id, option_text, is_correct)
                VALUES (?, ?, ?)
            ");
            while ($opt = $optRes->fetch_assoc()) {
                $opt_text = (string)$opt['option_text'];
                $is_cor   = (int)$opt['is_correct'];
                $insOpt->bind_param('isi', $new_qid, $opt_text, $is_cor);
                $insOpt->execute();
            }
            $insOpt->close();
        }
    }
    $ins_q->close();

    // ===== Clone random settings (ถ้ามี) =====
    $stmt = $conn->prepare("
        SELECT is_random_mode, always_include_questions, section_random_counts
        FROM iga_test_random_question_settings
        WHERE test_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $old_test_id);
    $stmt->execute();
    $rs = $stmt->get_result();
    $stmt->close();

    if ($rs && ($row = $rs->fetch_assoc())) {
        $is_random_mode = (int)$row['is_random_mode'];
        $ai_raw = $row['always_include_questions'] ?? '';
        $sc_raw = $row['section_random_counts'] ?? '';

        // remap always_include_questions (old_qid -> new_qid)
        $ai_list = [];
        if ($ai_raw !== '' && $ai_raw !== null) {
            $decoded = json_decode($ai_raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $old_q) {
                    $old_q = (int)$old_q;
                    if (isset($question_map[$old_q])) $ai_list[] = $question_map[$old_q];
                }
            } else {
                $csv = array_filter(array_map('trim', explode(',', $ai_raw)), fn($x) => $x !== '');
                foreach ($csv as $old_q) {
                    $old_q = (int)$old_q;
                    if (isset($question_map[$old_q])) $ai_list[] = $question_map[$old_q];
                }
            }
        }
        $ai_list = array_values(array_unique($ai_list));
        $ai_to_store = !empty($ai_list) ? implode(',', $ai_list) : '';

        // remap section_random_counts keys (old_sid -> new_sid)
        $sec_counts_new = [];
        if ($sc_raw !== '' && $sc_raw !== null) {
            $decoded = json_decode($sc_raw, true);
            if (is_array($decoded)) {
                $is_assoc = array_keys($decoded) !== range(0, count($decoded) - 1);
                if ($is_assoc) {
                    foreach ($decoded as $old_sid => $cnt) {
                        $old_sid = (int)$old_sid;
                        $cnt = (int)$cnt;
                        if (isset($section_map[$old_sid])) {
                            $sec_counts_new[(string)$section_map[$old_sid]] = $cnt;
                        }
                    }
                } else {
                    // [{"section_id":1,"count":5}, ...]
                    foreach ($decoded as $item) {
                        if (is_array($item) && isset($item['section_id'], $item['count'])) {
                            $old_sid = (int)$item['section_id'];
                            $cnt = (int)$item['count'];
                            if (isset($section_map[$old_sid])) {
                                $sec_counts_new[(string)$section_map[$old_sid]] = $cnt;
                            }
                        }
                    }
                }
            }
        }
        $sc_to_store = json_encode($sec_counts_new, JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("
            INSERT INTO iga_test_random_question_settings
            (test_id, is_random_mode, always_include_questions, section_random_counts)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('iiss', $new_test_id, $is_random_mode, $ai_to_store, $sc_to_store);
        $stmt->execute();
        $stmt->close();
    }

    // ===== Clone iga_test_emplevels (test_id, level_id) =====
    $stmt = $conn->prepare("
        SELECT level_id
        FROM iga_test_emplevels
        WHERE test_id = ?
    ");
    $stmt->bind_param('i', $old_test_id);
    $stmt->execute();
    $levelRes = $stmt->get_result();
    $stmt->close();

    if ($levelRes && $levelRes->num_rows > 0) {
        $insLevel = $conn->prepare("
            INSERT INTO iga_test_emplevels (test_id, level_id)
            VALUES (?, ?)
        ");
        while ($lv = $levelRes->fetch_assoc()) {
            $level_id = (int)$lv['level_id'];
            $insLevel->bind_param('ii', $new_test_id, $level_id);
            $insLevel->execute();
        }
        $insLevel->close();
    }

    // ===== Clone iga_test_orgunits (test_id, orgunitname) =====
    $stmt = $conn->prepare("
        SELECT orgunitname
        FROM iga_test_orgunits
        WHERE test_id = ?
    ");
    $stmt->bind_param('i', $old_test_id);
    $stmt->execute();
    $ouRes = $stmt->get_result();
    $stmt->close();

    if ($ouRes && $ouRes->num_rows > 0) {
        $insOU = $conn->prepare("
            INSERT INTO iga_test_orgunits (test_id, orgunitname)
            VALUES (?, ?)
        ");
        while ($ou = $ouRes->fetch_assoc()) {
            $name = (string)$ou['orgunitname'];
            $insOU->bind_param('is', $new_test_id, $name);
            $insOU->execute();
        }
        $insOU->close();
    }

    // ===== Commit =====
    $conn->commit();

    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    echo json_encode([
        'success' => true,
        'message' => 'Cloned successfully',
        'new_test_id' => $new_test_id
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($conn) && $conn) { @ $conn->rollback(); }
    error_log('clone_test.php error: ' . $e->getMessage());
    http_response_code(500);
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
