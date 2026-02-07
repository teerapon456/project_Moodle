<?php
// ไฟล์นี้จะทำหน้าที่เป็นหน้าสำหรับผู้ใช้งานทั่วไปเพื่อทำแบบทดสอบ
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../includes/header.php';
$page_title = get_text('page_title_test_history'); // ใช้ get_text()

$conn->query("SET time_zone = '+07:00'");

ini_set('display_errors', 0); // ไม่แสดง error บนหน้าเว็บจริงเพื่อความปลอดภัย
ini_set('log_errors', 1);     // เปิดใช้งานการบันทึก error
ini_set('error_log', LOG_FILE); // กำหนดไฟล์สำหรับบันทึก error

require_login();
if (!has_role('associate') && !has_role('applicant')) {
    set_alert(get_text('alert_no_permission_user'), "danger"); // ใช้ get_text()
    header("Location: /INTEQC_GLOBAL_ASSESMENT/login");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_attempts = [];

try {
    // ดึงข้อมูลการทำแบบทดสอบทั้งหมดของผู้ใช้คนนี้
    $stmt = $conn->prepare("
        SELECT
            uta.attempt_id,
            uta.test_id,
            t.test_name,
            uta.start_time,
            uta.end_time,
            uta.is_completed,
            uta.time_spent_seconds
        FROM user_test_attempts uta
        JOIN tests t ON uta.test_id = t.test_id
        WHERE uta.user_id = ?
        ORDER BY uta.start_time DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $user_attempts[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    set_alert(get_text('alert_load_test_history_error') . ": " . $e->getMessage(), "danger");
    // ไม่ redirect เพื่อให้เห็นข้อความ alert
}

?>

<?php echo get_alert(); ?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
    /* Custom CSS for responsiveness */
    /* Adjusts the custom width for different screen sizes */
    .w-80-custom {
        width: 100%;
        /* Full width on small screens */
        padding-left: 15px;
        /* Add some padding on smaller screens */
        padding-right: 15px;
        /* Add some padding on smaller screens */
    }

    @media (min-width: 768px) {

        /* On medium screens and up */
        .w-80-custom {
            width: 80%;
            /* 80% width for larger screens */
            max-width: 960px;
            /* Optional: set a max-width to prevent it from getting too wide on very large screens */
            margin-left: auto;
            /* Center the container */
            margin-right: auto;
            /* Center the container */
            padding-left: var(--bs-gutter-x, 1.5rem);
            /* Reset Bootstrap padding */
            padding-right: var(--bs-gutter-x, 1.5rem);
            /* Reset Bootstrap padding */
        }
    }

    /* Header adjustments for stacking and centering on small screens */
    @media (max-width: 767.98px) {

        /* Small devices (phones and up) */
        .header-buttons-container {
            margin-top: 1rem;
            /* Add space between title and buttons when stacked */
            width: 100%;
            /* Make buttons container full width */
            display: flex;
            /* Use flex to center the button */
            justify-content: center !important;
            /* Center button horizontally */
        }

        .header-buttons-container .btn {
            width: 100%;
            /* Make button full width if it's the only one */
            max-width: 250px;
            /* Limit max width for readability */
        }

        .text-center-sm {
            /* A utility class for centering on small screens */
            text-align: center !important;
        }

        /* Table specific adjustments for better mobile readability */
        .table-responsive table {
            border-collapse: collapse;
            /* Ensure borders collapse for cleaner look */
            width: 100%;
            /* Ensure table takes full width */
        }

        .table-responsive thead {
            display: none;
            /* Hide table headers on small screens */
        }

        .table-responsive tbody,
        .table-responsive tr,
        .table-responsive td {
            display: block;
            /* Make table rows and cells behave like block elements */
            width: 100%;
            /* Each cell takes full width of its "row" */
        }

        .table-responsive tr {
            margin-bottom: 1rem;
            /* Add space between "rows" (logical rows) */
            border: 1px solid #dee2e6;
            /* Add a border around each logical row */
            border-radius: 0.25rem;
            /* Slightly rounded corners */
            overflow: hidden;
            /* Ensure rounded corners apply */
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            /* Subtle shadow */
        }

        .table-responsive td {
            text-align: right;
            /* Align cell content to the right */
            padding-left: 50%;
            /* Make space for pseudo-element label */
            position: relative;
            /* For pseudo-element positioning */
            border: none;
            /* Remove individual cell borders */
            padding-top: 0.75rem;
            /* Add some padding top/bottom */
            padding-bottom: 0.75rem;
        }

        /* Use data-label for displaying column headers as labels on mobile */
        .table-responsive td::before {
            content: attr(data-label);
            /* Get label from data-label attribute */
            position: absolute;
            left: 0;
            width: 45%;
            /* Space for the label */
            padding-left: 0.75rem;
            font-weight: bold;
            text-align: left;
            /* Align label to the left */
            color: #495057;
            /* Darker color for labels */
        }

        /* Specific styling for badge status on mobile */
        .table-responsive td .badge {
            display: inline-block;
            /* Keep badge inline */
            margin-top: 0;
            /* Remove top margin if any */
        }

        /* Adjust button group within action column */
        .table-responsive td:last-child {
            text-align: center;
            /* Center the action buttons */
            padding-left: 0.75rem;
            /* Reset padding for action column */
        }

        .table-responsive td:last-child::before {
            display: none;
            /* Hide label for action column */
        }

        .table-responsive td a.btn {
            width: auto;
            /* Allow button to size naturally */
            margin: 0.25rem auto;
            /* Center with margin */
            display: inline-block;
            /* Allow multiple buttons to be side-by-side if they fit */
            font-size: 0.875rem;
            /* Slightly smaller font for buttons */
            padding: 0.5rem 0.75rem;
            /* Adjust padding */
        }
    }
</style>

<div class="container-fluid w-100-custom py-4">
    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4">
        <h2 class="mb-2 mb-md-0 text-primary-custom text-center-sm"><?php echo get_text('your_test_history'); ?></h2>
        <div class="header-buttons-container d-flex justify-content-center justify-content-md-end">
            <a href="/INTEQC_GLOBAL_ASSESMENT/user" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> <?php echo get_text('back_to_dashboard'); ?>
            </a>
        </div>
    </div>

    <?php if (empty($user_attempts)): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i> <?php echo get_text('no_test_history'); ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover shadow-sm rounded overflow-hidden">
                <thead class="bg-white text-white">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col"><?php echo get_text('table_header_test_name'); ?></th>
                        <th scope="col"><?php echo get_text('table_header_start_time'); ?></th>
                        <th scope="col"><?php echo get_text('table_header_end_time'); ?></th>
                        <th scope="col"><?php echo get_text('table_header_duration'); ?></th>
                        <th scope="col"><?php echo get_text('table_header_status'); ?></th>
                        <th scope="col"><?php echo get_text('table_header_action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_attempts as $index => $attempt): ?>
                        <tr>
                            <th scope="row" data-label="#"><?php echo $index + 1; ?></th>
                            <td data-label="<?php echo get_text('table_header_test_name'); ?>">
                                <?php echo htmlspecialchars(mb_strimwidth($attempt['test_name'], 0, 50, '...')); ?>
                            </td>
                            <td data-label="<?php echo get_text('table_header_start_time'); ?>"><?php echo htmlspecialchars($attempt['start_time']); ?></td>
                            <td data-label="<?php echo get_text('table_header_end_time'); ?>"><?php echo htmlspecialchars($attempt['end_time'] ?? get_text('not_applicable_abbr')); ?></td>
                            <td data-label="<?php echo get_text('table_header_duration'); ?>"><?php echo formatTimeSpent($attempt['time_spent_seconds']); ?></td> <?php // ใช้ formatTimeSpent จาก functions.php 
                                                                                                                                                                    ?>
                            <td data-label="<?php echo get_text('table_header_status'); ?>">
                                <?php if ($attempt['is_completed']): ?>
                                    <span class="badge bg-success"><?php echo get_text('status_completed'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?php echo get_text('status_in_progress'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php echo get_text('table_header_action'); ?>">
                                <?php if ($attempt['is_completed']): ?>
                                    <form action="/INTEQC_GLOBAL_ASSESMENT/user/results" method="POST" style="display:inline;">
                                        <input type="hidden" name="attempt_id" value="<?php echo $attempt['attempt_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-info-custom">
                                            <i class="fas fa-eye me-1"></i> <?php echo get_text('action_view_results'); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="/INTEQC_GLOBAL_ASSESMENT/user/test" method="POST" style="display:inline;">
                                        <input type="hidden" name="test_id" value="<?php echo $attempt['test_id']; ?>">
                                        <input type="hidden" name="attempt_id" value="<?php echo $attempt['attempt_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="fas fa-play me-1"></i> <?php echo get_text('action_continue'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>