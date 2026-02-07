<?php
// ไฟล์นี้จะทำหน้าที่เป็นหน้าแสดงรายงานผลภาพรวม
require_once __DIR__ . '/../../includes/header.php';

// ตั้งค่าหัวข้อหน้า
$page_title = get_text('page_title_view_reports');

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
require_login();

if (!has_role('admin') && !has_role('super_user') && !has_role('editor')) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/login");
    exit();
}

// --- NEW/MODIFIED: Handle all filter parameters for reports ---
$test_id_filter = $_POST['test_id'] ?? null; // รับ test_id จาก POST เพื่อกรอง
$search_query = $_POST['search_query'] ?? ''; // รับ search_query จาก POST
$filter_status = $_POST['filter_status'] ?? '-1'; // รับ filter_status จาก POST (-1: All, 0: In Progress, 1: Completed)
$filter_roles = $_POST['filter_roles'] ?? '-1'; // รับ filter_roles จาก POST (-1: All, 1: Admin, 2: User)
$pass_fail_filter = $_POST['pass_fail_filter'] ?? '-1'; // NEW: รับ filter สถานะผ่าน/ไม่ผ่าน

$tests = []; // สำหรับ dropdown เลือกแบบทดสอบ
$test_name_filtered = get_text("all_tests"); // ชื่อแบบทดสอบที่กรอง

