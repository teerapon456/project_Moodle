<?php
// admin/export-report-pdf.php
// ✅ ใช้ข้อมูลจาก iga_user_attempt_questions (ข้อที่แสดงจริง) แสดงรายงานรายบุคคล (1 attempt)

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// ไม่ใช้ Dompdf แล้ว
// use Dompdf\Dompdf;
// use Dompdf\Options;

require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
    set_alert(get_text('alert_no_admin_permission', []), "danger");
    header("Location: /login");
    exit();
}

$attempt_id = $_GET['attempt_id'] ?? null;
if (!is_numeric($attempt_id) || $attempt_id <= 0) {
    set_alert(get_text('error_invalid_attempt_id'), "danger");
    header("Location: view_reports.php");
    exit();
}

// ------------------------------
// ตัวแปรสรุปผล
// ------------------------------
$report_data = [];
$user_info = [];
$test_info = [];
$total_max_shown = 0.0; // คะแนนเต็มรวมของ "ข้อที่แสดงจริงทั้งหมด"
$mc_tf_correct = 0;
$mc_tf_incorrect = 0;
$mc_tf_not_answered = 0;
$sa_graded = 0;
$sa_pending = 0;

// สำหรับกราฟ/แสดงสรุป
$pie_chart_earned_score = 0.0;          // ได้จริง (นับ MC/TF/SA ที่มีคะแนน)
$pie_chart_max_score_auto_graded = 0.0; // คะแนนเต็มฝั่งที่ต้องการแสดงใน Pie (ที่นี่นับ MC/TF/SA ที่แสดงจริง)
$radar_labels = [];
$radar_data = [];
$sections_data = [];
$categories_data = [];
$has_categories = false;

// เกณฑ์ผ่าน
$min_passing_score = 0.0;
$failed_critical_question = false;
$test_passed = true;

