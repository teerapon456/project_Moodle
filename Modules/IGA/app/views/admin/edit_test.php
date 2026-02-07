<?php
// ไฟล์นี้สำหรับผู้ดูแลระบบเพื่อแก้ไขแบบทดสอบ
require_once __DIR__ . '/../../includes/header.php';


// กำหนดชื่อหน้าโดยใช้ get_text()
$page_title = get_text('page_title_edit_test'); 

// ต้องแน่ใจว่าได้รวม header.php ซึ่งมักจะรวม config.php (ที่มี $conn) และเริ่มต้น session ไว้แล้ว

require_login();
if (!has_role('admin') && !has_role('editor')) { // ตรวจสอบบทบาท
    // ใช้ get_text() สำหรับข้อความแจ้งเตือน
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: ../../public/login.php");
    exit();
}

// ตรวจสอบ CSRF token สำหรับการนำทางแบบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_id']) && !isset($_POST['test_name'])) {
    // นี่คือการนำทางจาก manage_tests.php ไปยัง edit_test.php
    if (!verify_csrf_token()) {
        set_alert(get_text('security_error_csrf'), "danger");
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/tests");
        exit();
    }
}

// รับ test_id จาก POST หรือ GET
$test_id = $_POST['test_id'] ?? $_GET['id'] ?? null;
$test = null; // จะเก็บข้อมูลแบบทดสอบที่จะถูกแก้ไข

// ตรวจสอบว่า test_id ถูกส่งมาและเป็นตัวเลขที่ถูกต้อง
if (!$test_id || !is_numeric($test_id) || $test_id <= 0) {
    set_alert(get_text('alert_invalid_test_id_general'), "danger");
    header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/tests");
    exit();
}

// ในไฟล์ PHP ของคุณ เช่น header.php หรือก่อนทำการ query ใดๆ ที่แก้ไขข้อมูล
if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (int)$_SESSION['user_id'];
    $conn->query("SET @user_id = " . $current_user_id);
} else {
    // หากไม่มี user_id ใน session (เช่น Guest) หรือไม่มีการเชื่อมต่อ db
    $conn->query("SET @user_id = NULL");
}

// 💡 โค้ดใหม่: ดึงรายการไฟล์ภาษา (ย้ายขึ้นมาข้างบน เพื่อให้พร้อมใช้งานก่อน POST หรือ GET)
$language_files = [];
$lang_dir = __DIR__ . '/../../languages/'; // ตรวจสอบ path นี้ให้ถูกต้องตามโครงสร้างโปรเจกต์ของคุณ
if (is_dir($lang_dir)) {
    $files = scandir($lang_dir);
    foreach ($files as $file) {
        if (preg_match('/^([a-z]{2})\.php$/', $file, $matches)) {
            $lang_code = $matches[1];
            $language_files[$lang_code] = $lang_code; // เก็บเป็น code => code
        }
    }
    ksort($language_files); // เรียงลำดับตามตัวอักษร
}