try {
    // ดึงรายการแบบทดสอบทั้งหมดสำหรับ dropdown โดยจัดกลุ่มตาม test_no
    $stmt = $conn->prepare("SELECT test_no, MIN(test_name) as test_name FROM tests GROUP BY test_no ORDER BY test_no");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tests[] = [
            'test_no' => $row['test_no'],
            'test_name' => $row['test_name']
        ];
    }
    $stmt->close();

    // เตรียม SQL query สำหรับดึงข้อมูลการทำแบบทดสอบ
    $sql = "
        SELECT
            uta.attempt_id,
            u.full_name AS user_name,
            r.role_name AS user_role,
            t.test_name,
            uta.start_time,
            uta.end_time,
            uta.total_score,
            uta.is_completed,
            uta.time_spent_seconds,
            t.min_passing_score, -- ดึงคะแนนผ่านขั้นต่ำของแบบทดสอบ
            COALESCE(test_max_scores.max_test_score, 0) AS max_test_score, -- รวมคะแนนสูงสุดที่เป็นไปได้ของแบบทดสอบ
            -- คำนวณคะแนนเป็นเปอร์เซ็นต์
            CASE
                WHEN COALESCE(test_max_scores.max_test_score, 0) > 0 THEN (uta.total_score / test_max_scores.max_test_score * 100)
                ELSE 0 -- กรณีที่คะแนนเต็มเป็น 0 (เช่น ไม่มีคำถาม)
            END AS user_percentage_score
        FROM user_test_attempts uta
        JOIN users u ON uta.user_id = u.user_id
        JOIN tests t ON uta.test_id = t.test_id
        JOIN roles r ON u.role_id = r.role_id
        LEFT JOIN ( -- Subquery เพื่อรวมคะแนนสูงสุดที่เป็นไปได้ของแต่ละแบบทดสอบ
            SELECT
                s.test_id,
                SUM(q.score) AS max_test_score
            FROM sections s
            JOIN questions q ON s.section_id = q.section_id
            GROUP BY s.test_id
        ) AS test_max_scores ON t.test_id = test_max_scores.test_id
    ";

    $where_clauses = [];
    $params = [];
    $types = "";

    // เพิ่มเงื่อนไขการกรองบทบาทเริ่มต้น (Associate หรือ Applicant)
    // หากไม่ต้องการให้ผู้ดูแลระบบเห็นรายงานของ admin/super_user/editor
    $where_clauses[] = "r.role_name IN ('associate', 'applicant')";

    // เพิ่มเงื่อนไขการกรองตาม test_no หากมี
    if (!empty($test_id_filter)) {
        $where_clauses[] = "t.test_no = ?";
        $types .= "i";
        $params[] = (int)$test_id_filter;

        // ดึงชื่อแบบทดสอบที่เลือก
        $stmt = $conn->prepare("SELECT MIN(test_name) as test_name FROM tests WHERE test_no = ?");
        $stmt->bind_param("i", $test_id_filter);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $test_name_filtered = $result->fetch_assoc()['test_name'];
        }
        $stmt->close();
    }

    // เพิ่มเงื่อนไขการค้นหาทั่วไป
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $where_clauses[] = "(u.full_name LIKE ? OR r.role_name LIKE ? OR t.test_name LIKE ? OR uta.attempt_id LIKE ? OR uta.total_score LIKE ? OR uta.start_time LIKE ? OR end_time LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= "sssssss";
    }

    // เพิ่มเงื่อนไขการกรองตามสถานะ (Completed/In Progress)
    // **สำคัญ:** หากมีการเลือก filter_status นี้ จะทำงานแยกกับ pass_fail_filter
    if ($filter_status !== '-1') {
        $where_clauses[] = "uta.is_completed = ?";
        $params[] = (int)$filter_status;
        $types .= "i";
    }

    // เพิ่มเงื่อนไขการกรองตาม Role ID (Associate/Applicant)
    if ($filter_roles == '1') { // Associate
        // ตรวจสอบว่า r.role_name IN ('associate', 'applicant') ถูกเพิ่มแล้วหรือไม่
        $key = array_search("r.role_name IN ('associate', 'applicant')", $where_clauses);
        if ($key !== false) {
            unset($where_clauses[$key]); // ลบเงื่อนไขกว้างๆ ออก
        }
        $where_clauses[] = "r.role_name = 'associate'";
    } elseif ($filter_roles == '2') { // Applicant
        // ตรวจสอบว่า r.role_name IN ('associate', 'applicant') ถูกเพิ่มแล้วหรือไม่
        $key = array_search("r.role_name IN ('associate', 'applicant')", $where_clauses);
        if ($key !== false) {
            unset($where_clauses[$key]); // ลบเงื่อนไขกว้างๆ ออก
        }
        $where_clauses[] = "r.role_name = 'applicant'";
    }

    // NEW & FIXED: เพิ่มเงื่อนไขการกรองตามสถานะผ่าน/ไม่ผ่าน
    if ($pass_fail_filter !== '-1') {
        // เมื่อมีการกรอง Pass/Fail ให้บังคับกรองเฉพาะที่ 'Completed' เท่านั้น
        // ตรวจสอบว่ามีการเพิ่มเงื่อนไข uta.is_completed ก่อนหน้านี้หรือไม่
        $is_completed_already_filtered = false;
        foreach ($where_clauses as $clause) {
            if (strpos($clause, 'uta.is_completed = ?') !== false) {
                $is_completed_already_filtered = true;
                break;
            }
        }

        // หากยังไม่ได้กรองสถานะ Completed ให้เพิ่มเงื่อนไข
        if (!$is_completed_already_filtered || (int)$filter_status === 0) { // ถ้า filter_status เป็น 0 (In Progress) แต่เลือก pass/fail ให้เปลี่ยนเป็น 1
            // ลบ filter_status = 0 ออกถ้ามี เพื่อไม่ให้ขัดแย้งกัน
            if ((int)$filter_status === 0) {
                $index = array_search("uta.is_completed = ?", $where_clauses);
                if ($index !== false) {
                    unset($where_clauses[$index]);
                    // ลบ parameter ที่สอดคล้องกัน
                    $param_index = strpos($types, 'i', $index); // หา index ของ 'i' ที่ถูกใช้กับ uta.is_completed
                    if ($param_index !== false) {
                        array_splice($params, $param_index, 1);
                        $types = substr_replace($types, '', $param_index, 1);
                    }
                }
            }
            $where_clauses[] = "uta.is_completed = 1"; // บังคับให้เป็น completed
        }

        // เงื่อนไขในการกรอง: (user_percentage_score >= min_passing_score)
        // ถ้า $pass_fail_filter เป็น '1' (Passed) แสดงว่า (user_percentage_score >= min_passing_score) ต้องเป็น TRUE (1)
        // ถ้า $pass_fail_filter เป็น '0' (Failed) แสดงว่า (user_percentage_score >= min_passing_score) ต้องเป็น FALSE (0)
        $where_clauses[] = "(CASE WHEN COALESCE(test_max_scores.max_test_score, 0) > 0 THEN (uta.total_score / test_max_scores.max_test_score * 100) >= t.min_passing_score ELSE 0 END) = ?";
        $params[] = (int)$pass_fail_filter;
        $types .= "i";
    }


    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " ORDER BY uta.start_time DESC";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $attempts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Error in view_reports.php: " . $e->getMessage());
    set_alert(get_text("error_loading_reports") . ": " . $e->getMessage(), "danger");
    $attempts = []; // Clear attempts on error
}

