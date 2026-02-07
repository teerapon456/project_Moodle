<?php
// /admin/reports-individual.php
// หน้าแสดง "รายงานผลรายบุคคล" (แสดงรายชื่อก่อน) — ใช้ GET เพื่อกัน resubmit เมื่อย้อนกลับ

require_once __DIR__ . '/../../includes/header.php';

// ตั้งค่าหัวข้อหน้า (ไปเพิ่มในไฟล์ภาษาเองได้)
// เช่น 'page_heading_individual_report' => 'รายงานรายบุคคล (Individual Report)'
$page_title = get_text('page_heading_individual_report');

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
require_login();
if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

// --- รับค่าฟิลเตอร์ผ่าน GET เสมอ (ป้องกัน back แล้วให้ resubmit) ---
$input_source = $_GET;

$search_query     = $input_source['search_query']     ?? '';
$filter_roles     = $input_source['filter_roles']     ?? '-1'; // -1: All, 1: Associate, 2: Applicant
$filter_status    = $input_source['filter_status']    ?? '-1'; // -1: All, 0: In Progress, 1: Completed
$pass_fail_filter = $input_source['pass_fail_filter'] ?? '-1'; // -1: All, 1: Passed, 0: Failed

// flag นี้ควรมาจาก session/role ที่ include ไว้แล้ว
$is_Super_user_Recruitment = (has_role('Super_user_Recruitment') || (!empty($is_Super_user_Recruitment) && $is_Super_user_Recruitment));

// Super_user_Recruitment เห็นเฉพาะ applicant
if ($is_Super_user_Recruitment) {
    $filter_roles = '2'; // บังคับให้เป็น Applicant เสมอ
}

// Pagination
$items_per_page = 10;
$page           = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page           = max(1, $page);
$offset         = ($page - 1) * $items_per_page;

try {
    // -----------------------------------------
    // SQL: ใช้ attempt ล่าสุดของแต่ละ user (ถ้ามี) สำหรับสรุปสถานะ
    // 1 แถว = 1 user
    // -----------------------------------------
    $sql = "
        SELECT
            u.user_id,
            u.full_name AS user_name,
            r.role_name AS user_role,

            uta_latest.attempt_id,
            t.test_name,
            uta_latest.start_time,
            uta_latest.end_time,
            uta_latest.total_score,
            uta_latest.is_completed,
            t.min_passing_score,

            COALESCE(test_max_scores.max_test_score, 0) AS max_test_score,
            COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) AS max_applicable_score,
            CASE
                WHEN COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) > 0
                THEN (uta_latest.total_score / COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) * 100)
                ELSE 0
            END AS user_percentage_score,
            COALESCE(critical_failures.has_critical_fail, 0) AS has_critical_fail
        FROM users u
        JOIN roles r ON u.role_id = r.role_id

        LEFT JOIN (
            SELECT uta.*
            FROM iga_user_test_attempts uta
            JOIN (
                SELECT user_id, MAX(start_time) AS max_start_time
                FROM iga_user_test_attempts
                GROUP BY user_id
            ) latest ON latest.user_id = uta.user_id
                  AND latest.max_start_time = uta.start_time
        ) AS uta_latest ON uta_latest.user_id = u.user_id

        LEFT JOIN iga_tests t ON uta_latest.test_id = t.test_id

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
        ) AS ams ON uta_latest.attempt_id = ams.attempt_id

        LEFT JOIN (
            SELECT ua.attempt_id,
                   MAX(CASE WHEN q.is_critical = 1 AND ua.is_correct = 0 THEN 1 ELSE 0 END) AS has_critical_fail
            FROM iga_user_answers ua
            JOIN iga_questions q ON ua.question_id = q.question_id
            GROUP BY ua.attempt_id
        ) AS critical_failures ON uta_latest.attempt_id = critical_failures.attempt_id
    ";

    $where_clauses = [];
    $params        = [];
    $types         = "";

    // Super_user_Recruitment → เห็นเฉพาะ applicant / role_id = 5
    if ($is_Super_user_Recruitment) {
        $where_clauses[] = "(r.role_name = 'applicant' OR r.role_id = 5)";
    } else {
        $where_clauses[] = "r.role_name IN ('associate','applicant')";
        if ($filter_roles === '1') {
            $where_clauses[] = "r.role_name = 'associate'";
        } elseif ($filter_roles === '2') {
            $where_clauses[] = "r.role_name = 'applicant'";
        }
    }

    // filter search
    if (!empty($search_query)) {
        $term = '%' . $search_query . '%';
        $where_clauses[] = "(u.full_name LIKE ? OR r.role_name LIKE ? OR t.test_name LIKE ?)";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $types   .= "sss";
    }

    // filter status (จาก attempt ล่าสุด)
    $is_completed_value = null;
    if ($pass_fail_filter !== '-1') {
        $is_completed_value = 1;  // ถ้าจะดูผ่าน/ไม่ผ่าน ต้องเป็น completed
    } elseif ($filter_status !== '-1') {
        $is_completed_value = (int)$filter_status;
    }

    if ($is_completed_value !== null) {
        $where_clauses[] = "uta_latest.is_completed = ?";
        $params[]        = $is_completed_value;
        $types          .= "i";
    }

    // filter pass/fail
    if ($pass_fail_filter !== '-1') {
        $pass_condition = "(
            uta_latest.is_completed = 1
            AND COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) > 0
            AND (uta_latest.total_score / COALESCE(ams.max_attempt_score, test_max_scores.max_test_score, 0) * 100) >= t.min_passing_score
            AND COALESCE(critical_failures.has_critical_fail, 0) = 0
        )";
        if ($pass_fail_filter === '1') {
            $where_clauses[] = $pass_condition;
        } else {
            $where_clauses[] = "(uta_latest.attempt_id IS NOT NULL AND NOT " . $pass_condition . ")";
        }
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // นับจำนวน user ทั้งหมดที่เข้าเงื่อนไข
    $count_sql  = "SELECT COUNT(*) AS total FROM (" . $sql . ") AS total_results";
    $count_stmt = $conn->prepare($count_sql);
    if ($types) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_records = (int)$count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    $total_pages = $total_records > 0 ? (int)ceil($total_records / $items_per_page) : 1;

    // ตรวจสอบว่า page ปัจจุบันไม่อยู่เกิน total_pages
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $items_per_page;
    }

    // ORDER + LIMIT (✅ ใช้ alias uta_latest ให้ถูกต้อง)
    $sql    .= " ORDER BY uta_latest.start_time DESC LIMIT ? OFFSET ?";
    $types  .= "ii";
    $params[] = $items_per_page;
    $params[] = $offset;

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Error in reports-individual.php: " . $e->getMessage());
    set_alert(get_text("error_loading_reports") . ": " . $e->getMessage(), "danger");
    $users = [];
    $total_records = 0; // Set total records to 0 on error
    $total_pages = 0; // Set total pages to 0 on error
}

