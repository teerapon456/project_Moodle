<?php
// admin/reports/individual.php
// รายงานรายบุคคล + กราฟสลับ (Radar/Bar/Histogram/Normal curve)
// Histogram: แกน X = 0..คะแนนเต็มจริงของ "ข้อที่แสดงจริง" ใน attempt ปัจจุบัน
// Legend Histogram แยก 2 รายการ: Frequency (แท่ง) / You are here (จุดแดง)
// กล่องสถิติ แสดงเฉพาะโหมด Histogram

require_once __DIR__ . '/../../includes/header.php';

$page_title = get_text('page_title_individual_report');

require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor')) {
    set_alert(get_text('alert_no_admin_permission', []), "danger");
    header("Location: /login");
    exit();
}

// เก็บ attempt_id รอบแรก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attempt_id'])) {
    $_SESSION['current_attempt_id'] = (int)$_POST['attempt_id'];
}

$attempt_id = $_POST['attempt_id'] ?? $_SESSION['current_attempt_id'] ?? null;
if (!is_numeric($attempt_id) || $attempt_id <= 0) {
    set_alert(get_text('error_invalid_attempt_id'), "danger");
    header("Location: /admin/reports");
    exit();
}

$report_data = [];
$user_info = [];
$test_info = [];
$min_passing_score = 0;

$pie_chart_earned_score = 0.0;
$pie_chart_max_score_auto_graded = 0.0;
$total_max_shown = 0.0; // คะแนนเต็มจริงของ attempt นี้ (sum max เฉพาะ "ข้อที่แสดงจริง")
$overall_max_score_for_graded_questions = 0.0;

$radar_labels = [];
$radar_data = [];
$sections_data = [];
$categories_data = [];
$has_categories = false;

$mc_tf_correct = 0;
$mc_tf_incorrect = 0;
$mc_tf_not_answered = 0;
$sa_graded = 0;
$sa_pending = 0;

$failed_critical_question = false;
$test_passed = true;

// สำหรับ Histogram/Normal curve (ดิสทริบิวชันทุก attempt ที่ทำเสร็จของ test นี้)
$all_attempt_scores = [];
$current_attempt_score = 0;
$test_id_for_distribution = null;

