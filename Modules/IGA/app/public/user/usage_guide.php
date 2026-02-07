<?php
// ไฟล์นี้สำหรับแสดงคำแนะนำการใช้งานหลังผู้ใช้เข้าสู่ระบบ

// กำหนดพาธสำหรับ error log (ควรอยู่นอก public_html หรือ web root เพื่อความปลอดภัย)
// สมมติว่าไฟล์ logs จะอยู่ในโฟลเดอร์ 'logs' ที่อยู่ระดับเดียวกับ 'public' และ 'includes'

require_once __DIR__ . '/../../includes/header.php'; // header.php จะรวม db_connect.php
// ตั้งค่าการรายงานข้อผิดพลาดและการบันทึก
ini_set('display_errors', 0); // ไม่แสดง error บนหน้าเว็บจริงเพื่อความปลอดภัย
ini_set('log_errors', 1);     // เปิดใช้งานการบันทึก error
ini_set('error_log', LOG_FILE); // กำหนดไฟล์สำหรับบันทึก error

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $log_message = sprintf(
        "[%s] PHP Error: [%d] %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    error_log($log_message, 3, LOG_FILE);
    if ($errno === E_USER_ERROR || $errno === E_RECOVERABLE_ERROR || $errno === E_PARSE) { /* handle */
    }
    return true;
});

set_exception_handler(function (Throwable $exception) {
    $log_message = sprintf(
        "[%s] Uncaught Exception: %s in %s on line %d\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    );
    error_log($log_message, 3, LOG_FILE);
});



$page_title = get_text('page_title_usage_guide');

require_login(); // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้ว
// บันทึก log เมื่อผู้ใช้เข้าถึงหน้าคู่มือ
error_log(sprintf("[%s] User ID %d accessed usage guide.\n", date('Y-m-d H:i:s'), $_SESSION['user_id'] ?? 0), 3, LOG_FILE);

// ไม่จำเป็นต้องใช้ $_SESSION['has_seen_guide'] อีกต่อไป เนื่องจากจะบังคับให้ดูทุกครั้ง

?>

<div class="container mt-5">
    <div class="card shadow-lg p-4">
        <div class="card-body">
            <h1 class="card-title text-center mb-4"><i class="fas fa-info-circle me-2"></i> <?php echo get_text('welcome_to_test_system'); ?></h1>
            <p class="card-text lead text-center"><?php echo get_text('usage_guide_intro'); ?></p>

            <hr class="my-4">

            <h2 class="mb-3 text-primary-custom"><i class="fas fa-clipboard-check me-2"></i> <?php echo get_text('test_selection_important'); ?></h2>
            <p><?php echo get_text('test_selection_explanation_part1'); ?></p>
            <ul>
                <li><strong><?php echo get_text('once_selected'); ?>:</strong> <?php echo get_text('cannot_change_test_language_warning'); ?></li>
                <li><strong><?php echo get_text('why_this_rule'); ?>:</strong> <?php echo get_text('data_integrity_explanation'); ?></li>
            </ul>
            <p><?php echo get_text('test_selection_explanation_part2'); ?></p>

            <hr class="my-4">

            <h2 class="mb-3 text-primary-custom"><i class="fas fa-question-circle me-2"></i> <?php echo get_text('how_to_answer_questions'); ?></h2>
            <p><?php echo get_text('diverse_question_types'); ?></p>
            <dl class="row">
                <dt class="col-sm-3"><i class="far fa-check-square me-2"></i> <?php echo get_text('yes_no_questions'); ?></dt>
                <dd class="col-sm-9"><?php echo get_text('yes_no_explanation'); ?></dd>

                <dt class="col-sm-3"><i class="fas fa-list-ul me-2"></i> <?php echo get_text('multiple_choice_questions'); ?></dt>
                <dd class="col-sm-9"><?php echo get_text('multiple_choice_explanation'); ?></dd>

                <dt class="col-sm-3"><i class="fas fa-keyboard me-2"></i> <?php echo get_text('short_answer_questions'); ?></dt>
                <dd class="col-sm-9"><?php echo get_text('short_answer_explanation'); ?></dd>
            </dl>

            <hr class="my-4">

            <h2 class="mb-3 text-primary-custom"><i class="fas fa-hourglass-half me-2"></i> <?php echo get_text('understanding_test_time'); ?></h2>
            <p><?php echo get_text('time_management_intro'); ?></p>
            <ul>
                <li><strong><?php echo get_text('overall_test_timer'); ?>:</strong> <?php echo get_text('overall_test_timer_explanation'); ?></li>
                <li><strong><?php echo get_text('section_timer'); ?>:</strong> <?php echo get_text('section_timer_explanation'); ?></li>
                <li><strong><?php echo get_text('saving_progress'); ?>:</strong> <?php echo get_text('saving_progress_explanation'); ?></li>
            </ul>
            <p><?php echo get_text('time_tip'); ?></p>

            <div class="text-center mt-5">
                <a href="/user" class="btn btn-success btn-lg">
                    <i class="fas fa-check-circle me-2"></i> <?php echo get_text('i_understand_start_now'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>