try {
    global $conn;
    if (!isset($conn)) {
        throw new Exception('Database connection $conn is not set.');
    }

    // 1) ข้อมูล attempt, user, test
    $stmt = $conn->prepare("
        SELECT
            uta.attempt_id, uta.start_time, uta.end_time, uta.total_score, uta.is_completed, uta.time_spent_seconds,
            u.full_name AS user_name, u.email,
            t.test_name, t.description AS test_description, t.min_passing_score
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
        header("Location: view_reports.php");
        exit();
    }

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

    // 2) ดึง “ข้อที่แสดงจริง” จาก iga_user_attempt_questions เท่านั้น
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
        $question_id = (int)$row['question_id'];
        $q_type = $row['question_type'];
        $q_max  = (float)$row['question_max_score'];
        $score_earned = ($row['score_earned'] !== null) ? (float)$row['score_earned'] : null;
        $user_is_correct = $row['user_is_correct'];

        if ($row['category_id'] !== null) {
            $has_categories = true;
        }

        // เตรียม section
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

        // เตรียม category
        if ($row['category_id'] !== null) {
            $cat_id = (int)$row['category_id'];
            if (!isset($categories_data[$cat_id])) {
                $categories_data[$cat_id] = [
                    'category_id'           => $cat_id,
                    'category_name'         => $row['category_name'],
                    'questions'             => [],
                    'category_score_earned' => 0.0,
                    'category_max_score'    => 0.0,
                ];
            }
        }

        // คำตอบสำหรับแสดง
        if ($q_type === 'multiple_choice' || $q_type === 'true_false') {
            $user_answer_display = $row['user_chosen_option_text_display'];
        } else {
            $user_answer_display = $row['user_answer_text'];
        }

        // เก็บคำถาม
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
            ];
        }

        // คะแนนเต็มรวม (ของ “ข้อที่แสดงจริง”)
        if ($q_max > 0) {
            $total_max_shown += $q_max;
            $sections_data[$section_id]['section_max_score'] += $q_max;
        }

        // คะแนนที่ได้ของ section
        if ($score_earned !== null) {
            $sections_data[$section_id]['section_score_earned'] += $score_earned;
        }

        // Pie (MC/TF/SA)
        if ($q_type === 'multiple_choice' || $q_type === 'true_false' || $q_type === 'short_answer') {
            if ($q_max > 0) {
                $pie_chart_max_score_auto_graded += $q_max;
            }
            if ($score_earned !== null) {
                $pie_chart_earned_score += $score_earned;
            }
        }

        // นับความถี่
        if ($q_type === 'multiple_choice' || $q_type === 'true_false') {
            if ($row['user_answer_text'] === null || $row['user_answer_text'] === '') {
                $mc_tf_not_answered++;
            } elseif ($user_is_correct === 1) {
                $mc_tf_correct++;
            } elseif ($user_is_correct === 0) {
                $mc_tf_incorrect++;
            }
        } elseif ($q_type === 'short_answer') {
            if ($score_earned !== null) {
                $sa_graded++;
            } else {
                $sa_pending++;
            }
        }

        // เฉลย (MC/TF)
        if (($q_type === 'multiple_choice' || $q_type === 'true_false') && (int)$row['option_is_correct'] === 1) {
            $sections_data[$section_id]['questions'][$question_id]['correct_answer'] = $row['correct_option_text'];
        }

        // Critical ผิด = สอบไม่ผ่าน
        if ((int)$row['is_critical'] === 1 && $user_is_correct === 0) {
            $failed_critical_question = true;
        }

        // รวมคะแนนต่อ category
        if ($row['category_id'] !== null) {
            if ($q_max > 0) {
                $categories_data[$cat_id]['category_max_score'] += $q_max;
            }
            if ($score_earned !== null) {
                $categories_data[$cat_id]['category_score_earned'] += $score_earned;
            }
        }
    }
    $stmt->close();

    $report_data = $sections_data;

    // Radar: สรุปเป็นราย section
    $radar_src = $sections_data;
    foreach ($radar_src as $s) {
        $lbl = $s['section_name'] ?? get_text('not_available_abbr');
        $max = (float)$s['section_max_score'];
        $earn = (float)$s['section_score_earned'];
        if ($max > 0) {
            $radar_labels[] = htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8');
            $radar_data[] = ($earn / $max) * 100.0;
        }
    }

    // ตัดสินผ่านไม่ผ่าน
    if ($failed_critical_question) {
        $test_passed = false;
    } else {
        if ($total_max_shown > 0 && $min_passing_score > 0) {
            $achieved_percent = ($pie_chart_earned_score / $total_max_shown) * 100.0;
            if ($achieved_percent + 1e-9 < $min_passing_score) {
                $test_passed = false;
            }
        }
    }
} catch (Throwable $e) {
    error_log("Error fetching report data for PDF: " . $e->getMessage());
    die(get_text('error_fetching_report_data_individual', 'PDF Export Failed'));
}

