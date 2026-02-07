<?php
// ไฟล์นี้จะทำหน้าที่เป็นหน้าแสดงรายงานผลภาพรวม
require_once __DIR__ . '/../../includes/header.php';

// ===== START: UPDATED FILTER LOGIC (USING POST + SESSION) =====

// 1. ตรวจสอบว่า Session เริ่มทำงานหรือยัง
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$filter_session_key = 'reports_filters'; // ชื่อ Key สำหรับเก็บฟิลเตอร์ใน Session

// 2. จัดการการ Reset Filter (เมื่อกดปุ่ม Reset)
if (isset($_GET['reset'])) {
    unset($_SESSION[$filter_session_key]);
    header("Location: /admin/reports"); // กลับไปหน้าแรกแบบ URL สะอาด
    exit();
}

// 3. จัดการการส่งฟิลเตอร์ (เมื่อกด Apply Filter)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // บันทึกค่าฟิลเตอร์ใหม่ลง Session
    $_SESSION[$filter_session_key] = $_POST;
    // Redirect ไปยังหน้าแรก (GET) เพื่อป้องกันการ submit form ซ้ำเวลากด F5
    header("Location: /admin/reports?page=1");
    exit();
}

// 4. ดึงค่าฟิลเตอร์จาก Session (สำหรับ GET request เช่น การแบ่งหน้า)
$input_source = $_SESSION[$filter_session_key] ?? [];

// ===== END: UPDATED FILTER LOGIC =====


// ตั้งค่าหัวข้อหน้า
$page_title = get_text('page_title_view_reports');

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
require_login();
$is_Super_user_Recruitment = has_role('Super_user_Recruitment');
if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !$is_Super_user_Recruitment) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

// --- ดึงค่าฟิลเตอร์จาก $input_source (ที่มาจาก Session) ---
$test_id_filter   = $input_source['test_id']        ?? null;    // รับ test_id
$search_query     = $input_source['search_query']   ?? '';      // รับ search_query
$filter_status    = $input_source['filter_status']  ?? '-1';    // -1: All, 0: In Progress, 1: Completed
$filter_roles     = $input_source['filter_roles']   ?? '-1';    // -1: All, 1: Associate, 2: Applicant
if ($is_Super_user_Recruitment) {
    $filter_roles = '2'; // บังคับให้เป็น Applicant เสมอ
}
$pass_fail_filter = $input_source['pass_fail_filter'] ?? '-1'; // -1: All, 1: Passed, 0: Failed

// Pagination settings
$items_per_page = 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page   = max(1, $page);
$offset = ($page - 1) * $items_per_page;

$tests = []; // สำหรับ dropdown เลือกแบบทดสอบ
$test_name_filtered = get_text("all_tests");
$total_records = 0;
$total_pages = 1;
$attempts = []; // khởi tạo

