<?php
// ** 1. จัดการคำขอ AJAX ก่อนการโหลดส่วนหัวของหน้าเว็บ **
// ส่วนนี้ต้องอยู่บนสุดของไฟล์ เพื่อให้มั่นใจว่าจะไม่มี HTML ใดๆ ถูกส่งออกมาก่อน JSON
if (isset($_GET['action']) && $_GET['action'] == 'fetch_data') {
    // เริ่มต้น Output Buffering เพื่อจับเอาต์พุตที่ไม่พึงประสงค์ทั้งหมด
    ob_start();

    // โหลดไฟล์ที่จำเป็นสำหรับการเชื่อมต่อฐานข้อมูลเท่านั้น
    require_once __DIR__ . '/../../includes/db_connect.php';
    require_once __DIR__ . '/../../includes/functions.php'; // โหลด functions.php ถ้าจำเป็น

    // สร้าง Object ที่จำเป็น
    $categoryManager = new CategoryManager($conn);
    $categoryTypeManager = new CategoryTypeManager($conn);

    header('Content-Type: application/json');

    $search_query_categories = $_GET['search_categories'] ?? '';
    $filter_type_id = $_GET['filter_type'] ?? '';
    $current_page_categories = isset($_GET['page_cat']) && is_numeric($_GET['page_cat']) ? (int)$_GET['page_cat'] : 1;
    
    $search_query_types = $_GET['search_types'] ?? '';
    $current_page_types = isset($_GET['page_type']) && is_numeric($_GET['page_type']) ? (int)$_GET['page_type'] : 1;

    $categories_data = renderCategoriesTableAndPagination($categoryManager, $search_query_categories, $filter_type_id, $current_page_categories);
    $types_data = renderTypesTableAndPagination($categoryTypeManager, $search_query_types, $current_page_types);

    // ล้างบัฟเฟอร์ก่อนส่งข้อมูล JSON
    ob_end_clean();

    echo json_encode([
        'categories_html' => $categories_data['table_html'],
        'categories_pagination_html' => $categories_data['pagination_html'],
        'total_categories' => $categories_data['total_items'],
        'types_html' => $types_data['table_html'],
        'types_pagination_html' => $types_data['pagination_html'],
        'total_types' => $types_data['total_items']
    ]);
    
    // สำคัญ: ต้องหยุดการทำงานของสคริปต์ทันทีที่ส่งข้อมูล JSON เสร็จแล้ว
    exit();
}

// ** 2. โค้ดส่วนที่เหลือ (สำหรับการแสดงผลหน้าเว็บเต็มรูปแบบ) **
// ใช้ header.php สำหรับการโหลดหน้าเว็บปกติเท่านั้น
require_once __DIR__ . '/../../includes/header.php';

// ** คลาสสำหรับจัดการข้อมูลหมวดหมู่และประเภทหมวดหมู่ (รวมไว้ในไฟล์เดียว) **
class CategoryManager {
    private $conn;
    public function __construct($db) { $this->conn = $db; }
    public function getAllQuestionCategories($search_query = '', $filter_type = '', $offset = 0, $limit = 10) {
        $query = "
            SELECT qc.category_id, qc.category_name, qc.category_description, qc.category_type_id, ct.type_name AS category_type 
            FROM iga_question_categories qc
            JOIN iga_category_types ct ON qc.category_type_id = ct.type_id
        ";
        $where_clauses = [];
        $params = [];
        $types = '';
        if (!empty($search_query)) {
            $where_clauses[] = "qc.category_name LIKE ?";
            $params[] = '%' . $search_query . '%';
            $types .= 's';
        }
        if (!empty($filter_type)) {
            $where_clauses[] = "qc.category_type_id = ?";
            $params[] = $filter_type;
            $types .= 'i';
        }
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $query .= " ORDER BY qc.category_id DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
             return false;
        }
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    public function getCategoryCount($search_query = '', $filter_type = '') {
        $query = "SELECT COUNT(*) AS count FROM iga_question_categories qc JOIN iga_category_types ct ON qc.category_type_id = ct.type_id";
        $where_clauses = [];
        $params = [];
        $types = '';
        if (!empty($search_query)) {
            $where_clauses[] = "qc.category_name LIKE ?";
            $params[] = '%' . $search_query . '%';
            $types .= 's';
        }
        if (!empty($filter_type)) {
            $where_clauses[] = "qc.category_type_id = ?";
            $params[] = $filter_type;
            $types .= 'i';
        }
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
             return 0;
        }
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    public function isCategoryInUse($category_id) {
        $query = "SELECT COUNT(*) AS count FROM iga_questions WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ($result['count'] > 0);
    }
    public function addQuestionCategory($category_name, $category_description, $category_type_id) {
        $query = "INSERT INTO iga_question_categories (category_name, category_description, category_type_id) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $category_name, $category_description, $category_type_id);
        return $stmt->execute();
    }
    public function updateQuestionCategory($category_id, $category_name, $category_description, $category_type_id) {
        $query = "UPDATE iga_question_categories SET category_name = ?, category_description = ?, category_type_id = ? WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssii", $category_name, $category_description, $category_type_id, $category_id);
        return $stmt->execute();
    }
    public function deleteQuestionCategory($category_id) {
        if ($this->isCategoryInUse($category_id)) { return false; }
        $query = "DELETE FROM iga_question_categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        return $stmt->execute();
    }
}