// ------------------------------
// สร้าง HTML (ใช้ฟอนต์ thsarabun ใน CSS ไว้ให้ตรงกับ mPDF)
// ------------------------------
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?php echo get_text('page_title_individual_report'); ?></title>
    <style>
        @page {
            margin: 20px 25px;
        }

        body {
            font-family: 'thsarabun', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            word-wrap: break-word;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'thsarabun', sans-serif;
            margin: 10px 0;
            font-weight: 700;
        }

        .container {
            width: 98%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 10px;
        }

        .card-header {
            background-color: #dc3545;
            color: #fff;
            padding: 0px 10px;
            border-bottom: 1px solid #dc3545;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
        }

        .card-body {
            padding: 10px;
        }

        .card-body,
        .card-body p {
            word-wrap: break-word;
            word-break: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: 400;
        }

        th {
            background-color: #f2f2f2;
            font-weight: 700;
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-muted {
            color: #6c757d;
        }

        .badge {
            display: inline-block;
            padding: .25em .4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            border-radius: .25rem;
        }

        .status-circle-wrapper {
            display: inline-block;
            margin-right: 6px;
            /* เว้นจากข้อความข้าง ๆ */
            vertical-align: middle;
        }

        .status-circle {
            display: block;
            /* ให้ mPDF ยอมใช้ width/height */
            width: 18px;
            height: 18px;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
        }



        .bg-success {
            background-color: #28a745;
            color: #fff;
        }

        .bg-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .bg-secondary {
            background-color: #6c757d;
            color: #fff;
        }

        .mb-1 {
            margin-bottom: 0.25rem;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }

        .py-4 {
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
        }

        hr {
            border: 0;
            border-top: 1px solid #eee;
            margin: 1rem 0;
        }

        .page-break-before {
            page-break-before: always;
        }

        .progress-bar {
            width: 100%;
            height: 14px;
            background-color: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin: 3px 0 8px 0;
        }

        .progress-fill {
            height: 14px;
            background-color: #dc3545;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            line-height: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><strong><?php echo get_text('page_heading_individual_report'); ?></strong></h1>
            <h2><?php echo htmlspecialchars($test_info['test_name'] ?? get_text('not_available_abbr')); ?></h2>
            <p><?php echo get_text('report_generated_on') . ' ' . thai_datetime_format(date('Y-m-d H:i:s')); ?></p>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><strong><?php echo get_text('examinee_test_info_heading'); ?></strong></h3>
            </div>
            <div class="card-body">
                <p><strong><?php echo get_text('examinee_name_label'); ?></strong> <?php echo htmlspecialchars($user_info['full_name'] ?? get_text('not_available_abbr')); ?></p>
                <p><strong><?php echo get_text('email_label'); ?>:</strong> <?php echo htmlspecialchars($user_info['email'] ?? get_text('not_available_abbr')); ?></p>
                <p><strong><?php echo get_text('test_name_label'); ?></strong> <?php echo htmlspecialchars($test_info['test_name'] ?? get_text('not_available_abbr')); ?></p>
                <p><strong><?php echo get_text('test_description_label'); ?></strong> <?php echo nl2br(htmlspecialchars($test_info['test_description'] ?? get_text('not_available_abbr'))); ?></p>
                <p><strong><?php echo get_text('start_time_label'); ?></strong> <?php echo htmlspecialchars(thai_datetime_format($test_info['start_time'] ?? null)); ?></p>
                <p><strong><?php echo get_text('end_time_label'); ?></strong> <?php echo htmlspecialchars($test_info['end_time'] ? thai_datetime_format($test_info['end_time']) : get_text('not_completed_status')); ?></p>
                <p><strong><?php echo get_text('total_time_spent_label'); ?></strong> <?php echo formatTimeSpent($test_info['time_spent_seconds'] ?? null); ?></p>
                <p>
                    <strong><?php echo get_text('total_score_earned_label'); ?></strong>
                    <span class="text-success"><?php echo htmlspecialchars(number_format($pie_chart_earned_score, 2)); ?></span> /
                    <span class="text-muted"><?php echo htmlspecialchars(number_format($total_max_shown, 2)); ?></span>
                    <span class="badge <?php echo ($test_info['is_completed'] ?? false) ? 'bg-success' : 'bg-secondary'; ?>">
                        <?php echo ($test_info['is_completed'] ?? false) ? get_text('completed_status') : get_text('in_progress_status'); ?>
                    </span>
                    <?php if ($min_passing_score > 0): ?>
                        <span class="badge <?php echo $test_passed ? 'bg-success' : 'bg-danger'; ?>" style="margin-left:8px;">
                            <?php echo $test_passed ? get_text('test_status_passed') : get_text('test_status_failed'); ?>
                        </span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- สรุปผลราย Section -->
        <div class="card mt-4 page-break-before">
            <div class="card-header">
                <h3><strong><?php echo get_text('section_performance_chart_title', 'Section Performance'); ?></strong></h3>
            </div>
            <div class="card-body">
                <div>
                    <?php foreach ($report_data as $section): ?>
                        <?php
                        $pct = $section['section_max_score'] > 0
                            ? ($section['section_score_earned'] / $section['section_max_score']) * 100
                            : 0;
                        $pct_clamped = min($pct, 100);
                        ?>
                        <div style="margin-bottom: 10px;">
                            <div style="margin-bottom: 2px;">
                                <strong><?php echo htmlspecialchars($section['section_name']); ?></strong>
                                <span style="float:right;color:#dc3545;font-weight:bold;">
                                    <?php echo number_format($pct, 1); ?>%
                                </span>
                                <div style="clear:both;"></div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $pct_clamped; ?>%;">
                                    <?php echo number_format($section['section_score_earned'], 0); ?>/<?php echo number_format($section['section_max_score'], 0); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>


                <table style="margin-top:15px;">
                    <thead>
                        <tr>
                            <th><?php echo get_text('section_name_label', 'Section'); ?></th>
                            <th><?php echo get_text('score_earned_label', 'Score Earned'); ?></th>
                            <th><?php echo get_text('max_score_label', 'Max Score'); ?></th>
                            <th><?php echo get_text('percentage_label', 'Percentage'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 0;
                        foreach ($report_data as $section): $idx++; ?>
                            <tr <?php echo ($idx % 2 === 0) ? 'style="background:#f8f9fa;"' : ''; ?>>
                                <td style="font-weight:bold;"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                <td style="color:#28a745;font-weight:bold;"><?php echo number_format($section['section_score_earned'], 0); ?></td>
                                <td><?php echo number_format($section['section_max_score'], 0); ?></td>
                                <td style="color:#dc3545;font-weight:bold;">
                                    <?php
                                    $pct = $section['section_max_score'] > 0 ? ($section['section_score_earned'] / $section['section_max_score']) * 100 : 0;
                                    echo number_format($pct, 0) . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- ตารางความถี่ -->
                <h3 class="mt-4"><?php echo get_text('frequency_table_title'); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th><?php echo get_text('question_type_label'); ?></th>
                            <th><?php echo get_text('status_label'); ?></th>
                            <th><?php echo get_text('table_header_question_count'); ?></th>
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

        <!-- แสดงรายละเอียดคำถามตาม Section -->
        <?php if (!empty($report_data)): ?>
            <?php $sec_idx = 0;
            foreach ($report_data as $section): $sec_idx++; ?>
                <div class="page-break-before"></div>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 style="font-weight:700;">
                            <?php echo htmlspecialchars($section['section_name']); ?>
                            <span style="float:right;font-weight:600;">
                                <?php echo get_text('section_score_label'); ?>
                                <?php echo htmlspecialchars(number_format($section['section_score_earned'], 0)); ?>
                                /
                                <?php echo htmlspecialchars(number_format($section['section_max_score'], 0)); ?>
                            </span>
                        </h3>
                    </div>
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
                            <?php $q_idx = 0;
                            foreach ($section['questions'] as $question): $q_idx++; ?>
                                <div style="
                                border:1px solid #dee2e6;
                                padding:12px;margin-bottom:15px;
                                border-left:5px solid <?php echo ($question['user_is_correct'] === 1) ? '#28a745' : (($question['user_is_correct'] === 0) ? '#dc3545' : '#6c757d'); ?>;
                                background-color: <?php echo ($question['user_is_correct'] === 1) ? '#f8fff9' : (($question['user_is_correct'] === 0) ? '#fff8f8' : '#f8f9fa'); ?>;
                                page-break-inside: avoid;">
                                    <p class="mb-1">
                                        <span class="status-circle-wrapper">
                                            <span class="status-circle <?php
                                                                        echo ($question['user_is_correct'] === 1)
                                                                            ? 'bg-success'
                                                                            : (($question['user_is_correct'] === 0) ? 'bg-danger' : 'bg-secondary');
                                                                        ?>">
                                                <?php
                                                if ($question['user_is_correct'] === 1) {
                                                    echo get_text('correct_answer_badge');   // เช่น "ถ"
                                                } else if ($question['user_is_correct'] === 0) {
                                                    echo get_text('incorrect_answer_badge'); // เช่น "ผ"
                                                } else {
                                                    echo get_text('not_available_abbr');     // "-"
                                                }
                                                ?>
                                            </span>
                                        </span>

                                        <strong style="font-weight:700;">
                                            <?php echo htmlspecialchars($question['question_order'] . ". " . $question['question_text']); ?>
                                        </strong>
                                        <br><small class="text-muted" style="font-weight:400;">
                                            (<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $question['question_type']))); ?>
                                            | <?php echo get_text('score_earned_label_question'); ?>
                                            <?php echo htmlspecialchars(number_format($question['score_earned'] ?? 0, 0)); ?>/<?php echo htmlspecialchars($question['question_max_score']); ?>)
                                            <?php if (!empty($question['is_critical'])): ?>
                                                <span class="badge bg-danger" style="margin-left:6px;"><?php echo get_text('critical_question_label'); ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </p>

                                    <p class="mb-1" style="margin-left:20px;">
                                        <strong style="font-weight:700;"><?php echo get_text('user_answer_label'); ?>:</strong>
                                        <?php if (!empty($question['user_answer_text'])): ?>
                                            <?php echo nl2br(htmlspecialchars($question['user_answer_text'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo get_text('not_answered'); ?></span>
                                        <?php endif; ?>
                                    </p>

                                    <?php if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false'): ?>
                                        <p class="mb-0" style="margin-left:20px;">
                                            <strong style="font-weight:700;"><?php echo get_text('correct_answer_display_label'); ?>:</strong>
                                            <span class="text-success" style="font-weight:400;">
                                                <?php echo htmlspecialchars($question['correct_answer'] ?? get_text('no_correct_answer_available')); ?>
                                            </span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted"><?php echo get_text('no_questions_or_incomplete_data'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="background:#e2f3ff;border:1px solid #bce8f1;padding:15px;text-align:center;border-radius:5px;">
                <p><?php echo get_text('no_report_details_found'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

// ---------- DEBUG HTML ----------
ini_set('display_errors', 1);
error_reporting(E_ALL);

$debugHtmlPath = __DIR__ . '/debug_individual_report_html_' . $attempt_id . '.html';
file_put_contents($debugHtmlPath, $html);

if (trim($html) === '') {
    error_log('PDF DEBUG: $html is empty in ' . __FILE__);
    die('DEBUG: HTML ว่างเปล่า ลองเปิดไฟล์ ' . basename($debugHtmlPath) . ' ในโฟลเดอร์ admin ดู');
}

// ---------- mPDF + THSarabunNew ----------
try {
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs      = $defaultConfig['fontDir'];

    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData          = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'mode'   => 'utf-8',
        'format' => 'A4',
        'fontDir' => array_merge($fontDirs, [
            __DIR__ . '/../fonts',   // ต้องมี THSarabunNew*.ttf อยู่ในโฟลเดอร์นี้
        ]),
        'fontdata' => $fontData + [
            'thsarabun' => [
                'R'  => 'THSarabunNew.ttf',
                'B'  => 'THSarabunNew Bold.ttf',
                'I'  => 'THSarabunNew Italic.ttf',
                'BI' => 'THSarabunNew BoldItalic.ttf',
            ],
        ],
        'default_font' => 'thsarabun',
    ]);

    $mpdf->showImageErrors = true;

    $mpdf->WriteHTML($html);

    $filename = $user_info['full_name'] . '_' . date('Ymd_His') . '.pdf';
    $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    exit;
} catch (\Mpdf\MpdfException $e) {
    error_log('PDF DEBUG (mPDFException): ' . $e->getMessage());
    die('DEBUG mPDF ERROR: ' . htmlspecialchars($e->getMessage()));
} catch (Throwable $e) {
    error_log('PDF DEBUG (Throwable): ' . $e->getMessage());
    die('DEBUG GENERAL ERROR: ' . htmlspecialchars($e->getMessage()));
}
