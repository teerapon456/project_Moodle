<?php
date_default_timezone_set('Asia/Bangkok'); // กำหนดโซนเวลาเป็นกรุงเทพฯ (ประเทศไทย)

require_once __DIR__ . '/../../includes/functions.php'; // Ensure functions.php is included

use Dompdf\Dompdf;
use Dompdf\Options;

require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor')) {
    set_alert(get_text('alert_no_admin_permission', []), "danger");
    // Redirect ไปหน้าผู้ใช้ทั่วไป หรือหน้าที่เหมาะสมกว่า
    header("Location: ../../public/login.php"); // หรือ ../views/user/dashboard.php
    exit();
}

$attempt_id = $_GET['attempt_id'] ?? null;

if (!is_numeric($attempt_id) || $attempt_id <= 0) {
    set_alert(get_text('error_invalid_attempt_id'), "danger"); // This alert won't be seen if redirected
    header("Location: view_reports.php");
    exit();
}

// Fetch report data - similar logic as individual_test_report.php
$report_data = [];
$user_info = [];
$test_info = [];
$max_total_score = 0;

$mc_tf_correct = 0;
$mc_tf_incorrect = 0;
$mc_tf_not_answered = 0;
$sa_graded = 0;
$sa_pending = 0;

try {
    $stmt = $conn->prepare("
        SELECT
            uta.attempt_id, uta.start_time, uta.end_time, uta.total_score, uta.is_completed, uta.time_spent_seconds,
            u.full_name AS user_name, u.email,
            t.test_name, t.description AS test_description
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
        set_alert(get_text('error_attempt_data_not_found'), "danger"); // This alert won't be seen if redirected
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

    $stmt = $conn->prepare("
        SELECT
            s.section_id,
            s.section_name,
            s.description AS section_description,
            s.section_order,
            s.duration_minutes,
            q.question_id,
            q.question_text,
            q.question_type,
            q.score AS question_max_score,
            q.question_order,
            ua.user_answer_text,
            CASE 
                WHEN ua.user_answer_text IS NULL THEN NULL
                WHEN q.question_type IN ('multiple_choice', 'true_false') AND 
                     ua.user_answer_text = (SELECT option_id FROM question_options WHERE question_id = q.question_id AND is_correct = 1 LIMIT 1) 
                THEN 1
                WHEN q.question_type IN ('multiple_choice', 'true_false') AND ua.user_answer_text IS NOT NULL 
                THEN 0
                ELSE NULL
            END AS user_is_correct,
            ua.score_earned,
            correct_option.option_text AS correct_option_text,
            correct_option.is_correct AS option_is_correct,
            user_option.option_text AS user_selected_option_text
        FROM sections s
        JOIN questions q ON s.section_id = q.section_id
        LEFT JOIN user_answers ua ON q.question_id = ua.question_id AND ua.attempt_id = ?
        LEFT JOIN question_options correct_option ON 
            q.question_id = correct_option.question_id 
            AND correct_option.is_correct = 1
        LEFT JOIN question_options user_option ON 
            user_option.option_id = CAST(ua.user_answer_text AS UNSIGNED)
            AND user_option.question_id = q.question_id
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
                'section_max_score' => 0
            ];
        }

        if (!isset($sections_data[$section_id]['questions'][$question_id])) {
            $sections_data[$section_id]['questions'][$question_id] = [
                'question_id' => $row['question_id'] ?? null,
                'question_text' => $row['question_text'] ?? '',
                'question_type' => $row['question_type'] ?? 'multiple_choice',
                'question_max_score' => $row['question_max_score'] ?? 0,
                'question_order' => $row['question_order'] ?? 0,
                'user_answer_text' => $row['user_answer_text'] ?? null,
                'user_is_correct' => $row['user_is_correct'] ?? null,
                'score_earned' => $row['score_earned'] ?? 0,
                'user_selected_option_text' => $row['user_selected_option_text'] ?? null,
                'correct_answer' => $row['correct_option_text'] ?? null
            ];
            $sections_data[$section_id]['section_max_score'] += $row['question_max_score'];
            $max_total_score += $row['question_max_score'];

            if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') {
                if ($row['user_answer_text'] === null || $row['user_answer_text'] === '') {
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
        }

        if (($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') && $row['option_is_correct'] == 1) {
            $sections_data[$section_id]['questions'][$question_id]['correct_answer'] = $row['correct_option_text'];
        }

        if ($row['score_earned'] !== null) {
            $sections_data[$section_id]['section_score_earned'] += $row['score_earned'];
        }
    }
    $stmt->close();

    $report_data = $sections_data;

    // เตรียมข้อมูลสำหรับ Pie Chart (เฉพาะ MC/TF)
    $pie_chart_earned_score = 0;
    $pie_chart_max_score_auto_graded = 0;

    // เตรียมข้อมูลสำหรับ Radar Chart
    $radar_labels = [];
    $radar_data = [];

    foreach ($report_data as $section) {
        $section_earned = $section['section_score_earned'];
        $section_max = $section['section_max_score'];

        // สำหรับ Radar Chart
        if ($section_max > 0) {
            $radar_labels[] = $section['section_name'];
            $percentage = ($section_earned / $section_max) * 100;
            $radar_data[] = round($percentage, 2);
        }

        // สำหรับ Pie Chart (เฉพาะคำถามที่ตรวจได้อัตโนมัติ)
        foreach ($section['questions'] as $question) {
            if (($question['question_type'] == 'multiple_choice' || $question['question_type'] == 'true_false') && $question['score_earned'] !== null) {
                $pie_chart_earned_score += $question['score_earned'];
                $pie_chart_max_score_auto_graded += $question['question_max_score'];
            }
        }
    }
} catch (Exception $e) {
    // Log the error but don't display it to the user directly in production
    error_log("Error fetching report data for PDF: " . $e->getMessage());
    die(get_text('error_fetching_report_data_individual', 'PDF Export Failed')); // User-friendly message
}

// Start output buffering to capture HTML content
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?php echo get_text('page_title_individual_report'); ?></title>
    <style>
        /* Basic CSS for PDF - Dompdf has limited CSS support */
        body {
            font-family: 'thsarabunnew', 'Noto Sans Myanmar', 'Pyidaungsu', sans-serif;
            font-size: 16px;
            /* Default font size for most text */
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'thsarabunnew', 'Noto Sans Myanmar', 'Pyidaungsu', sans-serif;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .container {
            width: 90%;
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
            color: white;
            padding: 8px 10px;
            border-bottom: 1px solid #dc3545;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
        }

        .card-body {
            padding: 10px;
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
        }

        th {
            background-color: #f2f2f2;
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
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .25rem;
        }

        .bg-success {
            background-color: #28a745;
            color: white;
        }

        .bg-danger {
            background-color: #dc3545;
            color: white;
        }

        .bg-secondary {
            background-color: #6c757d;
            color: white;
        }

        .ms-2 {
            margin-left: 0.5rem;
        }

        /* Simple margin approximation */
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

        /* Custom fonts for Thai characters in Dompdf */
        /* Make sure the font file path is correct relative to where export_individual_report_pdf.php is */
        @font-face {
            font-family: 'thsarabunnew';
            font-style: normal;
            font-weight: normal;
            src: url('../../assets/fonts/THSarabunNew.ttf') format('truetype');
        }

        @font-face {
            font-family: 'thsarabunnew';
            font-style: normal;
            font-weight: bold;
            src: url('../../assets/fonts/THSarabunNew Bold.ttf') format('truetype');
        }

        @font-face {
            font-family: 'thsarabunnew';
            font-style: italic;
            font-weight: normal;
            src: url('../../assets/fonts/THSarabunNew Italic.ttf') format('truetype');
        }

        @font-face {
            font-family: 'thsarabunnew';
            font-style: italic;
            font-weight: bold;
            src: url('../../assets/fonts/THSarabunNew BoldItalic.ttf') format('truetype');
        }

        /* Myanmar font for Dompdf */
        @font-face {
            font-family: 'Noto Sans Myanmar';
            font-style: normal;
            font-weight: normal;
            src: url('../../assets/fonts/NotoSansMyanmar-Regular.ttf') format('truetype');
        }

        @font-face {
            font-family: 'Noto Sans Myanmar';
            font-style: normal;
            font-weight: bold;
            src: url('../../assets/fonts/NotoSansMyanmar-Bold.ttf') format('truetype');
        }

        /* CSS for page breaks */
        .page-break-before {
            page-break-before: always;
        }

        /*
        * REMOVED: .card { page-break-inside: avoid; }
        * Dompdf will now break content inside a card if it overflows a page.
        */

        /* Chart Styles */
        .chart-container {
            margin: 20px 0;
            text-align: center;
        }

        .score-chart {
            width: 300px;
            margin: 0 auto;
            background-color: #f8f9fa;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
        }

        .score-display {
            text-align: center;
            margin-bottom: 15px;
        }

        .score-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .score-label {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }

        .progress-bar {
            height: 25px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-fill {
            height: 100%;
            background-color: #dc3545;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .chart-legend {
            display: flex;
            justify-content: center;
            margin-top: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-right: 8px;
        }

        .legend-earned {
            background-color: #28a745;
        }

        .legend-remaining {
            background-color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><?php echo get_text('page_heading_individual_report'); ?></h1>
            <p><?php echo get_text('report_generated_on') . ' ' . thai_datetime_format(date('Y-m-d H:i:s')); ?></p>
        </div>

        <div class="card">
            <div class="card-header">
                <h4><?php echo get_text('examinee_test_info_heading'); ?></h4>
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
                    <span class="text-success"><?php echo htmlspecialchars(number_format($test_info['total_score_earned'] ?? 0, 2)); ?></span> /
                    <span class="text-muted"><?php echo htmlspecialchars(number_format($max_total_score, 2)); ?></span>
                    <span class="badge <?php echo ($test_info['is_completed'] ?? false) ? 'bg-success' : 'bg-warning text-dark'; ?>">
                        <?php echo ($test_info['is_completed'] ?? false) ? get_text('completed_status') : get_text('in_progress_status'); ?>
                    </span>
                </p>
            </div>
        </div>

        <!-- Score Summary Chart
        <div class="card mt-4 page-break-before">
            <div class="card-header" style="background-color: #dc3545; color: white;">
                <h5><?php echo get_text('score_summary_chart_title', 'Auto-Graded Score Summary'); ?></h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="score-chart">
                        <div class="score-display">
                            <div class="score-number"><?php echo number_format($pie_chart_earned_score, 1); ?> / <?php echo number_format($pie_chart_max_score_auto_graded, 1); ?></div>
                            <div class="score-label"><?php echo get_text('auto_graded_score_label', 'Auto-Graded Score'); ?></div>
                        </div>

                        <?php
                        $percentage = $pie_chart_max_score_auto_graded > 0 ? ($pie_chart_earned_score / $pie_chart_max_score_auto_graded) * 100 : 0;
                        ?>

                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo number_format($percentage, 1); ?>%;">
                                <?php echo number_format($percentage, 1); ?>%
                            </div>
                        </div>
                    </div>


                </div>
                <table style="margin-top: 20px;">
                    <thead>
                        <tr style="background-color: #f8f9fa;">
                            <th><?php echo get_text('category_label', 'Category'); ?></th>
                            <th><?php echo get_text('score_earned_label', 'Score Earned'); ?></th>
                            <th><?php echo get_text('max_score_label', 'Max Score'); ?></th>
                            <th><?php echo get_text('percentage_label', 'Percentage'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo get_text('auto_graded_questions_label', 'Auto-Graded Questions (MC/TF)'); ?></td>
                            <td style="color: #28a745; font-weight: bold;"><?php echo number_format($pie_chart_earned_score, 2); ?></td>
                            <td><?php echo number_format($pie_chart_max_score_auto_graded, 2); ?></td>
                            <td style="color: #dc3545; font-weight: bold;">
                                <?php
                                $auto_percentage = $pie_chart_max_score_auto_graded > 0 ? ($pie_chart_earned_score / $pie_chart_max_score_auto_graded) * 100 : 0;
                                echo number_format($auto_percentage, 2) . '%';
                                ?>
                            </td>
                        </tr>
                        <tr style="background-color: #f8f9fa;">
                            <td><?php echo get_text('total_test_score_label', 'Total Test Score'); ?></td>
                            <td style="color: #28a745; font-weight: bold;"><?php echo number_format($test_info['total_score_earned'], 2); ?></td>
                            <td><?php echo number_format($max_total_score, 2); ?></td>
                            <td style="color: #dc3545; font-weight: bold;">
                                <?php
                                $total_percentage = $max_total_score > 0 ? ($test_info['total_score_earned'] / $max_total_score) * 100 : 0;
                                echo number_format($total_percentage, 2) . '%';
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

            </div>

            <div class="card-body">
                <table>
                    <thead>
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
    </div> -->

        <!-- Section Performance Chart -->
        <div class="card mt-4 page-break-before">
            <div class="card-header" style="background-color: #dc3545; color: white; font-weight: 700; font-size: 18px;">
                <h5><?php echo get_text('section_performance_chart_title', 'Section Performance'); ?></h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <?php foreach ($report_data as $section): ?>
                        <?php
                        $section_percentage = $section['section_max_score'] > 0 ? ($section['section_score_earned'] / $section['section_max_score']) * 100 : 0;
                        ?>
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <strong><?php echo htmlspecialchars($section['section_name']); ?></strong>
                                <span style="color: #dc3545; font-weight: bold;"><?php echo number_format($section_percentage, 1); ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($section_percentage, 100); ?>%;">
                                    <?php echo number_format($section['section_score_earned'], 1); ?>/<?php echo number_format($section['section_max_score'], 1); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <table style="margin-top: 20px;">
                    <thead>
                        <tr style="background-color:rgb(0, 0, 0);">
                            <th><?php echo get_text('section_name_label', 'Section'); ?></th>
                            <th><?php echo get_text('score_earned_label', 'Score Earned'); ?></th>
                            <th><?php echo get_text('max_score_label', 'Max Score'); ?></th>
                            <th><?php echo get_text('percentage_label', 'Percentage'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $index => $section): ?>
                            <tr <?php echo ($index % 2 == 1) ? 'style="background-color: #f8f9fa;"' : ''; ?>>
                                <td style="font-weight: bold;"><?php echo htmlspecialchars($section['section_name']); ?></td>
                                <td style="color: #28a745; font-weight: bold;"><?php echo number_format($section['section_score_earned'], 2); ?></td>
                                <td><?php echo number_format($section['section_max_score'], 2); ?></td>
                                <td style="color: #dc3545; font-weight: bold;">
                                    <?php
                                    $section_percentage = $section['section_max_score'] > 0 ? ($section['section_score_earned'] / $section['section_max_score']) * 100 : 0;
                                    echo number_format($section_percentage, 2) . '%';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($report_data)): ?>
            <?php foreach ($report_data as $index => $section): ?>
                <?php if ($index > 0): ?>
                    <div style="page-break-before: always;"></div>
                <?php endif; ?>
                <div class="card mb-3">

                    <div class="card-header" style="background-color: #dc3545; color: white;">
                        <h4 style="font-weight: 700;">
                            <?php echo htmlspecialchars($section['section_order'] . ". " . $section['section_name']); ?>
                            <span style="float: right; font-weight: 600;">
                                <?php echo get_text('section_score_label'); ?> <?php echo htmlspecialchars(number_format($section['section_score_earned'], 2)); ?> / <?php echo htmlspecialchars(number_format($section['section_max_score'], 2)); ?>
                            </span>
                        </h4>
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
                            <?php
                            $question_counter = 0; // เพิ่มตัวนับสำหรับคำถามภายใน Section
                            ?>
                            <?php foreach ($section['questions'] as $question): ?>
                                <?php $question_counter++; // นับคำถาม 
                                ?>
                                <?php
                                // เงื่อนไขใหม่: ถ้าเป็น Section 1 และเป็นคำถามลำดับที่ 3
                                // ให้ขึ้นหน้าใหม่
                                if ($section['section_order'] == 1 && $question_counter == 3) {
                                    echo '<div class="page-break-before"></div>';
                                }
                                ?>
                                <div style="border: 1px solid #dee2e6; padding: 12px; margin-bottom: 15px; border-left: 5px solid <?php echo ($question['user_is_correct'] === 1) ? '#28a745' : (($question['user_is_correct'] === 0) ? '#dc3545' : '#6c757d'); ?>; background-color: <?php echo ($question['user_is_correct'] === 1) ? '#f8fff9' : (($question['user_is_correct'] === 0) ? '#fff8f8' : '#f8f9fa'); ?>; page-break-inside: avoid;">
                                    <p class="mb-1">
                                        <span class="badge <?php echo ($question['user_is_correct'] === 1) ? 'bg-success' : (($question['user_is_correct'] === 0) ? 'bg-danger' : 'bg-secondary'); ?>">
                                            <?php
                                            if ($question['user_is_correct'] === 1) {
                                                echo get_text('correct_answer_badge');
                                            } else if ($question['user_is_correct'] === 0) {
                                                echo get_text('incorrect_answer_badge');
                                            } else {
                                                echo get_text('not_available_abbr');
                                            }
                                            ?>
                                        </span>
                                        <strong><?php echo htmlspecialchars($question['question_order'] . ". " . $question['question_text']); ?></strong>
                                        <small class="text-muted">(<?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $question['question_type']))); ?> | <?php echo get_text('score_earned_label_question'); ?> <?php echo htmlspecialchars(number_format($question['score_earned'] ?? 0, 2)); ?>/<?php echo htmlspecialchars($question['question_max_score']); ?>)</small>
                                    </p>
                                    <p class="mb-1" style="margin-left: 20px;">
                                        <strong><?php echo get_text('user_answer_label'); ?>: </strong>
                                        <?php if (!empty($question['user_answer_text'])): ?>
                                            <?php if (in_array($question['question_type'], ['multiple_choice', 'true_false'])): ?>
                                                <?php if (!empty($question['user_selected_option_text'])): ?>
                                                    <?php echo htmlspecialchars($question['user_selected_option_text']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php echo get_text('answer_not_found'); ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php echo nl2br(htmlspecialchars($question['user_answer_text'])); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo get_text('not_answered'); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false'): ?>
                                        <p class="mb-0" style="margin-left: 20px;">
                                            <strong><?php echo get_text('correct_answer_display_label'); ?>: </strong>
                                            <span class="text-success"><?php echo htmlspecialchars($question['correct_answer'] ?? get_text('no_correct_answer_available')); ?></span>
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
            <div style="background-color: #e2f3ff; border: 1px solid #bce8f1; padding: 15px; text-align: center; border-radius: 5px;">
                <p><?php echo get_text('no_report_details_found'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
<?php
$html = ob_get_clean(); // Get the buffered HTML content

// Instantiate Dompdf
$options = new Options();
$options->set('defaultFont', 'thsarabunnew'); // Set the default font for the PDF
$options->set('isHtml5ParserEnabled', true);
// IMPORTANT: Enable remote for any external assets (like images, though not used for chart here)
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

// Load HTML to Dompdf
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF (inline download)
$filename = 'individual_report_' . $attempt_id . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]); // "Attachment" => true to force download

// No chart image to clean up
exit();
?>