// --- ส่วนของการประมวลผลข้อมูลเมื่อมีการ POST form (ย้ายมาไว้ข้างบนสุดของไฟล์เพื่อให้ redirect ได้ทันที) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ตรวจสอบ CSRF token ก่อนประมวลผลข้อมูล
    if (!verify_csrf_token()) {
        set_alert(get_text('error_csrf_token_invalid'), "danger");
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/edit-test?id=" . htmlspecialchars($test_id)); // Redirect กลับหน้าเดิมพร้อม Alert
        exit();
    }

    // ตรวจสอบว่าเป็นการส่งฟอร์มจริงหรือเป็นการนำทาง
    // ถ้ามี test_name ใน POST แสดงว่าเป็นการส่งฟอร์ม
    if (isset($_POST['test_name'])) {
        // นี่คือการส่งฟอร์มแก้ไข test
        $test_name = trim($_POST['test_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $duration_minutes = filter_var($_POST['duration_minutes'] ?? 0, FILTER_VALIDATE_INT, array("options" => array("min_range"=>0)));
        $min_passing_score = (int)($_POST['min_passing_score'] ?? 0); // เปลี่ยนเป็น int ตามประเภทใน DB
        $creation_year = (int)($_POST['creation_year'] ?? date("Y"));
        $test_no = trim($_POST['test_no'] ?? ''); // 💡 รับค่า test_no เป็น string
        $language = trim($_POST['language'] ?? 'en'); // 💡 รับค่าภาษาที่เลือก

        $published_at = !empty($_POST['published_at']) ? str_replace('T', ' ', $_POST['published_at']) : null;
        $unpublished_at = !empty($_POST['unpublished_at']) ? str_replace('T', ' ', $_POST['unpublished_at']) : null;

        if (empty($test_name)) {
            set_alert(get_text('alert_test_name_required'), "danger");
        } elseif ($duration_minutes === false) {
            set_alert(get_text('alert_invalid_duration_format'), "danger");
        } elseif (!array_key_exists($language, $language_files)) { // 💡 ตรวจสอบภาษา
            set_alert(get_text('alert_invalid_language_selected'), "danger");
        } else {
            try {
                // 💡 เพิ่ม language = ? ใน UPDATE statement
                $stmt = $conn->prepare("UPDATE tests SET test_name = ?, description = ?, is_published = ?, duration_minutes = ?, min_passing_score = ?, creation_year = ?, test_no = ?, language = ?, published_at = ?, unpublished_at = ? WHERE test_id = ?");
                // 💡 อัปเดต bind_param: s (test_name), s (description), i (is_published), i (duration_minutes), i (min_passing_score), i (creation_year), s (test_no), s (language), s (published_at), s (unpublished_at), i (test_id)
                $stmt->bind_param("ssiiiissssi", $test_name, $description, $is_published, $duration_minutes, $min_passing_score, $creation_year, $test_no, $language, $published_at, $unpublished_at, $test_id);

                if ($stmt->execute()) {
                    set_alert(get_text('alert_update_test_success',$test_name), "success");
                    header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/tests");
                    exit();
                } else {
                    set_alert(get_text('error_update_test_failed', [$stmt->error]), "danger");
                }
                $stmt->close();
            } catch (Exception $e) {
                set_alert(get_text('error_technical', [$e->getMessage()]), "danger");
            }
        }
        // สำคัญ: Redirect หลังการประมวลผล POST เสมอ!
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/edit-test?id=" . htmlspecialchars($test_id));
        exit();
    } else {
        // นี่คือการนำทางจาก manage_tests.php - redirect ไปแสดงฟอร์ม
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/edit-test?id=" . htmlspecialchars($test_id));
        exit();
    }
}

// --- ดึงข้อมูลแบบทดสอบสำหรับแสดงผลในฟอร์ม (จะถูกเรียกหลังการประมวลผล POST หากมีการ Redirect) ---
try {
    // 💡 เพิ่ม language ใน SELECT statement
    $stmt = $conn->prepare("SELECT test_id, test_name, description, is_published, duration_minutes, min_passing_score, creation_year, test_no, language, published_at, unpublished_at FROM tests WHERE test_id = ?");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $test = $result->fetch_assoc();
        $language = $test['language']; // 💡 กำหนดค่า $language จากข้อมูลที่ดึงมา
    } else {
        set_alert(get_text('alert_test_not_found'), "danger");
        header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/tests");
        exit();
    }
    $stmt->close();

} catch (Exception $e) {
    set_alert(get_text('error_general', [$e->getMessage()]), "danger");
    header("Location: manage_tests.php");
    exit();
}

?>

