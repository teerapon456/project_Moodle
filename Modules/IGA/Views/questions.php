<?php

/**
 * IGA Questions Management - Content Fragment
 * Loaded inside index.php layout via ?page=questions
 * Variables available: $pdo, $canManage, $user
 */

if (!$canManage) {
    echo '<div class="alert alert-danger">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>';
    return;
}

require_once __DIR__ . '/../Models/QuestionModel.php';
require_once __DIR__ . '/../Models/TestModel.php';
require_once __DIR__ . '/../Models/CategoryModel.php';
$questionModel = new QuestionModel($pdo);
$testModel = new TestModel($pdo);
$categoryModel = new CategoryModel($pdo);

$viewTestId = isset($_GET['test_id']) ? (int)$_GET['test_id'] : null;
$mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';

// --- AJAX HANDLER ---
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_GET['type'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    header('Content-Type: application/json');

    try {
        if ($type === 'save_section') {
            if (!empty($input['section_id'])) {
                $res = $questionModel->updateSection($input['section_id'], $input);
            } else {
                $res = $questionModel->createSection($input);
            }
            echo json_encode(['success' => (bool)$res]);
        } elseif ($type === 'delete_section') {
            $res = $questionModel->deleteSection($input['section_id']);
            echo json_encode(['success' => (bool)$res]);
        } elseif ($type === 'save_question') {
            if (!empty($input['question_id'])) {
                $res = $questionModel->updateQuestion($input['question_id'], $input);
                $qid = $input['question_id'];
            } else {
                $qid = $questionModel->createQuestion($input);
                $res = (bool)$qid;
            }
            if ($res && !empty($input['options'])) {
                $questionModel->setOptions($qid, $input['options']);
            }
            echo json_encode(['success' => (bool)$res]);
        } elseif ($type === 'delete_question') {
            $res = $questionModel->deleteQuestion($input['question_id']);
            echo json_encode(['success' => (bool)$res]);
        } elseif ($type === 'get_question') {
            $q = $questionModel->getQuestionById($input['question_id']);
            $opts = $questionModel->getOptionsByQuestionId($input['question_id']);
            echo json_encode(['success' => true, 'question' => $q, 'options' => $opts]);
        } elseif ($type === 'get_test_settings') {
            $testId = $input['test_id'] ?? 0;
            $test = $testModel->getTestById($testId);
            if (!$test) throw new Exception('Test not found');
            echo json_encode([
                'success' => true,
                'is_random_mode' => (int)($test['is_random_mode'] ?? 0),
                'section_random_counts' => json_decode($test['section_random_counts'] ?? '[]', true),
                'always_include_questions' => json_decode($test['always_include_questions'] ?? '[]', true),
                'target_levels' => array_map(fn($l) => $l['level_id'], $testModel->getTestEmpLevels($testId)),
                'target_orgunits' => $testModel->getTestOrgUnits($testId),
                'target_users' => $testModel->getTestUsers($testId),
                'all_levels' => $testModel->getAllEmpLevels(),
                'all_orgunits' => $testModel->getAllOrgUnits(),
                'all_questions' => $testModel->getAllQuestions($testId),
                'sections' => $testModel->getSections($testId)
            ]);
        } elseif ($type === 'save_test_settings') {
            $testId = $input['test_id'] ?? 0;
            $testModel->setRandomSettings($testId, [
                'is_random_mode' => $input['is_random_mode'] ?? 0,
                'section_random_counts' => json_encode($input['section_random_counts'] ?? []),
                'always_include_questions' => json_encode($input['always_include_questions'] ?? [])
            ]);
            $testModel->setTestEmpLevels($testId, $input['target_levels'] ?? []);
            $testModel->setTestOrgUnits($testId, $input['target_orgunits'] ?? []);
            $testModel->setTestUsers($testId, $input['target_users'] ?? []);
            echo json_encode(['success' => true]);
        } elseif ($type === 'search_users') {
            $query = $input['query'] ?? '';
            $users = $testModel->searchUsers($query);
            echo json_encode(['success' => true, 'users' => $users]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get sections with questions for specific test
if ($viewTestId) {
    $testInfo = $testModel->getTestById($viewTestId);
    if (!$testInfo) {
        echo '<div class="alert alert-danger">ไม่พบแบบทดสอบ</div>';
        return;
    }
    $sections = $questionModel->getSectionsWithQuestions($viewTestId);
    $categories = $categoryModel->getAllCategories();
    $randomSettings = $testModel->getRandomSettings($viewTestId);
    $empLevels = $testModel->getTestEmpLevels($viewTestId);
    $orgUnits = $testModel->getTestOrgUnits($viewTestId);
    $totalMaxScore = 0;
    foreach ($sections as $s) $totalMaxScore += (int)($s['max_score'] ?? 0);
?>

    <!-- Test Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($testInfo['test_name']) ?></h5>
            <div class="d-flex flex-wrap gap-2 text-muted small">
                <?php if (!empty($testInfo['test_no'])): ?>
                    <span><i class="ri-hashtag me-1"></i><?= htmlspecialchars($testInfo['test_no']) ?></span>
                <?php endif; ?>
                <?php if (!empty($testInfo['language'])): ?>
                    <span><i class="ri-global-line me-1"></i><?= strtoupper($testInfo['language']) ?></span>
                <?php endif; ?>
                <span><i class="ri-time-line me-1"></i><?= $testInfo['duration_minutes'] ?? 0 ?> นาที</span>
                <span><i class="ri-star-line me-1"></i>คะแนนเต็ม: <?= $totalMaxScore ?></span>
                <?php if (!empty($testInfo['min_passing_score'])): ?>
                    <span><i class="ri-checkbox-circle-line me-1"></i>ผ่าน: <?= $testInfo['min_passing_score'] ?>%</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" onclick="addSection(<?= $viewTestId ?>)">
                <i class="ri-add-line me-1"></i>เพิ่ม Section
            </button>
            <a href="?page=questions<?= $mid ?>" class="btn btn-outline-secondary btn-sm">
                <i class="ri-arrow-left-line me-1"></i>กลับ
            </a>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="fw-bold fs-4 text-primary"><?= count($sections) ?></div>
                <div class="small text-muted">ส่วน (Sections)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="fw-bold fs-4 text-success"><?= array_sum(array_column($sections, 'question_count')) ?></div>
                <div class="small text-muted">คำถาม</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="fw-bold fs-4 text-warning"><?= $totalMaxScore ?></div>
                <div class="small text-muted">คะแนนเต็ม</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center py-2">
                <?php if (!empty($randomSettings) && $randomSettings['is_random_mode']): ?>
                    <div class="fw-bold fs-4 text-info"><i class="ri-shuffle-line"></i></div>
                    <div class="small text-muted">โหมดสุ่ม</div>
                <?php else: ?>
                    <div class="fw-bold fs-4 text-secondary"><i class="ri-list-ordered"></i></div>
                    <div class="small text-muted">เรียงตามลำดับ</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Targeting Info -->
    <?php if (!empty($empLevels) || !empty($orgUnits)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="ri-focus-3-line me-2"></i>กลุ่มเป้าหมาย</h6>
                <?php if (!empty($empLevels)): ?>
                    <div class="mb-1"><strong class="small">Employee Levels:</strong>
                        <?php foreach ($empLevels as $el): ?>
                            <span class="badge bg-info me-1"><?= htmlspecialchars($el['emplevel_name'] ?? 'Level ' . $el['level_id']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($orgUnits)): ?>
                    <div><strong class="small">Org Units:</strong>
                        <?php foreach ($orgUnits as $ou): ?>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($ou) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Random Settings -->
    <?php if (!empty($randomSettings) && $randomSettings['is_random_mode']): ?>
        <div class="card border-0 shadow-sm mb-4 border-start border-warning border-4">
            <div class="card-body">
                <h6 class="fw-bold mb-2"><i class="ri-shuffle-line text-warning me-2"></i>ตั้งค่าการสุ่มคำถาม</h6>
                <?php
                $sectionCounts = json_decode($randomSettings['section_random_counts'] ?? '{}', true);
                $alwaysInclude = json_decode($randomSettings['always_include_questions'] ?? '[]', true);
                ?>
                <?php if (!empty($sectionCounts)): ?>
                    <div class="mb-2">
                        <strong class="small">จำนวนข้อสุ่มต่อส่วน:</strong>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <?php foreach ($sectionCounts as $secId => $count): ?>
                                <?php
                                $secName = '';
                                foreach ($sections as $s) {
                                    if ($s['section_id'] == $secId) {
                                        $secName = $s['section_name'];
                                        break;
                                    }
                                }
                                ?>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($secName ?: "Section $secId") ?>: <strong><?= is_array($count) ? ($count['count'] ?? 0) : $count ?></strong> ข้อ
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($alwaysInclude)): ?>
                    <div class="small text-muted">
                        <i class="ri-pushpin-2-line me-1"></i>คำถามที่ต้องแสดงเสมอ: <?= count($alwaysInclude) ?> ข้อ
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sections Accordion -->
    <?php if (!empty($sections)): ?>
        <div class="accordion" id="sectionAccordion">
            <?php foreach ($sections as $idx => $section): ?>
                <div class="accordion-item border-0 shadow-sm mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#sec<?= $section['section_id'] ?>">
                            <div class="d-flex justify-content-between w-100 me-3">
                                <div>
                                    <span class="fw-bold"><?= htmlspecialchars($section['section_name']) ?></span>
                                    <span class="text-muted small ms-2">
                                        <?= $section['question_count'] ?> ข้อ | <?= (int)($section['max_score'] ?? 0) ?> คะแนน
                                        <?php if ($section['duration_minutes']): ?> | <?= $section['duration_minutes'] ?> นาที<?php endif; ?>
                                    </span>
                                </div>
                                <div class="btn-group btn-group-sm ms-auto me-2" onclick="event.stopPropagation()">
                                    <button class="btn btn-outline-primary" onclick="addQuestion(<?= $section['section_id'] ?>)" title="เพิ่มคำถาม">
                                        <i class="ri-add-line"></i>
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="editSection(<?= htmlspecialchars(json_encode($section)) ?>)" title="แก้ไข Section">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteSection(<?= $section['section_id'] ?>)" title="ลบ Section">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="sec<?= $section['section_id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>">
                        <div class="accordion-body p-0">
                            <?php if (!empty($section['description'])): ?>
                                <div class="bg-light p-3 border-bottom small text-muted">
                                    <i class="ri-information-line me-1"></i><?= htmlspecialchars($section['description']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($section['questions'] ?? [] as $qIdx => $q): ?>
                                    <div class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="me-3 flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-primary"><?= $qIdx + 1 ?></span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($q['question_text']) ?></span>
                                                </div>
                                                <div class="d-flex gap-2 flex-wrap mb-1">
                                                    <span class="badge bg-secondary bg-opacity-50 small">
                                                        <?php
                                                        $typeLabels = ['multiple_choice' => 'ปรนัย', 'true_false' => 'ถูก/ผิด', 'short_answer' => 'อัตนัย', 'accept' => 'ยอมรับ'];
                                                        echo $typeLabels[$q['question_type']] ?? $q['question_type'];
                                                        ?>
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-50 small"><?= $q['score'] ?? 0 ?> คะแนน</span>
                                                    <?php if (!empty($q['is_critical'])): ?>
                                                        <span class="badge bg-danger small"><i class="ri-error-warning-line me-1"></i>Critical</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($q['category_name'])): ?>
                                                        <span class="badge bg-info bg-opacity-50 small"><?= htmlspecialchars($q['category_name']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <!-- Options -->
                                                <?php if (!empty($q['options'])): ?>
                                                    <div class="mt-2 ms-3">
                                                        <?php foreach ($q['options'] as $opt): ?>
                                                            <div class="small <?= $opt['is_correct'] ? 'text-success fw-bold' : 'text-muted' ?>">
                                                                <?= $opt['is_correct'] ? '<i class="ri-checkbox-circle-fill me-1"></i>' : '<i class="ri-checkbox-blank-circle-line me-1"></i>' ?>
                                                                <?= htmlspecialchars($opt['option_text']) ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-warning" onclick="editQuestion(<?= $q['question_id'] ?>)" title="แก้ไขคำถาม">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="deleteQuestion(<?= $q['question_id'] ?>)" title="ลบคำถาม">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (empty($section['questions'])): ?>
                                    <div class="list-group-item text-center text-muted py-3">ยังไม่มีคำถามในส่วนนี้</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-4 text-muted">
            <i class="ri-inbox-line ri-2x mb-2 d-block"></i>
            <p>ยังไม่มี Section ในแบบทดสอบนี้</p>
        </div>
    <?php endif; ?>

<?php
} else {
    // No test selected — redirect to tests management page
    $mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';
    echo '<script>window.location.href = "?page=tests' . $mid . '";</script>';
    return;
}
?>


<!-- Section Modal -->
<div class="modal fade" id="sectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionModalTitle">เพิ่ม Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sectionForm">
                    <input type="hidden" name="section_id" id="sec_id">
                    <input type="hidden" name="test_id" id="sec_test_id">
                    <div class="mb-3">
                        <label class="form-label">ชื่อ Section</label>
                        <input type="text" class="form-control" name="section_name" id="sec_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">คำอธิบาย (ถ้ามี)</label>
                        <textarea class="form-control" name="description" id="sec_desc" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">เวลา (นาที)</label>
                            <input type="number" class="form-control" name="duration_minutes" id="sec_dur" value="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">ลำดับ</label>
                            <input type="number" class="form-control" name="section_order" id="sec_order" value="0">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveSection()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="questionModalTitle">เพิ่มคำถาม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="questionForm">
                    <input type="hidden" name="question_id" id="q_id">
                    <input type="hidden" name="section_id" id="q_sec_id">
                    <div class="mb-3">
                        <label class="form-label">โจทย์คำถาม</label>
                        <textarea class="form-control" name="question_text" id="q_text" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ประเภทคำถาม</label>
                            <select class="form-select" name="question_type" id="q_type" onchange="toggleOptions()" required>
                                <option value="multiple_choice">ปรนัย (หลายตัวเลือก)</option>
                                <option value="true_false">ถูก/ผิด</option>
                                <option value="short_answer">เติมคำตอบ</option>
                                <option value="accept">ยอมรับข้อตกลง</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">หมวดหมู่</label>
                            <select class="form-select" name="category_id" id="q_cat">
                                <option value="">-- ไม่ระบุ --</option>
                                <?php foreach ($categories ?? [] as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">คะแนน</label>
                            <input type="number" class="form-control" name="score" id="q_score" value="1">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">ลำดับ</label>
                            <input type="number" class="form-control" name="question_order" id="q_order" value="0">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_critical" id="q_crit" value="1">
                                <label class="form-check-label" for="q_crit">Critical Question</label>
                            </div>
                        </div>
                    </div>

                    <!-- Options Section -->
                    <div id="optionsWrapper" style="display: none;">
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 fw-bold">ตัวเลือกคำตอบ</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOptionRow()">
                                <i class="ri-add-line me-1"></i>เพิ่มตัวเลือก
                            </button>
                        </div>
                        <div id="optionsList">
                            <!-- Rows added by JS -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveQuestion()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Use relative URL to avoid Mixed Content / CORS issues and handle port/protocol automatically
    var ajaxUrl = '?page=questions&action=fetch_data';
    var sectionModal, questionModal;
    var searchTimeout = null;

    // Initialize modals only when everything is loaded
    function initModals() {
        try {
            if (typeof bootstrap !== 'undefined') {
                sectionModal = new bootstrap.Modal(document.getElementById('sectionModal'));
                questionModal = new bootstrap.Modal(document.getElementById('questionModal'));
            } else {
                console.error('Bootstrap is not defined. Modals may not work.');
            }
        } catch (e) {
            console.error('Error initializing modals:', e);
        }
    }

    if (document.readyState === 'complete') {
        initModals();
    } else {
        window.addEventListener('load', initModals);
    }

    // --- Randomization Settings ---

    async function openRandomSettings(testId) {
        const res = await callAjax('get_test_settings', {
            test_id: testId
        });
        if (res.success) {
            document.getElementById('ts_test_id').value = testId;

            // Random Mode & Quotas
            document.getElementById('ts_is_random').checked = res.is_random_mode === 1;
            const quotaBody = document.getElementById('ts_section_quotas');
            quotaBody.innerHTML = '';
            res.sections.forEach(sec => {
                const quota = res.section_random_counts[sec.section_id] || (typeof res.section_random_counts[sec.section_id] === 'object' ? res.section_random_counts[sec.section_id].count : 0);
                quotaBody.innerHTML += `
                    <tr>
                        <td class="small ps-3 pt-2">${sec.section_name}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm ts-quota-input" 
                                   data-section-id="${sec.section_id}" value="${quota}" min="0">
                        </td>
                        <td class="text-center small py-2">${sec.question_count}</td>
                    </tr>
                `;
            });

            // Always Include Questions
            const alwaysList = document.getElementById('ts_always_list');
            alwaysList.innerHTML = '';
            if (res.all_questions && res.all_questions.length > 0) {
                res.all_questions.forEach(q => {
                    const checked = res.always_include_questions.includes(parseInt(q.question_id)) ? 'checked' : '';
                    alwaysList.innerHTML += `
                        <div class="form-check border-bottom mb-1 pb-1">
                            <input class="form-check-input ts-always-check" type="checkbox" value="${q.question_id}" id="qalways${q.question_id}" ${checked}>
                            <label class="form-check-label x-small d-block" for="qalways${q.question_id}">
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

        const res = await callAjax('save_test_settings', data);
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                showConfirmButton: false,
                timer: 1500
            });
            randomModal.hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire('Error', res.message || 'Save failed', 'error');
        }
    }

    // --- Sections ---

    function addSection(testId) {
        document.getElementById('sectionModalTitle').textContent = 'เพิ่ม Section';
        document.getElementById('sectionForm').reset();
        document.getElementById('sec_id').value = '';
        document.getElementById('sec_test_id').value = testId;
        if (sectionModal) sectionModal.show();
        else Swal.fire('Error', 'Modal system not ready', 'error');
    }

    function editSection(data) {
        document.getElementById('sectionModalTitle').textContent = 'แก้ไข Section';
        document.getElementById('sec_id').value = data.section_id;
        document.getElementById('sec_test_id').value = data.test_id;
        document.getElementById('sec_name').value = data.section_name;
        document.getElementById('sec_desc').value = data.description || '';
        document.getElementById('sec_dur').value = data.duration_minutes || 0;
        document.getElementById('sec_order').value = data.section_order || 0;
        if (sectionModal) sectionModal.show();
        else Swal.fire('Error', 'Modal system not ready', 'error');
    }

    async function saveSection() {
        const data = {
            section_id: document.getElementById('sec_id').value,
            test_id: document.getElementById('sec_test_id').value,
            section_name: document.getElementById('sec_name').value,
            description: document.getElementById('sec_desc').value,
            duration_minutes: document.getElementById('sec_dur').value,
            section_order: document.getElementById('sec_order').value
        };

        if (!data.section_name) return Swal.fire('ผิดพลาด', 'กรุณาระบุชื่อ Section', 'error');

        const res = await callAjax('save_section', data);
        if (res.success) {
            if (sectionModal) sectionModal.hide();
            location.reload();
        }
    }

    function deleteSection(id) {
        Swal.fire({
            title: 'ยืนยันการลบ Section?',
            text: "คำถามทั้งหมดใน Section นี้จะถูกลบไปด้วย!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ลบข้อมูล'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const res = await callAjax('delete_section', {
                    section_id: id
                });
                if (res.success) location.reload();
            }
        });
    }

    // --- Questions ---

    function addQuestion(secId) {
        document.getElementById('questionModalTitle').textContent = 'เพิ่มคำถาม';
        document.getElementById('questionForm').reset();
        document.getElementById('q_id').value = '';
        document.getElementById('q_sec_id').value = secId;
        document.getElementById('optionsList').innerHTML = '';
        toggleOptions();
        if (questionModal) questionModal.show();
        else Swal.fire('Error', 'Modal system not ready', 'error');
    }

    async function editQuestion(id) {
        const res = await callAjax('get_question', {
            question_id: id
        });
        if (res.success) {
            const q = res.question;
            document.getElementById('questionModalTitle').textContent = 'แก้ไขคำถาม';
            document.getElementById('q_id').value = q.question_id;
            document.getElementById('q_sec_id').value = q.section_id;
            document.getElementById('q_text').value = q.question_text;
            document.getElementById('q_type').value = q.question_type;
            document.getElementById('q_cat').value = q.category_id || '';
            document.getElementById('q_score').value = q.score;
            document.getElementById('q_order').value = q.question_order;
            document.getElementById('q_crit').checked = q.is_critical == 1;

            toggleOptions();
            document.getElementById('optionsList').innerHTML = '';
            if (res.options) {
                res.options.forEach(opt => addOptionRow(opt.option_text, opt.is_correct == 1));
            }
            if (questionModal) questionModal.show();
            else Swal.fire('Error', 'Modal system not ready', 'error');
        }
    }

    function toggleOptions() {
        const type = document.getElementById('q_type').value;
        const wrapper = document.getElementById('optionsWrapper');
        if (type === 'multiple_choice' || type === 'true_false') {
            wrapper.style.display = 'block';
            if (document.getElementById('optionsList').children.length === 0) {
                if (type === 'true_false') {
                    addOptionRow('ถูก', false);
                    addOptionRow('ผิด', false);
                } else {
                    addOptionRow('', false);
                    addOptionRow('', false);
                }
            }
        } else {
            wrapper.style.display = 'none';
        }
    }

    function addOptionRow(text = '', isCorrect = false) {
        const div = document.createElement('div');
        div.className = 'input-group mb-2 option-row';
        div.innerHTML = `
            <div class="input-group-text">
                <input class="form-check-input mt-0 is-correct-check" type="radio" name="correct_opt" ${isCorrect ? 'checked' : ''}>
            </div>
            <input type="text" class="form-control opt-text" value="${text}" placeholder="ตัวเลือก...">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
        `;
        document.getElementById('optionsList').appendChild(div);
    }

    async function saveQuestion() {
        const options = [];
        document.querySelectorAll('.option-row').forEach(row => {
            options.push({
                option_text: row.querySelector('.opt-text').value,
                is_correct: row.querySelector('.is-correct-check').checked ? 1 : 0
            });
        });

        const data = {
            question_id: document.getElementById('q_id').value,
            section_id: document.getElementById('q_sec_id').value,
            question_text: document.getElementById('q_text').value,
            question_type: document.getElementById('q_type').value,
            category_id: document.getElementById('q_cat').value,
            score: document.getElementById('q_score').value,
            question_order: document.getElementById('q_order').value,
            is_critical: document.getElementById('q_crit').checked ? 1 : 0,
            options: options
        };

        if (!data.question_text) return Swal.fire('ผิดพลาด', 'กรุณาระบุโจทย์คำถาม', 'error');

        const res = await callAjax('save_question', data);
        if (res.success) {
            if (questionModal) questionModal.hide();
            location.reload();
        }
    }

    function deleteQuestion(id) {
        Swal.fire({
            title: 'ยืนยันการลบคำถาม?',
            text: "ข้อมูลตัวเลือกจะถูกลบไปด้วย!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ลบข้อมูล'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const res = await callAjax('delete_question', {
                    question_id: id
                });
                if (res.success) location.reload();
            }
        });
    }

    async function callAjax(type, data) {
        try {
            const response = await fetch(`${ajaxUrl}&type=${type}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (e) {
            console.error(e);
            return {
                success: false,
                message: 'Network error'
            };
        }
    }
</script>

<style>
    .hover-shadow:hover {
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
    }

    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: inherit;
    }
</style>
</content>