try {
    // ดึงรายการแบบทดสอบทั้งหมดสำหรับ dropdown โดยจัดกลุ่มตาม test_no
    $stmt = $conn->prepare("
        SELECT test_no, MIN(test_name) as test_name
        FROM iga_tests
        GROUP BY test_no
        ORDER BY test_no
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tests[] = [
            'test_no'   => $row['test_no'],
            'test_name' => $row['test_name']
        ];
    }
    $stmt->close();

    // ------------------------------------------------------------------
    // SQL ส่วน WHERE (สำหรับใช้ทั้งนับจำนวนและดึงข้อมูล)
    // ------------------------------------------------------------------
    $where_clauses = [];
    $params = [];
    $types  = "";

    // เฉพาะบทบาทผู้เข้าสอบหลัก (Associate / Applicant)
    if ($is_Super_user_Recruitment) {
        $where_clauses[] = "(r.role_name = 'applicant' OR r.role_id = 5)";
    } else {
        $where_clauses[] = "r.role_name IN ('associate','applicant')";
        if ($filter_roles === '1') {
            $where_clauses[] = "r.role_name = 'associate'";
        } else if ($filter_roles === '2') {
            $where_clauses[] = "r.role_name = 'applicant'";
        }
    }

    // กรองตาม test_no
    if (!empty($test_id_filter)) {
        $where_clauses[] = "t.test_no = ?";
        $types  .= "s";
        $params[] = $test_id_filter;

        // (โค้ดดึงชื่อ test_name_filtered ยังคงเดิม)
        $stmt_name = $conn->prepare("SELECT MIN(test_name) as test_name FROM iga_tests WHERE test_no = ?");
        $stmt_name->bind_param("s", $test_id_filter);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        if ($row_name = $result_name->fetch_assoc()) {
            $test_name_filtered = $row_name['test_name'];
        }
        $stmt_name->close();
    }

    // ค้นหาทั่วไป
    if (!empty($search_query)) {
        $search_term = '%' . $search_query . '%';
        $where_clauses[] = "(u.full_name LIKE ? OR r.role_name LIKE ? OR t.test_name LIKE ? OR uta.attempt_id LIKE ? OR uta.total_score LIKE ? OR uta.start_time LIKE ? OR uta.end_time LIKE ?)";
        $params[] = $search_term; $params[] = $search_term; $params[] = $search_term;
        $params[] = $search_term; $params[] = $search_term; $params[] = $search_term;
        $params[] = $search_term;
        $types  .= "sssssss";
    }

    // สถานะ Completed / In Progress / ทั้งหมด
    $is_completed_value = null;
    if ($pass_fail_filter !== '-1') {
        $is_completed_value = 1;
    } else if ($filter_status !== '-1') {
        $is_completed_value = (int)$filter_status;
    }
    if ($is_completed_value !== null) {
        $where_clauses[] = "uta.is_completed = ?";
        $types  .= "i";
        $params[] = $is_completed_value;
    }

    // ฟิลเตอร์ผ่าน/ไม่ผ่าน
    $pass_condition_sql = "
        (
            uta.is_completed = 1
            AND COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) > 0
            AND (uta.total_score / COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) * 100) >= t.min_passing_score
            AND COALESCE(critical_failures.has_critical_fail, 0) = 0
        )";
        
    if ($pass_fail_filter === '1') {
        $where_clauses[] = $pass_condition_sql;
    } elseif ($pass_fail_filter === '0') {
        // ต้องมั่นใจว่า "เสร็จแล้ว" แต่ "ไม่ผ่าน"
        $where_clauses[] = "(uta.is_completed = 1 AND NOT " . $pass_condition_sql . ")";
    }

    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    }

    // ------------------------------------------------------------------
    // SQL นับจำนวน (ปรับปรุงใหม่ให้มีประสิทธิภาพ)
    // ------------------------------------------------------------------
    $count_sql = "
        SELECT COUNT(DISTINCT uta.attempt_id) AS total
        FROM iga_user_test_attempts uta
        JOIN users u    ON uta.user_id = u.user_id
        JOIN iga_tests t    ON uta.test_id = t.test_id
        JOIN roles r    ON u.role_id   = r.role_id
        LEFT JOIN (
            SELECT uaq.attempt_id, SUM(q.score) AS max_attempt_score
            FROM iga_user_attempt_questions uaq
            JOIN iga_questions q ON q.question_id = uaq.question_id
            GROUP BY uaq.attempt_id
        ) AS ams ON uta.attempt_id = ams.attempt_id
        LEFT JOIN (
            SELECT s.test_id, SUM(q.score) AS max_test_score
            FROM iga_sections s
            JOIN iga_questions q ON s.section_id = q.section_id
            GROUP BY s.test_id
        ) AS test_max_scores ON t.test_id = test_max_scores.test_id
        LEFT JOIN (
            SELECT ua.attempt_id,
                   MAX(CASE WHEN q.is_critical = 1 AND ua.is_correct = 0 THEN 1 ELSE 0 END) AS has_critical_fail
            FROM iga_user_answers ua
            JOIN iga_questions q ON ua.question_id = q.question_id
            GROUP BY ua.attempt_id
        ) AS critical_failures ON uta.attempt_id = critical_failures.attempt_id
    " . $where_sql; // ใช้ $where_sql ที่สร้างไว้

    $count_stmt = $conn->prepare($count_sql);
    if ($types) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_records = (int)$count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    $total_pages = $total_records > 0 ? (int)ceil($total_records / $items_per_page) : 1;
    // ปรับ $page ถ้ามันเกิน
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $items_per_page;

    // ------------------------------------------------------------------
    // SQL ดึงข้อมูลหลัก
    // ------------------------------------------------------------------
    if ($total_records > 0) {
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
                t.min_passing_score,
                COALESCE(test_max_scores.max_test_score, 0) AS max_test_score,
                COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) AS max_applicable_score,
                CASE
                    WHEN COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) > 0
                    THEN (uta.total_score / COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) * 100)
                    ELSE 0
                END AS user_percentage_score,
                COALESCE(critical_failures.has_critical_fail, 0) AS has_critical_fail
            FROM iga_user_test_attempts uta
            JOIN users u    ON uta.user_id = u.user_id
            JOIN iga_tests t    ON uta.test_id = t.test_id
            JOIN roles r    ON u.role_id   = r.role_id
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
            ) AS ams ON uta.attempt_id = ams.attempt_id
            LEFT JOIN (
                SELECT ua.attempt_id,
                       MAX(CASE WHEN q.is_critical = 1 AND ua.is_correct = 0 THEN 1 ELSE 0 END) AS has_critical_fail
                FROM iga_user_answers ua
                JOIN iga_questions q ON ua.question_id = q.question_id
                GROUP BY ua.attempt_id
            ) AS critical_failures ON uta.attempt_id = critical_failures.attempt_id
        " . $where_sql; // ใช้ $where_sql เดียวกัน

        // group by attempt
        $sql .= " GROUP BY uta.attempt_id";

        // ใส่ ORDER BY + LIMIT/OFFSET
        $sql .= " ORDER BY uta.start_time DESC LIMIT ? OFFSET ?";
        $types  .= "ii"; // เพิ่ม ii สำหรับ LIMIT, OFFSET
        $params[] = $items_per_page;
        $params[] = $offset;

        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $attempts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

} catch (Exception $e) {
    error_log("Error in view_reports.php: " . $e->getMessage());
    set_alert(get_text("error_loading_reports") . ": " . $e->getMessage(), "danger");
    $attempts = [];
}

