<?php
// admin/export_user_history_pdf.php
// ✅ รายงาน PDF สรุปประวัติการทดสอบทั้งหมดของผู้ใช้ 1 คน (ตาม user_id)

date_default_timezone_set('Asia/Bangkok');

// include ฟังก์ชันหลัก + autoload ของ Composer
require_once __DIR__ . '/../../includes/functions.php';
// Use Portal's vendor/autoload.php (root level) instead of IGA's own vendor
if (file_exists('/var/www/html/vendor/autoload.php')) {
    require_once '/var/www/html/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// ---------- ฟังก์ชัน helper กันเรียกไฟล์ตรง ----------
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
            'test_result'                        => 'ผลการประเมิน',
            'role_label'                         => 'บทบาท',
            'page_heading_individual_report'     => 'รายงานผลการประเมินรายบุคคล',
            'label_emplevel'                     => 'ระดับตำแหน่ง',
        ];
        return $texts[$key] ?? $default;
    }
}

// ---------- ตรวจสิทธิ์ ----------
require_login();
if (
    !has_role('admin') &&
    !has_role('Super_user_Recruitment')
) {
    set_alert(get_text('admin_permissions_required', 'No permission'), "danger");
    header("Location: /login");
    exit();
}

// ---------- รับ user_id ----------
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
if (empty($user_id)) {
    set_alert(get_text('error_invalid_user_id'), "danger");
    header("Location: view_reports.php");
    exit();
}

$user_info         = [];
$final_report_data = [];

