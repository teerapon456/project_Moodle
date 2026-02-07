<?php
// /admin/report-individual.php

require_once __DIR__ . '/../../includes/header.php';

$page_title = get_text('page_heading_individual_report');

require_login();

// ให้เหมือนไฟล์อื่น ๆ
$is_Super_user_Recruitment = has_role('Super_user_Recruitment');

if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !$is_Super_user_Recruitment) {
    // ... (ส่วนเดิม: ตรวจสอบสิทธิ์)
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

// -----------------------------------------
// 💡 การจัดการ user_id ใหม่: ดึงค่าและเก็บใน Session
// -----------------------------------------

// 1. ตรวจสอบค่า user_id จาก POST หรือ GET
$new_user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
$session_key = 'report_user_id'; // กำหนด Session Key

if (!empty($new_user_id)) {
    // ถ้ามี user_id ใหม่ส่งมา (จาก POST/GET) ให้อัปเดต Session
    $_SESSION[$session_key] = $new_user_id;
    $user_id = $new_user_id;

    // 💡 สำคัญ: Redirect ตัวเองเพื่อล้าง POST/GET parameter และซ่อนค่า
    // Redirect ไปที่ URL ที่ไม่มี Query String (ถ้าไม่มี lang) หรือมีแต่ lang
    if (!isset($_GET['lang'])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
} else {
    // 2. ถ้าไม่มีค่าใหม่ส่งมา ให้ดึงจาก Session
    $user_id = $_SESSION[$session_key] ?? null;
}

// 3. ตรวจสอบ user_id สุดท้าย
if (empty($user_id)) {
    // หากไม่มี user_id เลย ไม่ว่าจะมาจาก POST, GET, หรือ SESSION
    set_alert(get_text('error_missing_user_id'), 'danger');
    // 💡 ลบค่าใน Session ออก ก่อน Redirect
    unset($_SESSION[$session_key]);
    header("Location: /admin/reports-individual");
    exit();
}

try {
    // ดึงข้อมูลผู้ใช้
    $stmt = $conn->prepare("
    SELECT u.*, r.role_name,level_code, u.role_id, u.emplevel_id,o.OrgCode,u.OrgUnitTypeName
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.role_id
    LEFT JOIN emplevelcode lv ON lv.level_id = u.emplevel_id
    LEFT JOIN iga_orgunit o ON o.OrgID = u.OrgID
    WHERE u.user_id = ?
    LIMIT 1
  ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        set_alert(get_text('error_user_not_found'), 'danger');
        header("Location: /admin/reports-individual");
        exit();
    }

    // ถ้าเป็น Super_user_Recruitment ต้องเช็คซ้ำว่า user นี้เป็น applicant / role_id=5
    if ($is_Super_user_Recruitment) {
        if (!($user['role_name'] === 'applicant' || (int)$user['role_id'] === 5)) {
            set_alert(get_text('alert_no_admin_permission'), 'danger');
            header("Location: /admin/reports-individual");
            exit();
        }
    }

    // -----------------------------------------
    // ดึงรายการ test ที่มี attempt ล่าสุดของ user
    // -----------------------------------------

    // ดึง level_id และ role_id ของ user มาใช้ (เผื่อในอนาคตต้องการใช้ แต่ไม่ได้ใช้ในการกรองแล้ว)
    $user_role_id = (int)($user['role_id'] ?? 0);
    $user_level_id = (int)($user['emplevel_id'] ?? 0);

    // ใช้การเตรียมคำสั่ง SQL แบบปลอดภัยสำหรับ user_id เท่านั้น
    $sql = "
    SELECT
      t.test_id,
      t.test_no,
      t.test_name,
      t.min_passing_score,
      t.role_id AS test_role_id,
      
      uta_last.attempt_id,
      uta_last.start_time,
      uta_last.end_time,
      uta_last.total_score,
      uta_last.is_completed,
      uta_last.time_spent_seconds,

      COALESCE(test_max_scores.max_test_score, 0) AS max_test_score,
      COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) AS max_applicable_score,

      CASE
        WHEN COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) > 0
        THEN (uta_last.total_score / COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) * 100)
        ELSE 0
      END AS user_percentage_score,

      COALESCE(critical_failures.has_critical_fail, 0) AS has_critical_fail
      
    FROM iga_tests t
    
    /* LEFT JOIN iga_user_test_attempts: ดึง Attempt ล่าสุด */
    LEFT JOIN (
      SELECT uta.*
      FROM iga_user_test_attempts uta
      JOIN (
        SELECT test_id, MAX(start_time) AS max_start_time
        FROM iga_user_test_attempts
        WHERE user_id = ?
        GROUP BY test_id
      ) latest ON latest.test_id = uta.test_id
          AND latest.max_start_time = uta.start_time
      WHERE uta.user_id = ?
    ) AS uta_last ON uta_last.test_id = t.test_id

    LEFT JOIN (
      SELECT s.test_id, SUM(q.score) AS max_test_score
      FROM iga_sections s
      JOIN iga_questions q ON s.section_id = q.section_id
      GROUP BY s.test_id
    ) AS test_max_scores ON t.test_id = test_max_scores.test_id

    LEFT JOIN (
      SELECT uaq.attempt_id, SUM(q.score) AS max_attempt_score
      FROM iga_user_attempt_questions uaq
      JOIN iga_questions q ON q.question_id = uaq.question_id
      GROUP BY uaq.attempt_id
    ) AS ams ON uta_last.attempt_id = ams.attempt_id

    LEFT JOIN (
      SELECT ua.attempt_id,
          MAX(CASE WHEN q.is_critical = 1 AND ua.is_correct = 0 THEN 1 ELSE 0 END) AS has_critical_fail
      FROM iga_user_answers ua
      JOIN iga_questions q ON ua.question_id = q.question_id
      GROUP BY ua.attempt_id
    ) AS critical_failures ON uta_last.attempt_id = critical_failures.attempt_id

    /* 💡 WHERE clause: แสดงเฉพาะ Test ที่มี Attempt ID เท่านั้น */
    WHERE uta_last.attempt_id IS NOT NULL
    
    ORDER BY t.test_no, t.test_name
    ";

    // Bind parameters: user_id, user_id (มีแค่ 2 ตัวสำหรับ uta_last)
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user_id, $user_id);
    $stmt->execute();
    $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Error in report-individual.php: " . $e->getMessage());
    set_alert(get_text("error_loading_reports") . ": " . $e->getMessage(), "danger");
    header("Location: /admin/reports-individual");
    exit();
}

