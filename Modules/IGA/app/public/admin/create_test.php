<?php
// ไฟล์นี้สำหรับผู้ดูแลระบบเพื่อสร้างแบบทดสอบใหม่
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/CategoryType.php';

// กำหนดชื่อหน้าโดยใช้ get_text()
$page_title = get_text('page_title_create_test'); 

require_login();
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment')) { // ตรวจสอบบทบาท
set_alert(get_text('alert_no_admin_permission'), "danger");
header("Location: /login");
exit();
}

// ตั้งค่า MySQL user-defined variable @user_id สำหรับ Audit Trail
if (isset($_SESSION['user_id']) && $conn) {
$current_user_id = (int)$_SESSION['user_id'];
$conn->query("SET @user_id = " . $current_user_id);
} else {
$conn->query("SET @user_id = NULL");
}

$categoryType = new CategoryType($conn);
$categoryTypesResult = $categoryType->getAll();

$categoryTypes = [];
if ($categoryTypesResult) {
while ($row = $categoryTypesResult->fetch_assoc()) {
 $categoryTypes[] = $row;
}
}

// ดึงรายการ Orgunitname ทั้งหมดจากฐานข้อมูล (สมมติว่าดึงจากตาราง users)
$orgunitnames = [];
try {
  $stmt = $conn->prepare("SELECT DISTINCT orgunitname FROM users WHERE orgunitname IS NOT NULL AND orgunitname != '' ORDER BY orgunitname ASC");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $orgunitnames[] = $row['orgunitname'];
  }
  $stmt->close();
} catch (Exception $e) {
  error_log("Failed to fetch Orgunitnames: " . $e->getMessage());
  $orgunitnames = [];
}

// ดึงรายการ Role ทั้งหมดจากฐานข้อมูล
$roles = [];
$roles_map = [];
try {
  $stmt = $conn->prepare("SELECT role_id, role_name FROM roles ORDER BY role_name ASC");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
    $roles_map[$row['role_name']] = $row['role_id'];
  }
  $stmt->close();
} catch (Exception $e) {
  error_log("Failed to fetch roles: " . $e->getMessage());
  $roles = [];
}

// ดึงรายการ EmplevelCode ทั้งหมดจากฐานข้อมูล (level_id และ level_code)
$emplevels = [];
$emplevels_map = [];
try {
$stmt = $conn->prepare("SELECT level_id, level_code FROM emplevelcode ORDER BY level_code ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
 $emplevels[] = $row;
 $emplevels_map[$row['level_code']] = $row['level_id'];
}
$stmt->close();
} catch (Exception $e) {
error_log("Failed to fetch EmplevelCodes: " . $e->getMessage());
$emplevels = [];
}

$language_files = [];
$lang_dir = __DIR__ . '/../../languages/'; 
if (is_dir($lang_dir)) {
$files = scandir($lang_dir);
foreach ($files as $file) {
 if (preg_match('/^([a-z]{2})\.php$/', $file, $matches)) {
 $lang_code = $matches[1];
 $language_files[$lang_code] = $lang_code;
 }
}
ksort($language_files);
}

// กำหนดค่าเริ่มต้นสำหรับฟอร์ม
$test_name = '';
$description = '';
$is_published = 0;
$duration_minutes = 0;
$min_passing_score = 0;
$creation_year = date("Y");
$test_no = '';
$language = 'en'; // ค่าเริ่มต้น
$category_type_id = null;
$selected_emplevel_ids = [];
$selected_orgunitnames = [];
$selected_role_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
if (!verify_csrf_token()) {
 set_alert(get_text('error_csrf_token_invalid'), "danger");
 header("Location: /admin/create-test");
 exit();
}

