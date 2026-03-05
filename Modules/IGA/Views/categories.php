<?php

/**
 * IGA Categories Management - Content Fragment
 * Loaded inside index.php layout via ?page=categories
 * Variables available: $pdo, $canManage, $user, $baseUrl
 */

if (!$canManage) {
    echo '<div class="alert alert-danger">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>';
    return;
}

// Load models
require_once __DIR__ . '/../Models/CategoryModel.php';
$categoryModel = new CategoryModel($pdo);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';

    switch ($action) {
        case 'add_category':
            $categoryModel->addCategory(
                $_POST['category_name'] ?? '',
                $_POST['category_description'] ?? '',
                $_POST['category_type_id'] ?? 0
            );
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'เพิ่มหมวดหมู่สำเร็จ'];
            header("Location: ?page=categories{$mid}");
            exit;

        case 'edit_category':
            $categoryModel->updateCategory(
                $_POST['category_id'] ?? 0,
                $_POST['category_name'] ?? '',
                $_POST['category_type_id'] ?? 0,
                $_POST['category_description'] ?? ''
            );
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'แก้ไขหมวดหมู่สำเร็จ'];
            header("Location: ?page=categories{$mid}");
            exit;

        case 'delete_category':
            try {
                $categoryModel->deleteCategory($_POST['category_id'] ?? 0);
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'ลบหมวดหมู่สำเร็จ'];
            } catch (Exception $e) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'ไม่สามารถลบหมวดหมู่ได้: ' . $e->getMessage()];
            }
            header("Location: ?page=categories{$mid}");
            exit;

        case 'add_type':
            $categoryModel->addCategoryType($_POST['type_name'] ?? '', $_POST['type_description'] ?? '');
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'เพิ่มประเภทสำเร็จ'];
            header("Location: ?page=categories{$mid}");
            exit;

        case 'edit_type':
            $categoryModel->updateCategoryType($_POST['type_id'] ?? 0, $_POST['type_name'] ?? '', $_POST['type_description'] ?? '');
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'แก้ไขประเภทสำเร็จ'];
            header("Location: ?page=categories{$mid}");
            exit;

        case 'delete_type':
            try {
                $categoryModel->deleteCategoryType($_POST['type_id'] ?? 0);
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'ลบประเภทสำเร็จ'];
            } catch (Exception $e) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'ไม่สามารถลบประเภทได้: ' . $e->getMessage()];
            }
            header("Location: ?page=categories{$mid}");
            exit;
    }
}