class CategoryTypeManager {
    private $conn;
    public function __construct($db) { $this->conn = $db; }
    public function getAllCategoryTypes($search_query = '', $offset = 0, $limit = 10) {
        $query = "SELECT type_id, type_name, type_description, created_at FROM iga_category_types";
        $where_clauses = [];
        $params = [];
        $types = '';
        if (!empty($search_query)) {
            $where_clauses[] = "type_name LIKE ?";
            $params[] = '%' . $search_query . '%';
            $types .= 's';
        }
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
             return false;
        }
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    public function getCategoryTypeCount($search_query = '') {
        $query = "SELECT COUNT(*) AS count FROM iga_category_types";
        $where_clauses = [];
        $params = [];
        $types = '';
        if (!empty($search_query)) {
            $where_clauses[] = "type_name LIKE ?";
            $params[] = '%' . $search_query . '%';
            $types .= 's';
        }
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(" AND ", $where_clauses);
        }
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
             return 0;
        }
        if (!empty($params)) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    public function isCategoryTypeInUse($type_id) {
        $query = "SELECT COUNT(*) as count FROM iga_question_categories WHERE category_type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $type_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ($result['count'] > 0);
    }
    public function addCategoryType($type_name, $type_description) {
        $query = "INSERT INTO iga_category_types (type_name, type_description) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $type_name, $type_description);
        return $stmt->execute();
    }
    public function updateCategoryType($type_id, $type_name, $type_description) {
        $query = "UPDATE iga_category_types SET type_name = ?, type_description = ? WHERE type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $type_name, $type_description, $type_id);
        return $stmt->execute();
    }
    public function deleteCategoryType($type_id) {
        if ($this->isCategoryTypeInUse($type_id)) { return false; }
        $query = "DELETE FROM iga_category_types WHERE type_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $type_id);
        return $stmt->execute();
    }
}