try {
    // -------- 1) attempt + user + test --------
    $stmt = $conn->prepare("
        SELECT
            uta.attempt_id, uta.test_id, uta.start_time, uta.end_time, uta.total_score, uta.is_completed, uta.time_spent_seconds,
            u.full_name AS user_name, u.email,
            t.test_id AS t_test_id, t.test_name, t.description AS test_description, t.min_passing_score
        FROM iga_user_test_attempts uta
        JOIN users u ON uta.user_id = u.user_id
        JOIN iga_tests t ON uta.test_id = t.test_id
        WHERE uta.attempt_id = ?
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $attempt_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$attempt_info) {
        set_alert(get_text('error_attempt_data_not_found'), "danger");
        header("Location: /admin/reports");
        exit();
    }

    $test_id_for_distribution = (int)$attempt_info['t_test_id'];

    $user_info = [
        'full_name' => $attempt_info['user_name'],
        'email'     => $attempt_info['email']
    ];
    $test_info = [
        'test_name'          => $attempt_info['test_name'],
        'test_description'   => $attempt_info['test_description'],
        'start_time'         => $attempt_info['start_time'],
        'end_time'           => $attempt_info['end_time'],
        'total_score_earned' => (float)($attempt_info['total_score'] ?? 0),
        'is_completed'       => (int)$attempt_info['is_completed'] === 1,
        'time_spent_seconds' => $attempt_info['time_spent_seconds']
    ];
    $min_passing_score = (float)($attempt_info['min_passing_score'] ?? 0);

    // -------- 2) คำถามที่ "แสดงจริง" ของ attempt นี้ --------
    $stmt = $conn->prepare("
        SELECT
            s.section_id, s.section_name, s.description AS section_description, s.section_order, s.duration_minutes,
            q.question_id, q.question_text, q.question_type, COALESCE(q.score,0) AS question_max_score,
            q.question_order, q.is_critical,
            qc.category_id, qc.category_name,
            ua.user_answer_text, ua.is_correct AS user_is_correct, ua.score_earned,
            correct_o.option_id AS correct_option_id, correct_o.option_text AS correct_option_text, correct_o.is_correct AS option_is_correct,
            user_chosen_option.option_text AS user_chosen_option_text_display,
            uaq_order.shown_order
        FROM (
            SELECT question_id, MIN(shown_order) AS shown_order
            FROM iga_user_attempt_questions
            WHERE attempt_id = ?
            GROUP BY question_id
        ) uaq_order
        JOIN iga_questions q ON q.question_id = uaq_order.question_id
        JOIN iga_sections  s ON s.section_id  = q.section_id
        LEFT JOIN iga_question_categories qc ON q.category_id = qc.category_id
        LEFT JOIN iga_user_answers ua
               ON ua.attempt_id = ?
              AND ua.question_id = uaq_order.question_id
        LEFT JOIN iga_question_options correct_o
               ON correct_o.question_id = q.question_id
              AND correct_o.is_correct  = 1
        LEFT JOIN iga_question_options user_chosen_option
               ON (q.question_type IN ('multiple_choice','true_false'))
              AND (ua.user_answer_text = user_chosen_option.option_id)
        ORDER BY uaq_order.shown_order ASC
    ");
    $stmt->bind_param("ii", $attempt_id, $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $section_id = (int)$row['section_id'];
        a:
        $question_id = (int)$row['question_id'];
        $q_type = $row['question_type'];
        $q_max  = (float)$row['question_max_score'];
        $score_earned = ($row['score_earned'] !== null) ? (float)$row['score_earned'] : null;
        $user_is_correct = $row['user_is_correct'];

        if ($row['category_id'] !== null) $has_categories = true;

        if (!isset($sections_data[$section_id])) {
            $sections_data[$section_id] = [
                'section_id'                       => $section_id,
                'section_name'                     => $row['section_name'],
                'section_description'              => $row['section_description'],
                'section_order'                    => (int)$row['section_order'],
                'duration_minutes'                 => (int)$row['duration_minutes'],
                'questions'                        => [],
                'section_score_earned'             => 0.0,
                'section_max_score'                => 0.0,
                'section_score_earned_auto_graded' => 0.0,
                'section_max_score_auto_graded'    => 0.0,
            ];
        }

        if ($row['category_id'] !== null) {
            $cat_id = (int)$row['category_id'];
            if (!isset($categories_data[$cat_id])) {
                $categories_data[$cat_id] = [
                    'category_id'            => $cat_id,
                    'category_name'          => $row['category_name'],
                    'questions'              => [],
                    'category_score_earned'  => 0.0,
                    'category_max_score'     => 0.0,
                ];
            }
        }

        $user_answer_display = ($q_type === 'multiple_choice' || $q_type === 'true_false')
            ? $row['user_chosen_option_text_display']
            : $row['user_answer_text'];

        if (!isset($sections_data[$section_id]['questions'][$question_id])) {
            $sections_data[$section_id]['questions'][$question_id] = [
                'question_id'        => $question_id,
                'question_text'      => $row['question_text'],
                'question_type'      => $q_type,
                'question_max_score' => $q_max,
                'question_order'     => (int)$row['question_order'],
                'is_critical'        => ((int)$row['is_critical'] === 1),
                'user_answer_text'   => $user_answer_display,
                'user_is_correct'    => $user_is_correct,
                'score_earned'       => $score_earned,
                'correct_answer'     => null,
                'category_name'      => $row['category_name'],
            ];
        }

        if ((int)$row['is_critical'] === 1 && $user_is_correct === 0) $failed_critical_question = true;

        // รวมคะแนนเต็ม/คะแนนที่ได้ของ attempt นี้
        if ($q_max > 0) {
            $total_max_shown += $q_max;
            $sections_data[$section_id]['section_max_score'] += $q_max;
        }
        if ($score_earned !== null) $sections_data[$section_id]['section_score_earned'] += $score_earned;

        // สำหรับ Pie
        if (in_array($q_type, ['multiple_choice','true_false','short_answer'])) {
            if ($q_max > 0) $pie_chart_max_score_auto_graded += $q_max;
            if ($score_earned !== null) $pie_chart_earned_score += $score_earned;
        }

        // auto-graded ต่อ section
        if (in_array($q_type, ['multiple_choice','true_false'])) {
            if ($q_max > 0) $sections_data[$section_id]['section_max_score_auto_graded'] += $q_max;
            if ($score_earned !== null) $sections_data[$section_id]['section_score_earned_auto_graded'] += $score_earned;
        }

        // ความถี่ MC/TF
        if (in_array($q_type, ['multiple_choice','true_false'])) {
            if ($row['user_answer_text'] === null || $row['user_answer_text'] === '') $mc_tf_not_answered++;
            elseif ($user_is_correct === 1) $mc_tf_correct++;
            elseif ($user_is_correct === 0) $mc_tf_incorrect++;
        } elseif ($q_type === 'short_answer') {
            if ($score_earned !== null) $sa_graded++; else $sa_pending++;
        }

        // เฉลย
        if (in_array($q_type, ['multiple_choice','true_false']) && (int)$row['option_is_correct'] === 1) {
            $sections_data[$section_id]['questions'][$question_id]['correct_answer'] = $row['correct_option_text'];
        }

        // รวมต่อหมวด
        if ($row['category_id'] !== null) {
            if ($q_max > 0) $categories_data[$cat_id]['category_max_score'] += $q_max;
            if ($score_earned !== null) $categories_data[$cat_id]['category_score_earned'] += $score_earned;
        }
    }
    $stmt->close();

    $overall_max_score_for_graded_questions = max(0.0, $total_max_shown);

    // Radar/Bar (percent)
    if ($has_categories) {
        usort($categories_data, fn($a,$b) => $a['category_id'] - $b['category_id']);
    }
    $radar_src = $has_categories ? $categories_data : $sections_data;
    foreach ($radar_src as $item) {
        $label = $has_categories ? ($item['category_name'] ?? get_text('not_available_abbr'))
                                 : ($item['section_name']  ?? get_text('not_available_abbr'));
        $max   = $has_categories ? (float)$item['category_max_score']  : (float)$item['section_max_score'];
        $earn  = $has_categories ? (float)$item['category_score_earned']: (float)$item['section_score_earned'];
        if ($max > 0) {
            $radar_labels[] = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            $radar_data[]   = ($earn / $max) * 100.0;
        }
    }

    // สถานะผ่าน/ไม่ผ่าน
    if ($failed_critical_question) $test_passed = false;
    if ($test_passed && $overall_max_score_for_graded_questions > 0 && $min_passing_score > 0) {
        $achieved_percent = ($pie_chart_earned_score / $overall_max_score_for_graded_questions) * 100.0;
        if ($achieved_percent + 1e-9 < $min_passing_score) $test_passed = false;
    }

    // คะแนน attempt นี้ (ดิบ)
    $stmt = $conn->prepare("
        SELECT SUM(COALESCE(ua.score_earned,0)) AS earned_total
        FROM (
            SELECT question_id, MIN(shown_order) AS min_shown
            FROM iga_user_attempt_questions
            WHERE attempt_id = ?
            GROUP BY question_id
        ) uq
        LEFT JOIN iga_user_answers ua
          ON ua.attempt_id = ?
         AND ua.question_id = uq.question_id
    ");
    $stmt->bind_param("ii", $attempt_id, $attempt_id);
    $stmt->execute();
    $rowCur = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $current_attempt_score = (float)($rowCur['earned_total'] ?? 0);

    // คะแนนของทุก attempt ที่ทำเสร็จใน test นี้ (ดิบ)
    if ($test_id_for_distribution) {
        $stmt = $conn->prepare("
            WITH shown AS (
                SELECT uaq.attempt_id, uaq.question_id, MIN(uaq.shown_order) AS min_shown
                FROM iga_user_attempt_questions uaq
                JOIN iga_user_test_attempts uta ON uta.attempt_id = uaq.attempt_id
                WHERE uta.test_id = ?
                  AND uta.is_completed = 1
                GROUP BY uaq.attempt_id, uaq.question_id
            )
            SELECT s.attempt_id, SUM(COALESCE(ua.score_earned,0)) AS earned_total
            FROM shown s
            LEFT JOIN iga_user_answers ua
              ON ua.attempt_id = s.attempt_id
             AND ua.question_id = s.question_id
            GROUP BY s.attempt_id
        ");
        $stmt->bind_param("i", $test_id_for_distribution);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $sc = (float)($r['earned_total'] ?? 0);
            if (is_finite($sc)) $all_attempt_scores[] = $sc;
        }
        $stmt->close();
    }
    if (empty($all_attempt_scores) && is_finite($current_attempt_score)) {
        $all_attempt_scores[] = $current_attempt_score;
    }

} catch (Throwable $e) {
    set_alert(get_text('error_fetching_report_data_individual', $e->getMessage()), "danger");
    $report_data = [];
}

echo get_alert();
?>

<div class="container-fluid w-80-custom py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0 text-primary-custom"><?php echo get_text('page_heading_individual_report'); ?></h1>
        <div class="d-flex">
            <a href="/admin/reports" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text('back_to_overview_report'); ?>
            </a>
            <a href="/admin/export-report-pdf?attempt_id=<?php echo htmlspecialchars($attempt_id); ?>" class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf me-2"></i> <?php echo get_text('export_pdf_button'); ?>
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary-custom text-white">
            <h4 class="mb-0"><?php echo get_text('examinee_test_info_heading'); ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong><?php echo get_text('examinee_name_label'); ?></strong> <?php echo htmlspecialchars($user_info['full_name'] ?? get_text('not_available_abbr')); ?></p>
                    <p><strong><?php echo get_text('email_label'); ?>:</strong> <?php echo htmlspecialchars($user_info['email'] ?? get_text('not_available_abbr')); ?></p>
                    <p><strong><?php echo get_text('test_name_label'); ?></strong> <?php echo htmlspecialchars($test_info['test_name'] ?? get_text('not_available_abbr')); ?></p>
                    <p><strong><?php echo get_text('test_description_label'); ?></strong> <?php echo nl2br(htmlspecialchars($test_info['test_description'] ?? get_text('not_available_abbr'))); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong><?php echo get_text('start_time_label'); ?></strong> <?php echo htmlspecialchars(thai_datetime_format($test_info['start_time'] ?? null)); ?></p>
                    <p><strong><?php echo get_text('end_time_label'); ?></strong> <?php echo htmlspecialchars($test_info['end_time'] ? thai_datetime_format($test_info['end_time']) : get_text('not_completed_status')); ?></p>
                    <p><strong><?php echo get_text('total_time_spent_label'); ?></strong> <?php echo formatTimeSpent($test_info['time_spent_seconds'] ?? null); ?></p>
                    <p>
                        <strong><?php echo get_text('total_score_earned_label'); ?></strong>
                        <span class="fs-4 text-success"><?php echo htmlspecialchars(number_format($pie_chart_earned_score, 2)); ?></span> /
                        <span class="fs-4 text-muted"><?php echo htmlspecialchars(number_format($overall_max_score_for_graded_questions, 2)); ?></span>
                        <span class="ms-2 badge <?php echo ($test_info['is_completed'] ?? false) ? 'bg-success' : 'bg-warning text-dark'; ?>">
                            <i class="fas <?php echo ($test_info['is_completed'] ?? false) ? 'fa-check-circle' : 'fa-hourglass-half'; ?> me-1"></i>
                            <?php echo ($test_info['is_completed'] ?? false) ? get_text('completed_status') : get_text('in_progress_status'); ?>
                        </span>
                    </p>
                    <p>
                        <strong><?php echo get_text('test_result_label'); ?></strong>
                        <?php if ($test_info['is_completed']): ?>
                            <span class="fs-4 badge <?php echo $test_passed ? 'bg-success' : 'bg-danger'; ?>">
                                <i class="fas <?php echo $test_passed ? 'fa-check-circle' : 'fa-times-circle'; ?> me-1"></i>
                                <?php echo $test_passed ? get_text('test_status_passed') : get_text('test_status_failed'); ?>
                            </span>
                            <?php if (!$test_passed): ?>
                                <?php if ($failed_critical_question): ?>
                                    <br><small class="text-danger ms-2"><i class="fas fa-exclamation-triangle"></i> <?php echo get_text('critical_question_failed_message'); ?></small>
                                <?php endif; ?>
                                <?php
                                if ($overall_max_score_for_graded_questions > 0 && $min_passing_score > 0) {
                                    $achieved_percent = ($pie_chart_earned_score / $overall_max_score_for_graded_questions) * 100.0;
                                    if ($achieved_percent + 1e-9 < $min_passing_score) {
                                        echo '<br><small class="text-danger ms-2"><i class="fas fa-exclamation-triangle"></i> ' .
                                             sprintf(get_text('minimum_score_not_met_message'), number_format($min_passing_score, 2) . '%') .
                                             '</small>';
                                    }
                                }
                                ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="fas badge bg-info text-dark">
                                <i class="fas fa-hourglass-half me-1"></i> <?php echo get_text('test_status_pending'); ?>
                            </span>
                        <?php endif; ?>
                    </p>
                    <?php if ($min_passing_score > 0): ?>
                        <p><strong><?php echo get_text('minimum_pass_score_label'); ?></strong> <?php echo htmlspecialchars(number_format($min_passing_score, 2)); ?>%</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary-custom text-white">
                    <h5 class="mb-0"><?php echo get_text('pie_chart_title'); ?></h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <canvas id="pieChart" style="max-height: 300px; height: max-content; max-width: 300px;"></canvas>
                    <div class="mt-3 text-center">
                        <p class="mb-0 fs-5"><strong><?php echo get_text('score_earned_label'); ?>:</strong> <span class="text-success"><?php echo htmlspecialchars(number_format($pie_chart_earned_score, 2)); ?> <strong><?php echo get_text('label_score'); ?></strong></span></p>
                        <p class="mb-0 fs-5"><strong><?php echo get_text('score_possible_label'); ?>:</strong> <span class="text-muted"><?php echo htmlspecialchars(number_format($pie_chart_max_score_auto_graded, 2)); ?> <strong><?php echo get_text('label_score'); ?></strong></span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟสลับ Radar / Bar / Histogram / Normal -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
                    <?php if ($has_categories): ?>
                        <h5 class="mb-0"><?php echo get_text('radar_chart_title_category'); ?></h5>
                    <?php else: ?>
                        <h5 class="mb-0"><?php echo get_text('radar_chart_title'); ?></h5>
                    <?php endif; ?>
                    <div class="d-flex align-items-center gap-2">
                        <label for="chartTypeSelect" class="me-2 mb-0 fw-semibold"><?php echo get_text('chart_type_label') ?: 'Chart type'; ?>:</label>
                        <select id="chartTypeSelect" class="form-select form-select-sm" style="width:auto">
                            <option value="radar"><?php echo get_text('radar_chart_option') ?: 'Radar chart'; ?></option>
                            <option value="bar"><?php echo get_text('bar_chart_option') ?: 'Bar chart'; ?></option>
                            <option value="hist"><?php echo get_text('histogram_normal_curve_option') ?: 'Histogram chart'; ?></option>
                        </select>
                    </div>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div id="radarLegend" class="mb-2"></div>
                    <canvas id="switchableChart" style="max-height: 300px; max-width: max-content;"></canvas>
                    <div class="mt-3 w-100">
                        <div id="statsBox" class="alert alert-light border d-flex flex-wrap gap-3 mb-0 d-none" style="font-size:.95rem"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary-custom text-white">
            <h5 class="mb-0"><?php echo get_text('frequency_table_title'); ?></h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo get_text('question_type_label'); ?></th>
                        <th><?php echo get_text('status_label'); ?></th>
                        <th><?php echo get_text('count_label'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td rowspan="3"><?php echo get_text('multiple_choice_and_true_false_label'); ?></td>
                        <td><?php echo get_text('questions_correct_label'); ?></td>
                        <td><?php echo $mc_tf_correct; ?></td>
                    </tr>
                    <tr>
                        <td><?php echo get_text('questions_incorrect_label'); ?></td>
                        <td><?php echo $mc_tf_incorrect; ?></td>
                    </tr>
                    <tr>
                        <td><?php echo get_text('questions_not_answered_label'); ?></td>
                        <td><?php echo $mc_tf_not_answered; ?></td>
                    </tr>
                    <tr>
                        <td rowspan="2"><?php echo get_text('short_answer_label'); ?></td>
                        <td><?php echo get_text('short_answers_graded_label'); ?></td>
                        <td><?php echo $sa_graded; ?></td>
                    </tr>
                    <tr>
                        <td><?php echo get_text('short_answers_pending_label'); ?></td>
                        <td><?php echo $sa_pending; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <hr>

    <h2 class="mt-4 mb-3 text-primary-custom"><?php echo get_text('section_score_details_heading'); ?></h2>

    <?php if (!empty($sections_data)): ?>
        <div id="accordionReportSections">
            <?php foreach ($sections_data as $section): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" id="sectionHeading<?php echo $section['section_id']; ?>">
                        <h5 class="mb-0">
                            <button class="btn btn-link text-white text-decoration-none d-flex align-items-center" data-bs-toggle="collapse" data-bs-target="#sectionCollapse<?php echo $section['section_id']; ?>" aria-expanded="true" aria-controls="sectionCollapse<?php echo $section['section_id']; ?>">
                                <i class="fas fa-chevron-down me-2"></i>
                                <?php echo htmlspecialchars($section['section_order'] . ". " . $section['section_name']); ?>
                                <span class="badge bg-light text-dark ms-3">
                                    <?php echo get_text('section_score_label'); ?>
                                    <?php echo htmlspecialchars(number_format($section['section_score_earned'], 2)); ?>
                                    /
                                    <?php echo htmlspecialchars(number_format($section['section_max_score'], 2)); ?>
                                </span>
                            </button>
                        </h5>
                    </div>
                    <div id="sectionCollapse<?php echo $section['section_id']; ?>" class="collapse show" aria-labelledby="sectionHeading<?php echo $section['section_id']; ?>" data-bs-parent="#accordionReportSections">
                        <div class="card-body">
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($section['section_description'])); ?></p>
                            <?php if ($section['duration_minutes'] > 0): ?>
                                <p class="text-muted"><strong><?php echo get_text('duration_label'); ?></strong> <?php echo htmlspecialchars($section['duration_minutes']); ?> <?php echo get_text('minutes_unit'); ?></p>
                            <?php else: ?>
                                <p class="text-muted"><strong><?php echo get_text('duration_label'); ?></strong> <?php echo get_text('unlimited_duration'); ?></p>
                            <?php endif; ?>
                            <hr>
                            <h6><?php echo get_text('questions_answers_heading'); ?></h6>
                            <?php if (!empty($section['questions'])): ?>
                                <div class="list-group">
                                    <?php foreach ($section['questions'] as $question): ?>
                                        <div class="list-group-item mb-2 shadow-sm rounded-3 
                                            <?php 
                                                if ($question['question_type'] === 'short_answer') {
                                                    echo ($question['score_earned'] !== NULL) ? 'border-success' : 'border-warning';
                                                } else {
                                                    echo ($question['user_is_correct'] === 1) ? 'border-success' : (($question['user_is_correct'] === 0) ? 'border-danger' : '');
                                                }
                                            ?>
                                        ">
                                            <h6 class="mb-1">
                                                <span class="badge 
                                                    <?php 
                                                        if ($question['question_type'] === 'short_answer') {
                                                            echo ($question['score_earned'] !== NULL) ? 'bg-success' : 'bg-warning text-dark';
                                                        } else {
                                                            echo ($question['user_is_correct'] === 1) ? 'bg-success' : (($question['user_is_correct'] === 0) ? 'bg-danger' : 'bg-secondary');
                                                        }
                                                    ?> 
                                                me-2">
                                                    <?php
                                                        if ($question['question_type'] === 'short_answer') {
                                                            echo ($question['score_earned'] !== NULL) ? get_text('graded') : get_text('pending');
                                                        } else if ($question['user_is_correct'] === 1) { 
                                                            echo '<i class="fas fa-check"></i> ' . get_text('correct_answer_badge'); 
                                                        } else if ($question['user_is_correct'] === 0) { 
                                                            echo '<i class="fas fa-times"></i> ' . get_text('incorrect_answer_badge'); 
                                                        } else { 
                                                            echo get_text('not_available_abbr'); 
                                                        } 
                                                    ?>
                                                </span>
                                                <?php echo htmlspecialchars($question['question_order'] . ". " . $question['question_text']); ?>
                                                <br><small class="text-muted ms-2">
                                                    (<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $question['question_type']))); ?>
                                                    | <?php echo get_text('score_earned_label_question'); ?>
                                                    <?php echo htmlspecialchars(number_format($question['score_earned'] ?? 0, 2)); ?>/<?php echo htmlspecialchars($question['question_max_score']); ?>
                                                     | <?php echo $question['category_name']; ?>)
                                                    <?php if (!empty($question['is_critical'])): ?>
                                                        <span class="badge bg-warning text-dark ms-1"><i class="fas fa-exclamation-triangle"></i> <?php echo get_text('critical_question_label'); ?></span>
                                                    <?php endif; ?>
                                                </small>
                                            </h6>
                                            <p class="mb-1 ms-4">
                                                <strong><?php echo get_text('user_answer_label'); ?></strong>
                                                <?php if (!empty($question['user_answer_text'])): ?>
                                                    <?php echo nl2br(htmlspecialchars($question['user_answer_text'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php echo get_text('not_answered'); ?></span>
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false'): ?>
                                                <p class="mb-0 ms-4">
                                                    <strong><?php echo get_text('correct_answer_display_label'); ?></strong>
                                                    <span class="text-success"><?php echo htmlspecialchars($question['correct_answer'] ?? get_text('no_correct_answer_available')); ?></span>
                                                </p>
                                            <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                                <p class="mb-0 ms-4 text-muted"><em><?php echo get_text('short_answer_admin_review_note'); ?></em></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted"><?php echo get_text('no_questions_or_incomplete_data'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_report_details_found'); ?>
        </div>
    <?php endif; ?>
</div>

<style>
  #radarLegend {
    display:flex; flex-wrap:wrap; gap:6px; justify-content:center; margin-bottom:8px; font-size:12px;
  }
  #radarLegend .item {
    display:inline-flex; align-items:center; gap:8px;
    border:1px solid #e0e0e0; border-radius:8px; padding:6px 8px; cursor:pointer;
    user-select:none; transition:opacity .15s ease; background:#fff;
  }
  #radarLegend .swatch { width:10px; height:10px; border:2px solid rgba(0,0,0,.2); border-radius:3px; }
  #radarLegend .item.off { opacity:.5; }
  #radarLegend .item.off .name { text-decoration:line-through; }

  #statsBox .stat-item{
    display:inline-flex; gap:6px; align-items:center; padding:4px 8px;
    border:1px dashed #e0e0e0; border-radius:8px; background:#fff;
  }
  #statsBox .label{opacity:.75; font-weight:600}
  #statsBox .value{font-variant-numeric: tabular-nums}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script>
  // ===== PIE =====
  Chart.register(ChartDataLabels);
  const pieEarned = <?php echo json_encode((float)number_format($pie_chart_earned_score, 2, '.', '')); ?>;
  const pieMax    = <?php echo json_encode((float)number_format($pie_chart_max_score_auto_graded, 2, '.', '')); ?>;
  const pieRemain = Math.max(0, pieMax - pieEarned);

  new Chart(document.getElementById('pieChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: ['<?php echo get_text('chart_score_earned'); ?>', '<?php echo get_text('chart_score_fail'); ?>'],
    datasets: [{
      data: [pieEarned, pieRemain],
      backgroundColor: ['rgba(75, 192, 192, 0.8)', 'rgba(255, 99, 132, 0.8)'],
      hoverOffset: 10
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top' },
      tooltip: {
        callbacks: {
          label: (ctx) => {
            let label = ctx.label ? ctx.label + ': ' : '';
            const v = (ctx.parsed !== null) ? parseFloat(ctx.parsed) : 0;
            return label + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
              .format(v) + ' <?php echo get_text('score_label_suffix'); ?>';
          }
        }
      },
      datalabels: {
        color: '#fff',
        borderRadius: 4,
        font: { weight: 'bold', size: 14 },
        formatter: (value, context) =>
          context.dataIndex === 0
            ? `<?php echo get_text('chart_score_earned'); ?>\n${pieEarned.toFixed(2)}`
            : `<?php echo get_text('chart_score_fail'); ?>\n${pieRemain.toFixed(2)}`,
        anchor: 'center',
        align: 'center',
        offset: 0
      }
    }
  }
});


  // ===== DATA สำหรับ Radar/Bar (เปอร์เซ็นต์) =====
  const allRadarLabels = <?php echo json_encode($radar_labels); ?>;
  const allRadarValues = <?php echo json_encode(array_map(function($v){ return round($v, 2); }, $radar_data)); ?>;

  // ===== Histogram/Normal (ใช้แกนคงที่ 0..FULL_INT ของ attempt ปัจจุบัน) =====
  const allAttemptScores    = <?php echo json_encode(array_map(function($v){ return round($v, 2); }, $all_attempt_scores)); ?>;
  const currentAttemptScore = <?php echo json_encode(round($current_attempt_score, 2)); ?>;
  const fullScoreCurrent    = <?php echo json_encode((float)$total_max_shown); ?>;
  const FULL_INT            = Math.max(1, Math.ceil(fullScoreCurrent)); // 0..FULL_INT

  function makeColor(i, a=0.8){ const h=(i*47)%360; return `hsla(${h},70%,55%,${a})`; }

  const shown = new Set(allRadarLabels.map((_, i)=>i));
  function subset(arr){ return arr.filter((_,i)=>shown.has(i)); }

  function computeStats(values){
    const n = values.length || 0;
    if (!n) return { n:0, mean:0, median:0, sd:0, min:0, max:0 };
    const sorted = [...values].sort((a,b)=>a-b);
    const sum = values.reduce((s,v)=>s+v,0);
    const mean = sum / n;
    const median = (n % 2) ? sorted[(n-1)/2] : (sorted[n/2-1] + sorted[n/2]) / 2;
    const variance = values.reduce((s,v)=> s + Math.pow(v-mean,2), 0) / n;
    const sd = Math.sqrt(variance);
    return { n, mean, median, sd, min:sorted[0], max:sorted[n-1] };
  }

  // ====== StatsBox helpers (แสดงเฉพาะ Histogram) ======
  function setStatsVisible(visible) {
    const box = document.getElementById('statsBox');
    if (!box) return;
    if (visible) box.classList.remove('d-none');
    else { box.classList.add('d-none'); box.innerHTML = ''; }
  }
  function renderStatsBox(values){
    const box = document.getElementById('statsBox');
    if (!box) return;
    const { n, mean, median, sd, min, max } = computeStats(values);
    const T = <?php echo json_encode([
      'count_label'  => get_text('count_label') ?: 'Count',
      'mean_label'   => get_text('mean_label') ?: 'Mean',
      'median_label' => get_text('median_label') ?: 'Median',
      'stddev_label' => get_text('stddev_label') ?: 'Std. Dev.',
      'min_label'    => get_text('min_label') ?: 'Min',
      'max_label'    => get_text('max_label') ?: 'Max',
      'you_are_here' => get_text('you_are_here') ?: 'Your score',
      'label_score'  => get_text('label_score') ?: 'Score',
    ]); ?>;
    const fmt2 = (x)=> (isFinite(x) ? x.toFixed(2) : '-');
    box.innerHTML = `
      <span class="stat-item"><span class="label">${T.count_label}:</span><span class="value">${n}</span></span>
      <span class="stat-item"><span class="label">${T.mean_label}:</span><span class="value">${fmt2(mean)} ${T.label_score}</span></span>
      <span class="stat-item"><span class="label">${T.median_label}:</span><span class="value">${fmt2(median)} ${T.label_score}</span></span>
      <span class="stat-item"><span class="label">${T.min_label}:</span><span class="value">${fmt2(min)} ${T.label_score}</span></span>
      <span class="stat-item"><span class="label">${T.max_label}:</span><span class="value">${fmt2(max)} ${T.label_score}</span></span>
      <span class="stat-item"><span class="label">${T.stddev_label}:</span><span class="value">${fmt2(sd)} ${T.label_score}</span></span>

      `;
  }

  let curChart = null;
  const chartCanvas = document.getElementById('switchableChart').getContext('2d');
  const chartTypeSelect = document.getElementById('chartTypeSelect');
  function destroyChart(){ if (curChart){ curChart.destroy(); curChart = null; } }

  // ===== Radar =====
  function renderRadar(labels, values){
    destroyChart();
    setStatsVisible(false); // ซ่อน stats ในโหมดนี้
    curChart = new Chart(chartCanvas, {
      type: 'radar',
      data: {
        labels,
        datasets: [{
          label: '<?php echo get_text('radar_chart_dataset_label') ?: "Performance"; ?>',
          data: values,
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 2,
          pointBackgroundColor: 'rgba(54, 162, 235, 1)',
          pointBorderColor: '#fff',
          pointHoverBackgroundColor: '#fff',
          pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { display:false },
          tooltip: { callbacks:{ label:(ctx)=> (ctx.parsed.r !== null ? `${Math.round(ctx.parsed.r)}%` : '') } },
          datalabels: {
            color:'rgba(0,0,0,.85)', borderWidth:1, borderRadius:4,
            padding:{top:4,bottom:4,left:6,right:6},
            font:{weight:'bold',size:10},
            formatter:(v)=> (parseFloat(v).toFixed(0) + '%'),
            anchor:'center', align:'center', offset:0, clamp:true
          }
        },
        scales: {
          r: {
            angleLines:{ display:false }, suggestedMin:0, suggestedMax:100,
            pointLabels:{ font:{size:10}, maxWidth:90, padding:40, color:'black' },
            ticks:{ display:false }
          }
        }
      }
    });
  }

  // ===== Bar =====
  function renderBar(labels, values){
    destroyChart();
    setStatsVisible(false); // ซ่อน stats ในโหมดนี้
    curChart = new Chart(chartCanvas, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: '<?php echo get_text('bar_chart_dataset_label') ?: "Score %"; ?>',
          data: values,
          backgroundColor: values.map((_,i)=> makeColor(i, .6)),
          borderColor: values.map((_,i)=> makeColor(i, 1)),
          borderWidth: 1
        }]
      },
      options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{
          legend:{ display:false },
          tooltip:{ callbacks:{ label:(ctx)=> `${(ctx.parsed?.y ?? 0).toFixed(0)}%` } },
          datalabels: {
            color:'rgba(0,0,0,.85)',
            font:{weight:'bold', size:10},
            formatter:(v)=> (parseFloat(v).toFixed(0) + '%'),
            anchor:'end', align:'top'
          }
        },
        scales:{
          x:{ ticks:{ autoSkip:false, maxRotation:45, minRotation:0 } },
          y:{ suggestedMin:0, suggestedMax:100, ticks:{ callback:(v)=> v + '%' } }
        }
      }
    });
  }

  // ===== Histogram + Normal curve overlay + You-are-here =====