// Handle AJAX fetch
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    header('Content-Type: application/json');

    $searchCat = $_GET['search_categories'] ?? '';
    $filterType = $_GET['filter_type'] ?? '';
    $pageCat = max(1, (int)($_GET['page_cat'] ?? 1));
    $searchType = $_GET['search_types'] ?? '';
    $pageType = max(1, (int)($_GET['page_type'] ?? 1));
    $limit = 10;

    // Categories
    $catOffset = ($pageCat - 1) * $limit;
    $categories = $categoryModel->getAllCategories($searchCat, $filterType, $catOffset, $limit);
    $totalCat = $categoryModel->getCategoryCount($searchCat, $filterType);
    $totalCatPages = max(1, ceil($totalCat / $limit));

    $catHtml = '';
    if (empty($categories)) {
        $catHtml = '<tr><td colspan="3" class="text-center text-muted py-3">ไม่พบข้อมูล</td></tr>';
    } else {
        foreach ($categories as $cat) {
            $catHtml .= '<tr>';
            $catHtml .= '<td>' . htmlspecialchars($cat['category_name']) . '</td>';
            $catHtml .= '<td><span class="badge bg-secondary">' . htmlspecialchars($cat['category_type'] ?? '-') . '</span></td>';
            $catHtml .= '<td class="text-nowrap">';
            $catHtml .= '<button class="btn btn-sm btn-outline-warning border-0 me-1 edit-category-btn" data-bs-toggle="modal" data-bs-target="#editCategoryModal" data-id="' . $cat['category_id'] . '" data-name="' . htmlspecialchars($cat['category_name']) . '" data-type-id="' . ($cat['category_type_id'] ?? '') . '" data-description="' . htmlspecialchars($cat['category_description'] ?? '') . '"><i class="ri-edit-line"></i></button>';
            $catHtml .= '<button class="btn btn-sm btn-outline-danger border-0 delete-category-btn" data-id="' . $cat['category_id'] . '"><i class="ri-delete-bin-line"></i></button>';
            $catHtml .= '</td></tr>';
        }
    }

    $catPaginationHtml = '';
    if ($totalCatPages > 1) {
        for ($i = 1; $i <= $totalCatPages; $i++) {
            $active = ($i == $pageCat) ? ' active' : '';
            $catPaginationHtml .= '<li class="page-item' . $active . '"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
        }
    }

    // Types
    $typeOffset = ($pageType - 1) * $limit;
    $types = $categoryModel->getAllCategoryTypes($searchType, $typeOffset, $limit);
    $totalType = $categoryModel->getCategoryTypeCount($searchType);
    $totalTypePages = max(1, ceil($totalType / $limit));

    $typeHtml = '';
    if (empty($types)) {
        $typeHtml = '<tr><td colspan="2" class="text-center text-muted py-3">ไม่พบข้อมูล</td></tr>';
    } else {
        foreach ($types as $t) {
            $typeHtml .= '<tr>';
            $typeHtml .= '<td>' . htmlspecialchars($t['type_name']) . '</td>';
            $typeHtml .= '<td class="text-nowrap">';
            $typeHtml .= '<button class="btn btn-sm btn-outline-warning border-0 me-1 edit-type-btn" data-bs-toggle="modal" data-bs-target="#editTypeModal" data-id="' . $t['type_id'] . '" data-name="' . htmlspecialchars($t['type_name']) . '" data-description="' . htmlspecialchars($t['type_description'] ?? '') . '"><i class="ri-edit-line"></i></button>';
            $typeHtml .= '<button class="btn btn-sm btn-outline-danger border-0 delete-type-btn" data-id="' . $t['type_id'] . '"><i class="ri-delete-bin-line"></i></button>';
            $typeHtml .= '</td></tr>';
        }
    }

    $typePaginationHtml = '';
    if ($totalTypePages > 1) {
        for ($i = 1; $i <= $totalTypePages; $i++) {
            $active = ($i == $pageType) ? ' active' : '';
            $typePaginationHtml .= '<li class="page-item' . $active . '"><a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
        }
    }

    echo json_encode([
        'categories_html' => $catHtml,
        'categories_pagination_html' => $catPaginationHtml,
        'total_categories' => $totalCat,
        'types_html' => $typeHtml,
        'types_pagination_html' => $typePaginationHtml,
        'total_types' => $totalType,
    ]);
    exit;
}

// Data for dropdowns
$categoryTypesForDropdown = $categoryModel->getAllCategoryTypes('', 0, 100);

// Show alerts using SweetAlert2
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '" . ($alert['type'] === 'danger' ? 'error' : $alert['type']) . "',
                title: '" . htmlspecialchars($alert['message']) . "',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>";
    unset($_SESSION['alert']);
}
?>

<style>
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
</style>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="manageTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-content" type="button" role="tab">
            <i class="ri-list-check me-2"></i>หมวดหมู่ (Categories)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="types-tab" data-bs-toggle="tab" data-bs-target="#types-content" type="button" role="tab">
            <i class="ri-folder-line me-2"></i>ประเภท (Types)
        </button>
    </li>
</ul>