?>
<main class="flex-grow-1 container-wide mt-4">
    <?php echo get_alert(); ?>
    <div class="container-wide py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0 text-primary-custom"><?php echo get_text("page_heading_view_reports"); ?></h1>
            <div class="d-flex">
                <a href="/INTEQC_GLOBAL_ASSESMENT/admin" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text("back_to_dashboard"); ?>
                </a>
                <a href="/INTEQC_GLOBAL_ASSESMENT/admin/export-reports?<?php echo http_build_query($_POST); ?>" class="btn btn-success">
                    <i class="fas fa-file-excel me-2"></i> <?php echo get_text("export_excel"); ?>
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <div class="flex-grow-1 ms-2">
                    <form action="/INTEQC_GLOBAL_ASSESMENT/admin/reports" method="POST" class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label for="search_query" class="form-label text-white"><?php echo get_text("search_label"); ?>:</label>
                            <input type="text" class="form-control form-control-sm" id="search_query" name="search_query" placeholder="<?php echo get_text("search_reports_placeholder"); ?>" value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_roles" class="form-label text-white"><?php echo get_text("filter_roles"); ?>:</label>
                            <select class="form-select form-select-sm" id="filter_roles" name="filter_roles">
                                <option value="-1" <?php echo ($filter_roles == -1) ? 'selected' : ''; ?>><?php echo get_text("all_roles"); ?></option>
                                <option value="1" <?php echo ($filter_roles == 1) ? 'selected' : ''; ?>><?php echo get_text("role_associate"); ?></option>
                                <option value="2" <?php echo ($filter_roles == 2) ? 'selected' : ''; ?>><?php echo get_text("role_applicant"); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="filter_status" class="form-label text-white"><?php echo get_text("status_label"); ?>:</label>
                            <select class="form-select form-select-sm" id="filter_status" name="filter_status">
                                <option value="-1" <?php echo ($filter_status == -1) ? 'selected' : ''; ?>><?php echo get_text("all_status"); ?></option>
                                <option value="1" <?php echo ($filter_status == 1) ? 'selected' : ''; ?>><?php echo get_text("status_completed"); ?></option>
                                <option value="0" <?php echo ($filter_status == 0) ? 'selected' : ''; ?>><?php echo get_text("status_in_progress"); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="pass_fail_filter" class="form-label text-white"><?php echo get_text("filter_pass_fail"); ?>:</label>
                            <select class="form-select form-select-sm" id="pass_fail_filter" name="pass_fail_filter">
                                <option value="-1" <?php echo ($pass_fail_filter == -1) ? 'selected' : ''; ?>><?php echo get_text("all_pass_fail_status"); ?></option>
                                <option value="1" <?php echo ($pass_fail_filter == 1) ? 'selected' : ''; ?>><?php echo get_text("status_passed"); ?></option>
                                <option value="0" <?php echo ($pass_fail_filter == 0) ? 'selected' : ''; ?>><?php echo get_text("status_failed"); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="testFilter" class="form-label text-white"><?php echo get_text("filter_by_test"); ?></label>
                            <select id="testFilter" name="test_id" class="form-select form-select-sm">
                                <option value=""><?php echo get_text("all_tests"); ?></option>
                                <?php foreach ($tests as $test): ?>
                                    <option value="<?php echo htmlspecialchars($test['test_no']); ?>"
                                        <?php echo ($test_id_filter == $test['test_no']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($test['test_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2 d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-sm"><?php echo get_text("apply_filter"); ?></button>
                            <button type="button" class="btn btn-primary-custom btn-sm" id="resetFilterBtn">
                                <?php echo get_text("reset_filter"); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($attempts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="bg-light">
                                <tr>
                                    <th><?php echo get_text("table_header_number"); ?></th>
                                    <th><?php echo get_text("table_header_examinee_name"); ?></th>
                                    <th><?php echo get_text("table_header_user_role"); ?></th>
                                    <th><?php echo get_text("table_header_test_name"); ?></th>
                                    <th><?php echo get_text("table_header_start_date"); ?></th>
                                    <th><?php echo get_text("table_header_end_date"); ?></th>
                                    <th><?php echo get_text("table_header_total_score"); ?></th>
                                    <th><?php echo get_text("table_header_status"); ?></th>
                                    <th><?php echo get_text("table_header_actions"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; ?>
                                <?php foreach ($attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($attempt['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['user_role']); ?></td>
                                        <td><?php echo htmlspecialchars(mb_substr($attempt['test_name'], 0, 40, 'UTF-8') . '...'); ?></td>
                                        <td><?php echo htmlspecialchars(thai_datetime_format($attempt['start_time'])); ?></td>
                                        <td>
                                            <?php
                                            echo $attempt['end_time'] ? htmlspecialchars(thai_datetime_format($attempt['end_time'])) : '<span class="text-muted">' . get_text("status_not_completed") . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(number_format($attempt['total_score'], 2)); ?></td>
                                        <td>
                                            <?php
                                            // แสดงสถานะ Completed/In Progress
                                            if ($attempt['is_completed']) {
                                                echo '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> ' . get_text("status_completed") . '</span>';
                                                // แสดงสถานะ Passed/Failed เพิ่มเติมเฉพาะเมื่อ Completed
                                                if ($attempt['max_test_score'] > 0 && $attempt['total_score'] !== null) { // ตรวจสอบไม่ให้หารด้วยศูนย์
                                                    $percentage_score = ($attempt['total_score'] / $attempt['max_test_score']) * 100;
                                                    if ($percentage_score >= $attempt['min_passing_score']) {
                                                        echo '<br><span class="badge bg-primary mt-1"><i class="fas fa-medal me-1"></i> ' . get_text("status_passed") . '</span>';
                                                    } else {
                                                        echo '<br><span class="badge bg-danger mt-1"><i class="fas fa-times-circle me-1"></i> ' . get_text("status_failed") . '</span>';
                                                    }
                                                }
                                            } else {
                                                echo '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> ' . get_text("status_in_progress") . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <form action="/INTEQC_GLOBAL_ASSESMENT/admin/report" method="POST" style="display: inline;">
                                                <input type="hidden" name="attempt_id" value="<?php echo htmlspecialchars($attempt['attempt_id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-info text-white" title="<?php echo get_text("view_details_button"); ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i> <?php echo get_text("no_attempts_found"); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const resetFilterBtn = document.getElementById('resetFilterBtn');
        if (resetFilterBtn) {
            resetFilterBtn.addEventListener('click', function() {
                window.location.href = 'reports'; // Redirect to the page without any GET parameters
            });
        }
    });
</script>