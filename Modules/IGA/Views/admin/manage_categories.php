<?php
$page_title = "จัดการหมวดหมู่และประเภทหมวดหมู่";
$allowed_roles = ['admin', 'editor', 'Super_user_Recruitment'];
include __DIR__ . '/../../../includes/header.php';

// Display alert messages
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($alert['message']) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
    unset($_SESSION['alert']);
}
?>

<style>
    /* Custom styles from legacy portal */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .pagination {
        margin-bottom: 0;
        justify-content: flex-end;
    }

    #categories-table th,
    #categories-table td,
    #types-table th,
    #types-table td {
        vertical-align: middle;
    }

    .nav-tabs .nav-link {
        font-weight: 500;
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        font-weight: 600;
        border-bottom: 2px solid #0d6efd;
        background-color: transparent;
    }
</style>

<div class="container-fluid mt-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0 text-primary fw-bold">
                <i class="fas fa-tags me-2"></i>จัดการหมวดหมู่และประเภท
            </h2>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 px-3" id="manageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories-content" type="button" role="tab" aria-controls="categories-content" aria-selected="true">
                <i class="fas fa-list-ul me-2"></i>จัดการหมวดหมู่ (Categories)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="types-tab" data-bs-toggle="tab" data-bs-target="#types-content" type="button" role="tab" aria-controls="types-content" aria-selected="false">
                <i class="fas fa-folder me-2"></i>จัดการประเภท (Types)
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content px-3" id="manageTabsContent">

        <!-- Categories Tab -->
        <div class="tab-pane fade show active" id="categories-content" role="tabpanel" aria-labelledby="categories-tab">
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
                                    <option value="<?php echo $type['type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus-circle me-1"></i> เพิ่มหมวดหมู่
                            </button>
                        </div>
                    </div>

                    <div class="position-relative">
                        <div id="loadingOverlayCategories" class="loading-overlay d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover border" id="categories-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ชื่อหมวดหมู่ (Category Name)</th>
                                        <th>ประเภท (Type)</th>
                                        <th style="width: 120px;">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <div class="text-muted small mb-2 mb-md-0" id="categories-info">กำลังโหลดข้อมูล...</div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm m-0" id="categories-pagination">
                                <!-- Pagination via AJAX -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Types Tab -->
        <div class="tab-pane fade" id="types-content" role="tabpanel" aria-labelledby="types-tab">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-10 mb-2 mb-md-0">
                            <input type="text" id="searchTypes" class="form-control" placeholder="ค้นหาประเภท...">
                        </div>
                        <div class="col-md-2 text-md-end">
                            <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                                <i class="fas fa-plus-circle me-1"></i> เพิ่มประเภท
                            </button>
                        </div>
                    </div>

                    <div class="position-relative">
                        <div id="loadingOverlayTypes" class="loading-overlay d-none">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover border" id="types-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ชื่อประเภท (Type Name)</th>
                                        <th style="width: 120px;">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <div class="text-muted small mb-2 mb-md-0" id="types-info">กำลังโหลดข้อมูล...</div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm m-0" id="types-pagination">
                                <!-- Pagination via AJAX -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/iga/admin_categories" method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addCategoryModalLabel"><i class="fas fa-plus-circle me-2"></i>เพิ่มหมวดหมู่ใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_type_id" class="form-label">ประเภทหมวดหมู่ <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_type_id" name="category_type_id" required>
                            <option value="">-- เลือกประเภท --</option>
                            <?php foreach ($categoryTypesForDropdown as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">รายละเอียด (ไม่บังคับ)</label>
                        <textarea class="form-control" id="category_description" name="category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/iga/admin_categories" method="POST">
                <input type="hidden" name="action" value="edit_category">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="editCategoryModalLabel"><i class="fas fa-edit me-2"></i>แก้ไขหมวดหมู่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_type_id" class="form-label">ประเภทหมวดหมู่ <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_category_type_id" name="category_type_id" required>
                            <option value="">-- เลือกประเภท --</option>
                            <?php foreach ($categoryTypesForDropdown as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_description" class="form-label">รายละเอียด (ไม่บังคับ)</label>
                        <textarea class="form-control" id="edit_category_description" name="category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning text-dark">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Confirm Modal -->
<div class="modal fade" id="deleteCategoryConfirmModal" tabindex="-1" aria-labelledby="deleteCategoryConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/iga/admin_categories" method="POST">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" id="delete_category_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteCategoryConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-0 fs-5">คุณแน่ใจหรือไม่ว่าต้องการลบหมวดหมู่นี้?</p>
                    <p class="text-muted small mt-2">(การกระทำนี้ไม่สามารถเรียกคืนได้ และจะไม่สามารถลบได้หากมีแบบทดสอบที่ใช้หมวดหมู่นี้อยู่)</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-secondary px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger px-4">ยืนยันการลบ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Type Modal -->
<div class="modal fade" id="addTypeModal" tabindex="-1" aria-labelledby="addTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/iga/admin_categories" method="POST">
                <input type="hidden" name="action" value="add_type">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addTypeModalLabel"><i class="fas fa-plus-circle me-2"></i>เพิ่มประเภทหมวดหมู่ใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="type_name" class="form-label">ชื่อประเภท <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="type_name" name="type_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="type_description" class="form-label">รายละเอียด (ไม่บังคับ)</label>
                        <textarea class="form-control" id="type_description" name="type_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success text-white">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Type Modal -->
<div class="modal fade" id="editTypeModal" tabindex="-1" aria-labelledby="editTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/iga/admin_categories" method="POST">
                <input type="hidden" name="action" value="edit_type">
                <input type="hidden" name="type_id" id="edit_type_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="editTypeModalLabel"><i class="fas fa-edit me-2"></i>แก้ไขประเภทหมวดหมู่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_type_name" class="form-label">ชื่อประเภท <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_type_name" name="type_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_type_description" class="form-label">รายละเอียด (ไม่บังคับ)</label>
                        <textarea class="form-control" id="edit_type_description" name="type_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning text-dark">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Type Confirm Modal -->
<div class="modal fade" id="deleteTypeConfirmModal" tabindex="-1" aria-labelledby="deleteTypeConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/iga/admin_categories" method="POST">
                <input type="hidden" name="action" value="delete_type">
                <input type="hidden" name="type_id" id="delete_type_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteTypeConfirmModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-0 fs-5">คุณแน่ใจหรือไม่ว่าต้องการลบประเภทนี้?</p>
                    <p class="text-muted small mt-2">(การกระทำนี้จะไม่สามารถทำได้หากมีการใช้ประเภทนี้ในหมวดหมู่ต่างๆ)</p>
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn btn-secondary px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger px-4">ยืนยันการลบ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentCatPage = 1;
        let currentTypePage = 1;

        // Tab switching state persistence
        const triggerTabList = document.querySelectorAll('#manageTabs button')
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl)
            triggerEl.addEventListener('click', event => {
                event.preventDefault()
                tabTrigger.show()
                localStorage.setItem('activeCategoryTab', triggerEl.id)
                fetchData();
            })
        })

        const activeTabObj = localStorage.getItem('activeCategoryTab')
        if (activeTabObj) {
            const activeTab = document.querySelector('#' + activeTabObj)
            if (activeTab) {
                new bootstrap.Tab(activeTab).show()
            }
        }

        function fetchData() {
            // Toggle Loaders based on active tab
            const isCategoriesActive = document.querySelector('#categories-tab').classList.contains('active');
            const overlayId = isCategoriesActive ? 'loadingOverlayCategories' : 'loadingOverlayTypes';
            const overlay = document.getElementById(overlayId);
            if (overlay) overlay.classList.remove('d-none');

            const searchCat = document.getElementById('searchCategories').value;
            const filterCat = document.getElementById('filterType').value;
            const searchType = document.getElementById('searchTypes').value;

            const url = `/iga/admin_categories?action=fetch_data&search_categories=${encodeURIComponent(searchCat)}&filter_type=${encodeURIComponent(filterCat)}&page_cat=${currentCatPage}&search_types=${encodeURIComponent(searchType)}&page_type=${currentTypePage}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.querySelector('#categories-table tbody').innerHTML = data.categories_html;
                    document.getElementById('categories-pagination').innerHTML = data.categories_pagination_html;
                    document.getElementById('categories-info').textContent = `ทั้งหมด ${data.total_categories} หมวดหมู่`;

                    document.querySelector('#types-table tbody').innerHTML = data.types_html;
                    document.getElementById('types-pagination').innerHTML = data.types_pagination_html;
                    document.getElementById('types-info').textContent = `ทั้งหมด ${data.total_types} ประเภท`;
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                    document.getElementById('categories-info').textContent = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
                    document.getElementById('types-info').textContent = 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
                })
                .finally(() => {
                    if (overlay) overlay.classList.add('d-none');
                });
        }

        // Event Listeners for search and filter
        let typingTimer;
        const doneTypingInterval = 500;

        document.getElementById('searchCategories').addEventListener('keyup', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                currentCatPage = 1;
                fetchData();
            }, doneTypingInterval);
        });

        document.getElementById('filterType').addEventListener('change', function() {
            currentCatPage = 1;
            fetchData();
        });

        document.getElementById('searchTypes').addEventListener('keyup', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                currentTypePage = 1;
                fetchData();
            }, doneTypingInterval);
        });

        // Event Listeners for pagination
        document.addEventListener('click', function(e) {
            const pageLink = e.target.closest('a.page-link');
            if (pageLink) {
                e.preventDefault();
                const page = pageLink.dataset.page;

                // Determine active tab to paginate correctly
                const isCategoriesActive = document.querySelector('#categories-tab').classList.contains('active');

                if (isCategoriesActive) {
                    currentCatPage = page;
                } else {
                    currentTypePage = page;
                }
                fetchData();
            }
        });

        // Event Listeners for edit/delete buttons
        document.addEventListener('click', function(e) {
            // Edit Category
            const editCatBtn = e.target.closest('.edit-category-btn');
            if (editCatBtn) {
                document.getElementById('edit_category_id').value = editCatBtn.dataset.id;
                document.getElementById('edit_category_name').value = editCatBtn.dataset.name;
                document.getElementById('edit_category_type_id').value = editCatBtn.dataset.typeId;
                document.getElementById('edit_category_description').value = editCatBtn.dataset.description;
            }

            // Delete Category
            const delCatBtn = e.target.closest('.delete-category-btn');
            if (delCatBtn) {
                document.getElementById('delete_category_id').value = delCatBtn.dataset.id;
            }

            // Edit Type
            const editTypeBtn = e.target.closest('.edit-type-btn');
            if (editTypeBtn) {
                document.getElementById('edit_type_id').value = editTypeBtn.dataset.id;
                document.getElementById('edit_type_name').value = editTypeBtn.dataset.name;
                document.getElementById('edit_type_description').value = editTypeBtn.dataset.description;
            }

            // Delete Type
            const delTypeBtn = e.target.closest('.delete-type-btn');
            if (delTypeBtn) {
                document.getElementById('delete_type_id').value = delTypeBtn.dataset.id;
            }
        });

        // Initial fetch
        fetchData();
    });
</script>