// โค้ดสำหรับสร้าง HTML ของตารางและ Pagination
function renderCategoriesTableAndPagination($categoryManager, $search_query_categories, $filter_type_id, $current_page_categories) {
    $items_per_page = 5;
    $offset_categories = ($current_page_categories - 1) * $items_per_page;
    $total_items_categories = $categoryManager->getCategoryCount($search_query_categories, $filter_type_id);
    $total_pages_categories = ceil($total_items_categories / $items_per_page);
    $questionCategories = [];
    $categoriesResult = $categoryManager->getAllQuestionCategories($search_query_categories, $filter_type_id, $offset_categories, $items_per_page);
    if ($categoriesResult) {
        while ($row = $categoriesResult->fetch_assoc()) {
            $questionCategories[] = $row;
        }
    }
    ob_start();
    ?>
    <?php if (!empty($questionCategories)) : ?>
        <?php foreach ($questionCategories as $category) : ?>
            <tr>
                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                <td><?php echo htmlspecialchars($category['category_type']); ?></td>
                <td>
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm edit-category-btn" data-bs-toggle="modal" data-bs-target="#editCategoryModal" 
                            data-id="<?php echo htmlspecialchars($category['category_id']); ?>" 
                            data-name="<?php echo htmlspecialchars($category['category_name']); ?>" 
                            data-type-id="<?php echo htmlspecialchars($category['category_type_id']); ?>"
                            data-description="<?php echo htmlspecialchars($category['category_description']); ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm delete-category-btn" data-bs-toggle="modal" data-bs-target="#deleteCategoryConfirmModal" data-id="<?php echo htmlspecialchars($category['category_id']); ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else : ?>
        <tr>
            <td colspan="3" class="text-center"><?php echo get_text('no_categories_found'); ?></td>
        </tr>
    <?php endif; ?>
    <?php
    $table_html = ob_get_clean();
    ob_start();
    ?>
    <?php if ($total_pages_categories > 1) : ?>
        <li class="page-item <?php echo $current_page_categories <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-page="<?php echo max(1, $current_page_categories - 1); ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = 1; $i <= $total_pages_categories; $i++) : ?>
            <li class="page-item <?php echo $i == $current_page_categories ? 'active' : ''; ?>">
                <a class="page-link" href="#" data-page="<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $current_page_categories >= $total_pages_categories ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-page="<?php echo min($total_pages_categories, $current_page_categories + 1); ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    <?php endif; ?>
    <?php
    $pagination_html = ob_get_clean();
    return ['table_html' => $table_html, 'pagination_html' => $pagination_html, 'total_items' => $total_items_categories];
}

function renderTypesTableAndPagination($categoryTypeManager, $search_query_types, $current_page_types) {
    $items_per_page = 5;
    $offset_types = ($current_page_types - 1) * $items_per_page;
    $total_items_types = $categoryTypeManager->getCategoryTypeCount($search_query_types);
    $total_pages_types = ceil($total_items_types / $items_per_page);
    $categoryTypesWithPagination = [];
    $typesResultForTable = $categoryTypeManager->getAllCategoryTypes($search_query_types, $offset_types, $items_per_page);
    if ($typesResultForTable) {
        while ($row = $typesResultForTable->fetch_assoc()) {
            $categoryTypesWithPagination[] = $row;
        }
    }
    ob_start();
    ?>
    <?php if (!empty($categoryTypesWithPagination)) : ?>
        <?php foreach ($categoryTypesWithPagination as $type) : ?>
            <tr>
                <td><?php echo htmlspecialchars($type['type_name']); ?></td>
                <td>
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm edit-type-btn" data-bs-toggle="modal" data-bs-target="#editTypeModal" 
                            data-id="<?php echo htmlspecialchars($type['type_id']); ?>" 
                            data-name="<?php echo htmlspecialchars($type['type_name']); ?>"
                            data-description="<?php echo htmlspecialchars($type['type_description']); ?>">  
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm delete-type-btn" data-bs-toggle="modal" data-bs-target="#deleteTypeConfirmModal" data-id="<?php echo htmlspecialchars($type['type_id']); ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else : ?>
        <tr>
            <td colspan="2" class="text-center"><?php echo get_text('no_categories_found'); ?></td>
        </tr>
    <?php endif; ?>
    <?php
    $table_html = ob_get_clean();
    ob_start();
    ?>
    <?php if ($total_pages_types > 1) : ?>
        <li class="page-item <?php echo $current_page_types <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-page="<?php echo max(1, $current_page_types - 1); ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = 1; $i <= $total_pages_types; $i++) : ?>
            <li class="page-item <?php echo $i == $current_page_types ? 'active' : ''; ?>">
                <a class="page-link" href="#" data-page="<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $current_page_types >= $total_pages_types ? 'disabled' : ''; ?>">
            <a class="page-link" href="#" data-page="<?php echo min($total_pages_types, $current_page_types + 1); ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    <?php endif; ?>
    <?php
    $pagination_html = ob_get_clean();
    return ['table_html' => $table_html, 'pagination_html' => $pagination_html, 'total_items' => $total_items_types];
}

