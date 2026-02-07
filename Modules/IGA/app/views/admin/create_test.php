<?php
// ไฟล์นี้จะทำหน้าที่เป็นหน้าสำหรับ Admin เพื่อสร้างแบบทดสอบใหม่

require_once __DIR__ . '/../../includes/header.php';


// กำหนดชื่อหน้าโดยใช้ get_text()
$page_title = get_text('page_title_create_test');

require_login(); // ตรวจสอบว่าล็อกอินแล้ว
if (!has_role('admin') && !has_role('editor')) { // ตรวจสอบบทบาท
    // ใช้ get_text() สำหรับข้อความแจ้งเตือน
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: ../../public/login.php");
    exit();
}

$test_name = '';
$description = '';
$duration_minutes = 0; // เพิ่มตัวแปรสำหรับระยะเวลา
$is_published = 0; // ค่าเริ่มต้นคือยังไม่เผยแพร่
$min_passing_score = 0; // 💡 เพิ่มตัวแปรสำหรับคะแนนขั้นต่ำ
$creation_year = date("Y"); // 💡 เพิ่มตัวแปรสำหรับปีที่สร้าง, กำหนดค่าเริ่มต้นเป็นปีปัจจุบัน
$test_no = ''; // 💡 เปลี่ยนค่าเริ่มต้นเป็นสตริงว่างสำหรับ test_no
$language = 'th'; // 💡 เพิ่มตัวแปรสำหรับภาษา, ค่าเริ่มต้นเป็น 'th'

