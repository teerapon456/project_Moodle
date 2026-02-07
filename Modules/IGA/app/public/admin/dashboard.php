<?php
// ไฟล์นี้จะทำหน้าที่แสดงผลลัพธ์ของแบบทดสอบที่ผู้ใช้ทำเสร็จสิ้นแล้ว

// เปิดการแสดงผล Error สำหรับการ Debug (ปิดเมื่อใช้งานจริง)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// เรียกใช้ Header และ functions.php
require_once __DIR__ . '/../../includes/header.php';


require_login(); // ตรวจสอบว่าล็อกอินแล้ว

// ตรวจสอบบทบาท: อนุญาตให้ 'admin' หรือ 'super_user' เข้าถึงได้
// ถ้าไม่ใช่ admin และไม่ใช่ super_user ให้ทำการ redirect
if (!has_role('admin') && !has_role('super_user') && !has_role('editor') && !has_role('Super_user_Recruitment')) {
    set_alert(get_text('alert_no_admin_permission', []), "danger");
    // Redirect ไปหน้าผู้ใช้ทั่วไป หรือหน้าที่เหมาะสมกว่า
    header("Location: /login"); // หรือ ../views/user/dashboard.php
    exit();
}

// ** โค้ดเพื่อดึง role_id ของผู้ใช้ปัจจุบัน **
// สมมติว่า user_id ถูกเก็บไว้ในเซสชันหลังจากล็อกอิน
$current_user_id = $_SESSION['user_id'] ?? null;
$current_user_role_id = null;

if ($current_user_id) {
    try {
        $stmt = $conn->prepare("SELECT role_id FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $current_user_role_id = $row['role_id'];
        }
        $stmt->close();
    } catch (Exception $e) {
        // จัดการข้อผิดพลาด เช่น บันทึกข้อผิดพลาด หรือแสดงข้อความ
        error_log("Error fetching user role: " . $e->getMessage());
    }
}

// ตรวจสอบว่าผู้ใช้ปัจจุบันมี role_id เป็น 3 (super_user ตามการใช้งานของคุณ)
$is_super_user = ($current_user_role_id === 2);
$is_editor = ($current_user_role_id === 3);
$is_Super_user_Recruitment = ($current_user_role_id === 9);


// ดึงข้อมูลสถิติจากฐานข้อมูล
$total_tests = 0;
$published_tests = 0;
$total_associates = 0;
$total_applicants = 0;
$users_completed_test = 0;
$pending_short_answers = 0; // เพิ่มตัวแปรสำหรับคำถามที่ต้องตรวจ

try {
    // จำนวนบททดสอบทั้งหมด
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_tests FROM tests");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_tests = $result->fetch_assoc()['total_tests'];
    $stmt->close();

    // จำนวนบททดสอบที่เผยแพร่
    $stmt = $conn->prepare("SELECT COUNT(*) AS published_tests FROM iga_tests WHERE is_published = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $published_tests = $result->fetch_assoc()['published_tests'];
    $stmt->close();

    // จำนวนผู้ใช้งานทั้งหมด (ไม่รวม admin)
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_associates FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'associate') and is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_associates = $result->fetch_assoc()['total_associates'];
    $stmt->close();

    // จำนวนผู้ใช้งานทั้งหมด (ไม่รวม admin)
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_applicants FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'applicant') and is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $total_applicants = $result->fetch_assoc()['total_applicants'];
    $stmt->close();

    // จำนวนผู้ใช้งานที่ทำแบบทดสอบแล้ว (นับจาก iga_user_test_attempts ที่ is_completed = 1)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) AS users_completed_test FROM iga_user_test_attempts WHERE is_completed = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $users_completed_test = $result->fetch_assoc()['users_completed_test'];
    $stmt->close();

    // จำนวนผู้ใช้งานที่ทำแบบทดสอบแล้ว (นับจาก iga_user_test_attempts ที่ is_completed = 1)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT test_id) AS tests_completed FROM iga_user_test_attempts WHERE is_completed = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $tests_completed = $result->fetch_assoc()['tests_completed'];
    $stmt->close();

    // ** เพิ่มการดึงจำนวนคำถามอัตนัยที่รอการตรวจ **
    $stmt = $conn->prepare("
        SELECT COUNT(ua.user_answer_id) AS pending_short_answers
        FROM iga_user_answers ua
        JOIN iga_questions q ON ua.question_id = q.question_id
        WHERE q.question_type = 'short_answer' AND ua.score_earned IS NULL
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_short_answers = $result->fetch_assoc()['pending_short_answers'];
    $stmt->close();
} catch (Exception $e) {
    set_alert(get_text('error_data_fetch', [htmlspecialchars($e->getMessage())]), "danger"); // ใช้ get_text
}
?>


<style>
    .card {
        height: 100%;
        display: flex;
        flex-direction: column;
        border: none;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .card-body {
        flex: 1;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
    }

    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
        background: rgba(255, 255, 255, 0.2);
    }

    .card-icon i {
        font-size: 1.4rem;
    }

    .card-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        opacity: 0.9;
    }

    .card-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0.25rem 0;
    }

    .card-footer {
        padding: 0.6rem 1.25rem;
        background: rgba(0, 0, 0, 0.1) !important;
        border-top: 1px solid rgba(255, 255, 255, 0.2) !important;
    }

    .card-footer a,
    .card-footer button {
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .equal-height-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -8px;
    }

    .equal-height-row>[class*='col-'] {
        display: flex;
        flex-direction: column;
        padding: 0 8px;
        margin-bottom: 16px;
    }

    .disabled-card {
        opacity: 0.7;
        transform: none !important;
        box-shadow: none !important;
    }