$page_title = get_text('manage_categories');
$categoryManager = new CategoryManager($conn);
$categoryTypeManager = new CategoryTypeManager($conn);

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
require_login();
if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment') ) {
    set_alert(get_text('alert_no_admin_permission'), "danger");
    header("Location: login");
    exit();
}

// ส่วนจัดการ POST Request (Add, Edit, Delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'add_category':
                $category_name = trim($_POST['category_name'] ?? '');
                $category_type_id = isset($_POST['category_type_id']) ? (int)$_POST['category_type_id'] : 0;
                $category_description = trim($_POST['category_description'] ?? '');
                if (empty($category_name) || $category_type_id <= 0) {
                    set_alert('กรุณากรอกชื่อหมวดหมู่และเลือกประเภท', 'danger');
                } else {
                    $result = $categoryManager->addQuestionCategory($category_name, $category_description, $category_type_id);
                    if ($result) { set_alert(get_text('alert_category_added_success'), 'success'); }
                    else { set_alert(get_text('error_category_added'), 'danger'); }
                }
                break;
            case 'edit_category':
                $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
                $category_name = trim($_POST['category_name'] ?? '');
                $category_type_id = isset($_POST['category_type_id']) ? (int)$_POST['category_type_id'] : 0;
                $category_description = trim($_POST['category_description'] ?? '');
                if (empty($category_name) || $category_type_id <= 0 || $category_id <= 0) {
                    set_alert('กรุณากรอกข้อมูลให้ครบถ้วน', 'danger');
                } else {
                    $result = $categoryManager->updateQuestionCategory($category_id, $category_name, $category_description, $category_type_id);
                    if ($result) { set_alert(get_text('alert_category_updated_success'), 'success'); }
                    else { set_alert(get_text('error_category_updated'), 'danger'); }
                }
                break;
            case 'delete_category':
                $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
                if ($category_id <= 0) {
                    set_alert('ไม่พบรหัสหมวดหมู่ที่ต้องการลบ', 'danger');
                } else {
                    $result = $categoryManager->deleteQuestionCategory($category_id);
                    if ($result) { set_alert(get_text('alert_category_deleted_success'), 'success'); }
                    else {
                        if ($categoryManager->isCategoryInUse($category_id)) { set_alert(get_text('alert_category_in_use'), 'danger'); }
                        else { set_alert(get_text('error_category_deleted'), 'danger'); }
                    }
                }
                break;
            case 'add_type':
                $type_name = trim($_POST['type_name'] ?? '');
                $type_description = trim($_POST['type_description'] ?? '');
                if (empty($type_name)) { set_alert(get_text('error_required_fields'), 'danger'); }
                else {
                    $result = $categoryTypeManager->addCategoryType($type_name, $type_description);
                    if ($result) { set_alert(get_text('alert_category_added_success'), 'success'); }
                    else { set_alert(get_text('error_category_added'), 'danger'); }
                }
                break;
            case 'edit_type':
                $type_id = filter_var($_POST['type_id'] ?? 0, FILTER_VALIDATE_INT);
                $type_name = trim($_POST['type_name'] ?? '');
                $type_description = trim($_POST['type_description'] ?? '');
                if (empty($type_name) || empty($type_id)) { set_alert(get_text('error_required_fields'), 'danger'); }
                else {
                    $result = $categoryTypeManager->updateCategoryType($type_id, $type_name, $type_description);
                    if ($result) { set_alert(get_text('alert_category_updated_success'), 'success'); }
                    else { set_alert(get_text('error_category_updated'), 'danger'); }
                }
                break;
            case 'delete_type':
                $type_id = filter_var($_POST['type_id'] ?? 0, FILTER_VALIDATE_INT);
                if (empty($type_id)) { set_alert(get_text('error_category_id_required'), 'danger'); }
                else {
                    $result = $categoryTypeManager->deleteCategoryType($type_id);
                    if ($result) { set_alert(get_text('alert_category_deleted_success'), 'success'); }
                    else {
                        if ($categoryTypeManager->isCategoryTypeInUse($type_id)) { set_alert('ไม่สามารถลบประเภทหมวดหมู่นี้ได้ เนื่องจากมีการใช้งานอยู่', 'danger'); }
                        else { set_alert(get_text('error_category_deleted'), 'danger'); }
                    }
                }
                break;
            default:
                set_alert(get_text('error_invalid_action'), 'danger');
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        set_alert(get_text('error_database_operation', [htmlspecialchars($e->getMessage())]), 'danger');
    }
}

