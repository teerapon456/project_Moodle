<?php

/**
 * IGA Test Overview - Content Fragment
 * Loaded inside index.php layout via ?page=test_overview&id=X
 * Variables available: $pdo, $canManage, $user
 */

if (!$canManage) {
    echo '<div class="alert alert-danger">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>';
    return;
}

require_once __DIR__ . '/../Models/TestModel.php';
require_once __DIR__ . '/../Models/QuestionModel.php';
$testModel = new TestModel($pdo);
$questionModel = new QuestionModel($pdo);

$testId = (int)($_GET['id'] ?? 0);
$mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';

if (!$testId) {
    echo '<script>window.location.href = "?page=tests' . $mid . '";</script>';
    return;
}

$test = $testModel->getTestById($testId);
if (!$test) {
    echo '<div class="bg-white rounded-3xl p-8 text-center shadow-sm border border-gray-100">
        <i class="ri-error-warning-line text-5xl text-red-300 mb-4 block"></i>
        <h3 class="text-xl font-bold text-gray-800 mb-2">ไม่พบแบบทดสอบ</h3>
        <a href="?page=tests' . $mid . '" class="mt-4 inline-block px-6 py-2 bg-primary text-white rounded-xl font-bold">กลับหน้ารายการ</a>
    </div>';
    return;
}

// Fetch related data
$sections = $questionModel->getSectionsWithQuestions($testId);
$empLevels = $testModel->getTestEmpLevels($testId);
$orgUnits = $testModel->getTestOrgUnits($testId);
$targetUsers = $testModel->getTestUsers($testId);
$randomSettings = $testModel->getRandomSettings($testId);

// Calculate stats
$totalQuestions = 0;
$totalMaxScore = 0;
foreach ($sections as $s) {
    $totalQuestions += (int)($s['question_count'] ?? 0);
    $totalMaxScore += (int)($s['max_score'] ?? 0);
}

// Status
$now = date('Y-m-d H:i:s');
$isScheduled = !empty($test['published_at']) && $test['published_at'] > $now;
$isExpired = !empty($test['unpublished_at']) && $test['unpublished_at'] <= $now;
$status = 'published';
if (!$test['is_published']) $status = 'draft';
elseif ($isScheduled) $status = 'scheduled';
elseif ($isExpired) $status = 'expired';

