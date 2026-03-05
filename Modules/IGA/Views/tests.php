<?php

/**
 * IGA Tests List - Content Fragment
 * Loaded inside index.php layout via ?page=tests
 * Variables available: $pdo, $canManage, $canEdit, $user, $isApplicant, $isEmployee
 */

require_once __DIR__ . '/../Models/TestModel.php';
$testModel = new TestModel($pdo);

// Auto-unpublish expired tests
$testModel->autoUnpublishExpired();

// Handle POST actions (publish/unpublish/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $action = $_POST['test_action'] ?? '';
    $testId = (int)($_POST['test_id'] ?? 0);
    $mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';

    switch ($action) {
        case 'publish':
            $testModel->publishTest($testId);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'เผยแพร่แบบทดสอบสำเร็จ'];
            break;
        case 'unpublish':
            $testModel->unpublishTest($testId);
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'ยกเลิกเผยแพร่แบบทดสอบสำเร็จ'];
            break;
        case 'clone':
            $newId = $testModel->cloneTest($testId, (int)($user['id'] ?? 0));
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'คัดลอกแบบทดสอบสำเร็จ (ID: #' . $newId . ')'];
            break;
    }
    header("Location: ?page=tests{$mid}");
    exit;
}

$search = $_GET['search'] ?? '';
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$limit = 10;
$offset = ($currentPage - 1) * $limit;

$tests = $testModel->getAllTests($search, $offset, $limit);
$totalTests = $testModel->getTestCount($search);
$totalPages = max(1, ceil($totalTests / $limit));
$mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';

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