<div class="tab-content" id="manageTabsContent">
    <!-- Categories Tab -->
    <div class="tab-pane fade show active" id="categories-content" role="tabpanel">
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <div class="row mb-3">
                    <div class="col-md-5 mb-2 mb-md-0">
                        <input type="text" id="searchCategories" class="form-control" placeholder="ค้นหาหมวดหมู่...">
                    </div>
                    <div class="col-md-5 mb-2 mb-md-0">
                        <select id="filterType" class="form-select">
                            <option value="">ทุกประเภท</option>
                            <?php foreach ($categoryTypesForDropdown as $type): ?>
                                <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="ri-add-circle-line me-1"></i> เพิ่ม
                        </button>
                    </div>
                </div>
                <div class="position-relative">
                    <div id="loadingOverlayCategories" class="loading-overlay d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover border" id="categories-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ชื่อหมวดหมู่</th>
                                    <th>ประเภท</th>
                                    <th style="width:120px">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small" id="categories-info">กำลังโหลด...</div>
                    <ul class="pagination pagination-sm m-0" id="categories-pagination"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Types Tab -->
    <div class="tab-pane fade" id="types-content" role="tabpanel">
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-4">
                <div class="row mb-3">
                    <div class="col-md-10 mb-2 mb-md-0">
                        <input type="text" id="searchTypes" class="form-control" placeholder="ค้นหาประเภท...">
                    </div>
                    <div class="col-md-2 text-md-end">
                        <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                            <i class="fas fa-plus-circle me-1"></i> เพิ่ม
                        </button>
                    </div>
                </div>
                <div class="position-relative">
                    <div id="loadingOverlayTypes" class="loading-overlay d-none">
                        <div class="spinner-border text-success"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover border" id="types-table">
                            <thead class="table-light">
                                <tr>
                                    <th>ชื่อประเภท</th>
                                    <th style="width:120px">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small" id="types-info">กำลังโหลด...</div>
                    <ul class="pagination pagination-sm m-0" id="types-pagination"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST"><input type="hidden" name="action" value="add_category">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>เพิ่มหมวดหมู่ใหม่</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">ชื่อหมวดหมู่ <span class="text-danger">*</span></label><input type="text" class="form-control" name="category_name" required></div>
                    <div class="mb-3"><label class="form-label">ประเภท <span class="text-danger">*</span></label><select class="form-select" name="category_type_id" required>
                            <option value="">-- เลือก --</option><?php foreach ($categoryTypesForDropdown as $t): ?><option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">รายละเอียด</label><textarea class="form-control" name="category_description" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-primary">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST"><input type="hidden" name="action" value="edit_category"><input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark"><i class="ri-edit-line me-2"></i>แก้ไขหมวดหมู่</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">ชื่อหมวดหมู่ <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_category_name" name="category_name" required></div>
                    <div class="mb-3"><label class="form-label">ประเภท <span class="text-danger">*</span></label><select class="form-select" id="edit_category_type_id" name="category_type_id" required>
                            <option value="">-- เลือก --</option><?php foreach ($categoryTypesForDropdown as $t): ?><option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">รายละเอียด</label><textarea class="form-control" id="edit_category_description" name="category_description" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-warning text-dark">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteCategoryForm"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="category_id" id="delete_category_id"></form>

<!-- Add Type -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST"><input type="hidden" name="action" value="add_type">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>เพิ่มประเภทใหม่</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">ชื่อประเภท <span class="text-danger">*</span></label><input type="text" class="form-control" name="type_name" required></div>
                    <div class="mb-3"><label class="form-label">รายละเอียด</label><textarea class="form-control" name="type_description" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-success">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Type -->
