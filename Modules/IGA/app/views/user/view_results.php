<?php
// ไฟล์นี้จะทำหน้าที่แสดงผลลัพธ์ของแบบทดสอบที่ผู้ใช้ทำเสร็จสิ้นแล้ว
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/header.php';
// กำหนดพาธสำหรับ error log (ควรอยู่นอก public_html หรือ web root เพื่อความปลอดภัย)
// ตั้งค่าการรายงานข้อผิดพลาดและการบันทึก
ini_set('display_errors', 0); // ไม่แสดง error บนหน้าเว็บจริงเพื่อความปลอดภัย
ini_set('log_errors', 1); // เปิดใช้งานการบันทึก error
ini_set('error_log', LOG_FILE); // กำหนดไฟล์สำหรับบันทึก error


$conn->query("SET time_zone = '+07:00'");

$page_title = get_text('page_title_view_result'); // ใช้ get_text()

require_login();
if (!has_role('associate') && !has_role('applicant')) {
    set_alert(get_text('alert_no_permission_user'), "danger"); // ใช้ get_text()
    header("Location: /INTEQC_GLOBAL_ASSESMENT/login");
    exit();
}

$user_id = $_SESSION['user_id'];
// ในไฟล์ take_test.php
$test_id = $_POST['test_id'] ?? null; // ตรวจสอบให้แน่ใจว่าเป็น $_POST
$attempt_id = $_POST['attempt_id'] ?? null; // เปลี่ยนจาก $_GET เป็น $_POST สำหรับ attempt_id
// การจัดการเมื่อไม่พบ attempt_id หรือ attempt_id ไม่ถูกต้อง
if (!is_numeric($attempt_id) || $attempt_id <= 0) {
    set_alert(get_text('alert_invalid_attempt_id'), "danger"); // ใช้ get_text()
    header("Location: /INTEQC_GLOBAL_ASSESMENT/user"); // redirect ไปยัง dashboard
    exit();
}

$attempt_info = [];
$test_info = [];
$questions_and_answers = [];
$total_score_earned = 0;
$total_max_score = 0;
$user_percentage_score = 0; // 💡 เพิ่มตัวแปรสำหรับคะแนนเป็นเปอร์เซ็นต์
$pass_fail_status = ''; // 💡 เพิ่มตัวแปรสำหรับสถานะผ่าน/ไม่ผ่าน

