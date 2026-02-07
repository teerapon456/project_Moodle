<?php
/**
 * review_short_answers.php
 * - ไฟล์นี้สำหรับผู้ดูแลระบบเพื่อตรวจคำถามอัตนัยที่ผู้ใช้ตอบ
 */

require_once __DIR__ . '/../../includes/header.php';
$page_title = get_text('page_title_review_short_answers');

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
require_login();
if (!has_role('admin') && !has_role('editor') ) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

// ===== START: UPDATED FILTER LOGIC (USING POST + SESSION) =====

// 1. ตรวจสอบว่า Session เริ่มทำงานหรือยัง
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$filter_session_key = 'review_short_filters'; // ชื่อ Key สำหรับเก็บฟิลเตอร์ใน Session

// 2. จัดการการ Reset Filter (เมื่อกดปุ่ม Reset)
if (isset($_GET['reset'])) {
    unset($_SESSION[$filter_session_key]);
    header("Location: review-answers"); // กลับไปหน้าแรกแบบ URL สะอาด
    exit();
}

// 3. จัดการการส่งฟิลเตอร์ (เมื่อกด Apply Filter)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // บันทึกค่าฟิลเตอร์ใหม่ลง Session
    $_SESSION[$filter_session_key] = $_POST;
    // Redirect ไปยังหน้าแรก (GET) เพื่อป้องกันการ submit form ซ้ำเวลากด F5
    header("Location: review-answers?page=1");
    exit();
}

// 4. ดึงค่าฟิลเตอร์จาก Session (สำหรับ GET request เช่น การแบ่งหน้า)
$input_source = $_SESSION[$filter_session_key] ?? [];

$search_query     = $input_source['search_query']   ?? '';
$filter_roles     = $input_source['filter_roles']   ?? '-1';
$test_id_filter   = $input_source['test_id']        ?? null;

// ===== END: UPDATED FILTER LOGIC =====


$is_Super_user_Recruitment = has_role('Super_user_Recruitment');
if ($is_Super_user_Recruitment) {
    $filter_roles = '2'; // บังคับบทบาท
}

// ดึง $tests สำหรับ dropdown
$tests = [];
try {
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
} catch (Exception $e) {
    error_log("Error fetching tests for filter: " . $e->getMessage());
}


// กำหนดค่าการแบ่งหน้า
$items_per_page = 7; // จำนวนรายการต่อหน้า
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // หน้าไม่ต่ำกว่า 1
$offset = ($current_page - 1) * $items_per_page;

$pending_attempts = [];
$total_items = 0;
$total_pages = 0;

try {
    // -------------------------------------------------------------------------
    // 1. นับจำนวนรายการทั้งหมดสำหรับการแบ่งหน้า (รวมฟิลเตอร์)
    // -------------------------------------------------------------------------
    $count_sql = "
        SELECT COUNT(DISTINCT uta.attempt_id) as total
        FROM iga_user_test_attempts uta
        JOIN iga_user_answers ua ON uta.attempt_id = ua.attempt_id
        JOIN iga_questions q ON ua.question_id = q.question_id
        JOIN users u ON uta.user_id = u.user_id
        JOIN roles r ON u.role_id = r.role_id
        JOIN iga_tests t ON uta.test_id = t.test_id
        WHERE q.question_type = 'short_answer' 
        AND ua.score_earned IS NULL 
        AND uta.is_completed = 1
    ";

    $where_clauses = [];
    $params = [];
    $types = "";

    // Role filter
    if ($is_Super_user_Recruitment) {
        $where_clauses[] = "(r.role_name = 'applicant' OR r.role_id = 5)";
    } else {
        if ($filter_roles === '1') {
            $where_clauses[] = "r.role_name = 'associate'";
        } elseif ($filter_roles === '2') {
            $where_clauses[] = "r.role_name = 'applicant'";
        }
    }

    // Test (test_no) filter
    if (!empty($test_id_filter)) {
        $where_clauses[] = "t.test_no = ?";
        $types .= "s";
        $params[] = $test_id_filter;
    }

    // Search query filter
    if (!empty($search_query)) {
        $term = '%' . $search_query . '%';
        $where_clauses[] = "(u.full_name LIKE ? OR u.username LIKE ? OR t.test_name LIKE ?)";
        $types .= "sss";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if (!empty($where_clauses)) {
        $count_sql .= " AND " . implode(" AND ", $where_clauses);
    }
    
    $count_stmt = $conn->prepare($count_sql);
    if ($types) {
        $count_stmt->bind_param($types, ...$params);
    }
    
    if (!$count_stmt->execute()) {
        error_log("Count Query Error: " . $conn->error);
        throw new Exception("SQL error in counting data. Check logs for details.");
    }

    $total_items = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    $total_pages = $total_items > 0 ? ceil($total_items / $items_per_page) : 1;
    $current_page = min($current_page, $total_pages > 0 ? $total_pages : 1);
    $offset = ($current_page - 1) * $items_per_page;

    // -------------------------------------------------------------------------
    // 2. ดึงรายการ (รวมฟิลเตอร์)
    // -------------------------------------------------------------------------
    if ($total_items > 0) {
        $sql = "
            SELECT
                uta.attempt_id,
                uta.user_id,
                u.full_name AS user_full_name,
                u.username AS user_username,
                t.test_name,
                uta.start_time,
                COUNT(ua.user_answer_id) AS pending_count
            FROM
                iga_user_test_attempts uta
            JOIN
                users u ON uta.user_id = u.user_id
            JOIN
                tests t ON uta.test_id = t.test_id
            JOIN
                iga_user_answers ua ON uta.attempt_id = ua.attempt_id
            JOIN
                questions q ON ua.question_id = q.question_id
            JOIN
                roles r ON u.role_id = r.role_id
            WHERE
                q.question_type = 'short_answer' AND ua.score_earned IS NULL AND uta.is_completed = 1
        ";
        
        // Add the same WHERE clauses
        if (!empty($where_clauses)) {
            $sql .= " AND " . implode(" AND ", $where_clauses);
        }

        $sql .= "
            GROUP BY
                uta.attempt_id, uta.user_id, u.full_name, u.username, t.test_name, uta.start_time
            ORDER BY
                uta.start_time ASC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $conn->error);
            throw new Exception("Failed to prepare SQL statement. Check logs for details.");
        }
        
        // Add pagination params to the existing filter params
        $types .= 'ii';
        $params[] = $items_per_page;
        $params[] = $offset;

        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            error_log("SQL Execute Error: " . $stmt->error);
            throw new Exception("Failed to execute SQL statement. Check logs for details.");
        }
        
        $result = $stmt->get_result();
        $pending_attempts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } // สิ้นสุด if ($total_items > 0)

} catch (Exception $e) {
    set_alert(sprintf(get_text('error_loading_data'), $e->getMessage()), "danger");
}