<div class="modal fade" id="editTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST"><input type="hidden" name="action" value="edit_type"><input type="hidden" name="type_id" id="edit_type_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark"><i class="fas fa-edit me-2"></i>แก้ไขประเภท</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">ชื่อประเภท <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_type_name" name="type_name" required></div>
                    <div class="mb-3"><label class="form-label">รายละเอียด</label><textarea class="form-control" id="edit_type_description" name="type_description" rows="3"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-warning text-dark">บันทึก</button></div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteTypeForm"><input type="hidden" name="action" value="delete_type"><input type="hidden" name="type_id" id="delete_type_id"></form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentCatPage = 1,
            currentTypePage = 1;
        const midParam = new URLSearchParams(window.location.search).get('mid');
        const baseUrl = `?page=categories${midParam ? '&mid=' + midParam : ''}`;

        function fetchData() {
            const isCategoriesActive = document.querySelector('#categories-tab').classList.contains('active');
            const overlayId = isCategoriesActive ? 'loadingOverlayCategories' : 'loadingOverlayTypes';
            const overlay = document.getElementById(overlayId);
            if (overlay) overlay.classList.remove('d-none');

            const url = `${baseUrl}&action=fetch_data&search_categories=${encodeURIComponent(document.getElementById('searchCategories').value)}&filter_type=${encodeURIComponent(document.getElementById('filterType').value)}&page_cat=${currentCatPage}&search_types=${encodeURIComponent(document.getElementById('searchTypes').value)}&page_type=${currentTypePage}`;

            fetch(url).then(r => r.json()).then(data => {
                document.querySelector('#categories-table tbody').innerHTML = data.categories_html;
                document.getElementById('categories-pagination').innerHTML = data.categories_pagination_html;
                document.getElementById('categories-info').textContent = `ทั้งหมด ${data.total_categories} หมวดหมู่`;
                document.querySelector('#types-table tbody').innerHTML = data.types_html;
                document.getElementById('types-pagination').innerHTML = data.types_pagination_html;
                document.getElementById('types-info').textContent = `ทั้งหมด ${data.total_types} ประเภท`;
            }).catch(err => {
                console.error(err);
                document.getElementById('categories-info').textContent = 'เกิดข้อผิดพลาด';
                document.getElementById('types-info').textContent = 'เกิดข้อผิดพลาด';
            }).finally(() => {
                if (overlay) overlay.classList.add('d-none');
            });
        }

        let typingTimer;
        document.getElementById('searchCategories').addEventListener('keyup', () => {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                currentCatPage = 1;
                fetchData();
            }, 500);
        });
        document.getElementById('filterType').addEventListener('change', () => {
            currentCatPage = 1;
            fetchData();
        });
        document.getElementById('searchTypes').addEventListener('keyup', () => {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                currentTypePage = 1;
                fetchData();
            }, 500);
        });

        document.addEventListener('click', function(e) {
            const pl = e.target.closest('a.page-link');
            if (pl) {
                e.preventDefault();
                const p = pl.dataset.page;
                const isCat = document.querySelector('#categories-tab').classList.contains('active');
                if (isCat) currentCatPage = p;
                else currentTypePage = p;
                fetchData();
            }
            const ecb = e.target.closest('.edit-category-btn');
            if (ecb) {
                document.getElementById('edit_category_id').value = ecb.dataset.id;
                document.getElementById('edit_category_name').value = ecb.dataset.name;
                document.getElementById('edit_category_type_id').value = ecb.dataset.typeId;
                document.getElementById('edit_category_description').value = ecb.dataset.description;
            }
            const dcb = e.target.closest('.delete-category-btn');
            if (dcb) {
                const id = dcb.dataset.id;
                Swal.fire({
                    title: 'ยืนยันการลบหมวดหมู่?',
                    text: 'คำถามในหมวดหมู่นี้จะไม่มีหมวดหมู่ระบุ แต่จะไม่ถูกลบ',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยันลบ',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#b91c1c'
                }).then((r) => {
                    if (r.isConfirmed) {
                        document.getElementById('delete_category_id').value = id;
                        document.getElementById('deleteCategoryForm').submit();
                    }
                });
            }
            const etb = e.target.closest('.edit-type-btn');
            if (etb) {
                document.getElementById('edit_type_id').value = etb.dataset.id;
                document.getElementById('edit_type_name').value = etb.dataset.name;
                document.getElementById('edit_type_description').value = etb.dataset.description;
            }
            const dtb = e.target.closest('.delete-type-btn');
            if (dtb) {
                const id = dtb.dataset.id;
                Swal.fire({
                    title: 'ยืนยันการลบประเภท?',
                    text: 'หมวดหมู่ที่ใช้ประเภทนี้อยู่จะได้รับผลกระทบ',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยันลบ',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#b91c1c'
                }).then((r) => {
                    if (r.isConfirmed) {
                        document.getElementById('delete_type_id').value = id;
                        document.getElementById('deleteTypeForm').submit();
                    }
                });
            }
        });

        fetchData();
    });
</script>