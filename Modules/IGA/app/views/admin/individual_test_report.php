<?php
require_once __DIR__ . '/../../includes/header.php';


$page_title = get_text('page_title_individual_report');

require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor')) {
    set_alert(get_text('alert_no_admin_permission', []), "danger");
    // Redirect ไปหน้าผู้ใช้ทั่วไป หรือหน้าที่เหมาะสมกว่า
    header("Location: /INTEQC_GLOBAL_ASSESMENT/public/login"); // หรือ ../views/user/dashboard.php
    exit();
}

$attempt_id = $_POST['attempt_id'] ?? null;

if (!is_numeric($attempt_id) || $attempt_id <= 0) {
    set_alert(get_text('error_invalid_attempt_id'), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/view_reports");
    exit();
}

$report_data = [];
$user_info = [];
$test_info = [];
$total_possible_score_all_questions = 0; // Initialize total_possible_score_all_questions (total for all questions regardless of graded status)
$overall_max_score_for_graded_questions = 0; // NEW: Initialize max score for questions that have been graded

// Variables for Pie Chart (only Multiple Choice & True/False)
$pie_chart_earned_score = 0;
$pie_chart_max_score_auto_graded = 0;

// Variables for Radar Chart
$radar_labels = [];
$radar_data = [];
$section_raw_scores = []; // Store raw scores for sections to calculate percentages later

// Variables for Frequency Table
$mc_tf_correct = 0;
$mc_tf_incorrect = 0;
$mc_tf_not_answered = 0;
$sa_graded = 0;
$sa_pending = 0;

// --- NEW VARIABLES FOR PASS/FAIL LOGIC ---
$test_passed = true; // Assume pass until conditions fail
$min_passing_score = 0; // Will be fetched from DB
$failed_critical_question = false; // Flag for critical questions
// --- END NEW VARIABLES ---


try {
    // ดึงข้อมูลหลักของการทำแบบทดสอบ (attempt)
    // MODIFIED: Added t.min_passing_score
    $stmt = $conn->prepare("
        SELECT
            uta.attempt_id, uta.start_time, uta.end_time, uta.total_score, uta.is_completed, uta.time_spent_seconds,
            u.full_name AS user_name, u.email,
            t.test_name, t.description AS test_description, t.min_passing_score
        FROM user_test_attempts uta
        JOIN users u ON uta.user_id = u.user_id
        JOIN tests t ON uta.test_id = t.test_id
        WHERE uta.attempt_id = ?
    ");
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $attempt_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$attempt_info) {
        set_alert(get_text('error_attempt_data_not_found'), "danger");
        header("Location: view_reports.php");
        exit();
    }

    $user_info = [
        'full_name' => $attempt_info['user_name'],
        'email' => $attempt_info['email']
    ];
    $test_info = [
        'test_name' => $attempt_info['test_name'],
        'test_description' => $attempt_info['test_description'],
        'start_time' => $attempt_info['start_time'],
        'end_time' => $attempt_info['end_time'],
        'total_score_earned' => $attempt_info['total_score'],
        'is_completed' => $attempt_info['is_completed'],
        'time_spent_seconds' => $attempt_info['time_spent_seconds']
    ];
    // NEW: Get minimum pass score
    $min_passing_score = $attempt_info['min_passing_score'] ?? 0;


    // ดึงข้อมูลคำตอบของผู้ใช้, คำถาม, ตัวเลือก และส่วนต่างๆ
    // MODIFIED: Added q.is_critical
    $stmt = $conn->prepare("
        SELECT
            s.section_id, s.section_name, s.description AS section_description, s.section_order, s.duration_minutes,
            q.question_id, q.question_text, q.question_type, q.score AS question_max_score, q.question_order, q.is_critical,
            ua.user_answer_text, ua.is_correct AS user_is_correct, ua.score_earned,
            correct_o.option_id AS correct_option_id, correct_o.option_text AS correct_option_text, correct_o.is_correct AS option_is_correct,
            user_chosen_option.option_text AS user_chosen_option_text_display /* เพิ่มคอลัมน์นี้เพื่อแสดงข้อความตัวเลือกที่ผู้ใช้เลือก */
        FROM sections s
        JOIN questions q ON s.section_id = q.section_id
        LEFT JOIN user_answers ua ON q.question_id = ua.question_id AND ua.attempt_id = ?
        LEFT JOIN question_options correct_o ON q.question_id = correct_o.question_id AND correct_o.is_correct = 1 /* ดึงเฉพาะตัวเลือกที่ถูกต้อง */
        LEFT JOIN question_options user_chosen_option ON ua.user_answer_text = user_chosen_option.option_id AND (q.question_type = 'multiple_choice' OR q.question_type = 'true_false') /* เพิ่ม Join สำหรับตัวเลือกที่ผู้ใช้เลือก */
        WHERE s.test_id = (SELECT test_id FROM user_test_attempts WHERE attempt_id = ?)
        ORDER BY s.section_order ASC, q.question_order ASC
    ");
    $stmt->bind_param("ii", $attempt_id, $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sections_data = [];
    while ($row = $result->fetch_assoc()) {
        $section_id = $row['section_id'];
        $question_id = $row['question_id'];

        if (!isset($sections_data[$section_id])) {
            $sections_data[$section_id] = [
                'section_id' => $row['section_id'],
                'section_name' => $row['section_name'],
                'section_description' => $row['section_description'],
                'section_order' => $row['section_order'],
                'duration_minutes' => $row['duration_minutes'],
                'questions' => [],
                'section_score_earned' => 0,
                'section_max_score' => 0,
                // NEW: Variables for auto-graded scores specifically for radar chart
                'section_score_earned_auto_graded' => 0,
                'section_max_score_auto_graded' => 0
            ];
            $section_raw_scores[$section_id] = ['earned' => 0, 'max' => 0];
        }

        if (!isset($sections_data[$section_id]['questions'][$question_id])) {
            $user_answer_display = null;
            if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') {
                // ใช้ user_chosen_option_text_display สำหรับคำถามปรนัย/จริง-เท็จ
                $user_answer_display = $row['user_chosen_option_text_display'];
            } else {
                // ใช้ user_answer_text สำหรับคำถามอัตนัย
                $user_answer_display = $row['user_answer_text'];
            }

            $sections_data[$section_id]['questions'][$question_id] = [
                'question_id' => $row['question_id'],
                'question_text' => $row['question_text'],
                'question_type' => $row['question_type'],
                'question_max_score' => $row['question_max_score'],
                'question_order' => $row['question_order'],
                'is_critical' => (bool)$row['is_critical'], // NEW: is_critical status
                'user_answer_text' => $user_answer_display, // แก้ไขตรงนี้
                'user_is_correct' => $row['user_is_correct'],
                'score_earned' => $row['score_earned'],
                'correct_answer' => null // จะเก็บตัวเลือกที่ถูกต้อง หรือเฉลยอัตนัย
            ];

            // Only add to section_max_score if question_max_score is greater than 0
            if ($row['question_max_score'] > 0) {
                $sections_data[$section_id]['section_max_score'] += $row['question_max_score'];
                $total_possible_score_all_questions += $row['question_max_score']; // Sum for overall test max score (all questions)

                // NEW LOGIC: Also add earned score to section_score_earned ONLY IF question_max_score > 0
                if ($row['score_earned'] !== null) {
                    $sections_data[$section_id]['section_score_earned'] += $row['score_earned'];
                }
            }


            // For Pie Chart (Multiple Choice, True/False, and Short Answer with score > 0)
            if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false' || $row['question_type'] == 'short_answer') {
                if ($row['question_max_score'] > 0) {
                    $pie_chart_max_score_auto_graded += $row['question_max_score'];
                    // NEW LOGIC FOR PIE CHART EARNED SCORE:
                    if ($row['score_earned'] !== null) { // Only add earned score if it's not null
                        $pie_chart_earned_score += $row['score_earned'];
                    }
                }
            }


            // Accumulate scores for auto-graded questions for the radar chart and overall graded score
            // Only add to section_max_score_auto_graded if question_max_score is greater than 0
            // This applies to MC/TF directly, and for SA, section_max_score handles its inclusion in the Radar
            if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') {
                if ($row['question_max_score'] > 0) {
                    $sections_data[$section_id]['section_max_score_auto_graded'] += $row['question_max_score'];
                }
                if ($row['score_earned'] !== null) {
                    $sections_data[$section_id]['section_score_earned_auto_graded'] += $row['score_earned'];
                }

                // Only count MC/TF as graded if question_max_score is greater than 0
                if ($row['question_max_score'] > 0) {
                     $overall_max_score_for_graded_questions += $row['question_max_score']; // Always count MC/TF as graded
                }
            } elseif ($row['question_type'] == 'short_answer') {
                // สำหรับ Short Answer, จะรวมคะแนนเต็มสูงสุดเข้าใน overall_max_score_for_graded_questions
                // ถ้าคำถามนั้นมี question_max_score มากกว่า 0
                // ซึ่งจะทำให้คล้ายกับ logic ของ Radar Chart ที่นับ max score ของ section ทันที
                if ($row['question_max_score'] > 0) {
                    $overall_max_score_for_graded_questions += $row['question_max_score'];
                }
            }


            // For Frequency Table
            if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') {
                if ($row['user_answer_text'] === null || $row['user_answer_text'] === '') { // ใช้ user_answer_text เดิม (ซึ่งอาจเป็น ID) เพื่อเช็คว่าตอบหรือไม่ตอบ
                    $mc_tf_not_answered++;
                } else if ($row['user_is_correct'] === 1) {
                    $mc_tf_correct++;
                } else if ($row['user_is_correct'] === 0) {
                    $mc_tf_incorrect++;
                }
            } elseif ($row['question_type'] == 'short_answer') {
                if ($row['score_earned'] !== null) {
                    $sa_graded++;
                } else {
                    $sa_pending++;
                }
            }

            // --- NEW: Check for critical question failure ---
            if ((bool)$row['is_critical'] && $row['user_is_correct'] === 0) {
                $failed_critical_question = true;
            }
            // --- END NEW ---
        }

        // สำหรับคำถามปรนัย/จริง-เท็จ ให้ดึงตัวเลือกที่ถูกต้อง
        if (($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') && $row['option_is_correct'] == 1) {
            $sections_data[$section_id]['questions'][$question_id]['correct_answer'] = $row['correct_option_text'];
        }
        // REMOVED THE OLD LINE FOR section_score_earned ACCUMULATION HERE
        // The accumulation is now handled inside the if ($row['question_max_score'] > 0) block
    }
    $stmt->close();

    $report_data = $sections_data;

    // Prepare Radar Chart Data after all sections are processed
    $radar_labels = [];
    $radar_data = [];

    foreach ($report_data as $section) {
        // We now only care if the section has a positive max score
        // เมื่อใด ที่ section นั้นมี max scoreof section จะเอามาคิดรวมทันที ไม่ว่าจะได้หกี่คะแนน
        if ($section['section_max_score'] > 0) {
            $display_label = $section['section_name'];
            $radar_labels[] = htmlspecialchars($display_label);

            $current_section_radar_score = $section['section_score_earned'];
            $current_section_radar_max_score = $section['section_max_score'];

            // Calculate percentage, preventing division by zero (should be covered by outer if)
            $percentage = 0;
            if ($current_section_radar_max_score > 0) {
                $percentage = ($current_section_radar_score / $current_section_radar_max_score) * 100;
            }
            $radar_data[] = $percentage;
        }
    }

    // --- NEW: Final Pass/Fail Determination ---
    // Condition 1: Check if any critical question was failed
    if ($failed_critical_question) {
        $test_passed = false;
    }

    // Condition 2: Check if total score meets minimum pass score (only if not already failed by critical question)
    if ($test_passed && $overall_max_score_for_graded_questions > 0) { // Use the graded max score here
        $percentage_score_achieved = ($test_info['total_score_earned'] / $overall_max_score_for_graded_questions) * 100;
        if ($percentage_score_achieved < $min_passing_score) {
            $test_passed = false;
        }
    } elseif ($test_passed && $overall_max_score_for_graded_questions === 0) {
        // If there are no scorable questions, assume pass if no critical questions are failed.
        // This is a design decision. You might want to make it "N/A" or "Undetermined".
        $test_passed = true;
    }
    // --- END NEW ---

} catch (Exception $e) {
    set_alert(get_text('error_fetching_report_data_individual', $e->getMessage()), "danger");
    $report_data = [];
}
?>