// ลบการสร้าง $query_string เพราะเราใช้ Session แทนแล้ว
// $filter_params = [ ... ];
// $query_string = http_build_query(...);
$query_string = ''; // ตั้งค่าเป็นค่าว่าง

?>
<div class="container-fluid w-80-custom py-1">
    <?php echo get_alert(); ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0 text-primary-custom"><?php echo get_text('page_heading_review_short_answers'); ?></h1>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <div class="flex-grow-1 ms-2">
                <form action="/admin/review-answers" method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search_query" class="form-label text-white"><?php echo get_text("search_label"); ?>:</label>
                        <input type="text" class="form-control form-control" id="search_query" name="search_query" placeholder="<?php echo get_text("search_reports_placeholder"); ?>" value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div class="col-md-4">
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
            <?php if (empty($pending_attempts)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_pending_short_answers'); ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo get_text('user_table_header'); ?></th>
                                <th><?php echo get_text('test_table_header'); ?></th>
                                <th><?php echo get_text('start_time_table_header'); ?></th>
                                <th><?php echo get_text('pending_questions_table_header'); ?></th>
                                <th><?php echo get_text('action_table_header'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_attempts as $attempt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attempt['user_full_name'] ?: $attempt['user_username']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['test_name']); ?></td>
                                    <td><?php echo htmlspecialchars(thai_datetime_format($attempt['start_time'])); ?></td>
                                    <td><span class="badge bg-warning"><?php echo htmlspecialchars($attempt['pending_count']); ?></span></td>
                                    <td>
                                        <form method="POST" action="review-answer" style="display: inline;">
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
                </div> <?php
                // คำนวณ x-z of z
                $start_item = $total_items > 0 ? $offset + 1 : 0;
                $end_item   = $total_items > 0 ? min($offset + count($pending_attempts), $total_items) : 0;
                ?>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3">
                    
                    <div class="text-muted small mb-2 mb-md-0">
                        <?php if ($total_items > 0): ?>
                            <?php
                            echo get_text("show", "Show") . ' ' . $start_item . '–' . $end_item . ' ' . get_text("of", "of") . ' ' . $total_items . ' ' . get_text("list", "items");
                            ?>
                        <?php else: ?>
                            <?php echo get_text("no_data_found", "No data found"); ?>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($total_pages) && $total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-0">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page - 1 ?>" <?= $current_page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php 
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $start_page + 4);
                                $start_page = max(1, $end_page - 4); 
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($i === $current_page ? 'active' : '') . '">';
                                    echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $current_page + 1 ?>" <?= $current_page >= $total_pages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
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
            // เปลี่ยน URL ไปยังหน้าปัจจุบัน + ?reset=1 เพื่อล้าง Session
            window.location.href = '/admin/review-answers?reset=1';
        });
    }
});
</script>