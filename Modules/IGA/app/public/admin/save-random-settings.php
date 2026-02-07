<?php
// /admin/save-random-settings.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
require_login();
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$test_id = isset($_POST['test_id']) ? (int)$_POST['test_id'] : 0;
if ($test_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid test_id']);
    exit;
}

$is_random_mode = isset($_POST['enable_random_mode']) ? 1 : 0;

// เผื่อค่า default (ถ้าอยากคงไว้)
$random_question_count = isset($_POST['random_question_count']) ? max(0, (int)$_POST['random_question_count']) : 0;

// always include questions (array of ints)
$always_include = isset($_POST['always_include']) && is_array($_POST['always_include'])
    ? array_values(array_unique(array_map('intval', $_POST['always_include'])))
    : [];

// section_random_counts ส่งมาเป็น JSON string จาก JS
$section_counts_json = isset($_POST['section_random_counts']) ? trim($_POST['section_random_counts']) : '{}';

// validate JSON
$section_counts = json_decode($section_counts_json, true);
if (!is_array($section_counts)) {
    $section_counts = [];
}

// ทำความสะอาดให้เป็น int ทั้ง key และ value
$clean_counts = [];
foreach ($section_counts as $sid => $cnt) {
    $sid = (int)$sid;
    $cnt = max(0, (int)$cnt);
    if ($sid > 0) {
        $clean_counts[(string)$sid] = $cnt; // เก็บเป็น key string ใน JSON
    }
}

// ปรับ always_include ให้เป็น array ของ int
$always_include = array_map('intval', $always_include);

// เตรียม JSON string (ให้รองรับ MariaDB LONGTEXT ด้วย)
$always_json = json_encode(array_values($always_include), JSON_UNESCAPED_UNICODE);
$section_json = json_encode((object)$clean_counts, JSON_UNESCAPED_UNICODE);

try {
    // verify test exists (optional butดี)
    $check = $conn->prepare("SELECT 1 FROM iga_tests WHERE test_id = ?");
    $check->bind_param('i', $test_id);
    $check->execute();
    if (!$check->get_result()->fetch_row()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Test not found']);
        exit;
    }
    $check->close();

    // upsert
    $sql = "INSERT INTO iga_test_random_question_settings
            (test_id, is_random_mode, random_question_count, always_include_questions, section_random_counts)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              is_random_mode = VALUES(is_random_mode),
              random_question_count = VALUES(random_question_count),
              always_include_questions = VALUES(always_include_questions),
              section_random_counts = VALUES(section_random_counts),
              updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'iiiss',
        $test_id,
        $is_random_mode,
        $random_question_count,
        $always_json,
        $section_json
    );
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Settings saved']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
