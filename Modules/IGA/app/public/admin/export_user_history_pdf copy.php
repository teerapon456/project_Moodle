<?php
// admin/export_user_history_pdf.php
// ✅ รายงาน PDF สรุปประวัติการทดสอบทั้งหมดของผู้ใช้ 1 คน (ตาม user_id)

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// กันกรณีเรียกไฟล์ตรงโดยไม่มีฟังก์ชันเหล่านี้
if (!function_exists('thai_datetime_format')) {
    function thai_datetime_format($datetime_str)
    {
        if (empty($datetime_str)) return '-';
        return date('d/m/Y H:i', strtotime($datetime_str));
    }
}
if (!function_exists('formatTimeSpent')) {
    function formatTimeSpent($seconds)
    {
        if ($seconds === null) return 'N/A';
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
// สำหรับแสดง "X นาที Y วินาที" แบบในตัวอย่าง
if (!function_exists('formatTimeSpentHuman')) {
    function formatTimeSpentHuman($seconds)
    {
        if ($seconds === null) return '-';
        $seconds = (int)$seconds;
        $m = floor($seconds / 60);
        $s = $seconds % 60;

        if ($m > 0 && $s > 0) {
            return $m . ' นาที ' . $s . ' วินาที';
        } elseif ($m > 0) {
            return $m . ' นาที';
        } else {
            return $s . ' วินาที';
        }
    }
}
if (!function_exists('get_text')) {
    function get_text($key, $default = 'N/A')
    {
        // mock ไว้เฉย ๆ ของจริงใช้จากระบบหลัก
        $texts = [
            'report_generated_on'                 => 'รายงานสร้างเมื่อ',
            'examinee_name_label'                => 'ชื่อผู้ร่วมประเมิน',
            'email_label'                        => 'อีเมล',
            'test_history_heading'               => 'ประวัติการทดสอบทั้งหมด',
            'test_name_label'                    => 'ชื่อแบบประเมิน',
            'test_description_label'             => 'คำอธิบายแบบประเมิน',
            'start_time_label'                   => 'เวลาเริ่มต้น',
            'end_time_label'                     => 'เวลาสิ้นสุด',
            'total_time_spent_label'             => 'เวลาที่ใช้ทั้งหมด',
            'total_score_earned_label'           => 'คะแนนรวมที่ได้',
            'section_name_label'                 => 'ชื่อส่วน',
            'score_earned_label'                 => 'คะแนนที่ได้',
            'max_score_label'                    => 'คะแนนเต็ม',
            'percentage_label'                   => 'เปอร์เซ็นต์',
            'test_status_passed'                 => 'ผ่าน',
            'test_status_failed'                 => 'ไม่ผ่าน',
            'not_completed_status'               => 'ยังไม่เสร็จสิ้น',
            'completed_status'                   => 'เสร็จสิ้น',
            'not_available_abbr'                 => '-',
            'no_tests_found'                     => 'ไม่พบประวัติการทดสอบที่เสร็จสมบูรณ์',
            'error_invalid_user_id'              => 'รหัสผู้ใช้ไม่ถูกต้อง',
            'admin_permissions_required'         => 'ไม่ได้รับอนุญาตให้เข้าถึง',
            'user_id'                            => 'รหัสผู้ใช้ (User ID)',
            'error_fetching_report_data_individual' => 'เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน',
            'attempt_id'                         => 'รหัสการพยายาม (Attempt ID)',
            'section_performance_chart_title'    => 'ประสิทธิภาพแต่ละส่วน',
            'not_answered'                       => 'ไม่ได้ตอบ',
            'test_result'                        => 'ผลการประเมิน',
            'role_label'                         => 'บทบาท',
            'page_heading_individual_report'     => 'รายงานผลการประเมินรายบุคคล',
        ];
        return $texts[$key] ?? $default;
    }
}

require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
    set_alert(get_text('admin_permissions_required', 'No permission'), "danger");
    header("Location: /login");
    exit();
}

$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
if (empty($user_id)) {
    set_alert(get_text('error_invalid_user_id'), "danger");
    header("Location: view_reports.php");
    exit();
}

$user_info         = [];
$final_report_data = [];

try {
    // 1) ข้อมูลผู้ใช้
    $stmt = $conn->prepare("
        SELECT u.full_name, u.email, r.role_name,lv.level_code
        FROM users u
        JOIN roles r ON r.role_id = u.role_id
        JOIN emplevelcode lv ON lv.level_id = u.emplevel_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user_info) {
        set_alert("User not found.", "danger");
        header("Location: view_reports.php");
        exit();
    }

    // 2) attempts ทั้งหมดที่เสร็จแล้ว
    $stmt = $conn->prepare("
        SELECT
            uta.attempt_id, uta.test_id, uta.start_time, uta.end_time, uta.total_score,
            uta.is_completed, uta.time_spent_seconds,
            t.test_name, t.description AS test_description, t.min_passing_score
        FROM iga_user_test_attempts uta
        JOIN iga_tests t ON uta.test_id = t.test_id
        WHERE uta.user_id = ? AND uta.is_completed = 1
        ORDER BY
            CASE WHEN uta.test_id IN (22, 23) THEN 1 ELSE 0 END ASC,
            uta.start_time DESC
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $attempts_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 3) เตรียมข้อมูลแต่ละ attempt
    foreach ($attempts_list as $attempt_info) {
        $attempt_id = (int)$attempt_info['attempt_id'];
        $test_id    = (int)$attempt_info['test_id'];

        $total_max_shown        = 0.0;
        $pie_chart_earned_score = 0.0;
        $sections_data          = [];
        $detailed_section_3_questions = [];
        $min_passing_score      = (float)($attempt_info['min_passing_score'] ?? 0);
        $test_passed            = true;
        $failed_critical_question = false;

        // A) คะแนนราย section (จากข้อที่แสดงจริง)
        $stmt = $conn->prepare("
            SELECT
                s.section_id, s.section_name, s.section_order,
                q.question_id, COALESCE(q.score,0) AS question_max_score, q.is_critical,
                ua.score_earned, ua.user_answer_text, ua.is_correct AS user_is_correct
            FROM (
                SELECT question_id, MIN(shown_order) AS shown_order
                FROM iga_user_attempt_questions
                WHERE attempt_id = ?
                GROUP BY question_id
            ) uaq_order
            JOIN iga_questions q ON q.question_id = uaq_order.question_id
            JOIN iga_sections s ON s.section_id = q.section_id
            LEFT JOIN iga_user_answers ua
                ON ua.attempt_id = ? AND ua.question_id = uaq_order.question_id
            ORDER BY uaq_order.shown_order ASC
        ");
        $stmt->bind_param("ii", $attempt_id, $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $section_id      = (int)$row['section_id'];
            $q_max           = (float)$row['question_max_score'];
            $score_earned    = ($row['score_earned'] !== null) ? (float)$row['score_earned'] : null;
            $user_is_correct = $row['user_is_correct'];

            if (!isset($sections_data[$section_id])) {
                $sections_data[$section_id] = [
                    'section_name'         => $row['section_name'],
                    'section_max_score'    => 0.0,
                    'section_score_earned' => 0.0,
                ];
            }

            if ($q_max > 0) {
                $total_max_shown += $q_max;
                $sections_data[$section_id]['section_max_score'] += $q_max;
            }

            if ($score_earned !== null) {
                $pie_chart_earned_score += $score_earned;
                $sections_data[$section_id]['section_score_earned'] += $score_earned;
            }

            if ((int)$row['is_critical'] === 1 && $user_is_correct === 0) {
                $failed_critical_question = true;
            }
        }
        $stmt->close();

        // B) pass / fail (ใช้เผื่ออนาคต ถึงหน้าตา pdf ตอนนี้ไม่ได้โชว์ badge แล้วก็ตาม)
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

        // C) รายละเอียดคำตอบ section 3 (เฉพาะ test 22,23)
        if (in_array($test_id, [22, 23], true)) {
            $detail_stmt = $conn->prepare("
                SELECT
                    q.section_id,
                    s.section_name,
                    q.question_text, q.question_type, COALESCE(q.score,0) AS question_max_score,
                    ua.user_answer_text, ua.score_earned
                FROM iga_user_attempt_questions uaq
                JOIN iga_questions q ON q.question_id = uaq.question_id
                JOIN iga_sections s  ON s.section_id = q.section_id
                LEFT JOIN iga_user_answers ua ON ua.attempt_id = ? AND ua.question_id = q.question_id
                WHERE uaq.attempt_id = ?
                  AND s.section_order = 3
                  AND q.question_type IN ('short_answer')
                ORDER BY uaq.shown_order ASC
            ");
            $detail_stmt->bind_param("ii", $attempt_id, $attempt_id);
            $detail_stmt->execute();
            $detailed_section_3_questions = $detail_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $detail_stmt->close();
        }

        $final_report_data[] = [
            'attempt_info'       => $attempt_info,
            'test_passed'        => $test_passed,
            'summary_sections'   => $sections_data,
            'total_max_shown'    => $total_max_shown,
            'total_score_earned' => $pie_chart_earned_score,
            'detailed_questions' => $detailed_section_3_questions,
        ];
    }
} catch (Throwable $e) {
    error_log("Error fetching user history report data for PDF: " . $e->getMessage());
    die(get_text('error_fetching_report_data_individual', 'PDF Export Failed'));
}

// ---------------- Dompdf ----------------
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);

// HTML
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title><?php echo get_text('page_heading_individual_report'); ?></title>
    <style>
        @page {
            margin: 20px 25px;
        }

        body {
            font-family: 'Helvetica', 'Sarabun', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 8px 0;
            font-weight: 700;
        }

        .container {
            width: 98%;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
        }

        .card {
            border: 1px solid #000;
            border-radius: 0;
            margin-bottom: 16px;
            padding: 10px;
        }

        .card-body {
            border: 1px solid #000;
            border-radius: 0;
            margin-bottom: 16px;
            padding: 10px;
        }

        .card-title {
            font-weight: 700;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            font-weight: 400;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            font-weight: 700;
        }

        tr {
            page-break-inside: avoid;
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

        .detail-box {
            border: 1px solid #ccc;
            padding: 8px;
            margin-top: 8px;
            background-color: #f9f9f9;
            page-break-inside: avoid;
        }

        .detail-question {
            margin-bottom: 10px;
            border-bottom: 1px dashed #d4d2d2;
            padding-bottom: 8px;
            page-break-inside: avoid;
        }

        .test-block {
            page-break-inside: avoid;
            margin-bottom: 18px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><?php echo get_text('page_heading_individual_report'); ?></h1>
            <p><?php echo get_text('report_generated_on') . ' ' . thai_datetime_format(date('Y-m-d H:i:s')); ?></p>
        </div>

        <?php
        function display_or_dash($value)
        {
            return ($value !== null && $value !== '')
                ? htmlspecialchars($value)
                : '<span class="text-muted">-</span>';
        }
        $role_name_key   = $user_info['role_name'] ?? '';
        $translation_key = 'role_' . $role_name_key;
        ?>

        <!-- ชื่อผู้เข้าสอบ (ลอย ไม่อยู่ในกรอบ) -->
        <div class="card-body" style="margin-bottom: 15px;">

            <span style="margin-right: 30px;">
                <strong><?php echo get_text('examinee_name_label'); ?></strong>
                <?php echo htmlspecialchars($user_info['full_name'] ?? get_text('not_available_abbr')); ?>
            </span>

            <?php if (!empty($user_info['email'])): ?>
                <span style="margin-right: 30px;">
                    <strong><?php echo get_text('email_label'); ?>:</strong>
                    <?php echo htmlspecialchars($user_info['email']); ?>
                </span>
            <?php endif; ?>

            <span style="margin-right: 30px;">
                <strong><?php echo get_text('role_label'); ?>:</strong>
                <?php echo display_or_dash(get_text($translation_key)); ?>
            </span>

            <span>
                <strong><?php echo get_text('label_emplevel'); ?>:</strong>
                <?php echo htmlspecialchars($user_info['level_code']); ?>
            </span>

        </div>
        <?php if (empty($final_report_data)): ?>
            <div style="background:#e2f3ff;border:1px solid #bce8f1;padding:15px;text-align:center;border-radius:5px;">
                <p><?php echo get_text('no_tests_found'); ?></p>
            </div>
        <?php else: ?>
            <?php
            $attempt_count = 0;
            foreach ($final_report_data as $report):
                $attempt_count++;
                $info         = $report['attempt_info'];
                $sections     = $report['summary_sections'];
                $total_max    = $report['total_max_shown'];
                $total_earned = $report['total_score_earned'];
            ?>
                <div class="test-block">
                    <div class="card">
                        <!-- หัวแบบประเมิน + รายละเอียด เหมือนรูปตัวอย่าง -->
                        <p class="card-title">
                            <strong><?php echo get_text('test_name_label'); ?>:</strong>
                            <?php echo ' ' . htmlspecialchars($info['test_name']); ?>
                        </p>

                        <?php if (!empty($info['test_description'])): ?>
                            <p>
                                <strong><?php echo get_text('test_description_label'); ?></strong>
                                <?php echo ' ' . nl2br(htmlspecialchars($info['test_description'])); ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            <strong><?php echo get_text('total_time_spent_label'); ?></strong>
                            <?php echo ' ' . formatTimeSpentHuman($info['time_spent_seconds'] ?? null); ?>
                        </p>

                        <p>
                            <strong><?php echo get_text('total_score_earned_label'); ?></strong>
                            <?php echo ' ' . htmlspecialchars(number_format($total_earned, 2)); ?>
                            /
                            <?php echo htmlspecialchars(number_format($total_max, 2)); ?>
                        </p>

                        <!-- ตารางประสิทธิภาพแต่ละส่วน -->
                        <table>
                            <thead>
                                <tr>
                                    <th><?php echo get_text('section_name_label'); ?></th>
                                    <th><?php echo get_text('score_earned_label'); ?></th>
                                    <th><?php echo get_text('max_score_label'); ?></th>
                                    <th><?php echo get_text('percentage_label'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sections as $section): ?>
                                    <?php
                                    $sec_earned = $section['section_score_earned'];
                                    $sec_max    = $section['section_max_score'];
                                    $pct        = $sec_max > 0 ? ($sec_earned / $sec_max) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($section['section_name']); ?></td>
                                        <td class="text-success" style="font-weight:bold;"><?php echo number_format($sec_earned, 2); ?></td>
                                        <td><?php echo number_format($sec_max, 2); ?></td>
                                        <td><?php echo number_format($pct, 2) . '%'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if (!empty($report['detailed_questions'])): ?>
                            <?php
                            $first_dq    = $report['detailed_questions'][0];
                            $section_title = $first_dq['section_name'] ?? 'Section 3';
                            ?>
                            <!-- {{-- **จุดที่แก้ไข: เพิ่ม style="page-break-before: always;" เพื่อบังคับขึ้นหน้าใหม่** --}} -->
                            <h4><?php echo htmlspecialchars($section_title); ?></h4>
                            <?php
                            $det_idx = 0;
                            foreach ($report['detailed_questions'] as $q_detail):
                                $det_idx++;
                            ?>
                                <div class="detail-question">
                                    <p style="margin-bottom: 5px;">
                                        <strong>
                                            <?php echo $det_idx; ?>.
                                            <?php echo nl2br(htmlspecialchars($q_detail['question_text'])); ?>:
                                        </strong>
                                    </p>
                                    <div style="margin-left: 10px;">
                                        <span style="display:block; border: 1px solid #ddd; padding: 5px; background: #fff;">
                                            <?php echo nl2br(htmlspecialchars($q_detail['user_answer_text'] ?? get_text('not_answered'))); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'user_history_report_' . $user_id . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);
exit();