<!-- Search and Filter -->
<div class="bg-white rounded-3xl p-4 md:p-6 shadow-sm border border-gray-100 mb-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex-grow max-w-2xl">
            <form method="GET" class="relative group">
                <input type="hidden" name="page" value="tests">
                <?php if (isset($_GET['mid'])): ?><input type="hidden" name="mid" value="<?= htmlspecialchars($_GET['mid']) ?>"><?php endif; ?>
                <i class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-lg transition-colors group-focus-within:text-primary"></i>
                <input type="text" name="search" class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-red-50/50 focus:border-primary transition-all outline-none text-sm font-medium" placeholder="ค้นหาแบบทดสอบจากชื่อหรือรายละเอียด..." value="<?= htmlspecialchars($search) ?>">
                <?php if (!empty($search)): ?>
                    <a href="?page=tests<?= $mid ?>" class="absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-all">
                        <i class="ri-close-circle-fill text-lg"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-sm text-gray-500 font-medium bg-gray-50 px-4 py-2 rounded-xl border border-gray-100 flex items-center gap-2">
                <i class="ri-information-line text-primary"></i>
                <span>พบทั้งหมด <?= $totalTests ?> รายการ</span>
            </div>
            <?php if ($canManage): ?>
                <a href="?page=edit_test<?= $mid ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-xl font-bold text-sm hover:bg-maroon-800 transition-all shadow-lg shadow-red-100 active:scale-95">
                    <i class="ri-add-line text-lg"></i> สร้างแบบทดสอบใหม่
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($tests)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($tests as $test):
            $now = date('Y-m-d H:i:s');
            $isScheduled = !empty($test['published_at']) && $test['published_at'] > $now;
            $isExpired = !empty($test['unpublished_at']) && $test['unpublished_at'] <= $now;

            $status = 'published';
            if (!$test['is_published']) $status = 'draft';
            elseif ($isScheduled) $status = 'scheduled';
            elseif ($isExpired) $status = 'expired';

            $statusConfig = [
                'draft' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => 'ปิดการใช้งาน', 'icon' => 'ri-eye-off-line'],
                'scheduled' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'label' => 'รอกำหนดการ', 'icon' => 'ri-calendar-todo-line'],
                'expired' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'label' => 'หมดอายุแล้ว', 'icon' => 'ri-history-line'],
                'published' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'label' => 'กำลังเผยแพร่', 'icon' => 'ri-checkbox-circle-line']
            ];
            $cfg = $statusConfig[$status];
        ?>
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col group overflow-hidden">
                <div class="p-6 flex-grow">
                    <div class="flex justify-between items-start mb-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full <?= $cfg['bg'] ?> <?= $cfg['text'] ?> text-[10px] font-bold uppercase tracking-wider">
                            <i class="<?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
                        </span>
                        <?php if (!empty($test['language'])): ?>
                            <span class="text-[10px] font-bold text-gray-300 uppercase tracking-widest bg-gray-50 px-2 py-0.5 rounded"><?= strtoupper($test['language']) ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2 leading-tight group-hover:text-primary transition-colors line-clamp-2 min-h-[3.5rem]">
                        <?= htmlspecialchars($test['test_name']) ?>
                    </h3>

                    <?php if (!empty($test['category_name'])): ?>
                        <div class="flex items-center gap-1 text-xs text-primary font-bold mb-3">
                            <i class="ri-price-tag-3-fill"></i>
                            <?= htmlspecialchars($test['category_name']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($test['description'])): ?>
                        <p class="text-gray-500 text-sm line-clamp-2 mb-4">
                            <?= htmlspecialchars(strip_tags($test['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-gray-50 mt-auto">
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <i class="ri-time-line text-primary opacity-70"></i>
                            <span class="font-medium"><?= $test['duration_minutes'] ?? 0 ?> นาที</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <i class="ri-question-line text-primary opacity-70"></i>
                            <span class="font-medium"><?= $test['question_count'] ?? 0 ?> คำถาม</span>
                        </div>
                    </div>
                </div>

                <?php if ($canManage): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-2">
                        <div class="flex gap-2">
                            <?php if ($test['is_published']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="test_action" value="unpublish">
                                    <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                                    <button type="submit" class="w-9 h-9 flex items-center justify-center bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition-all" title="ยกเลิกเผยแพร่">
                                        <i class="ri-eye-off-line text-lg"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="test_action" value="publish">
                                    <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                                    <button type="submit" class="w-9 h-9 flex items-center justify-center bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-100 transition-all" title="เผยแพร่">
                                        <i class="ri-eye-line text-lg"></i>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <a href="?page=edit_test&id=<?= $test['test_id'] ?><?= $mid ?>" class="w-9 h-9 flex items-center justify-center bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition-all" title="แก้ไข">
                                <i class="ri-edit-line text-lg"></i>
                            </a>

                            <form method="POST" class="inline-block" id="cloneForm_<?= $test['test_id'] ?>">
                                <input type="hidden" name="test_action" value="clone">
                                <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                                <button type="button" onclick="confirmClone(<?= $test['test_id'] ?>)" class="w-9 h-9 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition-all" title="คัดลอก">
                                    <i class="ri-file-copy-line text-lg"></i>
                                </button>
                            </form>

                            <a href="?page=questions&test_id=<?= $test['test_id'] ?><?= $mid ?>" class="w-9 h-9 flex items-center justify-center bg-purple-50 text-purple-600 rounded-xl hover:bg-purple-100 transition-all" title="จัดการคำถาม">
                                <i class="ri-question-line text-lg"></i>
                            </a>

                            <button type="button" onclick="openRandomSettings(<?= $test['test_id'] ?>)" class="w-9 h-9 flex items-center justify-center bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition-all" title="ตั้งค่าการสุ่ม">
                                <i class="ri-shuffle-line text-lg"></i>
                            </button>

                        </div>

                        <a href="?page=test_overview&id=<?= $test['test_id'] ?><?= $mid ?>" class="text-[10px] font-bold text-gray-400 hover:text-primary transition-colors flex items-center gap-1">
                            รายละเอียด <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-12 flex justify-center">
            <nav class="flex items-center gap-2">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=tests&p=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?><?= $mid ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:border-primary hover:text-primary transition-all">
                        <i class="ri-arrow-left-s-line text-xl"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <a href="?page=tests&p=<?= $i ?>&search=<?= urlencode($search) ?><?= $mid ?>" class="w-10 h-10 flex items-center justify-center rounded-xl font-bold text-sm transition-all <?= ($i == $currentPage) ? 'bg-primary text-white shadow-lg shadow-red-200' : 'bg-white border border-gray-200 text-gray-500 hover:border-primary hover:text-primary' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=tests&p=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?><?= $mid ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:border-primary hover:text-primary transition-all">
                        <i class="ri-arrow-right-s-line text-xl"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="bg-white border border-gray-100 rounded-[2.5rem] p-16 text-center shadow-sm">
        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300">
            <i class="ri-search-line text-5xl"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">ไม่พบแบบทดสอบ</h3>
        <p class="text-gray-500 max-w-sm mx-auto">ไม่พบแบบทดสอบที่ท่านกำลังค้นหา กรุณาลองใช้คำค้นใหม่หรือตรวจสอบการสะกดอีกครั้ง</p>
        <?php if (!empty($search)): ?>
            <a href="?page=tests<?= $mid ?>" class="mt-8 inline-flex items-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl font-bold transition-all">
                ล้างการค้นหา
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    function confirmClone(id) {
        Swal.fire({
            title: 'ยืนยันการคัดลอก?',
            text: 'ระบบจะสร้างแบบทดสอบใหม่ที่เหมือนกับชุดนี้ทุกประการ',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#f1f5f9',
            confirmButtonText: 'ยืนยันคัดลอก',
            cancelButtonText: 'ยกเลิก',
            customClass: {
                confirmButton: 'rounded-xl font-bold px-6 py-2.5',
                cancelButton: 'rounded-xl font-bold px-6 py-2.5 text-gray-500'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('cloneForm_' + id).submit();
            }
        });
    }

    // --- Randomization Settings ---
    var randomModal;
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof bootstrap !== 'undefined') {
            randomModal = new bootstrap.Modal(document.getElementById('randomModal'));
        }
    });

    async function openRandomSettings(testId) {
        const res = await fetch(`?page=questions&action=fetch_data&type=get_test_settings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                test_id: testId
            })
        }).then(r => r.json()).catch(() => ({
            success: false
        }));

        if (res.success) {
            document.getElementById('ts_test_id').value = testId;

            document.getElementById('ts_is_random').checked = res.is_random_mode === 1;
            const quotaBody = document.getElementById('ts_section_quotas');
            quotaBody.innerHTML = '';
            res.sections.forEach(sec => {
                const quota = res.section_random_counts[sec.section_id] || 0;
                quotaBody.innerHTML += `
                    <tr>
                        <td class="small ps-3 pt-2">${sec.section_name}</td>
                        <td><input type="number" class="form-control form-control-sm ts-quota-input" data-section-id="${sec.section_id}" value="${quota}" min="0"></td>
                        <td class="text-center small py-2">${sec.question_count}</td>
                    </tr>
                `;
            });

            const alwaysList = document.getElementById('ts_always_list');
            alwaysList.innerHTML = '';
            if (res.all_questions && res.all_questions.length > 0) {
                res.all_questions.forEach(q => {
                    const checked = res.always_include_questions.includes(parseInt(q.question_id)) ? 'checked' : '';
                    alwaysList.innerHTML += `
                        <div class="form-check border-bottom mb-1 pb-1">
                            <input class="form-check-input ts-always-check" type="checkbox" value="${q.question_id}" id="qalways${q.question_id}" ${checked}>
                            <label class="form-check-label" style="font-size:0.75rem" for="qalways${q.question_id}">
                                <span class="text-muted">[${q.section_name}]</span> ${q.question_text.substring(0, 60)}${q.question_text.length > 60 ? '...' : ''}
                            </label>
                        </div>
                    `;
                });
            } else {
                alwaysList.innerHTML = '<div class="text-center text-muted small py-3">ยังไม่มีคำถาม</div>';
            }

            toggleRandomConfig();
            randomModal.show();
        } else {
            Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลได้', 'error');
        }
    }

    function toggleRandomConfig() {
        const isRandom = document.getElementById('ts_is_random').checked;
        document.getElementById('ts_random_config').style.display = isRandom ? 'block' : 'none';
    }

    async function saveRandomSettings() {
        const testId = document.getElementById('ts_test_id').value;
        const alwaysQ = Array.from(document.querySelectorAll('.ts-always-check:checked')).map(el => el.value);
        const quotas = {};
        document.querySelectorAll('.ts-quota-input').forEach(el => {
            quotas[el.dataset.sectionId] = parseInt(el.value) || 0;
        });

        const data = {
            test_id: testId,
            target_levels: [],
            target_orgunits: [],
            target_users: [],
            is_random_mode: document.getElementById('ts_is_random').checked ? 1 : 0,
            section_random_counts: quotas,
            always_include_questions: alwaysQ
        };

        const res = await fetch(`?page=questions&action=fetch_data&type=save_test_settings`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).then(r => r.json()).catch(() => ({
            success: false
        }));

        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                showConfirmButton: false,
                timer: 1500
            });
            randomModal.hide();
        } else {
            Swal.fire('Error', res.message || 'Save failed', 'error');
        }
    }
</script>

<!-- Randomization Modal -->
<div class="modal fade" id="randomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-shuffle-line me-2 text-warning"></i>ตั้งค่าการสุ่มคำถาม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ts_test_id">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="ts_is_random" onchange="toggleRandomConfig()">
                    <label class="form-check-label fw-bold" for="ts_is_random">เปิดโหมดสุ่มคำถาม</label>
                </div>
                <div id="ts_random_config" style="display: none;">
                    <label class="form-label small fw-bold text-primary">จำนวนสุ่มต่อ Section:</label>
                    <div class="table-responsive border rounded mb-3">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="small py-2 ps-3">Section</th>
                                    <th style="width: 90px;" class="small py-2">สุ่ม</th>
                                    <th style="width: 80px;" class="small py-2 text-center">ทั้งหมด</th>
                                </tr>
                            </thead>
                            <tbody id="ts_section_quotas" class="small"></tbody>
                        </table>
                    </div>
                    <label class="form-label small fw-bold text-danger">ต้องแสดงเสมอ (Always Include):</label>
                    <div id="ts_always_list" class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;"></div>
                    <div class="form-text x-small text-muted mt-1">คำถามที่เลือกจะถูก "ล็อค" ให้ปรากฏในการสุ่มเสมอ</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary px-4" onclick="saveRandomSettings()">บันทึก</button>
            </div>
        </div>
    </div>
</div>