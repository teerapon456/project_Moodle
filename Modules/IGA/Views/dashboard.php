<?php

/**
 * IGA Dashboard - Content Fragment
 * Full logic migrated from legacy dashboard.php (477 lines)
 * Variables available: $user, $isApplicant, $isEmployee, $pdo, $canManage
 */
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../Models/TestModel.php';
$testModel = new TestModel($pdo);

// Get user profile info for targeting
$userId = $user['id'] ?? null;
$emplevelId = null;
$orgUnitName = null;
$emptype = null;

if ($isEmployee && !empty($user['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT emplevel_id, OrgUnitName, emptype FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $user['id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($profile) {
            $emplevelId = isset($profile['emplevel_id']) ? (int)$profile['emplevel_id'] : null;
            $orgUnitName = $profile['OrgUnitName'] ?? null;
            $emptype = $profile['emptype'] ?? 'employee';
        }
    } catch (Exception $e) {
        // fallback
    }
} elseif ($isApplicant) {
    $emptype = 'applicant';
}

// Build dashboard tests with full visibility/status logic
$dashboardTests = [];
$userAttempts = [];
try {
    $dashboardTests = $testModel->buildDashboardTests($emplevelId, $orgUnitName, $emptype, $userId);
    if ($userId) {
        $userAttempts = $testModel->getUserAttempts($userId);
    }
} catch (Exception $e) {
    // Log but don't crash
    error_log("[IGA Dashboard] " . $e->getMessage());
}

// Count stats
$totalTests = $testModel->getTestCount();
$inProgressCount = 0;
$completedCount = 0;
foreach ($userAttempts as $a) {
    if ($a['is_completed']) $completedCount++;
    else $inProgressCount++;
}

$mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';
?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    <div class="bg-white border-l-4 border-primary rounded-2xl p-4 md:p-6 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-maroon-50 flex items-center justify-center text-primary text-2xl md:text-3xl transition-transform group-hover:scale-110">
                <i class="ri-file-list-3-fill"></i>
            </div>
            <div>
                <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= count($dashboardTests) ?></div>
                <div class="text-gray-500 text-xs md:text-sm font-medium">แบบทดสอบที่เปิดอยู่</div>
            </div>
        </div>
    </div>

    <div class="bg-white border-l-4 border-amber-500 rounded-2xl p-4 md:p-6 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 text-2xl md:text-3xl transition-transform group-hover:scale-110">
                <i class="ri-time-fill"></i>
            </div>
            <div>
                <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $inProgressCount ?></div>
                <div class="text-gray-500 text-xs md:text-sm font-medium">กำลังทำอยู่</div>
            </div>
        </div>
    </div>

    <div class="bg-white border-l-4 border-emerald-500 rounded-2xl p-4 md:p-6 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-2xl md:text-3xl transition-transform group-hover:scale-110">
                <i class="ri-checkbox-circle-fill"></i>
            </div>
            <div>
                <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $completedCount ?></div>
                <div class="text-gray-500 text-xs md:text-sm font-medium">ทำเสร็จแล้ว</div>
            </div>
        </div>
    </div>

    <?php if ($canManage): ?>
        <div class="bg-white border-l-4 border-blue-500 rounded-2xl p-4 md:p-6 shadow-sm hover:shadow-md transition-all group">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 text-2xl md:text-3xl transition-transform group-hover:scale-110">
                    <i class="ri-database-2-fill"></i>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $totalTests ?></div>
                    <div class="text-gray-500 text-xs md:text-sm font-medium">รวมในระบบ</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Available Tests -->
<div class="mb-6 flex items-center justify-between">
    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
        <i class="ri-bookmark-3-fill text-primary"></i>
        แบบทดสอบสำหรับคุณ
    </h2>
    <div class="h-px flex-grow mx-4 bg-gray-200 hidden sm:block"></div>
</div>

<?php if (count($dashboardTests) > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
        <?php foreach ($dashboardTests as $test):
            $isInProgress = $test['status'] === 'in_progress';
            $statusColor = $isInProgress ? 'amber' : 'primary';
        ?>
            <div class="group relative bg-white border border-gray-100 rounded-3xl p-1 shadow-sm hover:shadow-xl transition-all hover:-translate-y-1">
                <div class="h-full flex flex-col p-5">
                    <!-- Status Badge -->
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex gap-2">
                            <?php if ($isInProgress): ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                    </span>
                                    In Progress
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($test['is_random_mode'])): ?>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-[10px] font-bold uppercase tracking-wider">
                                    <i class="ri-shuffle-line"></i> Randomized (<?= (int)$test['random_total_count'] ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($test['test_no'])): ?>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Set: <?= htmlspecialchars($test['test_no']) ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2 leading-tight group-hover:text-primary transition-colors">
                        <?= htmlspecialchars($test['test_name']) ?>
                    </h3>

                    <?php if (!empty($test['description'])): ?>
                        <p class="text-gray-500 text-sm line-clamp-2 mb-4">
                            <?= htmlspecialchars(strip_tags($test['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="mt-auto pt-4 border-t border-gray-50 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 text-xs font-medium text-gray-400">
                            <?php if (isset($test['duration_minutes'])): ?>
                                <span class="flex items-center gap-1"><i class="ri-time-line"></i> <?= $test['duration_minutes'] ?> นาที</span>
                            <?php endif; ?>
                            <?php
                            $allLangs = [];
                            $currentLang = !empty($test['language']) ? strtoupper($test['language']) : '';
                            if ($currentLang) $allLangs[] = $currentLang;
                            foreach (($test['language_options'] ?? []) as $lo) {
                                $lu = strtoupper($lo['language'] ?? '');
                                if ($lu && $lu !== $currentLang) $allLangs[] = $lu;
                            }
                            if (!empty($allLangs)):
                            ?>
                                <span class="flex items-center gap-1"><i class="ri-global-line"></i> <?= implode('/', $allLangs) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($isInProgress): ?>
                            <form action="<?= $baseUrl ?>/" method="GET" class="shrink-0">
                                <input type="hidden" name="page" value="take_test">
                                <?php if (!empty($mid)): ?><input type="hidden" name="mid" value="<?= htmlspecialchars($_GET['mid']) ?>"><?php endif; ?>
                                <input type="hidden" name="test_id" value="<?= (int)$test['test_id'] ?>">
                                <input type="hidden" name="attempt_id" value="<?= (int)$test['attempt_id'] ?>">
                                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl text-sm font-bold shadow-lg shadow-amber-200 transition-all active:scale-95">
                                    <i class="ri-play-fill text-lg"></i> ทำต่อ
                                </button>
                            </form>
                            <?php else:
                            $hasMultiLang = count($test['language_options'] ?? []) > 1;
                            if ($hasMultiLang): ?>
                                <button type="button" class="inline-flex items-center gap-2 px-5 py-2 bg-primary hover:bg-maroon-800 text-white rounded-2xl text-sm font-bold shadow-lg shadow-red-200 transition-all active:scale-95"
                                    data-bs-toggle="modal" data-bs-target="#langModal"
                                    data-test-name="<?= htmlspecialchars($test['test_name']) ?>"
                                    data-test-no="<?= htmlspecialchars($test['test_no'] ?? '') ?>"
                                    data-langs='<?= json_encode($test['language_options']) ?>'>
                                    <i class="ri-global-line"></i> เริ่มทำ
                                </button>
                            <?php else: ?>
                                <form action="<?= $baseUrl ?>/" method="GET" class="shrink-0">
                                    <input type="hidden" name="page" value="take_test">
                                    <?php if (!empty($mid)): ?><input type="hidden" name="mid" value="<?= htmlspecialchars($_GET['mid']) ?>"><?php endif; ?>
                                    <input type="hidden" name="test_id" value="<?= (int)$test['test_id'] ?>">
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-primary hover:bg-maroon-800 text-white rounded-2xl text-sm font-bold shadow-lg shadow-red-200 transition-all active:scale-95 group/btn">
                                        เริ่มทำ <i class="ri-arrow-right-line group-hover/btn:translate-x-1 transition-transform"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-3xl p-12 text-center">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-400">
            <i class="ri-inbox-line text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">ยังไม่มีแบบทดสอบ</h3>
        <p class="text-gray-500 max-w-sm mx-auto">ขณะนี้ยังไม่มีแบบทดสอบที่เปิดให้คุณทำ กรุณาติดต่อฝ่ายบุคคลหากมีข้อสงสัย</p>
    </div>
<?php endif; ?>

<!-- Language Selection Modal -->
<div class="modal fade" id="langModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content overflow-hidden border-0 rounded-[2rem] shadow-2xl">
            <div class="relative px-8 pt-8 pb-4">
                <button type="button" class="absolute right-6 top-6 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors" data-bs-dismiss="modal">
                    <i class="ri-close-line text-xl"></i>
                </button>
                <div class="w-16 h-16 rounded-2xl bg-maroon-50 flex items-center justify-center text-primary text-3xl mb-4">
                    <i class="ri-global-line"></i>
                </div>
                <h5 class="text-xl font-bold text-gray-900" id="langModalTitle">เลือกภาษา</h5>
                <p class="text-gray-500 text-sm">กรุณาเลือกภาษาที่ท่านต้องการใช้ในการทำแบบทดสอบ</p>
            </div>

            <div class="px-8 py-6">
                <form action="<?= $baseUrl ?>/" method="GET" id="langForm" class="space-y-3">
                    <input type="hidden" name="page" value="take_test">
                    <?php if (!empty($mid)): ?><input type="hidden" name="mid" value="<?= htmlspecialchars($_GET['mid']) ?>"><?php endif; ?>
                    <div id="langRadios" class="grid grid-cols-1 gap-3"></div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-6">
                        <button type="button" class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl font-bold transition-all active:scale-95" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-primary hover:bg-maroon-800 text-white rounded-2xl font-bold shadow-lg shadow-red-200 disabled:opacity-50 disabled:shadow-none transition-all active:scale-95" id="confirmLangBtn" disabled>ยืนยันภาษา</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom Styles for Dashboard */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    #langRadios label {
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border: 2px solid #f1f5f9;
        border-radius: 1.25rem;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s;
    }

    #langRadios input:checked+label {
        background-color: #fef2f2;
        border-color: #b91c1c;
        color: #b91c1c;
    }

    #langRadios label:hover {
        border-color: #cbd5e1;
    }

    #langRadios input:checked+label:after {
        content: '\eb7a';
        font-family: 'remixicon';
        font-size: 1.25rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tooltip init (Bootstrap 5)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Language modal handling
        var langModal = document.getElementById('langModal');
        if (langModal) {
            langModal.addEventListener('show.bs.modal', function(e) {
                var btn = e.relatedTarget;
                var name = btn.getAttribute('data-test-name') || '';
                var testNo = btn.getAttribute('data-test-no') || '';
                var langs = JSON.parse(btn.getAttribute('data-langs') || '[]');

                document.getElementById('langModalTitle').textContent = name + (testNo ? ' (ชุด ' + testNo + ')' : '');
                var container = document.getElementById('langRadios');
                container.innerHTML = '';
                document.getElementById('confirmLangBtn').disabled = true;

                langs.sort((a, b) => (a.language || '').localeCompare(b.language || ''));
                langs.forEach(function(lo, i) {
                    var div = document.createElement('div');

                    var inp = document.createElement('input');
                    inp.type = 'radio';
                    inp.className = 'hidden';
                    inp.id = 'lr' + i;
                    inp.name = 'test_id';
                    inp.value = lo.test_id;
                    inp.required = true;

                    var lbl = document.createElement('label');
                    lbl.htmlFor = 'lr' + i;
                    lbl.textContent = (lo.language || '').toUpperCase();

                    div.appendChild(inp);
                    div.appendChild(lbl);
                    container.appendChild(div);

                    inp.addEventListener('change', () => {
                        document.getElementById('confirmLangBtn').disabled = false;
                    });
                });
            });

            langModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('confirmLangBtn').disabled = true;
                if (document.getElementById('langForm')) {
                    document.getElementById('langForm').reset();
                }
            });
        }
    });
</script>