// สร้าง URL Query String สำหรับ **Export** (ยังคงใช้ GET ได้)
$filter_params = [
    'test_id'        => $test_id_filter,
    'search_query'   => $search_query,
    'filter_status'  => $filter_status,
    'filter_roles'   => $filter_roles,
    'pass_fail_filter' => $pass_fail_filter,
];

// $query_string สำหรับ pagination ไม่จำเป็นต้องมีฟิลเตอร์แล้ว
$query_string = '';

?>
<div class="container-fluid w-80-custom py-1">
    <?php echo get_alert(); ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0 text-primary-custom"><?php echo get_text("page_heading_view_reports"); ?></h1>
            <div class="d-flex">
                <a href="/admin/export-reports?<?php echo http_build_query($filter_params); ?>" class="btn btn-success me-2">
                    <i class="fas fa-file-excel me-2"></i> <?php echo get_text("export_excel"); ?>
                </a>
                <a href="/admin" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text("back_to_dashboard"); ?>
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <div class="flex-grow-1 ms-2">
                    <form action="/admin/reports" method="POST" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label for="search_query" class="form-label text-white"><?php echo get_text("search_label"); ?>:</label>
                            <input type="text" class="form-control form-control" id="search_query" name="search_query" placeholder="<?php echo get_text("search_reports_placeholder"); ?>" value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="filter_roles" class="form-label text-white"><?php echo get_text("filter_roles"); ?>:</label>

                            <?php if ($is_Super_user_Recruitment): ?>
                                <select class="form-select form-select" id="filter_roles" name="filter_roles" disabled>
                                    <option value="2" selected><?php echo get_text("role_applicant"); ?></option>
                                </select>
                                <input type="hidden" name="filter_roles" value="2">
                            <?php else: ?>
                                <select class="form-select form-select" id="filter_roles" name="filter_roles">
                                    <option value="-1" <?php echo ($filter_roles == -1) ? 'selected' : ''; ?>><?php echo get_text("all_roles"); ?></option>
                                    <option value="1"  <?php echo ($filter_roles == 1) ? 'selected' : ''; ?>><?php echo get_text("role_associate"); ?></option>
                                    <option value="2"  <?php echo ($filter_roles == 2) ? 'selected' : ''; ?>><?php echo get_text("role_applicant"); ?></option>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <label for="filter_status" class="form-label text-white"><?php echo get_text("status_label"); ?>:</label>
                            <select class="form-select form-select" id="filter_status" name="filter_status">
                                <option value="-1" <?php echo ($filter_status == -1) ? 'selected' : ''; ?>><?php echo get_text("all_status"); ?></option>
                                <option value="1"  <?php echo ($filter_status == 1) ? 'selected' : ''; ?>><?php echo get_text("status_completed"); ?></option>
                                <option value="0"  <?php echo ($filter_status == 0) ? 'selected' : ''; ?>><?php echo get_text("status_in_progress"); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="pass_fail_filter" class="form-label text-white"><?php echo get_text("filter_pass_fail"); ?>:</label>
                            <select class="form-select form-select" id="pass_fail_filter" name="pass_fail_filter">
                                <option value="-1" <?php echo ($pass_fail_filter == -1) ? 'selected' : ''; ?>><?php echo get_text("all_pass_fail_status"); ?></option>
                                <option value="1"  <?php echo ($pass_fail_filter == 1) ? 'selected' : ''; ?>><?php echo get_text("status_passed"); ?></option>
                                <option value="0"  <?php echo ($pass_fail_filter == 0) ? 'selected' : ''; ?>><?php echo get_text("status_failed"); ?></option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="testFilter" class="form-label text-white"><?php echo get_text("filter_by_test"); ?></label>
                            <select id="testFilter" name="test_id" class="form-select form-select">
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
                        <table id="reportsTable" class="table table-hover table-striped">
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
                                <?php $i = $offset + 1; ?>
                                <?php foreach ($attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($attempt['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['user_role']); ?></td>
                                        <td><?php echo htmlspecialchars(mb_substr($attempt['test_name'], 0, 40, 'UTF-8') . '...'); ?></td>
                                        <td><?php echo htmlspecialchars(thai_datetime_format($attempt['start_time'])); ?></td>
                                        <td>
                                            <?php
                                            echo $attempt['end_time']
                                                ? htmlspecialchars(thai_datetime_format($attempt['end_time']))
                                                : '<span class="text-muted">' . get_text("status_not_completed") . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(number_format($attempt['total_score'], 2)); ?></td>
                                        <td>
                                            <?php
                                            if ($attempt['is_completed']) {
                                                echo '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> ' . get_text("status_completed") . '</span>';

                                                $den = (float)($attempt['max_applicable_score'] ?? 0);
                                                $is_passed = false;
                                                if ($den > 0) {
                                                    $percentage_score = ($attempt['total_score'] / $den) * 100.0;
                                                    $is_passed = (
                                                        $percentage_score >= (float)$attempt['min_passing_score']
                                                        && (int)$attempt['has_critical_fail'] === 0
                                                    );
                                                }

                                                if ($is_passed) {
                                                    echo '<br><span class="badge bg-primary mt-1"><i class="fas fa-medal me-1"></i> ' . get_text("status_passed") . '</span>';
                                                } else {
                                                    echo '<br><span class="badge bg-danger mt-1"><i class="fas fa-times-circle me-1"></i> ' . get_text("status_failed") . '</span>';
                                                }
                                            } else {
                                                echo '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> ' . get_text("status_in_progress") . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <form action="/admin/report" method="POST" style="display: inline;">
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

                    <?php
                    $start_item = $total_records > 0 ? $offset + 1 : 0;
                    $end_item   = $total_records > 0 ? min($offset + count($attempts), $total_records) : 0;
                    ?>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3">
                        <div class="text-muted small mb-2 mb-md-0">
                            <?php if ($total_records > 0): ?>
                                <?php echo get_text("show", "Show")?> <?php echo $start_item; ?>–<?php echo $end_item; ?> <?php echo get_text("of", "of")?> <?php echo $total_records; ?> <?php echo get_text("list", "items")?>
                            <?php else: ?>
                                <?php echo get_text("no_data_found", "No data found")?>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($total_pages) && $total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-0">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                           href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) . '#reportsTable'; ?>"
                                           <?php echo ($page <= 1) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php
                                    $visible_pages = 5;
                                    $start_page = max(1, $page - floor($visible_pages / 2));
                                    $end_page   = min($total_pages, $start_page + $visible_pages - 1);
                                    if ($end_page - $start_page + 1 < $visible_pages) {
                                        $start_page = max(1, $end_page - $visible_pages + 1);
                                    }
                                    if ($start_page > 1): ?>
                                        <li class="page-item <?php echo ($page == 1) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=1#reportsTable">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo ((int)$i === (int)$page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>#reportsTable">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php
                                    if ($end_page < $total_pages):
                                        if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item <?php echo ($page == $total_pages) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $total_pages; ?>#reportsTable">
                                                <?php echo $total_pages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                           href="<?php echo ($page >= $total_pages) ? '#' : '?page=' . ($page + 1) . '#reportsTable'; ?>"
                                           <?php echo ($page >= $total_pages) ? 'tabindex="-1" aria-disabled="true"' : ''; ?>>
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        <i class="fas fa-info-circle me-2"></i> <?php echo get_text("no_attempts_found"); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resetFilterBtn = document.getElementById('resetFilterBtn');
    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function() {
            // ไปยัง URL + ?reset=1 เพื่อล้าง Session
            window.location.href = '/admin/reports?reset=1';
        });
    }
});
</script>