$categoryTypesForDropdown = [];
$typesResultForDropdown = $categoryTypeManager->getAllCategoryTypes(); 
if ($typesResultForDropdown) {
    while ($row = $typesResultForDropdown->fetch_assoc()) {
        $categoryTypesForDropdown[] = $row;
    }
}

$categories_data = renderCategoriesTableAndPagination($categoryManager, '', '', 1);
$types_data = renderTypesTableAndPagination($categoryTypeManager, '', 1);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
</head>
<body>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4"><?php echo htmlspecialchars($page_title); ?></h1>
            <?php echo get_alert(); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4"><?php echo get_text('list_of_categories'); ?></h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i> <?php echo get_text('add_category'); ?>
                </button>
            </div>
            
            <div class="row g-2 mb-3">
                <div class="col-md-7">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search_categories_input" placeholder="<?php echo get_text('search_placeholder'); ?>...">
                    </div>
                </div>
                <div class="col-md-5">
                    <select class="form-select" id="filter_type_select">
                        <option value=""><?php echo get_text('filter_all'); ?></option>
                        <?php foreach ($categoryTypesForDropdown as $type) : ?>
                            <option value="<?php echo htmlspecialchars($type['type_id']); ?>">
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo get_text('categories'); ?> (<span id="category_count"><?php echo $categories_data['total_items']; ?></span>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo get_text('category_name'); ?></th>
                                    <th scope="col"><?php echo get_text('category_type'); ?></th>
                                    <th scope="col"><?php echo get_text('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="categories_table_body">
                                <?php echo $categories_data['table_html']; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <nav aria-label="Page navigation for categories">
                <ul class="pagination justify-content-center" id="categories_pagination">
                    <?php echo $categories_data['pagination_html']; ?>
                </ul>
            </nav>
        </div>
        
        <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4"><?php echo get_text('list_of_types'); ?></h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                    <i class="fas fa-plus me-2"></i> <?php echo get_text('add_category_type'); ?>
                </button>
            </div>
            
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="search_types_input" placeholder="<?php echo get_text('search'); ?>...">
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo get_text('types'); ?> (<span id="type_count"><?php echo $types_data['total_items']; ?></span>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo get_text('type_name'); ?></th>
                                    <th scope="col"><?php echo get_text('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="types_table_body">
                                <?php echo $types_data['table_html']; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <nav aria-label="Page navigation for types">
                <ul class="pagination justify-content-center" id="types_pagination">
                    <?php echo $types_data['pagination_html']; ?>
                </ul>
            </nav>
        </div>
    </div>

    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel"><?php echo get_text('add_category'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_category">
                        <div class="mb-3">
                            <label for="addCategoryName" class="form-label"><?php echo get_text('form_category_name'); ?></label>
                            <input type="text" class="form-control" id="addCategoryName" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="addCategoryTypeId" class="form-label"><?php echo get_text('form_category_type'); ?></label>
                            <select class="form-control" id="addCategoryTypeId" name="category_type_id" required>
                                <option value="">- เลือกประเภทหมวดหมู่ -</option>
                                <?php foreach ($categoryTypesForDropdown as $type) : ?>
                                    <option value="<?php echo htmlspecialchars($type['type_id']); ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addCategoryDescription" class="form-label"><?php echo get_text('form_category_description'); ?></label>
                            <textarea class="form-control" id="addCategoryDescription" name="category_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo get_text('save'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel"><?php echo get_text('edit_category'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCategoryForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_category">
                        <input type="hidden" id="editCategoryId" name="category_id">
                        <div class="mb-3">
                            <label for="editCategoryName" class="form-label"><?php echo get_text('form_category_name'); ?></label>
                            <input type="text" class="form-control" id="editCategoryName" name="category_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCategoryTypeId" class="form-label"><?php echo get_text('form_category_type'); ?></label>
                            <select class="form-control" id="editCategoryTypeId" name="category_type_id" required>
                                <option value="">- เลือกประเภทหมวดหมู่ -</option>
                                <?php foreach ($categoryTypesForDropdown as $type) : ?>
                                    <option value="<?php echo htmlspecialchars($type['type_id']); ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCategoryDescription" class="form-label"><?php echo get_text('form_category_description'); ?></label>
                            <textarea class="form-control" id="editCategoryDescription" name="category_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo get_text('save'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="deleteCategoryConfirmModal" tabindex="-1" aria-labelledby="deleteCategoryConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCategoryConfirmModalLabel"><?php echo get_text('confirm_deletion'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo get_text('confirm_deletion_message'); ?></p>
                </div>
                <div class="modal-footer">
                    <form id="deleteCategoryForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" id="deleteCategoryId" name="category_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel_button'); ?></button>
                        <button type="submit" class="btn btn-danger"><?php echo get_text('delete'); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addTypeModal" tabindex="-1" aria-labelledby="addTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTypeModalLabel"><?php echo get_text('add_category_type'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_type">
                        <div class="mb-3">
                            <label for="addTypeName" class="form-label"><?php echo get_text('form_category_name'); ?></label>
                            <input type="text" class="form-control" id="addTypeName" name="type_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="addTypeDescription" class="form-label"><?php echo get_text('form_category_description'); ?></label>
                            <textarea class="form-control" id="addTypeDescription" name="type_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo get_text('save'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editTypeModal" tabindex="-1" aria-labelledby="editTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTypeModalLabel"><?php echo get_text('edit_category'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_type">
                        <input type="hidden" id="editTypeId" name="type_id">
                        <div class="mb-3">
                            <label for="editTypeName" class="form-label"><?php echo get_text('form_category_name'); ?></label>
                            <input type="text" class="form-control" id="editTypeName" name="type_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTypeDescription" class="form-label"><?php echo get_text('form_category_description'); ?></label>
                            <textarea class="form-control" id="editTypeDescription" name="type_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel'); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo get_text('save'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="deleteTypeConfirmModal" tabindex="-1" aria-labelledby="deleteTypeConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteTypeConfirmModalLabel"><?php echo get_text('confirm_deletion'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo get_text('confirm_deletion_message'); ?></p>
                </div>
                <div class="modal-footer">
                    <form id="deleteTypeForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <input type="hidden" name="action" value="delete_type">
                        <input type="hidden" id="deleteTypeId" name="type_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo get_text('cancel'); ?></button>
                        <button type="submit" class="btn btn-danger"><?php echo get_text('delete'); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/js/bootstrap.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoriesTable = document.getElementById('categories_table_body');
        const typesTable = document.getElementById('types_table_body');
        const categoriesPagination = document.getElementById('categories_pagination');
        const typesPagination = document.getElementById('types_pagination');

        const searchCategoriesInput = document.getElementById('search_categories_input');
        const filterTypeSelect = document.getElementById('filter_type_select');
        const searchTypesInput = document.getElementById('search_types_input');
        
        const categoryCount = document.getElementById('category_count');
        const typeCount = document.getElementById('type_count');

        let timeout = null;

        // ฟังก์ชันสำหรับเรียกข้อมูลแบบ AJAX
        function fetchData(page = 1, searchQuery = '', filterType = '', tableType = 'categories') {
            const url = `<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=fetch_data&${tableType === 'categories' ? `page_cat=${page}&search_categories=${encodeURIComponent(searchQuery)}&filter_type=${encodeURIComponent(filterType)}` : `page_type=${page}&search_types=${encodeURIComponent(searchQuery)}`}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    // ตรวจสอบ Content-Type ก่อน parse
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.indexOf('application/json') !== -1) {
                        return response.json();
                    } else {
                        // ถ้าไม่ใช่ JSON แสดงว่ามีปัญหา
                        throw new Error('Server did not return a valid JSON response.');
                    }
                })
                .then(data => {
                    if (tableType === 'categories') {
                        categoriesTable.innerHTML = data.categories_html;
                        categoriesPagination.innerHTML = data.categories_pagination_html;
                        // อัปเดตตัวเลขจำนวนหมวดหมู่
                        const newCategoryCount = document.getElementById('category_count');
                        if (newCategoryCount) {
                            newCategoryCount.textContent = data.total_categories;
                        }
                    } else {
                        typesTable.innerHTML = data.types_html;
                        typesPagination.innerHTML = data.types_pagination_html;
                        // อัปเดตตัวเลขจำนวนประเภทหมวดหมู่
                        const newTypeCount = document.getElementById('type_count');
                        if (newTypeCount) {
                            newTypeCount.textContent = data.total_types;
                        }
                    }
                })
                .catch(error => console.error(`Error fetching ${tableType} data:`, error));
        }

        // Live Search และ Filter สำหรับ Categories
        searchCategoriesInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchQuery = this.value;
                const filterType = filterTypeSelect.value;
                fetchData(1, searchQuery, filterType, 'categories');
            }, 300);
        });

        filterTypeSelect.addEventListener('change', function() {
            const searchQuery = searchCategoriesInput.value;
            const filterType = this.value;
            fetchData(1, searchQuery, filterType, 'categories');
        });

        // Live Search สำหรับ Types
        searchTypesInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchQuery = this.value;
                fetchData(1, searchQuery, '', 'types');
            }, 300);
        });

        // Event Delegation สำหรับ Pagination ของ Categories
        categoriesPagination.addEventListener('click', function(e) {
            if (e.target.closest('a')) {
                e.preventDefault();
                const page = e.target.closest('a').getAttribute('data-page');
                if (page) {
                    const searchQuery = searchCategoriesInput.value;
                    const filterType = filterTypeSelect.value;
                    fetchData(page, searchQuery, filterType, 'categories');
                }
            }
        });

        // Event Delegation สำหรับ Pagination ของ Types
        typesPagination.addEventListener('click', function(e) {
            if (e.target.closest('a')) {
                e.preventDefault();
                const page = e.target.closest('a').getAttribute('data-page');
                if (page) {
                    const searchQuery = searchTypesInput.value;
                    fetchData(page, searchQuery, '', 'types');
                }
            }
        });

        // Event Delegation สำหรับปุ่ม แก้ไขและลบในตาราง Categories และ Types
        document.body.addEventListener('click', function(e) {
            if (e.target.closest('.edit-category-btn')) {
                const btn = e.target.closest('.edit-category-btn');
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const typeId = btn.getAttribute('data-type-id');
                const description = btn.getAttribute('data-description');
                
                const editForm = document.getElementById('editCategoryForm');
                editForm.querySelector('#editCategoryId').value = id;
                editForm.querySelector('#editCategoryName').value = name || '';
                editForm.querySelector('#editCategoryDescription').value = description || '';
                editForm.querySelector('#editCategoryTypeId').value = typeId;
            }

            if (e.target.closest('.delete-category-btn')) {
                const btn = e.target.closest('.delete-category-btn');
                const id = btn.getAttribute('data-id');
                document.getElementById('deleteCategoryId').value = id;
            }

            if (e.target.closest('.edit-type-btn')) {
                const btn = e.target.closest('.edit-type-btn');
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const description = btn.getAttribute('data-description');
                
                const editForm = document.getElementById('editTypeModal');
                editForm.querySelector('#editTypeId').value = id;
                editForm.querySelector('#editTypeName').value = name || '';
                editForm.querySelector('#editTypeDescription').value = description || '';
            }

            if (e.target.closest('.delete-type-btn')) {
                const btn = e.target.closest('.delete-type-btn');
                const id = btn.getAttribute('data-id');
                document.getElementById('deleteTypeId').value = id;
            }
        });
    });
</script>
</body>
</html>