<?php

/**
 * IGA Edit/Create Test - Content Fragment
 * Loaded inside index.php layout via ?page=edit_test
 * Variables available: $pdo, $canManage, $user, $baseUrl
 */

if (!$canManage) {
    echo '<div class="alert alert-danger">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>';
    return;
}

require_once __DIR__ . '/../Models/TestModel.php';
require_once __DIR__ . '/../Models/CategoryModel.php';
$testModel = new TestModel($pdo);
$categoryModel = new CategoryModel($pdo);

$mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';
$editId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$test = null;
$isEdit = false;

if ($editId) {
    $test = $testModel->getTestById($editId);
    if (!$test) {
        echo '<div class="bg-red-50 text-red-600 p-4 rounded-xl text-center">ไม่พบแบบทดสอบ ID: ' . $editId . '</div>';
        return;
    }
    $isEdit = true;
}

// Handle POST (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_test'])) {
    $data = [
        'test_name'                => trim($_POST['test_name'] ?? ''),
        'description'              => trim($_POST['description'] ?? ''),
        'duration_minutes'         => (int)($_POST['duration_minutes'] ?? 0),
        'min_passing_score'        => ($_POST['min_passing_score'] !== '' && $_POST['min_passing_score'] !== null) ? (float)$_POST['min_passing_score'] : null,
        'show_result_immediately'  => isset($_POST['show_result_immediately']) ? 1 : 0,
        'category_type_id'         => !empty($_POST['category_type_id']) ? (int)$_POST['category_type_id'] : null,
        'emptype'                  => $_POST['emptype'] ?? 'all',
        'test_no'                  => !empty($_POST['test_no']) ? trim($_POST['test_no']) : null,
        'language'                 => $_POST['language'] ?? 'TH',
        'is_published'             => isset($_POST['publish_immediately']) ? 1 : 0,
        'published_at'             => !empty($_POST['published_at']) ? $_POST['published_at'] : null,
        'unpublished_at'           => !empty($_POST['unpublished_at']) ? $_POST['unpublished_at'] : null,
        'created_by_user_id'       => $user['id'] ?? null,
    ];

    if (empty($data['test_name'])) {
        $alertMsg = 'กรุณาระบุชื่อแบบทดสอบ';
        $alertType = 'error';
    } else {
        try {
            if ($isEdit) {
                $testModel->updateTest($editId, $data);
                $targetTestId = $editId;
                $alertMsg = 'แก้ไขแบบทดสอบสำเร็จ';
                $alertType = 'success';
            } else {
                $targetTestId = $testModel->createTest($data);
                $alertMsg = 'สร้างแบบทดสอบสำเร็จ (ID: #' . $targetTestId . ')';
                $alertType = 'success';
            }

            // Save targeting: Employee Levels
            $levels = $_POST['target_levels'] ?? [];
            $testModel->setTestEmpLevels($targetTestId, array_map('intval', $levels));

            // Save targeting: Org Units
            $orgUnits = $_POST['target_orgunits'] ?? [];
            $testModel->setTestOrgUnits($targetTestId, $orgUnits);

            // Save targeting: Individual Users
            $targetUsers = [];
            if (!empty($_POST['target_user_ids'])) {
                $targetUsers = array_map('intval', explode(',', $_POST['target_user_ids']));
            }
            $testModel->setTestUsers($targetTestId, $targetUsers);

            echo "<script>document.addEventListener('DOMContentLoaded',()=>{
                Swal.fire({icon:'success',title:'{$alertMsg}',toast:true,position:'top-end',showConfirmButton:false,timer:2000,timerProgressBar:true}).then(()=>{
                    window.location='?page=tests{$mid}';
                });
            });</script>";
        } catch (Exception $e) {
            $alertMsg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $alertType = 'error';
        }
    }

    if (isset($alertType) && $alertType === 'error') {
        echo "<script>document.addEventListener('DOMContentLoaded',()=>{
            Swal.fire({icon:'error',title:'" . addslashes($alertMsg) . "',toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true});
        });</script>";
    }
}

