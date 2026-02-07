<?php
// /admin/get-test-iga_questions.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();
if (!has_role('admin') && !has_role('editor') && !has_role('new_role')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
if ($test_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid test_id']);
    exit;
}

try {
    // ดึง sections
    $sql_sections = "SELECT section_id, section_name, section_order
                     FROM iga_sections
                     WHERE test_id = ?
                     ORDER BY section_order ASC, section_id ASC";
    $stmt_sec = $conn->prepare($sql_sections);
    $stmt_sec->bind_param('i', $test_id);
    $stmt_sec->execute();
    $rs_sec = $stmt_sec->get_result();

    $sections = [];
    while ($r = $rs_sec->fetch_assoc()) {
        $sections[(int)$r['section_id']] = [
            'section_id' => (int)$r['section_id'],
            'section_name' => $r['section_name'],
            'section_order' => (int)$r['section_order'],
        ];
    }
    $stmt_sec->close();

    if (empty($sections)) {
        echo json_encode(['success' => false, 'message' => 'No sections found']);
        exit;
    }

    // ดึงคำถาม
    $sql_q = "SELECT q.question_id, q.section_id, q.question_text, q.question_order
              FROM iga_questions q
              INNER JOIN iga_sections s ON s.section_id = q.section_id
              WHERE s.test_id = ?
              ORDER BY s.section_order ASC, q.question_order ASC, q.question_id ASC";
    $stmt_q = $conn->prepare($sql_q);
    $stmt_q->bind_param('i', $test_id);
    $stmt_q->execute();
    $rs_q = $stmt_q->get_result();

    $questions = [];
    $last_section = null;
    while ($row = $rs_q->fetch_assoc()) {
        $sid = (int)$row['section_id'];
        if ($last_section !== $sid) {
            $last_section = $sid;
            $questions[] = [
                'is_section_header' => true,
                'section_id' => $sid,
                'section_name' => $sections[$sid]['section_name'] ?? ('Section '.$sid),
            ];
        }
        $questions[] = [
            'is_section_header' => false,
            'section_id' => $sid,
            'question_id' => (int)$row['question_id'],
            'question_text' => $row['question_text'],
            'question_order' => (int)($row['question_order'] ?? 0),
        ];
    }
    $stmt_q->close();

    // ดึงค่าตั้งค่าใน test_random_question_settings
    $settings = [
        'is_random_mode' => 0,
        'random_question_count' => 0,
        'always_include_questions' => [],
        'section_random_counts' => new stdClass(), // {} ว่าง
    ];

    $sql_cfg = "SELECT is_random_mode, random_question_count, always_include_questions, section_random_counts
                FROM iga_test_random_question_settings WHERE test_id = ?";
    $stmt_cfg = $conn->prepare($sql_cfg);
    $stmt_cfg->bind_param('i', $test_id);
    $stmt_cfg->execute();
    $rs_cfg = $stmt_cfg->get_result();
    if ($cfg = $rs_cfg->fetch_assoc()) {
        $settings['is_random_mode'] = (int)$cfg['is_random_mode'];
        $settings['random_question_count'] = (int)$cfg['random_question_count'];

        $always = $cfg['always_include_questions'];
        $sectionCounts = $cfg['section_random_counts'];

        // ถ้าเป็น JSON column MySQL จะคืน string JSON มาต้อง decode
        $settings['always_include_questions'] = $always ? json_decode($always, true) ?: [] : [];
        $settings['section_random_counts'] = $sectionCounts ? json_decode($sectionCounts, true) ?: new stdClass() : new stdClass();
    }
    $stmt_cfg->close();

    echo json_encode([
        'success' => true,
        'sections' => array_values($sections),
        'questions' => $questions,
        'settings' => $settings
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