</style>
<div class="container-fluid w-80-custom py-1">
    <?php echo get_alert(); ?>
    <h1 class="mb-4 text-primary-custom"><?php echo get_text('admin_dashboard_title', []); ?></h1>
    <div class="row equal-height-row">
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #4ECDC4 !important; border: none;" shadow <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('total_tests_card_title', []); ?></h5>
                    <div class="card-value"><?php echo $total_tests; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/tests" class="text-white text-decoration-none <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #FF9F1C !important; border: none;" shadow <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('published_tests_card_title', []); ?></h5>
                    <div class="card-value"><?php echo $published_tests; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <form action="/admin/tests" method="POST" style="display: inline;">
                        <input type="hidden" name="published" value="1">
                        <?php echo generate_csrf_token(); ?>
                        <button type="submit" class="btn btn-link text-white text-decoration-none p-0 <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-link' : ''; ?>" <?php echo $is_super_user ? 'disabled' : ''; ?>>
                            <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #7AC74F !important; border: none;" shadow <?php echo $is_super_user || $is_editor ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('users_completed_test_card_title', []); ?></h5>
                    <div class="card-value"><?php echo $users_completed_test; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/reports" class="text-white text-decoration-none <?php echo $is_super_user || $is_editor || $is_Super_user_Recruitment ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #FF6B6B !important; border: none;" shadow <?php echo $is_super_user || $is_editor ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('tests_completed_card_title', []); ?></h5>
                    <div class="card-value"><?php echo $tests_completed; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/reports" class="text-white text-decoration-none <?php echo $is_super_user || $is_editor ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #FF4D6D !important; border: none;" shadow <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('pending_questions_table_header', []); ?></h5>
                    <div class="card-value"><?php echo $pending_short_answers; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/review-answers" class="text-white text-decoration-none <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('start_grading', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #B388FF !important; border: none;" shadow <?php echo $is_super_user || $is_editor ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('total_associates', []); ?></h5>
                    <div class="card-value"><?php echo $total_associates; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/users?role_id=4" class="text-white text-decoration-none <?php echo $is_super_user || $is_editor ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #4CC9F0 !important; border: none;" shadow <?php echo $is_super_user || $is_editor ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('total_applicants', []); ?></h5>
                    <div class="card-value"><?php echo $total_applicants; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/users?role_id=5" class="text-white text-decoration-none <?php echo $is_super_user || $is_editor ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #978800ff !important; border: none;" shadow <?php echo $is_super_user || $is_editor ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-file"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('page_heading_individual_report', []); ?></h5>
                    <div class="card-value"><?php echo $users_completed_test; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/reports-individual" class="text-white text-decoration-none <?php echo $is_super_user || $is_editor ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- <div class="col-md-6 col-lg-3 mb-4">
            <div class="card text-white" style="background: #823485ff !important; border: none;" shadow <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-card' : ''; ?>">
                <div class="card-body">
                    <div class="card-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h5 class="card-title"><?php echo get_text('report_dashboards', []); ?></h5>
                    <div class="card-value"><?php echo $pending_short_answers; ?></div>
                </div>
                <div class="card-footer" style="background: rgba(0, 0, 0, 0.1) !important; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                    <a href="/admin/report-dashboards" class="text-white text-decoration-none <?php echo $is_super_user || $is_Super_user_Recruitment ? 'disabled-link' : ''; ?>">
                        <?php echo get_text('view_details_link', []); ?> <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div> -->
    </div>
</div>

    <?php require_once __DIR__ . '/../../includes/footer.php'; // เรียกใช้ Footer 
    ?>

    <style>
        .disabled-card {
            opacity: 0.6;
            /* ทำให้การ์ดดูจางลง */
            cursor: not-allowed;
            /* เปลี่ยนเคอร์เซอร์เพื่อระบุว่าคลิกไม่ได้ */
        }

        .disabled-card .card-body,
        .disabled-card .card-footer {
            pointer-events: none;
            /* ป้องกันการคลิกบนเนื้อหาของการ์ด */
        }

        .disabled-link {
            pointer-events: none;
            /* ปิดการใช้งานการคลิกบนลิงก์ */
            color: #cccccc !important;
            /* เปลี่ยนสีลิงก์เป็นสีเทา */
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ค้นหาองค์ประกอบทั้งหมดที่มีคลาส 'disabled-link'
            const disabledLinks = document.querySelectorAll('.disabled-link');

            // วนลูปผ่านแต่ละลิงก์ที่ถูกปิดใช้งานและป้องกันการกระทำเริ่มต้น
            disabledLinks.forEach(function(link) {
                link.addEventListener('click', function(event) {
                    event.preventDefault(); // หยุดไม่ให้ลิงก์นำทาง
                });
            });
        });
    </script>