<?php echo get_alert(); ?>

<div class="container-fluid w-80-custom py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0 text-primary-custom"><?php echo get_text('page_heading_individual_report'); ?></h1>
        <div class="d-flex">
            <a href="/INTEQC_GLOBAL_ASSESMENT/admin/reports" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text('back_to_overview_report'); ?>
            </a>
            <a href="/INTEQC_GLOBAL_ASSESMENT/admin/export-report-pdf?attempt_id=<?php echo htmlspecialchars($attempt_id); ?>" class="btn btn-danger" target="_blank">
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
                        <span class="fs-4 text-success"><?php echo htmlspecialchars(number_format($pie_chart_earned_score ?? 0, 2)); ?></span> /
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
                                <?php if ($overall_max_score_for_graded_questions > 0 && ($test_info['total_score_earned'] / $overall_max_score_for_graded_questions) * 100 < $min_passing_score): ?>
                                    <br><small class="text-danger ms-2"><i class="fas fa-exclamation-triangle"></i> <?php echo sprintf(get_text('minimum_score_not_met_message'), number_format($min_passing_score, 2) . '%'); ?></small>
                                <?php endif; ?>
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
                    <canvas id="pieChart" style="max-height: 300px; max-width: 300px;"></canvas>
                    <div class="mt-3 text-center">
                        <p class="mb-0 fs-5"><strong><?php echo get_text('score_earned_label'); ?>:</strong> <span class="text-success"><?php echo htmlspecialchars(number_format($pie_chart_earned_score, 2)); ?> <strong><?php echo get_text('label_score'); ?></strong></span></p>
                        <p class="mb-0 fs-5"><strong><?php echo get_text('score_possible_label'); ?>:</strong> <span class="text-muted"><?php echo htmlspecialchars(number_format($pie_chart_max_score_auto_graded, 2)); ?> <strong><?php echo get_text('label_score'); ?></strong></span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary-custom text-white">
                    <h5 class="mb-0"><?php echo get_text('radar_chart_title'); ?></h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="radarChart" style="max-height: 500px; max-width: 500px;"></canvas>
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

    <?php if (!empty($report_data)): ?>
        <div id="accordionReportSections">
            <?php foreach ($report_data as $section): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center" id="sectionHeading<?php echo $section['section_id']; ?>">
                        <h5 class="mb-0">
                            <button class="btn btn-link text-white text-decoration-none d-flex align-items-center" data-bs-toggle="collapse" data-bs-target="#sectionCollapse<?php echo $section['section_id']; ?>" aria-expanded="true" aria-controls="sectionCollapse<?php echo $section['section_id']; ?>">
                                <i class="fas fa-chevron-down me-2"></i>
                                <?php echo htmlspecialchars($section['section_order'] . ". " . $section['section_name']); ?>
                                <span class="badge bg-light text-dark ms-3">
                                    <?php echo get_text('section_score_label'); ?> <?php echo htmlspecialchars(number_format($section['section_score_earned'], 2)); ?> / <?php echo htmlspecialchars(number_format($section['section_max_score'], 2)); ?>
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
                                        <div class="list-group-item mb-2 shadow-sm rounded-3 <?php echo ($question['user_is_correct'] === 1) ? 'border-success' : (($question['user_is_correct'] === 0) ? 'border-danger' : ''); ?>">
                                            <h6 class="mb-1">
                                                <span class="badge <?php echo ($question['user_is_correct'] === 1) ? 'bg-success' : (($question['user_is_correct'] === 0) ? 'bg-danger' : 'bg-secondary'); ?> me-2">
                                                    <?php
                                                        if ($question['user_is_correct'] === 1) { echo '<i class="fas fa-check"></i> ' . get_text('correct_answer_badge'); }
                                                        else if ($question['user_is_correct'] === 0) { echo '<i class="fas fa-times"></i> ' . get_text('incorrect_answer_badge'); }
                                                        else { echo get_text('not_available_abbr'); } // สำหรับ Short Answer หรือยังไม่ได้ตรวจ
                                                    ?>
                                                </span>
                                                <?php echo htmlspecialchars($question['question_order'] . ". " . $question['question_text']); ?>
                                                <small class="text-muted ms-2">(<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $question['question_type']))); ?> | <?php echo get_text('score_earned_label_question'); ?> <?php echo htmlspecialchars(number_format($question['score_earned'] ?? 0, 2)); ?>/<?php echo htmlspecialchars($question['question_max_score']); ?>)
                                                <?php if ($question['is_critical']): ?>
                                                    <span class="badge bg-warning text-dark ms-1"><i class="fas fa-exclamation-triangle"></i> <?php echo get_text('critical_question_label'); ?></span>
                                                <?php endif; ?>
                                                </small>
                                            </h6>
                                            <p class="mb-1 ms-4">
                                                <strong><?php echo get_text('user_answer_label'); ?></strong>
                                                <?php if (!empty($question['user_answer_text'])): /* ใช้ user_answer_text ที่ถูกปรับแล้ว */ ?>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
    // --- Pie Chart Configuration ---
    Chart.register(ChartDataLabels); // Register the datalables plugin

    const pieChartEarnedScore = <?php echo json_encode(number_format($pie_chart_earned_score, 2)); ?>;
    const pieChartMaxScoreAutoGraded = <?php echo json_encode(number_format($pie_chart_max_score_auto_graded, 2)); ?>;
    // Calculate remaining score based on the actual earned vs. max possible for auto-graded questions
    const pieChartRemainingScore = Math.max(0, pieChartMaxScoreAutoGraded - pieChartEarnedScore);


    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: [
                '<?php echo get_text('chart_score_earned'); ?>', // Label for earned score
                '<?php echo get_text('chart_score_possible'); ?>' // Label for remaining score (often phrased as "score not earned")
            ],
            datasets: [{
                data: [pieChartEarnedScore, pieChartRemainingScore], // Data for earned and remaining
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)', // Green-ish for earned
                    'rgba(255, 99, 132, 0.8)'  // Red-ish for remaining
                ],
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                const value = parseFloat(context.parsed); // Ensure it's a number
                                label += new Intl.NumberFormat('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(value) + ' <?php echo get_text('score_label_suffix'); ?>';
                            }
                            return label;
                        }
                    }
                },
                datalabels: { // This is for the `chartjs-plugin-datalabels`
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 14
                    },
                    formatter: (value, context) => {
                        // Display actual scores inside the segments
                        if (context.dataIndex === 0) { // For "Score Earned" segment
                            return `<?php echo get_text('chart_score_earned_short'); ?>\n${pieChartEarnedScore}`;
                        } else { // For "Remaining/Possible Score" segment
                            return `<?php echo get_text('chart_score_possible_short'); ?>\n${pieChartMaxScoreAutoGraded}`;
                        }
                    },
                    anchor: 'center',
                    align: 'center',
                    offset: 0
                }
            }
        },
    });

    // --- Radar Chart Configuration ---
    const radarLabels = <?php echo json_encode($radar_labels); ?>;
    const radarData = <?php echo json_encode($radar_data); ?>;

    const radarCtx = document.getElementById('radarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: radarLabels,
            datasets: [{
                label: '<?php echo get_text('radar_chart_dataset_label'); ?>',
                data: radarData,
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
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: {
                        display: false
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    pointLabels: {
                        font: {
                            size: 10 // Adjusted font size for two lines
                        },
                        // Enable text wrapping for point labels
                        // Set maxWidth based on your chart's expected size and label length
                        maxWidth: 90, // Keep this for wrapping long labels
                        padding: 40,  // **ปรับค่านี้ให้มากขึ้นเพื่อเพิ่มระยะห่างของชื่อ Section**
                        color: 'black' // Ensure labels are visible
                    },
                    ticks: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.r !== null) {
                                label += new Intl.NumberFormat('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }).format(context.parsed.r) + '%';
                            }
                            return label;
                        }
                    }
                },
                datalabels: { // Add datalabels for radar chart
                    color: 'black', // Choose a color that stands out on the chart
                    font: {
                        weight: 'bold',
                        size: 12 // Font size for data labels (percentages)
                    },
                    formatter: (value) => {
                        return value.toFixed(2) + '%';
                    },
                    anchor: 'end', // Position the label at the end of the point
                    align: 'end',  // Align it to the end
                    offset: 4      // Offset it slightly from the point
                }
            }
        }
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>