$test_name = trim($_POST['test_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$is_published = isset($_POST['is_published']) ? 1 : 0;
$duration_minutes = filter_var($_POST['duration_minutes'] ?? 0, FILTER_VALIDATE_INT, array("options" => array("min_range"=>0)));
$min_passing_score = (int)($_POST['min_passing_score'] ?? 0);
$creation_year = (int)($_POST['creation_year'] ?? date("Y"));
$test_no = trim($_POST['test_no'] ?? '');
$language = trim($_POST['language'] ?? 'en');

// ดึงรายการ emplevel_code ที่ถูกเลือกมาเป็น array
$emplevel_codes_selected = $_POST['EmplevelCode'] ?? [];
// ดึง role_id ที่ถูกเลือก (ได้แค่ 1 ค่า)
$role_id_selected = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
// ดึงรายการ orgunitname ที่ถูกเลือก
$orgunitname_selected = $_POST['Orgunitname'] ?? [];
$created_by_user_id = $_SESSION['user_id'] ?? null;
$category_type_id = !empty($_POST['category_type_id']) ? (int)$_POST['category_type_id'] : null;

if (empty($test_name)) {
 set_alert(get_text('alert_test_name_required'), "danger");
} elseif ($duration_minutes === false) {
 set_alert(get_text('alert_invalid_duration_format'), "danger");
} elseif (!array_key_exists($language, $language_files)) {
 set_alert(get_text('alert_invalid_language_selected'), "danger");
} else {
 try {
  
  $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
  $unpublished_at = !empty($_POST['unpublished_at']) ? $_POST['unpublished_at'] : null;

  // ใช้ INSERT INTO แทน UPDATE
  $stmt = $conn->prepare("INSERT INTO iga_tests (test_name, description, is_published, duration_minutes, min_passing_score, creation_year, test_no, language, category_type_id, role_id, created_by_user_id, published_at, unpublished_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
  
  if (!$stmt) {
      error_log("Prepare failed in create_test.php: " . $conn->error);
      set_alert(get_text('error_technical', ['DB Prepare Error: ' . $conn->error]), "danger");
      header("Location: /admin/create-test");
      exit();
  }

  $stmt->bind_param("ssiiiissiiiss", $test_name, $description, $is_published, $duration_minutes, $min_passing_score, $creation_year, $test_no, $language, $category_type_id, $role_id_selected, $created_by_user_id, $published_at, $unpublished_at);

  if ($stmt->execute()) {
  $new_test_id = $stmt->insert_id;
  $stmt->close();

  // Step 1: Insert the new selected emplevels
  if (!empty($emplevel_codes_selected)) {
   $stmt_insert = $conn->prepare("INSERT INTO iga_test_emplevels (test_id, level_id) VALUES (?, ?)");
   foreach ($emplevel_codes_selected as $selected_code) {
   if (isset($emplevels_map[$selected_code])) {
    $level_id_to_insert = $emplevels_map[$selected_code];
    $stmt_insert->bind_param("ii", $new_test_id, $level_id_to_insert);
    $stmt_insert->execute();
   }
   }
   $stmt_insert->close();
  }
  
  // เพิ่ม orgunitname ที่ถูกเลือก
  if (!empty($orgunitname_selected)) {
    $stmt_insert_orgs = $conn->prepare("INSERT INTO iga_test_orgunits (test_id, orgunitname) VALUES (?, ?)");
    foreach ($orgunitname_selected as $unitname) {
      $stmt_insert_orgs->bind_param("is", $new_test_id, $unitname);
      $stmt_insert_orgs->execute();
    }
    $stmt_insert_orgs->close();
  }

  set_alert(get_text('alert_create_test_success', [$test_name]), "success");
  header("Location: /admin/tests");
  exit();
  } else {
  set_alert(get_text('error_create_test_failed', [$stmt->error]), "danger");
  $stmt->close();
  }
 } catch (Exception $e) {
  set_alert(get_text('error_technical', [$e->getMessage()]), "danger");
 }
 }
 header("Location: /admin/create-test");
 exit();
}


?>

<style>
  /* สีพื้นหลังของ card และปุ่ม */
  .card.shadow-lg {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
  }
  .btn-primary-custom {
    background-color: #6a89cc; /* Soft Blue */
    border-color: #6a89cc;
  }
  .btn-primary-custom:hover {
    background-color: #5579b3;
    border-color: #5579b3;
  }
  .btn-secondary {
    background-color: #a5d8d8; /* Soft Teal */
    border-color: #a5d8d8;
  }
  .btn-secondary:hover {
    background-color: #8bbaba;
    border-color: #8bbaba;
  }

  /* ปรับแต่ง Select2 ให้เข้ากับธีม */
  .select2-container--default .select2-selection--multiple {
    border-color: #ced4da;
  }
  .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #6a89cc;
    box-shadow: 0 0 0 0.25rem rgba(106, 137, 204, 0.25);
  }
  .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #f7d794; /* Soft Yellow */
    border-color: #f7d794;
    color: #333;
  }
  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #f7d794 !important;
  }
  
  /* สีหัวข้อและป้ายกำกับ */
  .text-primary-custom {
    color: #6a89cc !important;
  }
  .form-label {
    color: #6a89cc;
  }
</style>

<div class="container py-4">
<h1 class="mb-4 text-primary-custom"><?php echo get_text('create_new_test_title'); ?></h1>

<?php echo get_alert(); ?>