$statusConfig = [
    'draft'     => ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => 'ปิดการใช้งาน',  'icon' => 'ri-eye-off-line'],
    'scheduled' => ['bg' => 'bg-blue-50',  'text' => 'text-blue-600', 'label' => 'รอกำหนดการ',    'icon' => 'ri-calendar-todo-line'],
    'expired'   => ['bg' => 'bg-red-50',   'text' => 'text-red-600',  'label' => 'หมดอายุแล้ว',   'icon' => 'ri-history-line'],
    'published' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'label' => 'กำลังเผยแพร่', 'icon' => 'ri-checkbox-circle-line'],
];
$cfg = $statusConfig[$status];
?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <div class="flex items-center gap-3 mb-2">
            <a href="?page=tests<?= $mid ?>" class="w-10 h-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-xl transition-all text-gray-500">
                <i class="ri-arrow-left-line text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($test['test_name']) ?></h2>
                <div class="flex items-center gap-3 mt-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full <?= $cfg['bg'] ?> <?= $cfg['text'] ?> text-xs font-bold">
                        <i class="<?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
                    </span>
                    <?php if (!empty($test['test_no'])): ?>
                        <span class="text-xs text-gray-400 font-mono">#<?= htmlspecialchars($test['test_no']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($test['language'])): ?>
                        <span class="text-xs text-gray-400 uppercase font-bold"><?= strtoupper($test['language']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="?page=edit_test&id=<?= $testId ?><?= $mid ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-600 rounded-xl font-bold text-sm hover:bg-blue-100 transition-all">
            <i class="ri-edit-line"></i> แก้ไข
        </a>
        <a href="?page=questions&test_id=<?= $testId ?><?= $mid ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-50 text-purple-600 rounded-xl font-bold text-sm hover:bg-purple-100 transition-all">
            <i class="ri-question-line"></i> จัดการคำถาม
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm text-center">
        <div class="text-3xl font-bold text-primary mb-1"><?= count($sections) ?></div>
        <div class="text-xs text-gray-400 font-medium">ส่วน (Sections)</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm text-center">
        <div class="text-3xl font-bold text-emerald-500 mb-1"><?= $totalQuestions ?></div>
        <div class="text-xs text-gray-400 font-medium">คำถาม</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm text-center">
        <div class="text-3xl font-bold text-amber-500 mb-1"><?= $totalMaxScore ?></div>
        <div class="text-xs text-gray-400 font-medium">คะแนนเต็ม</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm text-center">
        <div class="text-3xl font-bold text-blue-500 mb-1"><?= $test['duration_minutes'] ?? 0 ?></div>
        <div class="text-xs text-gray-400 font-medium">นาที</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">
    <!-- Test Details -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden h-full">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-2">
                <i class="ri-file-text-line text-primary text-lg"></i>
                <h3 class="font-bold text-gray-800">ข้อมูลแบบทดสอบ</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (!empty($test['description'])): ?>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">คำอธิบาย</label>
                            <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($test['description'])) ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($test['category_name'])): ?>
                        <div>
                            <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">หมวดหมู่</label>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-50 text-primary rounded-lg text-sm font-bold">
                                <i class="ri-price-tag-3-fill"></i> <?= htmlspecialchars($test['category_name']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($test['min_passing_score'])): ?>
                        <div>
                            <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">เกณฑ์ผ่าน</label>
                            <span class="text-sm font-bold text-emerald-600"><?= $test['min_passing_score'] ?>%</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($test['max_attempts'])): ?>
                        <div>
                            <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">จำนวนครั้งที่สอบได้</label>
                            <span class="text-sm font-bold text-gray-700"><?= $test['max_attempts'] ?> ครั้ง</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($test['creator_name'])): ?>
                        <div>
                            <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">สร้างโดย</label>
                            <span class="text-sm text-gray-700"><?= htmlspecialchars($test['creator_name']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($test['published_at'])): ?>
                        <div>
                            <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">เผยแพร่ตั้งแต่</label>
                            <span class="text-sm text-gray-700"><?= $test['published_at'] ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($test['unpublished_at'])): ?>
                        <div>
                            <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">หยุดเผยแพร่</label>
                            <span class="text-sm text-gray-700"><?= $test['unpublished_at'] ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-xs text-gray-400 font-bold uppercase tracking-wide block mb-1">สร้างเมื่อ</label>
                        <span class="text-sm text-gray-700"><?= $test['created_at'] ?? '-' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Targeting & Random -->
    <div class="flex flex-col gap-6">
        <!-- Targeting -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-2">
                <i class="ri-focus-3-line text-blue-500 text-lg"></i>
                <h3 class="font-bold text-gray-800">กลุ่มเป้าหมาย</h3>
            </div>
            <div class="p-6">
                <?php if (empty($empLevels) && empty($orgUnits) && empty($targetUsers)): ?>
                    <div class="text-center text-gray-400 text-sm py-4">
                        <i class="ri-group-line text-3xl block mb-2 opacity-50"></i>
                        เปิดให้ทุกคน (ไม่ได้กำหนดเป้าหมาย)
                    </div>
                <?php else: ?>
                    <?php if (!empty($empLevels)): ?>
                        <div class="mb-4">
                            <label class="text-xs text-gray-400 font-bold block mb-2">ระดับพนักงาน</label>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($empLevels as $el): ?>
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold"><?= htmlspecialchars($el['emplevel_name'] ?? 'Level ' . $el['level_id']) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($orgUnits)): ?>
                        <div class="mb-4">
                            <label class="text-xs text-gray-400 font-bold block mb-2">หน่วยงาน</label>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach ($orgUnits as $ou): ?>
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold"><?= htmlspecialchars($ou) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($targetUsers)): ?>
                        <div>
                            <label class="text-xs text-gray-400 font-bold block mb-2">รายบุคคล (<?= count($targetUsers) ?> คน)</label>
                            <div class="space-y-1.5 max-h-32 overflow-y-auto">
                                <?php foreach ($targetUsers as $tu): ?>
                                    <div class="flex items-center gap-2 text-xs">
                                        <i class="ri-user-line text-gray-300"></i>
                                        <span class="font-bold text-gray-700"><?= htmlspecialchars($tu['fullname']) ?></span>
                                        <span class="text-gray-400"><?= htmlspecialchars($tu['department'] ?? '') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Randomization -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-2">
                <i class="ri-shuffle-line text-amber-500 text-lg"></i>
                <h3 class="font-bold text-gray-800">การสุ่มคำถาม</h3>
            </div>
            <div class="p-6">
                <?php if (!empty($randomSettings) && $randomSettings['is_random_mode']): ?>
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 text-amber-600 rounded-lg text-xs font-bold mb-3">
                        <i class="ri-shuffle-line"></i> เปิดใช้งานโหมดสุ่ม
                    </div>
                    <?php
                    $sectionCounts = json_decode($randomSettings['section_random_counts'] ?? '{}', true);
                    $alwaysInclude = json_decode($randomSettings['always_include_questions'] ?? '[]', true);
                    ?>
                    <?php if (!empty($sectionCounts)): ?>
                        <div class="mb-3">
                            <label class="text-xs text-gray-400 font-bold block mb-2">จำนวนข้อสุ่มต่อส่วน</label>
                            <?php foreach ($sectionCounts as $secId => $count): ?>
                                <?php
                                $secName = '';
                                foreach ($sections as $s) {
                                    if ($s['section_id'] == $secId) {
                                        $secName = $s['section_name'];
                                        break;
                                    }
                                }
                                $countVal = is_array($count) ? ($count['count'] ?? 0) : $count;
                                ?>
                                <div class="flex justify-between items-center text-xs py-1.5 border-b border-gray-50 last:border-0">
                                    <span class="text-gray-600"><?= htmlspecialchars($secName ?: "Section $secId") ?></span>
                                    <span class="font-bold text-amber-600"><?= $countVal ?> ข้อ</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($alwaysInclude)): ?>
                        <div class="text-xs text-gray-500">
                            <i class="ri-pushpin-2-fill text-red-400 me-1"></i>
                            คำถามล็อค: <strong><?= count($alwaysInclude) ?></strong> ข้อ
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center text-gray-400 text-sm py-4">
                        <i class="ri-list-ordered text-3xl block mb-2 opacity-50"></i>
                        แสดงตามลำดับ (ไม่สุ่ม)
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sections & Questions -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="ri-list-check-2 text-purple-500 text-lg"></i>
            <h3 class="font-bold text-gray-800">โครงสร้างแบบทดสอบ</h3>
        </div>
        <a href="?page=questions&test_id=<?= $testId ?><?= $mid ?>" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
            จัดการคำถาม <i class="ri-arrow-right-s-line"></i>
        </a>
    </div>

    <?php if (!empty($sections)): ?>
        <div class="divide-y divide-gray-50">
            <?php foreach ($sections as $idx => $section): ?>
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 flex items-center justify-center bg-purple-50 text-purple-600 rounded-lg font-bold text-sm"><?= $idx + 1 ?></span>
                            <div>
                                <h4 class="font-bold text-gray-800"><?= htmlspecialchars($section['section_name']) ?></h4>
                                <?php if (!empty($section['description'])): ?>
                                    <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($section['description']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-gray-400">
                            <span><strong class="text-gray-600"><?= $section['question_count'] ?? 0 ?></strong> ข้อ</span>
                            <span><strong class="text-gray-600"><?= (int)($section['max_score'] ?? 0) ?></strong> คะแนน</span>
                            <?php if ($section['duration_minutes']): ?>
                                <span><strong class="text-gray-600"><?= $section['duration_minutes'] ?></strong> นาที</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($section['questions'])): ?>
                        <div class="ml-11 space-y-2">
                            <?php foreach ($section['questions'] as $qIdx => $q): ?>
                                <div class="flex items-start gap-3 py-2 px-3 rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                    <span class="text-xs font-bold text-gray-300 mt-0.5 w-5 text-right flex-shrink-0"><?= $qIdx + 1 ?></span>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-sm text-gray-700 leading-relaxed"><?= htmlspecialchars(mb_strimwidth($q['question_text'], 0, 120, '...')) ?></p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <?php
                                            $typeLabels = ['multiple_choice' => 'ปรนัย', 'true_false' => 'ถูก/ผิด', 'short_answer' => 'อัตนัย', 'accept' => 'ยอมรับ'];
                                            ?>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase"><?= $typeLabels[$q['question_type']] ?? $q['question_type'] ?></span>
                                            <span class="text-[10px] text-gray-300">|</span>
                                            <span class="text-[10px] font-bold text-emerald-500"><?= $q['score'] ?? 0 ?> คะแนน</span>
                                            <?php if (!empty($q['is_critical'])): ?>
                                                <span class="text-[10px] text-gray-300">|</span>
                                                <span class="text-[10px] font-bold text-red-500">Critical</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="ml-11 text-sm text-gray-400 italic py-2">ยังไม่มีคำถามในส่วนนี้</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="p-12 text-center text-gray-400">
            <i class="ri-inbox-line text-4xl mb-3 block opacity-50"></i>
            <p class="text-sm">ยังไม่มี Section ในแบบทดสอบนี้</p>
        </div>
    <?php endif; ?>
</div>