// query string สำหรับ pagination (ไม่ต้องเก็บค่าที่เป็น -1/ว่าง)
$filter_params = [
    'search_query'     => $search_query,
    'filter_status'    => $filter_status,
    'filter_roles'     => $filter_roles,
    'pass_fail_filter' => $pass_fail_filter,
];
$query_string = http_build_query(array_filter($filter_params, function ($v) {
    return ($v !== null && $v !== '-1' && $v !== '');
}));
?>
<div class="container-fluid w-80-custom py-1">
    <?php echo get_alert(); ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0 text-primary-custom">
            <?php echo get_text("page_heading_individual_report"); ?>
        </h1>
        <div class="d-flex">
            <a href="/admin" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text("back_to_dashboard"); ?>
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <div class="flex-grow-1 ms-2">
                <!-- ✅ ใช้ method=GET เพื่อกัน back แล้ว browser ขอ resubmit -->
                <form action="/admin/reports-individual" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="search_query" class="form-label text-white">
                            <?php echo get_text("search_label"); ?>:
                        </label>
                        <input type="text"
                               class="form-control form-control"
                               id="search_query"
                               name="search_query"
                               placeholder="<?php echo get_text("search_reports_placeholder"); ?>"
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="filter_roles" class="form-label text-white">
                            <?php echo get_text("filter_roles"); ?>:
                        </label>
                        <?php if ($is_Super_user_Recruitment): ?>
                            <select class="form-select form-select" id="filter_roles" name="filter_roles" disabled>
                                <option value="2" selected><?php echo get_text("role_applicant"); ?></option>
                            </select>
                            <input type="hidden" name="filter_roles" value="2">
                        <?php else: ?>
                            <select class="form-select form-select" id="filter_roles" name="filter_roles">
                                <option value="-1" <?php echo ($filter_roles == -1) ? 'selected' : ''; ?>>
                                    <?php echo get_text("all_roles"); ?>
                                </option>
                                <option value="1" <?php echo ($filter_roles == 1) ? 'selected' : ''; ?>>
                                    <?php echo get_text("role_associate"); ?>
                                </option>
                                <option value="2" <?php echo ($filter_roles == 2) ? 'selected' : ''; ?>>
                                    <?php echo get_text("role_applicant"); ?>
                                </option>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_status" class="form-label text-white">
                            <?php echo get_text("status_label"); ?>:
                        </label>
                        <select class="form-select form-select" id="filter_status" name="filter_status">
                            <option value="-1" <?php echo ($filter_status == -1) ? 'selected' : ''; ?>>
                                <?php echo get_text("all_status"); ?>
                            </option>
                            <option value="1" <?php echo ($filter_status == 1) ? 'selected' : '' ; ?>>
                                <?php echo get_text("status_completed"); ?>
                            </option>
                            <option value="0" <?php echo ($filter_status == 0) ? 'selected' : ''; ?>>
                                <?php echo get_text("status_in_progress"); ?>
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="pass_fail_filter" class="form-label text-white">
                            <?php echo get_text("filter_pass_fail"); ?>:
                        </label>
                        <select class="form-select form-select" id="pass_fail_filter" name="pass_fail_filter">
                            <option value="-1" <?php echo ($pass_fail_filter == -1) ? 'selected' : ''; ?>>
                                <?php echo get_text("all_pass_fail_status"); ?>
                            </option>
                            <option value="1" <?php echo ($pass_fail_filter == 1) ? 'selected' : ''; ?>>
                                <?php echo get_text("status_passed"); ?>
                            </option>
                            <option value="0" <?php echo ($pass_fail_filter == 0) ? 'selected' : ''; ?>>
                                <?php echo get_text("status_failed"); ?>
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2 d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-sm">
                            <?php echo get_text("apply_filter"); ?>
                        </button>
                        <button type="button" class="btn btn-primary-custom btn-sm" id="resetFilterBtn">
                            <?php echo get_text("reset_filter"); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th><?php echo get_text("table_header_number"); ?></th>
                                <th><?php echo get_text("table_header_examinee_name"); ?></th>
                                <th><?php echo get_text("table_header_user_role"); ?></th>
                                <th><?php echo get_text("table_header_test_name"); ?></th>
                                <th><?php echo get_text("table_header_status"); ?></th>
                                <th><?php echo get_text("table_header_actions"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = $offset + 1; ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($u['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['user_role']); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($u['test_name'])) {
                                            $name = trim($u['test_name']);
                                            $short = mb_substr($name, 0, 40, 'UTF-8');
                                            echo htmlspecialchars(mb_strlen($name, 'UTF-8') > 40 ? ($short . '...') : $short);
                                        } else {
                                            echo '<span class="text-muted">' . get_text("no_test_attempted") . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if (empty($u['attempt_id'])) {
                                            echo '<span class="badge bg-secondary">' . get_text("status_not_started") . '</span>';
                                        } else {
                                            if (!empty($u['is_completed'])) {
                                                echo '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> ' . get_text("status_completed") . '</span>';

                                                $den       = (float)($u['max_applicable_score'] ?? 0);
                                                $is_passed = false;
                                                if ($den > 0) {
                                                    $percentage_score = ($u['total_score'] / $den) * 100.0;
                                                    $is_passed = ($percentage_score >= (float)$u['min_passing_score']
                                                        && (int)$u['has_critical_fail'] === 0);
                                                }

                                                if ($is_passed) {
                                                    echo '<br><span class="badge bg-primary mt-1"><i class="fas fa-medal me-1"></i> ' . get_text("status_passed") . '</span>';
                                                } else {
                                                    echo '<br><span class="badge bg-danger mt-1"><i class="fas fa-times-circle me-1"></i> ' . get_text("status_failed") . '</span>';
                                                }
                                            } else {
                                                echo '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i> ' . get_text("status_in_progress") . '</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <form action="report-individual" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['user_id']); ?>">
                                            <button type="submit"
                                                    class="btn btn-sm btn-info text-white"
                                                    title="<?php echo get_text("view_individual_report_button"); ?>">
                                                <i class="fa-solid fa-user" style="color: #ffffff;"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                // คำนวณ x–z of Z
                $start_item = $total_records > 0 ? $offset + 1 : 0;
                $end_item   = $total_records > 0 ? min($offset + count($users), $total_records) : 0;
                ?>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3">
                    <div class="text-muted small mb-2 mb-md-0">
                        <?php if ($total_records > 0): ?>
                            <?php
                            // "Show 1–10 of 100 items"
                            echo get_text("show", "Show") . ' ' . $start_item . '–' . $end_item . ' ' . get_text("of", "of") . ' ' . $total_records . ' ' . get_text("list", "items");
                            ?>
                        <?php else: ?>
                            <?php echo get_text("no_data_found", "No data found"); ?>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-0">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="<?= $page <= 1 ? '#' : ('?page=' . ($page - 1) . (!empty($query_string) ? '&' . $query_string : '')) ?>"
                                       <?= $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>

                                <?php
                                // แสดงช่วงหน้ารอบ ๆ ปัจจุบัน ±2
                                $visible_range = 2;
                                $start_page    = max(1, $page - $visible_range);
                                $end_page      = min($total_pages, $page + $visible_range);

                                if ($start_page > 1): ?>
                                    <li class="page-item <?= 1 === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=1<?= !empty($query_string) ? '&' . $query_string : '' ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link"
                                           href="?page=<?= $p ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                            <?= $p ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item <?= $total_pages === $page ? 'active' : '' ?>">
                                        <a class="page-link"
                                           href="?page=<?= $total_pages ?><?= !empty($query_string) ? '&' . $query_string : '' ?>">
                                            <?= $total_pages ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="<?= $page >= $total_pages ? '#' : ('?page=' . ($page + 1) . (!empty($query_string) ? '&' . $query_string : '')) ?>"
                                       <?= $page >= $total_pages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
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
            // ✅ รีเซ็ตเป็นค่าเริ่มต้นด้วย GET (กัน resubmit)
            window.location.href = '/admin/reports-individual';
        });
    }
});
</script>