<div class="container py-4">
    <h1 class="mb-4 text-primary-custom"><?php echo get_text('edit_test_title'); ?>: <?php echo htmlspecialchars($test['test_name']); ?></h1>

    <?php echo get_alert(); // แสดงข้อความแจ้งเตือนที่ถูก set ไว้ ?>

    <div class="card shadow-lg p-4">
        <div class="card-body">
            <form action="/INTEQC_GLOBAL_ASSESMENT/admin/edit-test?id=<?php echo htmlspecialchars($test_id); ?>" method="POST">
                <?php echo generate_csrf_token(); // เพิ่ม CSRF token ในฟอร์ม ?>
                <div class="mb-3">
                    <label for="test_name" class="form-label"><?php echo get_text('label_test_name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="test_name" name="test_name" value="<?php echo htmlspecialchars($test['test_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label"><?php echo get_text('label_test_description'); ?></label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($test['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="testDuration" class="form-label"><?php echo get_text('label_test_duration'); ?> <span class="text-muted">(<?php echo get_text('duration_unlimited_hint'); ?>)</span></label>
                    <input type="number" class="form-control" id="testDuration" name="duration_minutes" value="<?php echo htmlspecialchars($test['duration_minutes']); ?>" min="0">
                </div>
                <div class="mb-3">
                    <label for="min_passing_score" class="form-label"><?php echo get_text('label_min_passing_score'); ?></label>
                    <input type="number" step="1" class="form-control" id="min_passing_score" name="min_passing_score" value="<?php echo htmlspecialchars($test['min_passing_score']); ?>" min="0" max="100">
                </div>
                <div class="mb-3">
                    <label for="creation_year" class="form-label"><?php echo get_text('label_creation_year'); ?></label>
                    <input type="number" class="form-control" id="creation_year" name="creation_year" value="<?php echo htmlspecialchars($test['creation_year']); ?>" min="1900" max="<?php echo date("Y") + 5; ?>">
                </div>

                <div class="mb-3">
                    <label for="test_no" class="form-label"><?php echo get_text('label_test_group_number'); ?></label>
                    <input type="text" class="form-control" id="test_no" name="test_no" value="<?php echo htmlspecialchars($test['test_no'] ?? ''); ?>" min="1" placeholder="<?php echo get_text('placeholder_optional_test_group'); ?>">
                    <small class="form-text text-muted"><?php echo get_text('hint_test_no'); ?></small>
                </div>
                
                <div class="mb-3">
                    <label for="language" class="form-label"><?php echo get_text('label_language'); ?></label>
                    <select class="form-select" id="language" name="language" required>
                        <?php foreach ($language_files as $code => $name): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($code === $language) ? 'selected' : ''; ?>>
                                <?php
                                    // คุณสามารถใช้ get_text เพื่อแสดงชื่อเต็มของภาษาได้ ถ้ามีการกำหนดในไฟล์ภาษา
                                    // เช่น 'language_name_en' => 'English'
                                    echo htmlspecialchars(get_text('language_name_' . $code) ?: $name);
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted"><?php echo get_text('hint_test_language'); ?></small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="published_at" class="form-label"><?php echo get_text('label_published_at', 'Start Publish Date'); ?></label>
                        <input type="datetime-local" class="form-control" id="published_at" name="published_at" value="<?php echo htmlspecialchars($test['published_at'] ? date('Y-m-d\TH:i', strtotime($test['published_at'])) : ''); ?>">
                        <small class="text-muted"><?php echo get_text('hint_published_at', 'Leave empty to publish immediately upon activation.'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="unpublished_at" class="form-label"><?php echo get_text('label_unpublished_at', 'End Publish Date'); ?></label>
                        <input type="datetime-local" class="form-control" id="unpublished_at" name="unpublished_at" value="<?php echo htmlspecialchars($test['unpublished_at'] ? date('Y-m-d\TH:i', strtotime($test['unpublished_at'])) : ''); ?>">
                        <small class="text-muted"><?php echo get_text('hint_unpublished_at', 'Leave empty for no expiration.'); ?></small>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="is_published" name="is_published" <?php echo ($test['is_published'] ? 'checked' : ''); ?>>
                    <label class="form-check-label" for="is_published">
                        <?php echo get_text('label_publish_test_immediately'); ?>
                    </label>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fas fa-save me-2"></i> <?php echo get_text('save_changes_button'); ?>
                    </button>
                    <a href="/INTEQC_GLOBAL_ASSESMENT/admin/tests" class="btn btn-secondary">
                        <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text('back_button'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>