try {
    $stmt_attempt = $conn->prepare("SELECT attempt_id, test_id, user_id, start_time, end_time, time_spent_seconds, is_completed FROM user_test_attempts WHERE attempt_id = ? AND user_id = ?");
    $stmt_attempt->bind_param("ii", $attempt_id, $user_id);
    $stmt_attempt->execute();
    $attempt_info = $stmt_attempt->get_result()->fetch_assoc();
    $stmt_attempt->close();

    if (!$attempt_info) {
        set_alert(get_text('alert_attempt_not_found_or_no_permission'), "danger"); // ใช้ get_text()
        header("Location: /INTEQC_GLOBAL_ASSESMENT/user"); // redirect ไปยัง dashboard
        exit();
    }

    if ($attempt_info['is_completed'] == 0) {
        set_alert(get_text('alert_test_not_completed_yet'), "warning"); // ใช้ get_text()
        header("Location: /INTEQC_GLOBAL_ASSESMENT/user/test?test_id=" . $attempt_info['test_id']); // Redirect กลับไปทำต่อ
        exit();
    }

    $stmt_test = $conn->prepare("SELECT test_name, description, duration_minutes, show_result_immediately, min_passing_score FROM tests WHERE test_id = ?");
    $stmt_test->bind_param("i", $attempt_info['test_id']);
    $stmt_test->execute();
    $test_info = $stmt_test->get_result()->fetch_assoc();
    $stmt_test->close();

    if (!$test_info) {
        set_alert(get_text('alert_test_info_not_found'), "danger"); // ใช้ get_text()
        header("Location: /INTEQC_GLOBAL_ASSESMENT/user"); // redirect ไปยัง dashboard
        exit();
    }

    $stmt_qa = $conn->prepare("
SELECT
 s.section_name,
 q.question_id,
 q.question_text,
 q.question_type,
 q.score AS question_max_score,
 ua.user_answer_text,
 ua.is_correct,
 ua.score_earned
FROM sections s
JOIN questions q ON s.section_id = q.section_id
LEFT JOIN user_answers ua ON q.question_id = ua.question_id AND ua.attempt_id = ?
WHERE s.test_id = ?
ORDER BY s.section_order ASC, q.question_order ASC
 ");
    $stmt_qa->bind_param("ii", $attempt_id, $attempt_info['test_id']);
    $stmt_qa->execute();
    $result_qa = $stmt_qa->get_result();

    while ($row = $result_qa->fetch_assoc()) {
        $question_id = $row['question_id'];
        $row['options'] = []; // ไม่จำเป็นต้องดึง options ถ้าไม่แสดงรายละเอียด แต่ใส่ไว้เพื่อความสมบูรณ์ของโครงสร้าง

        if ($row['question_type'] == 'multiple_choice' || $row['question_type'] == 'true_false') {
            $options_stmt = $conn->prepare("SELECT option_id, option_text, is_correct FROM question_options WHERE question_id = ? ORDER BY option_id ASC");
            $options_stmt->bind_param("i", $question_id);
            $options_stmt->execute();
            $options_result = $options_stmt->get_result();
            while ($opt_row = $options_result->fetch_assoc()) {
                $row['options'][] = $opt_row;
            }
            $options_stmt->close();
        }

        $total_max_score += $row['question_max_score'];
        if ($row['score_earned'] !== NULL && $row['question_max_score'] > 0) {
            $total_score_earned += $row['score_earned'];
        }
        $questions_and_answers[] = $row;
    }
    $stmt_qa->close();
} catch (Exception $e) {
    set_alert(get_text('alert_load_result_error') . ": " . $e->getMessage(), "danger"); // ใช้ get_text()
    header("Location: /INTEQC_GLOBAL_ASSESMENT/user/");
    exit();
}

// Logic สำหรับตรวจสอบว่ามีคำถามอัตนัยที่ยังไม่ถูกตรวจหรือไม่
$has_unchecked_short_answer = false;
foreach ($questions_and_answers as $qa) {
    if ($qa['question_type'] == 'short_answer' && $qa['is_correct'] === NULL) {
        $has_unchecked_short_answer = true;
        break; // พบคำถามอัตนัยที่ยังไม่ถูกตรวจอย่างน้อยหนึ่งข้อ
    }
}

// 💡 คำนวณคะแนนเป็นเปอร์เซ็นต์และตรวจสอบสถานะผ่าน/ไม่ผ่าน
if ($total_max_score > 0) {
    $user_percentage_score = ($total_score_earned / $total_max_score) * 100;
    if ($user_percentage_score >= $test_info['min_passing_score']) {
        $pass_fail_status = 'passed';
    } else {
        $pass_fail_status = 'failed';
    }
} else {
    $user_percentage_score = 0;
    $pass_fail_status = 'N/A'; // Not Applicable
}

?>

<?php echo get_alert(); ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">



<div class="container-fluid w-80-custom py-4">
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
        <h2 class="mb-0 text-primary-custom fs-4 fs-md-2">
            <?php echo get_text('test_result'); ?>: <?php echo htmlspecialchars($test_info['test_name']); ?>
        </h2>
        <div class="header-buttons-container d-flex justify-content-center justify-content-md-end">
            <a href="/INTEQC_GLOBAL_ASSESMENT/user/history" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left "></i> <?php echo get_text('back_to_test_history'); ?>
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary-custom text-white">
            <h4 class="mb-0"><?php echo get_text('test_summary'); ?></h4>
        </div>
        <div class="card-body">
            <p><strong><?php echo get_text('test_name_label'); ?>:</strong> <?php echo htmlspecialchars($test_info['test_name']); ?></p>
            <p><strong><?php echo get_text('description_label'); ?>:</strong> <?php echo nl2br(htmlspecialchars($test_info['description'])); ?></p>
            <p><strong><?php echo get_text('start_time_label'); ?>:</strong> <?php echo htmlspecialchars($attempt_info['start_time']); ?></p>
            <p><strong><?php echo get_text('end_time_label'); ?>:</strong> <?php echo htmlspecialchars($attempt_info['end_time'] ?? get_text('not_applicable_abbr')); ?></p>
            <p><strong><?php echo get_text('time_spent_label'); ?>:</strong> <?php echo formatTimeSpent($attempt_info['time_spent_seconds']); ?></p>
            <p class="fs-5">
                <strong><?php echo get_text('total_score_label'); ?>:</strong>
                <?php
                if ($test_info['show_result_immediately']) {
                    // ถ้า show_result_immediately เป็น true ให้แสดงคะแนนทันที ไม่ว่าจะมีคำถามอัตนัยค้างตรวจหรือไม่
                    echo "<span class='badge bg-success'>" . number_format($total_score_earned, 2) . " / " . number_format($total_max_score, 2) . "</span>";
                } elseif ($has_unchecked_short_answer) {
                    // ถ้าไม่แสดงผลทันที และมีคำถามอัตนัยค้างตรวจ
                    echo "<span class='badge bg-warning'>" . get_text('status_pending_review_short_answer') . "</span>";
                } else {
                    // ถ้าไม่แสดงผลทันที และไม่มีคำถามอัตนัยค้างตรวจ (หมายถึงตรวจหมดแล้วแต่ซ่อนผล)
                    echo "<span class='badge bg-info'>" . get_text('results_not_immediate') . "</span>";
                }
                ?>
            </p>
            <p class="fs-5">
                <strong><?php echo get_text('test_status_label'); ?>:</strong>
                <?php
                if ($test_info['show_result_immediately']) {
                    // ถ้า show_result_immediately เป็น true ให้แสดงสถานะผ่าน/ไม่ผ่านทันที ไม่ว่าจะมีคำถามอัตนัยค้างตรวจหรือไม่
                    if ($pass_fail_status == 'passed') {
                        echo "<span class='badge bg-success'><i class='fas fa-check-circle me-2'></i>" . get_text('test_status_passed') . "</span>";
                    } elseif ($pass_fail_status == 'failed') {
                        echo "<span class='badge bg-danger'><i class='fas fa-times-circle me-2'></i>" . get_text('test_status_failed') . "</span>";
                    } else {
                        // กรณีที่สถานะไม่สามารถระบุได้ (เช่น ไม่มีคำถาม) แต่ show_result_immediately เป็น true
                        echo "<span class='badge bg-secondary'>" . get_text('status_not_available') . "</span>";
                    }
                } elseif ($has_unchecked_short_answer) {
                    // ถ้าไม่แสดงผลทันที และมีคำถามอัตนัยค้างตรวจ
                    echo "<span class='badge bg-warning '>" . get_text('status_pending_review_short_answer') . "</span>";
                } else {
                    // ถ้าไม่แสดงผลทันที และไม่มีคำถามอัตนัยค้างตรวจ (หมายถึงตรวจหมดแล้วแต่ซ่อนผล)
                    echo "<span class='badge bg-info'>" . get_text('results_not_immediate') . "</span>";
                }
                ?>
            </p>
        </div>
    </div>

    <?php if ($has_unchecked_short_answer && !$test_info['show_result_immediately']): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i> <?php echo get_text('alert_results_pending_review'); ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>