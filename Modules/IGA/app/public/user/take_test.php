<?php
require_once __DIR__ . '/../../includes/header.php';
$page_title = get_text('page_title_take_test');

require_login();
if (!has_role('associate') && !has_role('applicant')) {
    set_alert(get_text('alert_no_permission_user'), "danger");
    header("Location: /login");
    exit();
}

$user_id = $_SESSION['user_id'];
$test_id = $_POST['test_id'] ?? null;

if (!is_numeric($test_id) || $test_id <= 0) {
    set_alert(get_text('alert_invalid_test_id'), "danger");
    header("Location: /user");
    exit();
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

$test_info = [];
$questions_data = [];
$current_question_index = 0;
$total_questions = 0;
$attempt_id = null;
$time_spent_at_resume = 0;
$user_section_times = [];

/** ---------- helpers ---------- */
function parse_maybe_json_array($val): array {
    if ($val === null || $val === '') return [];
    if (is_array($val)) return $val;
    $val = trim((string)$val);
    if ($val === '') return [];
    if ($val[0] === '[' || $val[0] === '{') {
        $decoded = json_decode($val, true);
        return is_array($decoded) ? $decoded : [];
    }
    $parts = array_filter(array_map('trim', explode(',', $val)), fn($x) => $x !== '');
    return array_values($parts);
}

/**
 * เลือกคำถามแบบ "สุ่มให้ครอบคลุมทุก category ใน section" เท่าที่ทำได้
 * - always รวมเข้าชุดแน่นอน (ถ้า always > quota ก็ยอมเกิน)
 * - เติม "1 ข้อ/หมวด" ให้ได้มากที่สุดภายใต้ quota ที่เหลือ
 * - ถ้ายังเหลือโควตา เติมแบบสุ่มจากข้อที่ยังไม่ได้เลือก
 */
function pickQuestionsPerSectionWithCategoryCoverage(array $q_list, int $quota, array $always_include_set): array {
    $always = [];
    $others = [];
    foreach ($q_list as $q) {
        if (isset($always_include_set[$q['question_id']])) $always[] = $q;
        else $others[] = $q;
    }
    if ($quota <= 0) return $always;

    // group by category
    $byCat = [];
    foreach ($others as $q) {
        $cid = $q['category_id'] ?? 0; // 0 = uncategorized
        $byCat[$cid] ??= [];
        $byCat[$cid][] = $q;
    }
    foreach ($byCat as $cid => $arr) {
        shuffle($arr);
        $byCat[$cid] = $arr;
    }

    $remaining = max(0, $quota - count($always));

    // cover categories: 1 per category if possible
    $coverPicks = [];
    if ($remaining > 0) {
        $coveredCats = [];
        foreach ($always as $aq) $coveredCats[$aq['category_id'] ?? 0] = true;

        foreach ($byCat as $cid => $arr) {
            if ($remaining <= 0) break;
            if (!isset($coveredCats[$cid]) && !empty($arr)) {
                $coverPicks[] = array_shift($byCat[$cid]);
                $coveredCats[$cid] = true;
                $remaining--;
            }
        }
    }

    // fill remaining quota from any leftover pool
    $fillPicks = [];
    if ($remaining > 0) {
        $pool = [];
        foreach ($byCat as $cid => $arr) foreach ($arr as $q) $pool[] = $q;
        if (!empty($pool)) {
            shuffle($pool);
            $fillPicks = array_slice($pool, 0, min($remaining, count($pool)));
        }
    }

    return array_merge($always, $coverPicks, $fillPicks);
}

// 0) Auto-unpublish check (Consistency)
if (isset($conn) && $conn) {
    $conn->query("UPDATE iga_tests SET is_published = 0 WHERE is_published = 1 AND unpublished_at IS NOT NULL AND unpublished_at <= NOW()");
}

try {
    // 1) ข้อมูลแบบทดสอบ (Select is_published too, remove filter)
    $stmt = $conn->prepare("SELECT test_id, test_name, description, duration_minutes, show_result_immediately, is_published FROM iga_tests WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Validation: Test not found OR Unpublished
    // STRICT CHECK: If test is unpublished, NO ONE can access it, even if they started already.
    if (!$test_info || $test_info['is_published'] == 0) {
        set_alert(get_text('alert_test_not_found_or_unpublished'), "danger");
        header("Location: /user");
        exit();
    }

    // 2) attempt ปัจจุบัน
    $attempt_id = $_POST['attempt_id'] ?? null;
    $existing_attempt = null;

    if ($attempt_id) {
        $stmt_check_attempt = $conn->prepare("SELECT attempt_id, start_time, time_spent_seconds, current_question_index FROM iga_user_test_attempts WHERE attempt_id = ? AND user_id = ? AND test_id = ? AND is_completed = 0");
        $stmt_check_attempt->bind_param("isi", $attempt_id, $user_id, $test_id);
        $stmt_check_attempt->execute();
        $existing_attempt = $stmt_check_attempt->get_result()->fetch_assoc();
        $stmt_check_attempt->close();
    } else {
        $stmt_check_attempt = $conn->prepare("SELECT attempt_id, start_time, time_spent_seconds, current_question_index FROM iga_user_test_attempts WHERE user_id = ? AND test_id = ? AND is_completed = 0 ORDER BY start_time DESC LIMIT 1");
        $stmt_check_attempt->bind_param("si", $user_id, $test_id);
        $stmt_check_attempt->execute();
        $existing_attempt = $stmt_check_attempt->get_result()->fetch_assoc();
        $stmt_check_attempt->close();
    }

    if ($existing_attempt) {
        $attempt_id = $existing_attempt['attempt_id'];
        $time_spent_at_resume = $existing_attempt['time_spent_seconds'] ?? 0;
        $current_question_index = $existing_attempt['current_question_index'] ?? 0;
        $_SESSION['current_attempt_id'] = $attempt_id;
        $_SESSION['time_spent_at_resume'] = $time_spent_at_resume;
        $_SESSION['current_question_index'] = $current_question_index;
    } else {
        $start_time = date('Y-m-d H:i:s');
        $stmt_new_attempt = $conn->prepare("INSERT INTO iga_user_test_attempts (user_id, test_id, start_time, is_completed, current_question_index, time_spent_seconds) VALUES (?, ?, ?, 0, 0, 0)");
        $stmt_new_attempt->bind_param("sis", $user_id, $test_id, $start_time);
        $stmt_new_attempt->execute();
        $attempt_id = $conn->insert_id;
        $stmt_new_attempt->close();

        if (!$attempt_id) throw new Exception(get_text('error_create_test_attempt'));
        $_SESSION['current_attempt_id'] = $attempt_id;
        $_SESSION['time_spent_at_resume'] = 0;
        $_SESSION['current_question_index'] = 0;
    }

    // 3) เวลา section ที่เคยใช้
    $stmt_section_times = $conn->prepare("SELECT section_id, time_spent_seconds, start_timestamp FROM iga_user_section_times WHERE attempt_id = ?");
    $stmt_section_times->bind_param("i", $attempt_id);
    $stmt_section_times->execute();
    $result_section_times = $stmt_section_times->get_result();
    while ($row_section_time = $result_section_times->fetch_assoc()) {
        $start_timestamp_unix = null;
        if (!empty($row_section_time['start_timestamp'])) {
            $parsed_time = strtotime($row_section_time['start_timestamp']);
            $start_timestamp_unix = ($parsed_time !== false) ? $parsed_time : null;
        }
        $user_section_times[$row_section_time['section_id']] = [
            'time_spent' => (int)$row_section_time['time_spent_seconds'],
            'start_timestamp' => $start_timestamp_unix
        ];
    }
    $stmt_section_times->close();

    // 4) ค่าการสุ่ม
    $random_mode = false;
    $section_random_counts = [];  // { section_id: count }
    $always_include_questions = []; // [question_id, ...]

    $rs = $conn->prepare("
        SELECT is_random_mode, always_include_questions, section_random_counts
        FROM iga_test_random_question_settings
        WHERE test_id = ?
        LIMIT 1
    ");
    $rs->bind_param("i", $test_id);
    $rs->execute();
    $rs_res = $rs->get_result();
    if ($row = $rs_res->fetch_assoc()) {
        $random_mode = (bool)$row['is_random_mode'];
        $ai = parse_maybe_json_array($row['always_include_questions']);
        $always_include_questions = array_values(array_unique(array_map('intval', $ai)));

        $sc = parse_maybe_json_array($row['section_random_counts']);
        if (is_array($sc)) {
            $tmp = [];
            $is_assoc = array_keys($sc) !== range(0, count($sc) - 1);
            if ($is_assoc) {
                foreach ($sc as $k => $v) $tmp[(int)$k] = (int)$v;
            } else {
                foreach ($sc as $item) {
                    if (is_array($item) && isset($item['section_id'], $item['count']))
                        $tmp[(int)$item['section_id']] = (int)$item['count'];
                }
            }
            $section_random_counts = $tmp;
        }
    }
    $rs->close();

    // 5) โหลดคำถามทั้งหมด (รวม category) + เก็บ metadata ของ section
    $questions_data = [];
    $all_questions = [];
    $section_questions = [];
    $sections_meta = []; // section_id => ['section_order'=>, 'section_name'=>, 'section_duration'=>]
    $always_include_set = array_flip($always_include_questions);

    $stmt = $conn->prepare("
        SELECT
            s.section_id, s.section_name, s.description AS section_description,
            s.duration_minutes AS section_duration, s.section_order,
            q.question_id, q.question_text, q.question_type, q.score AS question_max_score,
            q.question_order, qc.category_id, qc.category_name
        FROM iga_sections s
        JOIN iga_questions q ON s.section_id = q.section_id
        LEFT JOIN iga_question_categories qc ON q.category_id = qc.category_id
        WHERE s.test_id = ?
        ORDER BY s.section_order ASC, q.question_order ASC
    ");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sid = (int)$row['section_id'];
        if (!isset($sections_meta[$sid])) {
            $sections_meta[$sid] = [
                'section_id'       => $sid,
                'section_name'     => $row['section_name'],
                'section_order'    => (int)$row['section_order'],
                'section_duration' => (int)$row['section_duration'],
                'section_description' => $row['section_description'] ?? null,
            ];
        }

        $question_id = (int)$row['question_id'];
        $q = [
            'question_id'       => $question_id,
            'question_text'     => $row['question_text'],
            'question_type'     => $row['question_type'],
            'question_max_score'=> $row['question_max_score'],
            'question_order'    => $row['question_order'],
            'section_id'        => $sid,
            'section_name'      => $row['section_name'],
            'section_duration'  => $row['section_duration'],
            'section_order'     => (int)$row['section_order'],
            'category_id'       => $row['category_id'] ?? null,
            'category_name'     => $row['category_name'] ?? 'Uncategorized',
            'question_options'  => [],
            'section_description' => $row['section_description'] ?? null,
            
        ];

        if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') {
            $question_options_stmt = $conn->prepare("SELECT option_id, option_text FROM iga_question_options WHERE question_id = ? ORDER BY option_id ASC");
            $question_options_stmt->bind_param("i", $question_id);
            $question_options_stmt->execute();
            $question_options_result = $question_options_stmt->get_result();
            while ($opt_row = $question_options_result->fetch_assoc()) {
                $q['question_options'][] = $opt_row;
            }
            $question_options_stmt->close();
        }

        $all_questions[] = $q;
        if (!isset($section_questions[$sid])) $section_questions[$sid] = [];
        $section_questions[$sid][] = $q;
    }
    $stmt->close();

    // ===== 6) เลือก+ล็อกชุดคำถาม (อ่านจาก iga_user_attempt_questions ถ้ามีแล้ว) =====
    $selected_questions = [];

    // มีชุดเดิมหรือยัง?
    $locked = [];
    $chk = $conn->prepare("SELECT question_id, shown_order FROM iga_user_attempt_questions WHERE attempt_id = ? ORDER BY shown_order ASC");
    $chk->bind_param("i", $attempt_id);
    $chk->execute();
    $rsLocked = $chk->get_result();
    while ($r = $rsLocked->fetch_assoc()) {
        $locked[] = ['question_id' => (int)$r['question_id'], 'shown_order' => (int)$r['shown_order']];
    }
    $chk->close();

    if (!empty($locked)) {
        // (A) ใช้ชุดเดิม (คงลำดับเดิมที่เคยล็อกไว้)
        $mapAllByQid = [];
        foreach ($all_questions as $q) $mapAllByQid[(int)$q['question_id']] = $q;

        foreach ($locked as $item) {
            $qid = $item['question_id'];
            if (isset($mapAllByQid[$qid])) $selected_questions[] = $mapAllByQid[$qid];
        }

        // เรียงตาม shown_order เดิม
        usort($selected_questions, function($a, $b) use ($locked) {
            static $ord = null;
            if ($ord === null) {
                $ord = [];
                foreach ($locked as $row) $ord[$row['question_id']] = $row['shown_order'];
            }
            $oa = $ord[$a['question_id']] ?? PHP_INT_MAX;
            $ob = $ord[$b['question_id']] ?? PHP_INT_MAX;
            return $oa <=> $ob;
        });

        // รีเซ็ตเลขข้อ 1..N ตามลำดับเดิม
        foreach ($selected_questions as $i => &$q) $q['question_order'] = $i+1;
        unset($q);

    } else {
        // (B) ยังไม่มี → "เรียง Section ก่อน" แล้ว "สุ่มคำถามในแต่ละ Section"
        // เตรียมรายการ section เรียงตาม section_order
        $section_list = array_values($sections_meta);
        usort($section_list, fn($a,$b) => $a['section_order'] <=> $b['section_order']);

        $selected_questions = [];

        // ... ภายใน else { // (B) ยังไม่มี → "เรียง Section ก่อน" ...
            foreach ($section_list as $sec) {
                $sid = $sec['section_id'];
                $q_list = $section_questions[$sid] ?? [];

                if ($random_mode) {
                    $quota = (int)($section_random_counts[$sid] ?? 0);
                    if ($quota > 0) {
                        $picked = pickQuestionsPerSectionWithCategoryCoverage($q_list, $quota, $always_include_set);
                    } else {
                        // โหมดสุ่ม แต่ quota = 0 → ใช้ทุกข้อใน section (แล้วค่อย shuffle)
                        $picked = $q_list;
                    }
                    // ✅ สุ่มลำดับภายใน section เฉพาะเมื่อสุ่มเท่านั้น
                    if (!empty($picked)) {
                        shuffle($picked);
                    }
                } else {
                    // ❌ โหมดไม่สุ่ม → ห้าม shuffle เพื่อให้คงลำดับ DB (section_order, question_order)
                    $picked = $q_list; // $q_list มาตาม ORDER BY ใน SQL อยู่แล้ว
                }

                foreach ($picked as $q) $selected_questions[] = $q;
            }

            // รีเซ็ตเลขข้อ 1..N ตามลำดับ "Section → (ถ้าสุ่มก็สุ่มใน Section)"
            foreach ($selected_questions as $idx => &$q) {
                $q['question_order'] = $idx + 1;
            }
            unset($q);

        // รีเซ็ตเลขข้อ 1..N ตามลำดับ "Section → สุ่มใน Section"
        foreach ($selected_questions as $idx => &$q) {
            $q['question_order'] = $idx + 1;
        }
        unset($q);

        // บันทึกล็อกลง iga_user_attempt_questions ตามลำดับที่จัดใหม่
        if (!empty($selected_questions)) {
            $ins = $conn->prepare("
                INSERT INTO iga_user_attempt_questions (attempt_id, question_id, shown_order)
                VALUES (?, ?, ?)
            ");
            foreach ($selected_questions as $idx => $q) {
                $qid = (int)$q['question_id'];
                $ord = (int)($idx + 1);
                $ins->bind_param("iii", $attempt_id, $qid, $ord);
                $ins->execute();
            }
            $ins->close();
        }
    }

    $questions_data  = $selected_questions;
    $total_questions = count($questions_data);

    if ($total_questions === 0) {
        set_alert(get_text('alert_no_questions_in_test'), "warning");
        header("Location: /user");
        exit();
    }

    // 8) โหลดคำตอบเดิม
    $_SESSION['test_answers'][$attempt_id] = $_SESSION['test_answers'][$attempt_id] ?? [];
    $user_answers_stmt = $conn->prepare("SELECT question_id, user_answer_text FROM iga_user_answers WHERE attempt_id = ?");
    $user_answers_stmt->bind_param("i", $attempt_id);
    $user_answers_stmt->execute();
    $existing_user_answers = $user_answers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $user_answers_stmt->close();
    foreach ($existing_user_answers as $ans) {
        $_SESSION['test_answers'][$attempt_id][$ans['question_id']] = $ans['user_answer_text'];
    }

    // 9) เวลาโดยรวม
    $time_remaining_seconds_overall = 0;
    if ($test_info['duration_minutes'] > 0) {
        $total_duration_seconds = $test_info['duration_minutes'] * 60;
        $time_remaining_seconds_overall = max(0, $total_duration_seconds - $time_spent_at_resume);
    }

} catch (Exception $e) {
    set_alert(get_text('alert_load_test_error') . ": " . $e->getMessage(), "danger");
    header("Location: /user");
    exit();
}

$js_vars = [
    'attemptId' => $attempt_id,
    'testDurationMinutes' => $test_info['duration_minutes'],
    'timeRemainingSecondsOverall' => $time_remaining_seconds_overall,
    'totalQuestions' => $total_questions,
    'currentQuestionIndex' => $current_question_index,
    'questionsData' => $questions_data,
    'initialUserAnswers' => $_SESSION['test_answers'][$attempt_id] ?? [],
    'userSectionTimes' => $user_section_times,
    'lang' => [
        'overall_test_time' => get_text('overall_test_time'),
        'section_time_remaining' => get_text('section_time_remaining'),
        'exit_test' => get_text('exit_test'),
        'loading_questions' => get_text('loading_questions'),
        'question_full_score' => get_text('question_full_score'),
        'question_not_found' => get_text('question_not_found'),
        'unsupported_question_type' => get_text('unsupported_question_type'),
        'type_your_answer_here' => get_text('type_your_answer_here'),
        'previous_question' => get_text('previous_question'),
        'next_question' => get_text('next_question'),
        'go_to_next_section' => get_text('go_to_next_section'),
        'submit_test' => get_text('submit_test'),
        'question_of_total' => get_text('question_of_total'),
        'time_up_overall_alert' => get_text('time_up_overall_alert'),
        'time_up_section_alert' => get_text('time_up_section_alert'),
        'test_completed_alert' => get_text('test_completed_alert'),
        'confirm_submit_test' => get_text('confirm_submit_test'),
        'error_submitting_test' => get_text('error_submitting_test'),
        'network_error_submitting_test' => get_text('network_error_submitting_test'),
        'break_screen_title' => get_text('break_screen_title'),
        'break_screen_message' => get_text('break_screen_message'),
        'break_timer_display' => get_text('break_timer_display'),
        'break_auto_continue_message' => get_text('break_auto_continue_message'),
        'skip_break' => get_text('skip_break'),
        'error_saving_answer' => get_text('error_saving_answer'),
        'network_error_saving_answer' => get_text('network_error_saving_answer'),
        'error_updating_test_state' => get_text('error_updating_test_state'),
        'network_error_updating_test_state' => get_text('network_error_updating_test_state'),
        'error_saving_section_time' => get_text('error_saving_section_time'),
        'network_error_saving_section_time' => get_text('network_error_saving_section_time'),
        'error_updating_section_start_timestamp' => get_text('error_updating_section_start_timestamp'),
        'network_error_updating_section_start_timestamp' => get_text('network_error_updating_section_start_timestamp'),
        'hours_abbr' => get_text('hours_abbr'),
        'minutes_abbr' => get_text('minutes_abbr'),
        'seconds_abbr' => get_text('seconds_abbr'),
        'afk_alert_title' => get_text('afk_alert_title'),
        'afk_alert_message' => get_text('afk_alert_message'),
        'afk_alert_confirm_button' => get_text('afk_alert_confirm_button'),
    ]
];
?>

<?php echo get_alert(); ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<div style="display:none"><?php echo generate_csrf_token(); ?></div>

<style>
    .w-80-custom { width: 100%; padding-left: 15px; padding-right: 15px; }
    @media (min-width: 768px) {
        .w-80-custom { width: 80%; max-width: 960px; margin-left: auto; margin-right: auto; padding-left: var(--bs-gutter-x, 1.5rem); padding-right: var(--bs-gutter-x, 1.5rem); }
    }
    @media (max-width: 575.98px) {
        #overallTimer, #sectionTimer { font-size: 0.85rem !important; margin-right: 0.5rem !important; margin-bottom: 0.5rem; }
        .btn-responsive-stack { width: 100%; margin-bottom: 0.5rem; }
    }
    .preserve-whitespace { white-space: pre-wrap; tab-size: 4; -moz-tab-size: 4; line-height: 1.6; }
</style>

<div class="container-fluid w-80-custom py-4">
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
        <h1 class="mb-2 mb-md-0 text-primary-custom text-center text-md-start w-100 w-md-auto"><?php echo htmlspecialchars($test_info['test_name']); ?></h1>

        <div class="d-flex flex-wrap justify-content-center justify-content-md-end align-items-center mt-3 mt-md-0 w-100 w-md-auto">
            <?php if ($test_info['duration_minutes'] > 0): ?>
                <span class="badge bg-info fs-6 me-md-3 mb-2 mb-md-1" id="overallTimer"><?php echo get_text('overall_test_time'); ?>: Loading...</span>
            <?php endif; ?>
            <span class="badge bg-danger fs-6 me-md-3 mb-2 mb-md-1" id="sectionTimer"><?php echo get_text('section_time_remaining'); ?>: Loading...</span>
        </div>
        <a href="/user" class="btn btn-outline-secondary btn-responsive-stack" id="exitTestBtn">
            <i class="fas fa-times-circle me-2"></i> <?php echo get_text('exit_test'); ?>
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary-custom text-white">
            <h4 class="mb-0 text-center text-md-start">
                <span id="questionCounter"></span>
                <span id="currentSectionName"></span>
            </h4>
        </div>
        <div class="card-body">
            <div id="questionDisplayArea">
                <p class="text-center text-muted"><?php echo get_text('loading_questions'); ?></p>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                <button class="btn btn-secondary btn-responsive-stack" id="prevQuestionBtn" style="display: none;">
                    <i class="fas fa-chevron-left me-2"></i> <?php echo get_text('previous_question'); ?>
                </button>
                <button class="btn btn-success btn-responsive-stack" id="nextQuestionBtn" style="display: none;">
                    <?php echo get_text('next_question'); ?> <i class="fas fa-chevron-right ms-2"></i>
                </button>
                <button class="btn btn-success btn-responsive-stack" id="submitTestBtn" style="display: none;">
                    <?php echo get_text('submit_test'); ?> <i class="fas fa-paper-plane ms-2"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const jsVars = <?php echo json_encode($js_vars); ?>;
    const csrfToken = (document.querySelector('input[name="_csrf_token"]') || {}).value || '';

    let attemptId = jsVars.attemptId;
    let testDurationMinutes = jsVars.testDurationMinutes;
    let timeRemainingSecondsOverall = jsVars.timeRemainingSecondsOverall;
    let totalQuestions = jsVars.totalQuestions;
    let currentQuestionIndex = jsVars.currentQuestionIndex;
    let questionsData = jsVars.questionsData;

    let userAnswers = jsVars.initialUserAnswers;
    let userSectionTimes = jsVars.userSectionTimes;
    let currentSectionId = null;
    let currentSectionStartTime = null;
    let sectionTimeSpent = 0;
    let currentSectionTimeRemaining = 0;
    let expiredSections = new Set();
    let overallTestTimerStarted = false;

    const questionDisplayArea = document.getElementById('questionDisplayArea');
    const prevQuestionBtn = document.getElementById('prevQuestionBtn');
    const nextQuestionBtn = document.getElementById('nextQuestionBtn');
    const submitTestBtn = document.getElementById('submitTestBtn');
    const questionCounter = document.getElementById('questionCounter');
    const currentSectionName = document.getElementById('currentSectionName');
    const overallTimerElement = document.getElementById('overallTimer');
    const sectionTimerElement = document.getElementById('sectionTimer');
    const exitTestBtn = document.getElementById('exitTestBtn');

    let overallTestInterval;
    let sectionTimerInterval;
    let breakInterval;

    const BREAK_DURATION_SECONDS = 60;
    let breakTimeRemaining = BREAK_DURATION_SECONDS;
    const AFK_TIMEOUT_SECONDS = 60;
    let afkTimer;
    let isAfkAlertShowing = false;
    let alertSound = null;
    let isAudioReady = false;

    function initAudio() {
        if (isAudioReady) return;
        alertSound = new Audio('/static/sounds/alert_sound.mp3');
        alertSound.volume = 1;
        alertSound.loop = true;
        const playPromise = alertSound.play();
        if (playPromise !== undefined) {
            playPromise.catch(() => {}).then(() => {
                if (alertSound) {
                    alertSound.pause();
                    alertSound.currentTime = 0;
                    isAudioReady = true;
                }
            });
        }
    }
    const initAudioOnInteraction = () => {
        initAudio();
        document.removeEventListener('click', initAudioOnInteraction);
        document.removeEventListener('keydown', initAudioOnInteraction);
    };
    document.addEventListener('click', initAudioOnInteraction);
    document.addEventListener('keydown', initAudioOnInteraction);
    function playAlertSound() {
        if (!isAudioReady) { initAudio(); return; }
        if (alertSound) {
            const playPromise = alertSound.play();
            if (playPromise !== undefined) playPromise.catch(() => {});
        }
    }

    let wasOverallTimerActiveBeforeAfk = false;
    let wasSectionTimerActiveBeforeAfk = false;
    let wasBreakTimerActiveBeforeAfk = false;

    function escapeHTML(str) {
        if (str == null) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function replaceTabs(str, tabSize = 4) {
        if (str == null) return '';
        const spaces = ' '.repeat(tabSize);
        return String(str).replace(/\t/g, spaces);
    }
    function formatForDisplay(rawText) { return escapeHTML(replaceTabs(rawText, 4)); }

    function resetAfkTimer() { clearTimeout(afkTimer); afkTimer = setTimeout(triggerAfkAlert, AFK_TIMEOUT_SECONDS * 1000); }

    function triggerAfkAlert() {
        if (!isAfkAlertShowing) {
            if (timeRemainingSecondsOverall <= 0) { submitTest(); return; }

            if (currentSectionTimeRemaining <= 0 && questionsData[currentQuestionIndex]) {
                const currentQuestion = questionsData[currentQuestionIndex];
                if (currentQuestion.section_duration > 0) {
                    expiredSections.add(currentQuestion.section_id);
                    let nextSectionFound = false;
                    for (let i = currentQuestionIndex + 1; i < totalQuestions; i++) {
                        if (questionsData[i].section_id !== currentQuestion.section_id) {
                            currentQuestionIndex = i;
                            saveSectionTimeSpent(currentQuestion.section_id);
                            displayBreakScreen();
                            nextSectionFound = true;
                            break;
                        }
                    }
                    if (!nextSectionFound) {
                        saveSectionTimeSpent(currentQuestion.section_id);
                        submitTestTimeExpired();
                    }
                    return;
                }
            }

            isAfkAlertShowing = true;

            if (overallTestInterval) { wasOverallTimerActiveBeforeAfk = true; clearInterval(overallTestInterval); overallTestInterval = null; overallTestTimerStarted = false; }
            if (sectionTimerInterval) { wasSectionTimerActiveBeforeAfk = true; clearInterval(sectionTimerInterval); sectionTimerInterval = null; }
            if (breakInterval) { wasBreakTimerActiveBeforeAfk = true; clearInterval(breakInterval); breakInterval = null; }

            playAlertSound();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: jsVars.lang.afk_alert_title,
                    text: jsVars.lang.afk_alert_message,
                    icon: 'warning',
                    showConfirmButton: true,
                    confirmButtonText: jsVars.lang.afk_alert_confirm_button || 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    willClose: () => {
                        alertSound.pause(); alertSound.currentTime = 0; isAfkAlertShowing = false;
                        if (wasOverallTimerActiveBeforeAfk) { startOverallTestTimer(); wasOverallTimerActiveBeforeAfk = false; }
                        if (wasSectionTimerActiveBeforeAfk) { startSectionTimer(); wasSectionTimerActiveBeforeAfk = false; }
                        if (wasBreakTimerActiveBeforeAfk) {
                            breakInterval = setInterval(() => {
                                breakTimeRemaining--; updateBreakTimerDisplay();
                                if (breakTimeRemaining <= 0) {
                                    clearInterval(breakInterval); breakInterval = null;
                                    updateTestState(); displayQuestion();
                                }
                            }, 1000);
                            updateBreakTimerDisplay();
                            wasBreakTimerActiveBeforeAfk = false;
                        }
                        resetAfkTimer();
                    }
                });
            } else {
                alert(jsVars.lang.afk_alert_title + "\n" + jsVars.lang.afk_alert_message);
                isAfkAlertShowing = false; alertSound.pause(); alertSound.currentTime = 0;
                if (wasOverallTimerActiveBeforeAfk) { startOverallTestTimer(); wasOverallTimerActiveBeforeAfk = false; }
                if (wasSectionTimerActiveBeforeAfk) { startSectionTimer(); wasSectionTimerActiveBeforeAfk = false; }
                if (wasBreakTimerActiveBeforeAfk) {
                    breakInterval = setInterval(() => {
                        breakTimeRemaining--; updateBreakTimerDisplay();
                        if (breakTimeRemaining <= 0) {
                            clearInterval(breakInterval); breakInterval = null; updateTestState(); displayQuestion();
                        }
                    }, 1000);
                    updateBreakTimerDisplay();
                    wasBreakTimerActiveBeforeAfk = false;
                }
                resetAfkTimer();
            }
        }
    }

    document.addEventListener('mousemove', resetAfkTimer);
    document.addEventListener('keydown', resetAfkTimer);
    document.addEventListener('click', resetAfkTimer);
    document.addEventListener('scroll', resetAfkTimer);
    document.addEventListener('touchstart', resetAfkTimer, false);
    document.addEventListener('touchmove', resetAfkTimer, false);
    document.addEventListener('touchend', resetAfkTimer, false);
    window.onload = function() { resetAfkTimer(); };

    function displayQuestion() {
        if (typeof langSelect !== 'undefined' && langSelect.length > 0) langSelect.prop('disabled', true);
        if (currentQuestionIndex < 0 || currentQuestionIndex >= totalQuestions) {
            questionDisplayArea.innerHTML = `<p class='text-center text-danger'>${jsVars.lang.question_not_found}</p>`;
            return;
        if (testDurationMinutes > 0 && !overallTestTimerStarted) {
            startOverallTestTimer(); // ✅ เริ่ม overall เสมอถ้ามีการจำกัดเวลารวม
        }
        }
        

        const q = questionsData[currentQuestionIndex];

        const qText = formatForDisplay(q.question_text);
        const sectionName = formatForDisplay(q.section_name);
        const section_description = formatForDisplay(q.section_description);
        
        

        let htmlContent = `
            <div class="question-block">
              <h5 class="mb-3">
                <span class="me-1">${q.question_order}.</span>
                <span class="preserve-whitespace q-text">${qText}</span>
                <small class="text-muted ms-2">(${jsVars.lang.question_full_score}: ${q.question_max_score})</small>
              </h5>
              <p class="text-muted preserve-whitespace">${sectionName} <small class="text-muted ms-2">( ${section_description} )</small></p>
            </div>
        `;

        if (q.question_type === 'multiple_choice' || q.question_type === 'true_false') {
            htmlContent += '<div class="form-group">';
            (q.question_options || []).forEach(option => {
                const isChecked = (userAnswers[q.question_id] == option.option_id) ? 'checked' : '';
                const optText = formatForDisplay(option.option_text);
                htmlContent += `
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="question_${q.question_id}" id="option_${option.option_id}" value="${option.option_id}" ${isChecked}>
                        <label class="form-check-label preserve-whitespace" for="option_${option.option_id}">${optText}</label>
                    </div>
                `;
            });
            htmlContent += '</div>';
        } else if (q.question_type === 'short_answer') {
            const userAnswer = userAnswers[q.question_id] || '';
            const userAnswerEscaped = escapeHTML(userAnswer);
            htmlContent += `
                <div class="form-group">
                    <textarea class="form-control" id="answer_${q.question_id}" rows="4" placeholder="${jsVars.lang.type_your_answer_here}">${userAnswerEscaped}</textarea>
                </div>
            `;
        } else if (q.question_type === 'accept') {
            htmlContent += `
                <div class="form-group">
                    <div class="form-control preserve-whitespace" style="min-height:120px;">${qText}</div>
                </div>
            `;
        } else {
            htmlContent += `<p class='text-danger'>${jsVars.lang.unsupported_question_type}</p>`;
        }

        questionDisplayArea.innerHTML = htmlContent;

        const prevQuestionSectionId = (currentQuestionIndex > 0) ? questionsData[currentQuestionIndex - 1].section_id : null;
        const currentQuestionSectionId = questionsData[currentQuestionIndex].section_id;

        if (currentQuestionIndex > 0 &&
            (currentQuestionSectionId === prevQuestionSectionId || !expiredSections.has(prevQuestionSectionId))) {
            prevQuestionBtn.style.display = 'block';
        } else {
            prevQuestionBtn.style.display = 'none';
        }

        const nextQuestionSectionId = (currentQuestionIndex + 1 < totalQuestions) ? questionsData[currentQuestionIndex + 1].section_id : null;

        if (currentQuestionIndex === totalQuestions - 1) {
            nextQuestionBtn.style.display = 'none';
            submitTestBtn.style.display = 'block';
        } else if (nextQuestionSectionId && nextQuestionSectionId !== currentQuestionSectionId) {
            nextQuestionBtn.style.display = 'block';
            nextQuestionBtn.innerHTML = `${jsVars.lang.go_to_next_section} <i class="fas fa-chevron-right ms-2"></i>`;
            submitTestBtn.style.display = 'none';
        } else {
            nextQuestionBtn.style.display = 'block';
            nextQuestionBtn.innerHTML = `${jsVars.lang.next_question} <i class="fas fa-chevron-right ms-2"></i>`;
            submitTestBtn.style.display = 'none';
        }

        questionCounter.innerText = `${jsVars.lang.question_of_total.replace('{current}', currentQuestionIndex + 1).replace('{total}', totalQuestions)}`;
        currentSectionName.innerText = `(${q.section_name}.)`;

        const newSectionId = q.section_id;
        if (currentSectionId !== newSectionId) {
            if (currentSectionId !== null) saveSectionTimeSpent(currentSectionId);

            currentSectionId = newSectionId;
            sectionTimeSpent = userSectionTimes[currentSectionId] ? userSectionTimes[currentSectionId].time_spent : 0;

            if (userSectionTimes[currentSectionId] && userSectionTimes[currentSectionId].start_timestamp) {
                currentSectionStartTime = userSectionTimes[currentSectionId].start_timestamp;
            } else {
                currentSectionStartTime = Math.floor(Date.now() / 1000);
            }

            updateSectionStartTimestamp(currentSectionId, currentSectionStartTime);
        }

        const currentQuestionSectionDuration = q.section_duration;
            if (currentQuestionSectionDuration > 0) {
            if (testDurationMinutes > 0 && !overallTestTimerStarted) startOverallTestTimer();
            startSectionTimer();
            sectionTimerElement.style.display = 'inline-block';
            } else {
            // ไม่มีเวลาต่อ section → ปิดเฉพาะตัวนับ section
            clearInterval(sectionTimerInterval);
            sectionTimerInterval = null;
            sectionTimerElement.style.display = 'none';

            // ✅ สำคัญ: อย่าหยุดนาฬิการวม ถ้ามีเวลาแบบทดสอบรวม ให้ทำงานต่อ/แสดงผล
            if (testDurationMinutes > 0 && !overallTestTimerStarted) startOverallTestTimer();
            if (testDurationMinutes > 0) {
                overallTimerElement.style.display = 'inline-block';
            } else {
                overallTimerElement.style.display = 'none';
            }
            }

            // === ผูกอีเวนต์เพื่อ "เซฟทันที" ===
            if (q.question_type === 'multiple_choice' || q.question_type === 'true_false') {
            document.querySelectorAll(`input[name="question_${q.question_id}"]`).forEach(radio => {
                // ไม่ต้อง removeEventListener ก็ได้ถ้าเราทับด้วย onChange ตรงๆ
                radio.onchange = (e) => saveAnswer(q.question_id, e.target.value); // เลือกช้อยส์แล้วเซฟทันที
            });
            } else if (q.question_type === 'short_answer') {
            const textarea = document.getElementById(`answer_${q.question_id}`);
            if (textarea) {
                // debounce ระหว่างพิมพ์ + บังคับเซฟตอน blur
                let t;
                textarea.oninput = (e) => {
                clearTimeout(t);
                t = setTimeout(() => saveAnswer(q.question_id, e.target.value), 500);
                };
                textarea.onblur = (e) => saveAnswer(q.question_id, e.target.value);
            }
            }

            // ซิงค์แคชคำตอบล่าสุด (ถ้าใช้วิธีออโต้เช็คเปลี่ยนค่าเป็นระยะ)
            if (typeof lastSavedAnswers !== 'undefined' && typeof getCurrentAnswerValue === 'function') {
            lastSavedAnswers[q.question_id] = String(getCurrentAnswerValue(q));
            }


    }


    function saveAnswerHandler(event) {
        const question = questionsData[currentQuestionIndex];
        const answerValue = event.target.value;
        saveAnswer(question.question_id, answerValue);
    }

    function displayBreakScreen() {
        clearInterval(sectionTimerInterval); sectionTimerInterval = null;
        overallTestTimerStarted = false;
        clearInterval(overallTestInterval); overallTestInterval = null;

        if (typeof langSelect !== 'undefined' && langSelect) langSelect.prop('disabled', true);

        questionDisplayArea.innerHTML = `
            <div class="text-center py-5">
                <h3 class="text-primary-custom mb-4">${jsVars.lang.break_screen_title}</h3>
                <p class="lead">${jsVars.lang.break_screen_message}</p>
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                <p class="mt-3 fs-4" id="breakTimerDisplay">${jsVars.lang.break_timer_display.replace('{time}', formatTime(breakTimeRemaining))}</p>                <p class="text-muted">${jsVars.lang.break_auto_continue_message}</p>
                <button class="btn btn-primary mt-4" id="skipBreakBtn">${jsVars.lang.skip_break} <i class="fas fa-forward ms-2"></i></button>
            </div>
        `;

        prevQuestionBtn.style.display = 'none';
        nextQuestionBtn.style.display = 'none';
        submitTestBtn.style.display = 'none';
        sectionTimerElement.style.display = 'none';
        if (testDurationMinutes > 0) overallTimerElement.style.display = 'inline-block';

        breakTimeRemaining = BREAK_DURATION_SECONDS;
        updateBreakTimerDisplay();
        breakInterval = setInterval(() => {
            breakTimeRemaining--; updateBreakTimerDisplay();
            if (breakTimeRemaining <= 0) {
                clearInterval(breakInterval); breakInterval = null;
                updateTestState(); displayQuestion();
            }
        }, 1000);

        const skipBreakBtn = document.getElementById('skipBreakBtn');
        if (skipBreakBtn) {
            skipBreakBtn.addEventListener('click', () => {
                clearInterval(breakInterval); breakInterval = null;
                updateTestState(); displayQuestion();
            });
        }
    }
    function updateBreakTimerDisplay() {
  const el = document.getElementById('breakTimerDisplay');
  if (el) el.innerText = jsVars.lang.break_timer_display.replace('{time}', formatTime(breakTimeRemaining));
}


    async function saveAnswer(questionId, answerValue) {
        userAnswers[questionId] = answerValue;
        try {
            const response = await fetch('/user/save_answer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ attempt_id: attemptId, question_id: questionId, user_answer_text: answerValue, _csrf_token: csrfToken })
            });
            const result = await response.json();
            if (!result.success) console.error(`${jsVars.lang.error_saving_answer}:`, result.message);
        } catch (error) {
            console.error(`${jsVars.lang.network_error_saving_answer}:`, error);
        }
    }

    prevQuestionBtn.addEventListener('click', () => {
        const prevQuestionSectionId = (currentQuestionIndex > 0) ? questionsData[currentQuestionIndex - 1].section_id : null;
        const currentQuestionSectionId = questionsData[currentQuestionIndex].section_id;

        if (currentQuestionIndex > 0 &&
            (currentQuestionSectionId === prevQuestionSectionId || !expiredSections.has(prevQuestionSectionId))) {
            currentQuestionIndex--;
            updateTestState();
            displayQuestion();
        }
    });

    nextQuestionBtn.addEventListener('click', () => {
        if (currentQuestionIndex < totalQuestions - 1) {
            const currentQuestionSectionId = questionsData[currentQuestionIndex].section_id;
            const nextQuestionSectionId = questionsData[currentQuestionIndex + 1].section_id;

            if (nextQuestionSectionId !== currentQuestionSectionId) {
                currentQuestionIndex++;
                displayBreakScreen();
            } else {
                currentQuestionIndex++;
                updateTestState();
                displayQuestion();
            }
        }
    });

    async function updateTestState() {
        let totalTimeSpent = (testDurationMinutes * 60) - timeRemainingSecondsOverall;
        if (totalTimeSpent < 0) totalTimeSpent = 0;

        const currentSection = questionsData[currentQuestionIndex]?.section_id || null;
        const currentSectionTimeSpent = currentSection ? (userSectionTimes[currentSection]?.time_spent || 0) : 0;

        try {
            const response = await fetch('/user/update_test_state.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({
                    attempt_id: attemptId,
                    current_question_index: currentQuestionIndex,
                    time_spent_seconds: totalTimeSpent,
                    section_id: currentSection,
                    time_spent_in_section: currentSectionTimeSpent,
                    _csrf_token: csrfToken
                })
            });
            const result = await response.json();
            if (!result.success) console.error(`${jsVars.lang.error_updating_test_state}:`, result.message);
        } catch (error) {
            console.error(`${jsVars.lang.network_error_updating_test_state}:`, error);
        }
    }

    function startOverallTestTimer() {
        if (overallTestTimerStarted) return;
        overallTestTimerStarted = true;

        if (testDurationMinutes <= 0) { overallTimerElement.style.display = 'none'; return; }

        overallTimerElement.style.display = 'inline-block';
        updateOverallTimerDisplay();

        overallTestInterval = setInterval(() => {
            timeRemainingSecondsOverall--;
            updateOverallTimerDisplay();

            if (timeRemainingSecondsOverall % 10 === 0 || timeRemainingSecondsOverall <= 0) updateTestState();

            if (timeRemainingSecondsOverall <= 0) {
                clearInterval(overallTestInterval); overallTestInterval = null; overallTestTimerStarted = false;
                clearInterval(sectionTimerInterval); sectionTimerInterval = null;
                submitTest();
            }
        }, 1000); //speed overall time
    }
    function updateOverallTimerDisplay() { overallTimerElement.innerText = `${jsVars.lang.overall_test_time}: ${formatTime(timeRemainingSecondsOverall)}`; }

    function startSectionTimer() {
        clearInterval(sectionTimerInterval); sectionTimerInterval = null;

        const currentQuestion = questionsData[currentQuestionIndex];
        const sectionDuration = currentQuestion.section_duration;

        if (sectionDuration > 0) {
            sectionTimerElement.style.display = 'inline-block';
            let totalSectionDuration = sectionDuration * 60;
            currentSectionTimeRemaining = Math.max(0, totalSectionDuration - sectionTimeSpent);
            updateSectionTimerDisplay();
        } else {
            sectionTimerElement.style.display = 'none';
            currentSectionTimeRemaining = 0;
        }

        if (currentQuestion.section_duration > 0) {
            sectionTimerInterval = setInterval(() => {
                currentSectionTimeRemaining--; sectionTimeSpent++;
                updateSectionTimerDisplay();

                if (sectionTimeSpent % 10 === 0 || currentSectionTimeRemaining <= 0) {
                    saveSectionTimeSpent(currentQuestion.section_id);
                }

                if (currentSectionTimeRemaining <= 0) {
                    clearInterval(sectionTimerInterval); sectionTimerInterval = null;
                    expiredSections.add(currentQuestion.section_id);

                    let nextSectionFound = false;
                    for (let i = currentQuestionIndex + 1; i < totalQuestions; i++) {
                        if (questionsData[i].section_id !== currentQuestion.section_id) {
                            currentQuestionIndex = i;
                            saveSectionTimeSpent(currentQuestion.section_id);
                            displayBreakScreen();
                            nextSectionFound = true;
                            break;
                        }
                    }
                    if (!nextSectionFound) {
                        saveSectionTimeSpent(currentQuestion.section_id);
                        submitTestTimeExpired();
                    }
                }
            }, 1000); //speed section time
        }
    }
    function updateSectionTimerDisplay() { sectionTimerElement.innerText = `${jsVars.lang.section_time_remaining}: ${formatTime(currentSectionTimeRemaining)}`; }

    function formatTime(totalSeconds) {
        if (totalSeconds < 0) totalSeconds = 0;
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes.toString().padStart(2,'0')}:${seconds.toString().padStart(2,'0')}`;
    }

    async function saveSectionTimeSpent(sectionId) {
        userSectionTimes[sectionId] = userSectionTimes[sectionId] || {};
        userSectionTimes[sectionId].time_spent = sectionTimeSpent;

        try {
            const response = await fetch('/user/update_section_time.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ attempt_id: attemptId, section_id: sectionId, time_spent_seconds: sectionTimeSpent, _csrf_token: csrfToken })
            });
            const result = await response.json();
            if (!result.success) console.error(`${jsVars.lang.error_saving_section_time}:`, result.message);
        } catch (error) {
            console.error(`${jsVars.lang.network_error_saving_section_time}:`, error);
        }
    }

    async function updateSectionStartTimestamp(sectionId, timestamp) {
        try {
            const response = await fetch('/user/update_section_time_start.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ attempt_id: attemptId, section_id: sectionId, start_timestamp: timestamp, _csrf_token: csrfToken })
            });
            const result = await response.json();
            if (!result.success) console.error(`${jsVars.lang.error_updating_section_start_timestamp}:`, result.message);
        } catch (error) {
            console.error(`${jsVars.lang.network_error_updating_section_start_timestamp}:`, error);
        }
    }

    function submitTest() {
        stopAllTimers();
        if (currentSectionId !== null) saveSectionTimeSpent(currentSectionId);

        Swal.fire({
            title: '<?php echo get_text("submitting_test_title"); ?>',
            text: '<?php echo get_text("submitting_test_message"); ?>',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        setTimeout(() => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/user/submit_test.php';

            const hiddenAttemptId = document.createElement('input');
            hiddenAttemptId.type = 'hidden'; hiddenAttemptId.name = 'attempt_id'; hiddenAttemptId.value = attemptId;

            const hiddenCsrf = document.createElement('input');
            hiddenCsrf.type = 'hidden'; hiddenCsrf.name = '_csrf_token'; hiddenCsrf.value = csrfToken;

            form.appendChild(hiddenCsrf);
            form.appendChild(hiddenAttemptId);
            document.body.appendChild(form);

            form.submit();
        }, 3000);
    }

    function submitTestTimeExpired() {
        stopAllTimers();

        Swal.fire({
            title: '<?php echo get_text("time_expired_title"); ?>',
            text: '<?php echo get_text("test_submitted_message"); ?>',
            icon: 'warning',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            timer: 10000,
            timerProgressBar: true
        }).then(() => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/user/submit_test.php';

            const hiddenAttemptId = document.createElement('input');
            hiddenAttemptId.type = 'hidden';
            hiddenAttemptId.name = 'attempt_id';
            hiddenAttemptId.value = attemptId;

            // ✅ ใส่ CSRF token ให้เหมือน submitTest()
            const hiddenCsrf = document.createElement('input');
            hiddenCsrf.type = 'hidden';
            hiddenCsrf.name = '_csrf_token';
            hiddenCsrf.value = csrfToken;

            const hiddenTimeExpired = document.createElement('input');
            hiddenTimeExpired.type = 'hidden';
            hiddenTimeExpired.name = 'time_expired';
            hiddenTimeExpired.value = '1';

            form.appendChild(hiddenCsrf);
            form.appendChild(hiddenAttemptId);
            form.appendChild(hiddenTimeExpired);
            document.body.appendChild(form);

            form.submit();
        });
    }
    submitTestBtn.addEventListener('click', () => { submitTest(); });

    function stopAllTimers() {
        if (overallTestInterval) { clearInterval(overallTestInterval); overallTestInterval = null; overallTestTimerStarted = false; }
        if (sectionTimerInterval) { clearInterval(sectionTimerInterval); sectionTimerInterval = null; }
        if (breakInterval) { clearInterval(breakInterval); breakInterval = null; }
        clearTimeout(afkTimer);
        if (alertSound) { alertSound.pause(); alertSound.currentTime = 0; }
    }

    if (exitTestBtn) {
        exitTestBtn.addEventListener('click', async (event) => {
            event.preventDefault();
            stopAllTimers();
            if (currentSectionId !== null) await saveSectionTimeSpent(currentSectionId);
            await updateTestState();
            if (typeof langSelect !== 'undefined' && langSelect) langSelect.prop('disabled', false);
            window.location.href = '/user';
        });
    }

    window.addEventListener('beforeunload', (event) => {
        let totalTimeSpent = (testDurationMinutes * 60) - timeRemainingSecondsOverall;
        if (totalTimeSpent < 0) totalTimeSpent = 0;

        if (currentSectionId !== null) {
            const sectionData = { attempt_id: attemptId, section_id: currentSectionId, time_spent_seconds: sectionTimeSpent };
            try {
                if (navigator.sendBeacon) {
                    sectionData._csrf_token = csrfToken;
                    navigator.sendBeacon('/user/update_section_time.php', JSON.stringify(sectionData));
                } else {
                    fetch('/user/update_section_time.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                        body: JSON.stringify(Object.assign({}, sectionData, { _csrf_token: csrfToken })),
                        keepalive: true
                    }).catch(() => {});
                }
            } catch(e) {}
        }

        const data = { attempt_id: attemptId, current_question_index: currentQuestionIndex, time_spent_seconds: totalTimeSpent, _csrf_token: csrfToken };
        try {
            if (navigator.sendBeacon) {
                navigator.sendBeacon('/user/update_test_state.php', JSON.stringify(data));
            } else {
                fetch('/user/update_test_state.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify(data),
                    keepalive: true
                }).catch(() => {});
            }
        } catch (e) {}
    });

    let langSelect;
    $(document).ready(() => {
        langSelect = $('select[name="lang"]');

        if (totalQuestions > 0) {
            if (testDurationMinutes > 0 && !overallTestTimerStarted) startOverallTestTimer();
            if (questionsData[currentQuestionIndex]?.section_duration > 0) startSectionTimer();

            if (langSelect && langSelect.length > 0) langSelect.prop('disabled', true);

            currentSectionId = questionsData[currentQuestionIndex].section_id;
            sectionTimeSpent = userSectionTimes[currentSectionId] ? userSectionTimes[currentSectionId].time_spent : 0;

            if (userSectionTimes[currentSectionId] && userSectionTimes[currentSectionId].start_timestamp) {
                currentSectionStartTime = userSectionTimes[currentSectionId].start_timestamp;
            } else {
                currentSectionStartTime = Math.floor(Date.now() / 1000);
            }
            displayQuestion();
        } else {
            questionDisplayArea.innerHTML = `<p class='text-center text-muted'>${jsVars.lang.no_questions_in_test || 'No questions'}</p>`;
            prevQuestionBtn.style.display = 'none';
            nextQuestionBtn.style.display = 'none';
            submitTestBtn.style.display = 'none';
            overallTimerElement.style.display = 'none';
            sectionTimerElement.style.display = 'none';
            if (langSelect) langSelect.prop('disabled', false);
        }
    });
</script>