function display_or_dash($value)
{
    return ($value !== null && $value !== '')
        ? htmlspecialchars($value)
        : '<span class="text-muted">-</span>';
}
?>
<main class="flex-grow-1 container-wide mt-4">
    <?php echo get_alert(); ?>
    <div class="container-wide py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0 text-primary-custom">
                <?php echo get_text("page_heading_individual_report"); ?>
            </h1>
            <div class="d-flex">
                <button type="button" onclick="history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text("button_back"); ?>
                </button>

                <form action="/admin/export_user_history_pdf.php" method="POST" style="display:inline;" target="_blank" class="ms-2">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    <button type="submit" class="btn btn-danger" title="<?php echo get_text("export_pdf_button") ?: 'ส่งออกเป็น PDF'; ?>">
                        <i class="fas fa-file-pdf me-1"></i> <?php echo get_text("export_pdf_button") ?: 'Export PDF'; ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary-custom text-white">
                <i class="fas fa-user-circle me-2"></i>
                <?php echo get_text("examinee_info_heading"); ?>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <strong><?php echo get_text("full_name"); ?>:</strong>
                        <div><?php echo display_or_dash($user['full_name'] ?? ''); ?></div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <strong><?php echo get_text("email"); ?>:</strong>
                        <div><?php echo display_or_dash($user['email'] ?? ''); ?></div>
                    </div>
                    <?php
                    // กำหนดค่า Role Name ที่ดึงมา
                    $role_name_key = $user['role_name'] ?? '';

                    // สร้าง Key ที่ใช้ในการแปล เช่น 'role_applicant' หรือ 'role_admin'
                    $translation_key = 'role_' . $role_name_key;
                    ?>
                    <div class="col-md-3 mb-2">
                        <strong><?php echo get_text("table_header_role"); ?>:</strong>
                        <div><?php echo display_or_dash(get_text($translation_key)); ?></div>


                    </div>
                    <div class="col-md-3 mb-2">
                        <strong><?php echo get_text("label_emplevel"); ?>:</strong>
                        <div><?php echo display_or_dash($user['level_code'] ?? ''); ?></div>
                    </div>
                </div>

                <div class="row">
                    <?php
                    $is_role_4_or_associate = ((int)($user['role_id'] ?? 0) === 4 || ($user['role_name'] ?? '') === 'associate');

                    if ($is_role_4_or_associate):
                    ?>
                        <div class="col-md-3 mb-2">
                            <strong><?php echo get_text("EmpType"); ?>:</strong>
                            <div><?php echo display_or_dash($user['EmpType'] ?? ''); ?></div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong><?php echo display_or_dash($user['OrgUnitTypeName'] ?? ''); ?>:</strong>
                            <div><?php echo display_or_dash($user['OrgUnitName'] ?? ''); ?></div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong><?php echo get_text("company"); ?>:</strong>
                            <div><?php echo display_or_dash($user['OrgCode'] ?? ''); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-list-check me-2"></i>
                <?php echo get_text("list_of_tests"); ?>
            </div>
            <div class="card-body">
                <?php if (!empty($tests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th><?php echo get_text("table_header_number"); ?></th>
                                    <th><?php echo get_text("table_header_test_name"); ?></th>
                                    <th><?php echo get_text("table_header_status"); ?></th>
                                    <th><?php echo get_text("table_header_total_score"); ?></th>
                                    <th><?php echo get_text("table_header_start_date"); ?></th>
                                    <th><?php echo get_text("table_header_end_date"); ?></th>
                                    <th><?php echo get_text("table_header_actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; ?>
                                <?php foreach ($tests as $t): ?>
                                    <?php
                                    $status_html = '';
                                    $score_text = '-';

                                    // 💡 เนื่องจาก SQL กรองให้แล้ว เราจึงเหลือแค่ Logic ของ Test ที่มี Attempt เท่านั้น
                                    if (!empty($t['attempt_id'])) {
                                        $score_text = number_format((float)$t['total_score'], 2);

                                        if ($t['is_completed']) {
                                            $status_html = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> ' . get_text("status_completed") . '</span>';

                                            $den = (float)($t['max_applicable_score'] ?? 0);
                                            $is_passed = false;
                                            if ($den > 0) {
                                                $percentage_score = ($t['total_score'] / $den) * 100.0;
                                                $is_passed = ($percentage_score >= (float)$t['min_passing_score']
                                                    && (int)$t['has_critical_fail'] === 0);
                                            }

                                            if ($is_passed) {
                                                $status_html .= '<br><span class="badge bg-primary mt-1"><i class="fas fa-medal me-1"></i> ' . get_text("status_passed") . '</span>';
                                            } else {
                                                $status_html .= '<br><span class="badge bg-danger mt-1"><i class="fas fa-times-circle me-1"></i> ' . get_text("status_failed") . '</span>';
                                            }
                                        } else {
                                            $status_html = '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> ' . get_text("status_in_progress") . '</span>';
                                        }
                                    } else {
                                        // Safety check: ไม่ควรเกิดขึ้นแล้ว เพราะ SQL กรองไว้แล้ว
                                        $status_html = '<span class="badge bg-secondary"><i class="fas fa-minus me-1"></i> ' . get_text("status_not_started") . '</span>';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($t['test_name']); ?></td>
                                        <td><?php echo $status_html; ?></td>
                                        <td><?php echo htmlspecialchars($score_text); ?></td>
                                        <td>
                                            <?php
                                            echo !empty($t['start_time'])
                                                ? htmlspecialchars(thai_datetime_format($t['start_time']))
                                                : '<span class="text-muted">-</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            echo !empty($t['end_time'])
                                                ? htmlspecialchars(thai_datetime_format($t['end_time']))
                                                : '<span class="text-muted">-</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($t['attempt_id'])): ?>
                                                <form action="/admin/report" method="POST" style="display:inline;">
                                                    <input type="hidden" name="attempt_id" value="<?php echo htmlspecialchars($t['attempt_id']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-info text-white"
                                                        title="<?php echo get_text("view_details_button"); ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo get_text("no_test_history") ?: "ไม่พบแบบทดสอบที่ผู้ใช้ทำแล้ว"; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>