// Data for dropdowns
$categoryTypes = $categoryModel->getAllCategoryTypes('', 0, 100);
$allLevels = $testModel->getAllEmpLevels();
$allOrgUnits = $testModel->getAllOrgUnits();

// Current targeting data (for edit mode)
$currentLevels = [];
$currentOrgUnits = [];
$currentUsers = [];
if ($isEdit) {
    $empLevels = $testModel->getTestEmpLevels($editId);
    $currentLevels = array_map(fn($l) => (int)$l['level_id'], $empLevels);
    $currentOrgUnits = $testModel->getTestOrgUnits($editId);
    $currentUsers = $testModel->getTestUsers($editId);
}

// Get roles
$roles = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM roles WHERE is_active = 1 ORDER BY name");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}
?>

<style>
    .dd-checkbox {
        position: relative;
    }

    .dd-checkbox .dd-toggle {
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.625rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background: #fff;
        font-size: 0.875rem;
        transition: all 0.15s;
    }

    .dd-checkbox .dd-toggle:hover {
        border-color: var(--primary-color, #991b1b);
    }

    .dd-checkbox .dd-panel {
        display: none;
        position: absolute;
        z-index: 50;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 4px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
        max-height: 240px;
        overflow-y: auto;
    }

    .dd-checkbox.open .dd-panel {
        display: block;
    }

    .dd-checkbox .dd-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.8125rem;
        transition: background 0.1s;
    }

    .dd-checkbox .dd-item:hover {
        background: #fef2f2;
    }

    .dd-checkbox .dd-item input[type=checkbox] {
        accent-color: #991b1b;
        width: 16px;
        height: 16px;
    }

    .dd-badge {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 2px 8px;
        background: #fee2e2;
        color: #991b1b;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
    }
</style>

<!-- Page Header -->
<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-black text-gray-900">
        <i class="ri-<?= $isEdit ? 'edit-line' : 'add-circle-line' ?> text-primary me-2"></i>
        <?= $isEdit ? 'แก้ไขแบบทดสอบ' : 'สร้างแบบทดสอบใหม่' ?>
    </h2>
    <a href="?page=tests<?= $mid ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-bold transition-all">
        <i class="ri-arrow-left-line"></i> กลับ
    </a>
</div>