// ---------- ดึงข้อมูลจากฐาน ----------
try {
    global $conn;
    if (!isset($conn)) {
        throw new Exception('Database connection $conn is not set.');
    }

    // 1) ข้อมูลผู้ใช้
    $stmt = $conn->prepare("
        SELECT u.fullname, u.email, r.role_name, lv.level_code,o.OrgCode,u.OrgUnitName,u.EmpType,u.OrgUnitTypeName
        FROM users u
        LEFT JOIN roles r ON r.role_id = u.role_id
        LEFT JOIN emplevelcode lv ON lv.level_id = u.emplevel_id
        LEFT JOIN iga_orgunit o ON o.OrgID = u.OrgID
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
            CASE WHEN uta.test_id IN (14,15,22, 23) THEN 1 ELSE 0 END ASC,
            uta.test_id ASC
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

        // A) คะแนนราย section
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

        // B) pass / fail
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
        if (in_array($test_id, [14, 15, 22, 23], true)) {
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

// ---------- สร้าง HTML ----------
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
            font-family: 'thsarabun', sans-serif;
            font-size: 12pt;
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
            margin-bottom: 15px;
        }

        .card {
            border: 1px solid #000;
            border-radius: 0;
            margin-bottom: 15px;
            padding: 10px;
        }

        .card p {
            margin: 3px 0;
            /* เดิมมันเยอะไป ลดให้เตี้ยลง */
        }

        /* ถ้าอยากกระทบเฉพาะตารางสรุปคะแนนก็โอเค เพราะใช้ table ทั้งหมดอยู่แล้ว */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            /* เดิม 10px → ลดลงให้สมดุลกับข้อความด้านบน */
            margin-bottom: 4px;
        }

        .text-center {
            text-align: center;
        }

        .card-body {
            border: 1px solid #000;
            border-radius: 0;
            margin-bottom: 15px;
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

        .detail-question {
            border-bottom: 1px;
            /* ✅ เส้นเดียวด้านล่าง */
            padding: 8px 0;
            /* ช่องไฟด้านบน–ล่างภายในข้อ */
            margin: 0;
            /* ไม่ให้มี margin เกินมาจากกล่อง */
            page-break-inside: avoid;
        }

        .detail-question p {
            margin: 0;
            /* ตัด margin ของ <p> ทิ้ง ให้เท่ากันทุกข้อ */
        }

        .answer-wrapper {
            margin-top: 6px;
            /* ช่องไฟระหว่างคำถามกับกล่องคำตอบ */
        }

        .answer-box {
            border: 1px solid #ddd;
            padding: 5px 8px;
            background: #fff;
            width: 100%;
            box-sizing: border-box;
        }

        .test-block {
            page-break-inside: avoid;
            margin-bottom: 15px;
        }

        .user-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .user-info-table td {
            padding: 2px 4px;
            vertical-align: top;
            border: none;
            font-size: 11pt;
        }

        .user-info-table .label {
            font-weight: bold;
            white-space: nowrap;
            /* ไม่ต้องกำหนด width */
        }

        .user-info-table .value {
            /* ปล่อยให้กินพื้นที่ที่เหลือ */
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><strong><?php echo get_text('page_heading_individual_report'); ?></strong></h1>
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

        <!-- ข้อมูลผู้เข้าสอบ -->
        <div class="card-body">
            <table class="user-info-table">
                <tr>
                    <td class="label">
                        <?php echo get_text('examinee_name_label'); ?>
                    </td>
                    <td class="value">
                        <?php echo htmlspecialchars($user_info['full_name'] ?? get_text('not_available_abbr')); ?>
                    </td>

                    <td class="label">
                        <?php echo get_text('email_label'); ?>:
                    </td>
                    <td class="value">
                        <?php
                        echo !empty($user_info['email'])
                            ? htmlspecialchars($user_info['email'])
                            : '<span class="text-muted">-</span>';
                        ?>
                    </td>
                </tr>

                <tr>
                    <td class="label">
                        <?php echo get_text('role_label'); ?>:
                    </td>
                    <td class="value">
                        <?php echo display_or_dash(get_text($translation_key)); ?>
                    </td>

                    <td class="label">
                        <?php echo get_text('label_emplevel'); ?>:
                    </td>
                    <td class="value">
                        <?php echo display_or_dash($user_info['level_code'] ?? null); ?>
                    </td>
                </tr>

                <?php
                // เงื่อนไข associate / role_id = 4
                $isAssociate = false;
                if (!empty($user_info['role_id']) && (int)$user_info['role_id'] === 4) {
                    $isAssociate = true;
                } elseif (!empty($user_info['role_name']) && strcasecmp($user_info['role_name'], 'associate') === 0) {
                    $isAssociate = true;
                }

                if ($isAssociate):
                    // เตรียมข้อความ OrgUnit + OrgCode
                    $orgText = '';
                    if (!empty($user_info['OrgUnitName'])) {
                        $orgText .= $user_info['OrgUnitName'];
                    }
                    if (!empty($user_info['OrgCode'])) {
                        $orgText .= ($orgText !== '' ? ' ' : '') . '(' . $user_info['OrgCode'] . ')';
                    }
                ?>
                    <tr>
                        <td class="label"> <?php echo get_text('EmpType'); ?>: </td>
                        <td class="value">
                            <?php echo display_or_dash($user_info['EmpType'] ?? null); ?>
                        </td>

                        <td class="label"><?php echo display_or_dash($user_info['OrgUnitTypeName'] ?? ''); ?>: </td>

                        <td class="value">
                            <?php echo display_or_dash($orgText ?: null); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
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
                        <!-- หัวแบบประเมิน + รายละเอียด -->
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

                        <!-- ตารางประสิทธิภาพแต่ละส่วน -->
                        <table>
                            <thead>
                                <tr>
                                    <th class="text-center"><strong><?php echo get_text('section_name_label'); ?></strong></th>
                                    <th class="text-center"><strong><?php echo get_text('score_earned_label'); ?></strong></th>
                                    <th class="text-center"><strong><?php echo get_text('max_score_label'); ?></strong></th>
                                    <th class="text-center"><strong><?php echo get_text('percentage_label'); ?></strong></th>
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
                                        <td class="text-success text-center" style="font-weight:bold;">
                                            <?php echo number_format($sec_earned, 0); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo number_format($sec_max, 0); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php echo number_format($pct, 0) . '%'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php
                                // คำนวณเปอร์เซ็นต์รวมของแบบประเมิน
                                $overall_pct = $total_max > 0 ? ($total_earned / $total_max) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo get_text('total_score_earned_label'); ?></strong></td>
                                    <td class="text-success text-center" style="font-weight:bold;">
                                        <?php echo number_format($total_earned, 0); ?>
                                    </td>
                                    <td class="text-center" style="font-weight:bold;">
                                        <?php echo number_format($total_max, 0); ?>
                                    </td>
                                    <td class="text-center" style="font-weight:bold;">
                                        <?php echo number_format($overall_pct, 0) . '%'; ?>
                                    </td>
                                </tr>
                            </tbody>

                        </table>
                        <?php if (!empty($report['detailed_questions']) || ''): ?>
                            <?php
                            $first_dq      = $report['detailed_questions'][0];
                            $section_title = $first_dq['section_name'] ?? 'Section 3';
                            ?>
                            <h4><?php echo htmlspecialchars($section_title); ?></h4>
                            <?php
                            $det_idx = 0;
                            foreach ($report['detailed_questions'] as $q_detail):
                                $det_idx++;
                            ?>
                                <div class="detail-question">
                                    <p>
                                        <strong>
                                            <?php echo $det_idx; ?>.
                                            <?php echo nl2br(htmlspecialchars($q_detail['question_text'])); ?>:
                                        </strong>
                                    </p>

                                    <div class="answer-wrapper">
                                        <div class="answer-box">
                                            <?php
                                            $ans = trim($q_detail['user_answer_text'] ?? '');
                                            echo nl2br(htmlspecialchars($ans !== '' ? $ans : 'N/A'));
                                            ?>

                                        </div>
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

// // ---------- DEBUG HTML ----------
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// $debugHtmlPath = __DIR__ . '/debug_user_history_html_' . $user_id . '.html';
// file_put_contents($debugHtmlPath, $html);

// if (trim($html) === '') {
//     error_log('PDF DEBUG: $html is empty in ' . __FILE__);
//     die('DEBUG: HTML ว่างเปล่า ลองเปิดไฟล์ ' . basename($debugHtmlPath) . ' ในโฟลเดอร์ admin ดู');
// }

// ---------- mPDF + ฟอนต์ไทย ----------
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