// ในไฟล์ PHP ของคุณ เช่น header.php หรือก่อนทำการ query ใดๆ ที่แก้ไขข้อมูล
// บล็อกนี้ใช้สำหรับตั้งค่า MySQL user-defined variable @user_id ซึ่งเกี่ยวข้องกับ Audit Trail (ถ้ามี)
// ไม่ได้ส่งผลโดยตรงต่อค่า created_by_user_id ที่ insert ในตาราง tests
if (isset($_SESSION['user_id']) && $conn) {
    $current_user_id = (int)$_SESSION['user_id'];
    $conn->query("SET @user_id = " . $current_user_id);
} else {
    // หากไม่มี user_id ใน session (เช่น Guest) หรือไม่มีการเชื่อมต่อ db
    $conn->query("SET @user_id = NULL");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $test_name = trim($_POST['test_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 0); // รับค่า duration
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $min_passing_score = (int)($_POST['min_passing_score'] ?? 0); // 💡 รับค่าคะแนนขั้นต่ำ (เปลี่ยนเป็น int)
    $creation_year = (int)($_POST['creation_year'] ?? date("Y")); // 💡 รับค่าปีที่สร้าง

    // --- แก้ไขตรงนี้สำหรับ test_no ---
    // ไม่ใช้ filter_var(..., FILTER_VALIDATE_INT) เพราะ test_no สามารถเป็นตัวอักษรผสมตัวเลขได้
    $test_no = trim($_POST['test_no'] ?? ''); // Treat it as a string
    // --- สิ้นสุดการแก้ไข test_no ---

    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
    $unpublished_at = !empty($_POST['unpublished_at']) ? $_POST['unpublished_at'] : null;

    $language = trim($_POST['language'] ?? 'th'); // รับค่าภาษาจากฟอร์ม

    // ดึง user_id จาก session เพื่อใช้เป็น created_by_user_id
    // เพิ่มการตรวจสอบให้แน่ใจว่าค่านี้มีอยู่ ไม่ใช่แค่ null
    $created_by_user_id = $_SESSION['user_id'] ?? null;

    // --- เพิ่ม DEBUG LOG สำหรับ created_by_user_id ---
    error_log("DEBUG in create_test.php (POST request): created_by_user_id is " . var_export($created_by_user_id, true));
    // --- สิ้นสุด DEBUG LOG ---

    if (empty($test_name)) {
        set_alert(get_text('alert_test_name_required'), "danger");
    } elseif (empty($created_by_user_id)) { // เพิ่มการตรวจสอบ created_by_user_id
        set_alert(get_text('error_user_not_logged_in'), "danger"); // ควรมีข้อความนี้ในไฟล์ภาษา
        // Optional: Redirect to login if user_id is truly missing during POST
        header("Location: ../../public/login.php");
        exit();
    }
    else {
        try {
            // 💡 เพิ่ม min_passing_score, creation_year, และ test_no ใน INSERT statement
            // เพิ่ม created_at, updated_at และใช้ NOW()
            $stmt = $conn->prepare("INSERT INTO tests (test_name, description, is_published, created_by_user_id, duration_minutes, min_passing_score, creation_year, test_no, language, published_at, unpublished_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

            // 💡 อัปเดต bind_param:
            // test_name (s), description (s), is_published (i), created_by_user_id (i), duration_minutes (i),
            // min_passing_score (i), creation_year (i), test_no (s), language (s), published_at (s), unpublished_at (s)
            $stmt->bind_param(
                "ssiiiiissss",
                $test_name,
                $description,
                $is_published,
                $created_by_user_id, // ใช้ค่าที่ตรวจสอบแล้ว
                $duration_minutes,
                $min_passing_score,
                $creation_year,
                $test_no, // ตอนนี้เป็น string แน่นอน
                $language,
                $published_at,
                $unpublished_at
            );

            if ($stmt->execute()) {
                // ใช้ get_text() สำหรับข้อความสำเร็จ
                set_alert(get_text('alert_create_test_success', $test_name), "success");
                // Redirect ไปหน้าจัดการแบบทดสอบหลังจากสร้างเสร็จ
                header("Location: /INTEQC_GLOBAL_ASSESMENT/admin/tests");
                exit();
            } else {
                // ใช้ get_text() สำหรับข้อความข้อผิดพลาด
                set_alert(get_text('error_create_test_failed', [$stmt->error]), "danger");
            }
            $stmt->close();
        } catch (Exception $e) {
            // ใช้ get_text() สำหรับข้อความข้อผิดพลาดทั่วไป
            set_alert(get_text('error_general', [$e->getMessage()]), "danger");
            error_log("Error creating test: " . $e->getMessage()); // Log the actual error
        }
    }
}

// 💡 โค้ดใหม่: ดึงรายการไฟล์ภาษา
$language_files = [];
$lang_dir = __DIR__ . '/../../languages/';
if (is_dir($lang_dir)) {
    $files = scandir($lang_dir);
    foreach ($files as $file) {
        if (preg_match('/^([a-z]{2})\.php$/', $file, $matches)) {
            $lang_code = $matches[1];
            // คุณอาจต้องการแสดงชื่อเต็มของภาษาแทนโค้ด เช่น 'English', 'Thai'
            // สำหรับตัวอย่างนี้ เราจะใช้โค้ดภาษา
            $language_files[$lang_code] = $lang_code; // เก็บเป็น code => code
            // ถ้าอยากได้ชื่อเต็ม: $language_files[$lang_code] = get_text('language_name_' . $lang_code); 
            // ซึ่งต้องเพิ่ม 'language_name_en' => 'English' ในไฟล์ภาษา
        }
    }
    // เรียงลำดับตามตัวอักษร
    ksort($language_files);
}
?>

<h1 class="mb-4 text-primary-custom"><?php echo get_text('create_new_test_title'); ?></h1>
<?php echo get_alert(); // <-- บรรทัดนี้สำคัญมาก! ใช้สำหรับแสดง Alert Message ต่างๆ 
?>

<div class="card shadow-lg p-4">
    <div class="card-body">
        <form action="/INTEQC_GLOBAL_ASSESMENT/admin/create-test" method="POST">
            <div class="mb-3">
                <label for="test_name" class="form-label"><?php echo get_text('label_test_name'); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="test_name" name="test_name" value="<?php echo htmlspecialchars($test_name); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label"><?php echo get_text('label_test_description'); ?></label>
                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($description); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="testDuration" class="form-label"><?php echo get_text('label_test_duration'); ?> <span class="text-muted">(<?php echo get_text('duration_unlimited_hint'); ?>)</span></label>
                <input type="number" class="form-control" id="testDuration" name="duration_minutes" value="<?php echo htmlspecialchars($duration_minutes); ?>" min="0">
            </div>
            <div class="mb-3">
                <label for="min_passing_score" class="form-label"><?php echo get_text('label_min_passing_score'); ?></label>
                <input type="number" step="1" class="form-control" id="min_passing_score" name="min_passing_score" value="<?php echo htmlspecialchars($min_passing_score); ?>" min="0" max="100">
            </div>
            <div class="mb-3">
                <label for="creation_year" class="form-label"><?php echo get_text('label_creation_year'); ?></label>
                <input type="number" class="form-control" id="creation_year" name="creation_year" value="<?php echo htmlspecialchars($creation_year); ?>" min="1900" max="<?php echo date("Y") + 5; ?>">
            </div>

            <div class="mb-3">
                <label for="test_no" class="form-label"><?php echo get_text('label_test_group_number'); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="test_no" name="test_no" value="<?php echo htmlspecialchars($test_no ?? ''); ?>" placeholder="<?php echo get_text('placeholder_optional_test_group'); ?>" required>
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
                    <input type="datetime-local" class="form-control" id="published_at" name="published_at" value="<?php echo htmlspecialchars($published_at ?? ''); ?>">
                    <small class="text-muted"><?php echo get_text('hint_published_at', 'Leave empty to publish immediately upon activation.'); ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="unpublished_at" class="form-label"><?php echo get_text('label_unpublished_at', 'End Publish Date'); ?></label>
                    <input type="datetime-local" class="form-control" id="unpublished_at" name="unpublished_at" value="<?php echo htmlspecialchars($unpublished_at ?? ''); ?>">
                    <small class="text-muted"><?php echo get_text('hint_unpublished_at', 'Leave empty for no expiration.'); ?></small>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="is_published" name="is_published" <?php echo ($is_published ? 'checked' : ''); ?>>
                <label class="form-check-label" for="is_published">
                    <?php echo get_text('label_publish_test_immediately'); ?>
                </label>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary-custom">
                    <i class="fas fa-save me-2"></i> <?php echo get_text('save_test_button'); ?>
                </button>
                <a href="/INTEQC_GLOBAL_ASSESMENT/admin/tests" class="btn btn-secondary">
                    <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text('back_button'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>