<form method="POST" id="editTestForm">
    <input type="hidden" name="save_test" value="1">

    <!-- Section 1: Basic Info -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <i class="ri-information-line"></i> ข้อมูลพื้นฐาน
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Test Name -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Assessment Name <span class="text-red-500">*</span></label>
                <input type="text" name="test_name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" value="<?= htmlspecialchars($test['test_name'] ?? '') ?>" placeholder="ชื่อแบบทดสอบ">
            </div>

            <!-- Category Type -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Category Type</label>
                <select name="category_type_id" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm bg-white">
                    <option value="">Select Category Type (optional)</option>
                    <?php foreach ($categoryTypes as $ct): ?>
                        <option value="<?= $ct['type_id'] ?>" <?= ($test['category_type_id'] ?? '') == $ct['type_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ct['type_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Description -->
        <div class="mb-4">
            <label class="block text-sm font-bold text-amber-700 mb-1">Assessment Description</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" placeholder="รายละเอียดแบบทดสอบ"><?= htmlspecialchars($test['description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <!-- Duration -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Duration (minutes)</label>
                <input type="number" name="duration_minutes" min="0" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" value="<?= $test['duration_minutes'] ?? 0 ?>">
                <p class="text-xs text-gray-400 mt-1">0 = Unlimited time</p>
            </div>

            <!-- Min Passing Score -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Minimum Passing Score (%)</label>
                <input type="number" name="min_passing_score" min="0" max="100" step="0.01" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" value="<?= $test['min_passing_score'] ?? '0.00' ?>">
            </div>

            <!-- Test Group Number -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Test Group Number</label>
                <input type="text" name="test_no" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" value="<?= htmlspecialchars($test['test_no'] ?? '') ?>" placeholder="e.g. 1">
                <p class="text-xs text-gray-400 mt-1">Used to group related assessments</p>
            </div>

            <!-- Language -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Language</label>
                <select name="language" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm bg-white">
                    <option value="TH" <?= ($test['language'] ?? 'TH') === 'TH' ? 'selected' : '' ?>>Thai</option>
                    <option value="EN" <?= ($test['language'] ?? '') === 'EN' ? 'selected' : '' ?>>English</option>
                    <option value="JP" <?= ($test['language'] ?? '') === 'JP' ? 'selected' : '' ?>>Japanese</option>
                    <option value="MM" <?= ($test['language'] ?? '') === 'MM' ? 'selected' : '' ?>>Myanmar</option>
                    <option value="KH" <?= ($test['language'] ?? '') === 'KH' ? 'selected' : '' ?>>Khmer</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Emptype -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Emptype</label>
                <select name="emptype" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm bg-white">
                    <option value="all" <?= ($test['emptype'] ?? 'all') === 'all' ? 'selected' : '' ?>>ทั้งหมด (All)</option>
                    <option value="employee" <?= ($test['emptype'] ?? '') === 'employee' ? 'selected' : '' ?>>พนักงาน (Employee)</option>
                    <option value="applicant" <?= ($test['emptype'] ?? '') === 'applicant' ? 'selected' : '' ?>>ผู้สมัคร (Applicant)</option>
                </select>
            </div>
        </div>

        <!-- Show Result Immediately -->
        <div class="mt-4">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="show_result_immediately" value="1" class="w-4 h-4 accent-primary rounded border-gray-300" <?= ($test['show_result_immediately'] ?? 1) ? 'checked' : '' ?>>
                <span class="text-sm font-medium text-gray-700">แสดงผลสอบทันทีหลังส่งคำตอบ</span>
            </label>
        </div>
    </div>

    <!-- Section 2: Targeting -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <i class="ri-focus-3-line"></i> กลุ่มเป้าหมาย (Targeting)
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
            <!-- Org Units — Dropdown Checkbox -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Orgunitname</label>
                <div class="dd-checkbox" id="ddOrg">
                    <div class="dd-toggle" onclick="toggleDD('ddOrg')">
                        <span id="ddOrgLabel" class="text-gray-500">Select Orgunitname</span>
                        <i class="ri-arrow-down-s-line text-gray-400"></i>
                    </div>
                    <div class="dd-panel">
                        <?php foreach ($allOrgUnits as $org):
                            $chk = in_array($org, $currentOrgUnits) ? 'checked' : '';
                        ?>
                            <label class="dd-item">
                                <input type="checkbox" name="target_orgunits[]" value="<?= htmlspecialchars($org) ?>" <?= $chk ?> onchange="updateDDLabel('ddOrg','target_orgunits[]','Orgunitname')">
                                <?= htmlspecialchars($org) ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if (empty($allOrgUnits)): ?>
                            <div class="px-4 py-3 text-sm text-gray-400 text-center">ไม่พบข้อมูล</div>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">ไม่เลือก = ทุกหน่วยงาน</p>
            </div>

            <!-- Employee Level — Dropdown Checkbox -->
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Employee Level</label>
                <div class="dd-checkbox" id="ddLevel">
                    <div class="dd-toggle" onclick="toggleDD('ddLevel')">
                        <span id="ddLevelLabel" class="text-gray-500">Select Employee Level</span>
                        <i class="ri-arrow-down-s-line text-gray-400"></i>
                    </div>
                    <div class="dd-panel">
                        <?php foreach ($allLevels as $lvl):
                            $chk = in_array((int)$lvl['level_id'], $currentLevels) ? 'checked' : '';
                        ?>
                            <label class="dd-item">
                                <input type="checkbox" name="target_levels[]" value="<?= $lvl['level_id'] ?>" <?= $chk ?> onchange="updateDDLabel('ddLevel','target_levels[]','Employee Level')">
                                <?= htmlspecialchars($lvl['level_name'] ?? 'Level ' . $lvl['level_id']) ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if (empty($allLevels)): ?>
                            <div class="px-4 py-3 text-sm text-gray-400 text-center">ไม่พบข้อมูล</div>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">select multiple options. (ไม่เลือก = ทุกระดับ)</p>
            </div>
        </div>

        <!-- Target Individual Users -->
        <div>
            <label class="block text-sm font-bold text-amber-700 mb-1">Target Individual Users</label>
            <div id="selectedUsersDisplay" class="flex flex-wrap gap-2 min-h-[48px] p-3 border border-amber-200 rounded-xl bg-amber-50/50 mb-2">
                <?php if (!empty($currentUsers)): ?>
                    <?php foreach ($currentUsers as $tu): ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-medium border border-amber-200" data-user-id="<?= $tu['id'] ?>">
                            <?= htmlspecialchars($tu['fullname'] ?? $tu['username'] ?? 'User#' . $tu['id']) ?>
                            <small class="text-amber-500">(<?= htmlspecialchars($tu['department'] ?? '') ?>)</small>
                            <button type="button" onclick="removeUser(this, <?= $tu['id'] ?>)" class="ml-1 text-amber-400 hover:text-red-500 font-bold">&times;</button>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-amber-400 text-xs" id="noUsersPlaceholder">ไม่ได้ระบุรายบุคคล — จะใช้เงื่อนไข Level/Org แทน</span>
                <?php endif; ?>
            </div>
            <input type="hidden" name="target_user_ids" id="targetUserIds" value="<?= implode(',', array_map(fn($u) => $u['id'], $currentUsers)) ?>">
            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="userSearchInput" class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" placeholder="Search users by name or EmpCode and select one or more.">
                <div id="userSearchResults" class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto hidden"></div>
            </div>
        </div>
    </div>

    <!-- Section 3: Publishing -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
            <i class="ri-calendar-check-line"></i> การเผยแพร่ (Publishing)
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">Start Publish Date</label>
                <input type="datetime-local" name="published_at" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" value="<?= !empty($test['published_at']) ? date('Y-m-d\TH:i', strtotime($test['published_at'])) : '' ?>">
            </div>
            <div>
                <label class="block text-sm font-bold text-amber-700 mb-1">End Publish Date</label>
                <input type="datetime-local" name="unpublished_at" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" value="<?= !empty($test['unpublished_at']) ? date('Y-m-d\TH:i', strtotime($test['unpublished_at'])) : '' ?>">
            </div>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="publish_immediately" value="1" class="w-4 h-4 accent-primary rounded border-gray-300" <?= ($test['is_published'] ?? 0) ? 'checked' : '' ?>>
                <span class="text-sm font-medium text-gray-700">Publish assessment immediately</span>
            </label>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <button type="submit" class="inline-flex items-center gap-2 px-8 py-3 bg-primary text-white rounded-xl font-bold text-sm hover:bg-maroon-800 transition-all shadow-lg shadow-red-100 active:scale-95">
            <i class="ri-save-line text-lg"></i>
            <?= $isEdit ? 'Save Changes' : 'สร้างแบบทดสอบ' ?>
        </button>
        <a href="?page=tests<?= $mid ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-500 text-white rounded-xl font-bold text-sm hover:bg-blue-600 transition-all">
            <i class="ri-arrow-go-back-line"></i> Back
        </a>
    </div>
</form>

<script>
    // ─── Dropdown Checkbox ───
    function toggleDD(id) {
        const el = document.getElementById(id);
        // Close all others
        document.querySelectorAll('.dd-checkbox.open').forEach(d => {
            if (d.id !== id) d.classList.remove('open');
        });
        el.classList.toggle('open');
    }

    function updateDDLabel(ddId, inputName, defaultText) {
        const container = document.getElementById(ddId);
        const checked = container.querySelectorAll('input[name="' + inputName + '"]:checked');
        const label = container.querySelector('[id$="Label"]');
        if (checked.length === 0) {
            label.innerHTML = '<span class="text-gray-500">Select ' + defaultText + '</span>';
        } else {
            let html = '';
            checked.forEach(cb => {
                html += '<span class="dd-badge">' + cb.parentElement.textContent.trim() + '</span> ';
            });
            label.innerHTML = html;
        }
    }

    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.dd-checkbox.open').forEach(d => {
            if (!d.contains(e.target)) d.classList.remove('open');
        });
    });

    // Init labels on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateDDLabel('ddOrg', 'target_orgunits[]', 'Orgunitname');
        updateDDLabel('ddLevel', 'target_levels[]', 'Employee Level');
    });

    // ─── User Search for Individual Targeting ───
    let searchTimeout = null;
    const searchInput = document.getElementById('userSearchInput');
    const searchResults = document.getElementById('userSearchResults');
    const selectedDisplay = document.getElementById('selectedUsersDisplay');
    const targetUserIds = document.getElementById('targetUserIds');

    function getCurrentUserIds() {
        return targetUserIds.value ? targetUserIds.value.split(',').filter(Boolean).map(Number) : [];
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) {
            searchResults.classList.add('hidden');
            return;
        }
        searchTimeout = setTimeout(() => {
            fetch('?page=questions&action=fetch_data&type=search_users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        query: q
                    })
                })
                .then(r => r.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.success && data.users && data.users.length > 0) {
                        const existingIds = getCurrentUserIds();
                        data.users.forEach(u => {
                            if (existingIds.includes(parseInt(u.id))) return;
                            const item = document.createElement('div');
                            item.className = 'px-4 py-2.5 hover:bg-red-50 cursor-pointer text-sm flex justify-between items-center border-b border-gray-50 transition-colors';
                            item.innerHTML = `
                            <div>
                                <div class="font-bold text-gray-800">${u.fullname || u.username}</div>
                                <div class="text-xs text-gray-400">${u.username} | ${u.department || '-'}</div>
                            </div>
                            <i class="ri-add-circle-fill text-primary text-lg"></i>
                        `;
                            item.onclick = () => addUser(u);
                            searchResults.appendChild(item);
                        });
                    } else {
                        searchResults.innerHTML = '<div class="px-4 py-3 text-sm text-gray-400 text-center">ไม่พบข้อมูล</div>';
                    }
                    searchResults.classList.remove('hidden');
                });
        }, 400);
    });

    function addUser(u) {
        const placeholder = document.getElementById('noUsersPlaceholder');
        if (placeholder) placeholder.remove();

        const ids = getCurrentUserIds();
        if (ids.includes(parseInt(u.id))) return;
        ids.push(parseInt(u.id));
        targetUserIds.value = ids.join(',');

        const badge = document.createElement('span');
        badge.className = 'inline-flex items-center gap-1 px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-medium border border-amber-200';
        badge.dataset.userId = u.id;
        badge.innerHTML = `${u.fullname || u.username} <small class="text-amber-500">(${u.department || ''})</small>
            <button type="button" onclick="removeUser(this, ${u.id})" class="ml-1 text-amber-400 hover:text-red-500 font-bold">&times;</button>`;
        selectedDisplay.appendChild(badge);

        searchInput.value = '';
        searchResults.classList.add('hidden');
    }

    function removeUser(btn, userId) {
        const ids = getCurrentUserIds().filter(id => id !== userId);
        targetUserIds.value = ids.join(',');
        btn.closest('span').remove();
        if (ids.length === 0) {
            selectedDisplay.innerHTML = '<span class="text-amber-400 text-xs" id="noUsersPlaceholder">ไม่ได้ระบุรายบุคคล — จะใช้เงื่อนไข Level/Org แทน</span>';
        }
    }

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });
</script>