function renderHistogramFixedRange(allScores, curScore, fullInt){
  destroyChart();
  setStatsVisible(true);
  renderStatsBox(allScores);

  const N = Math.max(1, parseInt(fullInt,10));              // x = 0..N
  const labels = Array.from({length: N+1}, (_,i)=> String(i));
  const counts = new Array(N+1).fill(0);

  // ---- นับความถี่: ปัดลงเป็น bin (k = floor(score)) คีบใน [0,N]
  (allScores||[]).forEach(s => {
    if (!Number.isFinite(s)) return;
    let k = Math.floor(s);
    if (k < 0) k = 0;
    if (k > N) k = N;
    counts[k]++;
  });

  // ---- จุด you_are_here: วางบนปลายแท่ง
  const youData = new Array(N+1).fill(null);
  if (Number.isFinite(curScore)) {
    let idx = Math.min(N, Math.max(0, Math.floor(curScore)));
    youData[idx] = counts[idx];
  }

  // ---- คำนวณ normal curve (สเกลให้เทียบกับ "จำนวนคนต่อ bin=1")
  const n = (allScores || []).length;
  let mean = 0, sd = 0;
  if (n > 0) {
    mean = allScores.reduce((s,v)=>s+v,0)/n;
    const varPop = allScores.reduce((s,v)=> s + Math.pow(v-mean,2), 0) / n; // ประชากร
    sd = Math.sqrt(varPop);
  }

  // ใช้ค่าที่ "กึ่งกลาง bin" เพื่อให้แนบกับแท่ง (k+0.5)
  const binWidth = 1;
  const curveAtBins = new Array(N+1).fill(0);
  function pdfNorm(x, mu, s){
    if (!(s > 0)) return 0;
    const z = (x - mu) / s;
    return Math.exp(-0.5*z*z) / (s * Math.sqrt(2*Math.PI));
  }
  if (sd > 0 && n > 0) {
    for (let k=0; k<=N; k++){
      const xMid = k + 0.5; // กึ่งกลางช่วง [k, k+1)
      // scale = n * binWidth เพื่อให้พื้นที่ใต้โค้ง ≈ จำนวนคนทั้งหมด
      curveAtBins[k] = pdfNorm(xMid, mean, sd) * n * binWidth;
    }
  }

  // ---- y-axis ให้ครอบทั้งแท่งและเส้นโค้ง
  const peakBars  = Math.max(...counts, 1);
  const peakCurve = Math.max(...curveAtBins, 0);
  const yMax = Math.ceil(Math.max(peakBars, peakCurve) * 1.1) || 1;

  // ---- Datasets
  const dsFreq = {
    type: 'bar',
    label: '<?php echo get_text('histogram_label') ?: 'Frequency'; ?>',
    data: counts,
    backgroundColor: 'rgba(121, 224, 255, 0.35)',
    borderColor: 'rgba(0, 20, 110, 0.8)',
    borderWidth: 1,
    barPercentage: 1.0,
    categoryPercentage: 1.0,
    grouped: false,
    yAxisID: 'y',
    order: 1
  };

  const dsCurve = {
    type: 'line',
    label: '<?php echo get_text('normal_curve_dataset_label') ?: 'Normal curve'; ?>',
    data: curveAtBins,
    borderColor: 'rgb(7, 78, 0)',
    backgroundColor: 'rgba(51, 255, 0, 0.08)',
    fill: true,
    pointRadius: 0,
    borderWidth: 2,
    yAxisID: 'y',
    order: 2
  };

  const dsYou = {
    type: 'line',
    label: '<?php echo get_text('you_are_here') ?: 'Your score'; ?>',
    data: youData,
    showLine: false,
    pointRadius: Number.isFinite(curScore) ? 7 : 0,
    pointHoverRadius: Number.isFinite(curScore) ? 9 : 0,
    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
    backgroundColor: 'rgba(255, 99, 132, 1)',
    pointBorderColor: '#fff',
    pointBorderWidth: 2,
    borderWidth: 0,
    yAxisID: 'y',
    order: 3
  };

  // ---- Render
  curChart = new Chart(chartCanvas, {
    data: { labels, datasets: [dsFreq, dsCurve, dsYou] },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{
        legend:{ display:true, position:'top' },
        tooltip:{
          mode:'nearest', intersect:false,
          callbacks:{
            title:(items)=> (items?.[0]?.label ? '<?php echo get_text('label_score') ?: 'Score'; ?>: ' + items[0].label : ''),
            label:(ctx)=>{
              const freqLabel  = '<?php echo get_text('histogram_label') ?: 'Frequency'; ?>';
              const curveLabel = '<?php echo get_text('normal_curve_dataset_label') ?: 'Normal curve'; ?>';
              if (ctx.dataset.label === freqLabel) {
                return '<?php echo get_text('count_label') ?: 'Count'; ?>: ' + (ctx.parsed?.y ?? 0);
              }
              if (ctx.dataset.label === curveLabel) {
                const pct = (n > 0) ? ( (ctx.parsed?.y ?? 0) / n * 100 ) : 0;
                return curveLabel + ' (~' + pct.toFixed(1) + '%)';
              }
              return ctx.dataset.label; // You are here
            }
          }
        },
        datalabels:{ display:false }
      },
      scales:{
        x:{
          title:{ display:true, text:'<?php echo get_text('label_score') ?: 'Score'; ?> (0–' + N + ')' },
          ticks:{ autoSkip:false, maxRotation:0, minRotation:0 }
        },
        y:{
          beginAtZero:true,
          max: yMax,
          title:{ display:true, text:'<?php echo get_text('count_label') ?: 'Count'; ?>' }
        }
      }
    }
  });
}


    // ===== Legend (เฉพาะ Radar/Bar) =====
    function buildToggleLegend() {
        const box = document.getElementById('radarLegend');
        box.innerHTML = '';
        allRadarLabels.forEach((name, i) => {
        const item = document.createElement('div');
        item.className = 'item';
        item.dataset.index = i;

        const sw = document.createElement('span');
        sw.className = 'swatch';
        sw.style.background = makeColor(i, .6);
        sw.style.borderColor = makeColor(i, 1);

        const nm = document.createElement('span');
        nm.className = 'name';
        nm.textContent = name;

        item.appendChild(sw); item.appendChild(nm);
        item.addEventListener('click', () => {
            if (shown.has(i)) { shown.delete(i); item.classList.add('off'); }
            else { shown.add(i); item.classList.remove('off'); }
            if (shown.size === 0) { shown.add(i); item.classList.remove('off'); }
            rerenderCurrentChart();
        });
        box.appendChild(item);
        });
    }

  function setLegendVisible(show) {
    const el = document.getElementById('radarLegend');
    if (!el) return;
    if (show) {
      el.classList.remove('d-none');
      el.style.display = 'flex';
    } else {
      el.style.display = 'none';
      el.classList.add('d-none');
    }
  }

  function rerenderCurrentChart(){
    const type = document.getElementById('chartTypeSelect').value;
    if (type === 'hist') {
      setLegendVisible(false);
      renderHistogramFixedRange(allAttemptScores, currentAttemptScore, FULL_INT);
      return;
    }
    if (type === 'normal') {
      setLegendVisible(false);
      renderNormalCurve(allAttemptScores, currentAttemptScore, FULL_INT);
      return;
    }
    setLegendVisible(true);
    const labels = subset(allRadarLabels);
    const values = subset(allRadarValues);
    if (type === 'radar') renderRadar(labels, values);
    else renderBar(labels, values);
  }

  // init
  buildToggleLegend();
  document.getElementById('chartTypeSelect').addEventListener('change', rerenderCurrentChart);
  rerenderCurrentChart();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
