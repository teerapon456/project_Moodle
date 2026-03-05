<?php
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../Models/CategoryModel.php';
require_once __DIR__ . '/../../../core/Security/SecureSession.php';

class AdminCategoryController
{
    private $pdo;
    private $categoryModel;

    public function __construct()
    {
        // Require Login and RBAC check for Admin functions
        if (!isset($_SESSION['user_id'])) {
            header("Location: /auth/login");
            exit;
        }

        // Simplistic role check for demonstration. Real implementation would use MyHR's core RBAC.
        // if (!has_role('admin') && !has_role('editor') && !has_role('Super_user_Recruitment'))
        $is_admin = true; // Temporary mock for Phase 1
        if (!$is_admin) {
            $_SESSION['alert'] = ['message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้', 'type' => 'danger'];
            header("Location: /dashboard");
            exit;
        }

        $db = new Database();
        $this->pdo = $db->getConnection();
        $this->categoryModel = new CategoryModel($this->pdo);
    }

    public function processRequest()
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'index';

        switch ($action) {
            case 'fetch_data':
                $this->fetchData();
                break;
            case 'add_category':
                $this->addCategory();
                break;
            case 'edit_category':
                $this->editCategory();
                break;
            case 'delete_category':
                $this->deleteCategory();
                break;
            case 'add_type':
                $this->addType();
                break;
            case 'edit_type':
                $this->editType();
                break;
            case 'delete_type':
                $this->deleteType();
                break;
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    private function index()
    {
        // Load data needed for initial view dropdowns
        $categoryTypesForDropdown = $this->categoryModel->getAllCategoryTypesList();

        // Let the frontend fetch actual lists via AJAX or render them server-side here.
        // For simplicity and matching legacy approach, we can render the first page server-side
        $page_title = "จัดการหมวดหมู่และประเภทหมวดหมู่";

        // Pass to View
        include __DIR__ . '/../Views/admin/manage_categories.php';
    }

    private function fetchData()
    {
        header('Content-Type: application/json');

        // Categories params
        $searchCategories = $_GET['search_categories'] ?? '';
        $filterType = $_GET['filter_type'] ?? '';
        $pageCat = isset($_GET['page_cat']) && is_numeric($_GET['page_cat']) ? (int)$_GET['page_cat'] : 1;

        // Types params
        $searchTypes = $_GET['search_types'] ?? '';
        $pageType = isset($_GET['page_type']) && is_numeric($_GET['page_type']) ? (int)$_GET['page_type'] : 1;

        // Fetch Data
        $categoryData = $this->renderCategoriesTable($searchCategories, $filterType, $pageCat);
        $typeData = $this->renderTypesTable($searchTypes, $pageType);

        echo json_encode([
            'categories_html' => $categoryData['html'],
            'categories_pagination_html' => $categoryData['pagination'],
            'total_categories' => $categoryData['total'],
            'types_html' => $typeData['html'],
            'types_pagination_html' => $typeData['pagination'],
            'total_types' => $typeData['total']
        ]);
        exit;
    }

    private function addCategory()
    {
        $name = trim($_POST['category_name'] ?? '');
        $typeId = (int)($_POST['category_type_id'] ?? 0);
        $desc = trim($_POST['category_description'] ?? '');

        if (empty($name) || $typeId <= 0) {
            $this->setAlert('กรุณากรอกชื่อหมวดหมู่และเลือกประเภท', 'danger');
        } else {
            if ($this->categoryModel->addCategory($name, $desc, $typeId)) {
                $this->setAlert('เพิ่มหมวดหมู่สำเร็จ', 'success');
            } else {
                $this->setAlert('เกิดข้อผิดพลาดในการเพิ่มหมวดหมู่', 'danger');
            }
        }
        $this->redirectBack();
    }

    private function editCategory()
    {
        $id = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');
        $typeId = (int)($_POST['category_type_id'] ?? 0);
        $desc = trim($_POST['category_description'] ?? '');

        if (empty($name) || $typeId <= 0 || $id <= 0) {
            $this->setAlert('กรุณากรอกข้อมูลให้ครบถ้วน', 'danger');
        } else {
            if ($this->categoryModel->updateCategory($id, $name, $desc, $typeId)) {
                $this->setAlert('อัปเดตหมวดหมู่สำเร็จ', 'success');
            } else {
                $this->setAlert('เกิดข้อผิดพลาดในการอัปเดตหมวดหมู่', 'danger');
            }
        }
        $this->redirectBack();
    }

    private function deleteCategory()
    {
        $id = (int)($_POST['category_id'] ?? 0);

        if ($id <= 0) {
            $this->setAlert('ไม่พบรหัสหมวดหมู่ที่ต้องการลบ', 'danger');
        } else {
            if ($this->categoryModel->deleteCategory($id)) {
                $this->setAlert('ลบหมวดหมู่สำเร็จ', 'success');
            } else {
                if ($this->categoryModel->isCategoryInUse($id)) {
                    $this->setAlert('ไม่สามารถลบหมวดหมู่นี้ได้ เนื่องจากมีการใช้งานอยู่', 'danger');
                } else {
                    $this->setAlert('เกิดข้อผิดพลาดในการลบหมวดหมู่', 'danger');
                }
            }
        }
        $this->redirectBack();
    }

    // -- Type Handlers --
    private function addType()
    {
        $name = trim($_POST['type_name'] ?? '');
        $desc = trim($_POST['type_description'] ?? '');

        if (empty($name)) {
            $this->setAlert('กรุณากรอกข้อมูลให้ครบถ้วน', 'danger');
        } else {
            if ($this->categoryModel->addCategoryType($name, $desc)) {
                $this->setAlert('เพิ่มประเภทหมวดหมู่สำเร็จ', 'success');
            } else {
                $this->setAlert('เกิดข้อผิดพลาดในการเพิ่มประเภท', 'danger');
            }
        }
        $this->redirectBack();
    }

    private function editType()
    {
        $id = (int)($_POST['type_id'] ?? 0);
        $name = trim($_POST['type_name'] ?? '');
        $desc = trim($_POST['type_description'] ?? '');

        if (empty($name) || $id <= 0) {
            $this->setAlert('กรุณากรอกข้อมูลให้ครบถ้วน', 'danger');
        } else {
            if ($this->categoryModel->updateCategoryType($id, $name, $desc)) {
                $this->setAlert('อัปเดตประเภทหมวดหมู่สำเร็จ', 'success');
            } else {
                $this->setAlert('เกิดข้อผิดพลาดในการอัปเดตประเภท', 'danger');
            }
        }
        $this->redirectBack();
    }

    private function deleteType()
    {
        $id = (int)($_POST['type_id'] ?? 0);

        if ($id <= 0) {
            $this->setAlert('ไม่พบรหัสที่ต้องการลบ', 'danger');
        } else {
            if ($this->categoryModel->deleteCategoryType($id)) {
                $this->setAlert('ลบประเภทสำเร็จ', 'success');
            } else {
                if ($this->categoryModel->isCategoryTypeInUse($id)) {
                    $this->setAlert('ไม่สามารถลบประเภทนี้ได้ เนื่องจากมีการใช้งานอยู่', 'danger');
                } else {
                    $this->setAlert('เกิดข้อผิดพลาดในการลบประเภท', 'danger');
                }
            }
        }
        $this->redirectBack();
    }


    // -- Helpers --
    private function setAlert($message, $type)
    {
        $_SESSION['alert'] = ['message' => $message, 'type' => $type];
    }

    private function redirectBack()
    {
        header("Location: /iga/admin_categories");
        exit;
    }

    // -- Table Rendering Logic (Extracted from old PHP script) --
    private function renderCategoriesTable($search, $filterType, $page)
    {
        $limit = 5;
        $offset = ($page - 1) * $limit;
        $total = $this->categoryModel->getCategoryCount($search, $filterType);
        $totalPages = ceil($total / $limit);
        $categories = $this->categoryModel->getAllCategories($search, $filterType, $offset, $limit);

        // Generate HTML
        ob_start();
        if (!empty($categories)) {
            foreach ($categories as $cat) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($cat['category_name']) . '</td>';
                echo '<td>' . htmlspecialchars($cat['category_type']) . '</td>';
                echo '<td><div class="d-flex gap-2">';
                echo '<button class="btn btn-warning btn-sm edit-category-btn" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-id="' . $cat['category_id'] . '" data-name="' . htmlspecialchars($cat['category_name']) . '" data-type-id="' . $cat['category_type_id'] . '" data-description="' . htmlspecialchars($cat['category_description'] ?? '') . '"><i class="fas fa-edit"></i></button>';
                echo '<button class="btn btn-danger btn-sm delete-category-btn" data-bs-toggle="modal" data-bs-target="#deleteCategoryConfirmModal" data-id="' . $cat['category_id'] . '"><i class="fas fa-trash-alt"></i></button>';
                echo '</div></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3" class="text-center">ไม่พบข้อมูล</td></tr>';
        }
        $html = ob_get_clean();

        // Generate Pagination
        ob_start();
        if ($totalPages > 1) {
            echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="#" data-page="' . max(1, $page - 1) . '"><i class="fas fa-chevron-left"></i></a></li>';
            for ($i = 1; $i <= $totalPages; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
            }
            echo '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="#" data-page="' . min($totalPages, $page + 1) . '"><i class="fas fa-chevron-right"></i></a></li>';
        }
        $pagination = ob_get_clean();

        return ['html' => $html, 'pagination' => $pagination, 'total' => $total];
    }

    private function renderTypesTable($search, $page)
    {
        $limit = 5;
        $offset = ($page - 1) * $limit;
        $total = $this->categoryModel->getCategoryTypeCount($search);
        $totalPages = ceil($total / $limit);
        $types = $this->categoryModel->getAllCategoryTypes($search, $offset, $limit);

        ob_start();
        if (!empty($types)) {
            foreach ($types as $type) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($type['type_name']) . '</td>';
                echo '<td><div class="d-flex gap-2">';
                echo '<button class="btn btn-warning btn-sm edit-type-btn" data-bs-toggle="modal" data-bs-target="#editTypeModal" data-id="' . $type['type_id'] . '" data-name="' . htmlspecialchars($type['type_name']) . '" data-description="' . htmlspecialchars($type['type_description'] ?? '') . '"><i class="fas fa-edit"></i></button>';
                echo '<button class="btn btn-danger btn-sm delete-type-btn" data-bs-toggle="modal" data-bs-target="#deleteTypeConfirmModal" data-id="' . $type['type_id'] . '"><i class="fas fa-trash-alt"></i></button>';
                echo '</div></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="2" class="text-center">ไม่พบข้อมูล</td></tr>';
        }
        $html = ob_get_clean();

        ob_start();
        if ($totalPages > 1) {
            echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '"><a class="page-link" href="#" data-page="' . max(1, $page - 1) . '"><i class="fas fa-chevron-left"></i></a></li>';
            for ($i = 1; $i <= $totalPages; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
            }
            echo '<li class="page-item ' . ($page >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="#" data-page="' . min($totalPages, $page + 1) . '"><i class="fas fa-chevron-right"></i></a></li>';
        }
        $pagination = ob_get_clean();

        return ['html' => $html, 'pagination' => $pagination, 'total' => $total];
    }
}