<div class="card shadow-lg p-4">
 <div class="card-body">
 <form action="/admin/create-test" method="POST">
  <?php echo generate_csrf_token(); ?>
  <div class="row">
  <div class="col-md-8">
   <div class="mb-3">
   <label for="test_name" class="form-label"><?php echo get_text('label_test_name'); ?> <span class="text-danger">*</span></label>
   <input type="text" class="form-control" id="test_name" name="test_name" value="<?php echo htmlspecialchars($test_name); ?>" required>
   </div>
  </div>
  <div class="col-md-4">
   <div class="mb-3">
   <label for="category_type_id" class="form-label"><?php echo get_text('category_type'); ?></label>
   <select class="form-select" id="category_type_id" name="category_type_id">
    <option value=""><?php echo get_text('not_selected'); ?></option>
    <?php foreach ($categoryTypes as $type) { 
    $selected = (isset($category_type_id) && $category_type_id == $type['type_id']) ? 'selected' : '';
    echo '<option value="' . $type['type_id'] . '" ' . $selected . '>';
    echo htmlspecialchars($type['type_name']);
    echo '</option>';
    } ?>
   </select>
   <div class="form-text"><?php echo get_text('select_category_type_hint'); ?> <?php echo get_text('optional'); ?></div>
   </div>
  </div>
  </div>
  
  <div class="mb-3">
    <label for="orgunitname" class="form-label"><?php echo get_text('label_orgunitname'); ?></label>
    <select class="form-select orgunitname-select2" id="orgunitname" name="Orgunitname[]" multiple="multiple">
      <?php foreach ($orgunitnames as $unitname): ?>
        <option value="<?php echo htmlspecialchars($unitname); ?>"
          <?php echo in_array($unitname, $selected_orgunitnames) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($unitname); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="form-text text-muted"><?php echo get_text('hint_multiple_select_orgunits'); ?></small>
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
  <label for="test_no" class="form-label"><?php echo get_text('label_test_group_number'); ?></label>
  <input type="text" class="form-control" id="test_no" name="test_no" value="<?php echo htmlspecialchars($test_no ?? ''); ?>" placeholder="<?php echo get_text('placeholder_optional_test_group'); ?>">
  <small class="form-text text-muted"><?php echo get_text('hint_test_no'); ?></small>
  </div>
  
  <div class="mb-3">
  <label for="language" class="form-label"><?php echo get_text('label_language'); ?></label>
  <select class="form-select" id="language" name="language" required>
   <?php foreach ($language_files as $code => $name): ?>
   <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ($code === $language) ? 'selected' : ''; ?>>
    <?php
    echo htmlspecialchars(get_text('language_name_' . $code) ?: $name);
    ?>
   </option>
   <?php endforeach; ?>
  </select>
  <small class="form-text text-muted"><?php echo get_text('hint_test_language'); ?></small>
  </div>
  
  <div class="mb-3">
    <label for="emplevel_codes" class="form-label"><?php echo get_text('label_emplevel'); ?></label>
    <select class="form-select emplevel-select2" id="emplevel_codes" name="EmplevelCode[]" multiple="multiple">
      <?php foreach ($emplevels as $level): 
        $emplevel_code = htmlspecialchars($level['level_code']);
        $is_selected = in_array($level['level_id'], $selected_emplevel_ids);
      ?>
        <option value="<?php echo $emplevel_code; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
          <?php echo $emplevel_code; ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="form-text text-muted"><?php echo get_text('hint_multiple_select'); ?></small>
  </div>
  
  <div class="mb-3">
    <label for="role_id" class="form-label"><?php echo get_text('EmpType'); ?></label>
    <select class="form-select" id="role_id" name="role_id">
      <option value=""><?php echo get_text('not_selected'); ?></option>
      <?php foreach ($roles as $role): ?>
        <option value="<?php echo htmlspecialchars($role['role_id']); ?>"
          <?php echo (isset($selected_role_id) && $selected_role_id == $role['role_id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($role['role_name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="form-text text-muted">
      <?php echo get_text('hint_single_select_role'); ?>
    </small>
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
  <a href="/admin/tests" class="btn btn-secondary">
   <i class="fas fa-arrow-alt-circle-left me-2"></i> <?php echo get_text('back_button'); ?>
  </a>
  </div>
 </form>
 </div>
</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
  $('.orgunitname-select2').select2({
    placeholder: '<?php echo get_text('select_orgunitname_placeholder'); ?>',
    allowClear: true
  });
  
  // เปิดใช้งาน Select2 สำหรับ EmplevelCode
  $('.emplevel-select2').select2({
    placeholder: '<?php echo get_text('select_emplevel_placeholder'); ?>',
    allowClear: true
  });
});
</script>