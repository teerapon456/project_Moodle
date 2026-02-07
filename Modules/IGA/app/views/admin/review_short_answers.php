<?php
// ไฟล์นี้สำหรับผู้ดูแลระบบเพื่อตรวจคำถามอัตนัยที่ผู้ใช้ตอบ
require_once __DIR__ . '/../../includes/header.php';
$page_title = get_text('page_title_review_short_answers');



require_login();
if (!has_role('admin')) {
    set_alert(get_text('no_permission_alert'), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/public/login.php");
    exit();
}

$pending_attempts = [];

try {
    // ดึงรายการการทำแบบทดสอบ (user_test_attempts) ที่มีคำถามอัตนัยที่ยังไม่ถูกตรวจ
    // โดยดึงข้อมูลที่เกี่ยวข้อง: ชื่อผู้ใช้, ชื่อแบบทดสอบ, เวลาที่ทำ
    $stmt = $conn->prepare("
        SELECT
            uta.attempt_id,
            uta.user_id,
            u.full_name AS user_full_name,
            u.username AS user_username,
            t.test_name,
            uta.start_time,
            COUNT(ua.user_answer_id) AS pending_count
        FROM
            user_test_attempts uta
        JOIN
            users u ON uta.user_id = u.user_id
        JOIN
            tests t ON uta.test_id = t.test_id
        JOIN
            user_answers ua ON uta.attempt_id = ua.attempt_id
        JOIN
            questions q ON ua.question_id = q.question_id
        WHERE
            q.question_type = 'short_answer' AND ua.score_earned IS NULL AND uta.is_completed = 1
        GROUP BY
            uta.attempt_id, uta.user_id, u.full_name, u.username, t.test_name, uta.start_time
        ORDER BY
            uta.start_time ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_attempts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    set_alert(sprintf(get_text('error_loading_data'), $e->getMessage()), "danger");
}

?>

<h1 class="mb-4 text-primary-custom"><?php echo get_text('page_heading_review_short_answers'); ?></h1>

<?php echo get_alert(); // แสดงข้อความแจ้งเตือน 
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><?php echo get_text('pending_attempts_list_heading'); ?></h5>
    </div>
    <div class="card-body">
        <?php if (empty($pending_attempts)): ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_pending_short_answers'); ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo get_text('attempt_id_table_header'); ?></th>
                            <th><?php echo get_text('user_table_header'); ?></th>
                            <th><?php echo get_text('test_table_header'); ?></th>
                            <th><?php get_text('start_time_table_header'); ?></th>
                            <th><?php get_text('pending_questions_table_header'); ?></th>
                            <th><?php get_text('action_table_header'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_attempts as $attempt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attempt['attempt_id']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['user_full_name'] ?: $attempt['user_username']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['test_name']); ?></td>
                                <td><?php echo htmlspecialchars(thai_datetime_format($attempt['start_time'])); ?></td>
                                <td><span class="badge bg-warning"><?php echo htmlspecialchars($attempt['pending_count']); ?></span></td>
                                <td>
                                    <form method="POST" action="/INTEQC_GLOBAL_ASSESMENT/admin/review-answer" style="display: inline;">
                                        <input type="hidden" name="attempt_id" value="<?php echo htmlspecialchars($attempt['attempt_id']); ?>">
                                        <button type="submit" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye me-1"></i> <?php echo get_text('review_button'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>