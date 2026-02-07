<?php
// Test Structure View
?>
<div class="max-w-5xl mx-auto pb-20">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="index.php?controller=test&action=index" class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1 mb-1 transition-colors">
                <i class="ri-arrow-left-line"></i> กลับหน้ารายการ
            </a>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                โครงสร้าง: <?= htmlspecialchars($test['test_name']) ?>
                <span class="text-sm bg-gray-100 text-gray-600 px-2 py-1 rounded font-normal border border-gray-200"><?= count($sections) ?> Sections</span>
            </h2>
        </div>
        <button onclick="document.getElementById('addSectionModal').classList.remove('hidden')" class="flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium shadow-lg shadow-primary/30 transition-all hover:-translate-y-0.5">
            <i class="ri-layout-top-line"></i>
            <span>เพิ่มส่วน (Section)</span>
        </button>
    </div>

    <!-- Content -->
    <div class="space-y-8">
        <?php if (empty($sections)): ?>
            <div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                    <i class="ri-layout-masonry-line text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">ยังไม่มีโครงสร้าง</h3>
                <p class="text-gray-500 mb-6">เริ่มต้นด้วยการเพิ่มส่วน (Section) แรกให้กับแบบทดสอบ</p>
                <button onclick="document.getElementById('addSectionModal').classList.remove('hidden')" class="px-6 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors">
                    เพิ่มส่วนใหม่
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($sections as $section): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group">
                    <!-- Section Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded bg-white border border-gray-200 flex items-center justify-center font-bold text-gray-500 shadow-sm">
                                <?= $section['section_order'] ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800"><?= htmlspecialchars($section['section_title']) ?></h3>
                                <?php if ($section['instructions']): ?>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate max-w-md"><?= htmlspecialchars($section['instructions']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 opacity-50 group-hover:opacity-100 transition-opacity">
                            <button onclick="addQuestion(<?= $section['section_id'] ?>)" class="text-xs bg-white border border-green-200 text-green-700 hover:bg-green-50 px-3 py-1.5 rounded-lg flex items-center gap-1 transition-colors">
                                <i class="ri-add-line"></i> เพิ่มคำถาม
                            </button>
                            <a href="index.php?controller=section&action=delete&id=<?= $section['section_id'] ?>&test_id=<?= $test['test_id'] ?>" onclick="return confirm('ลบส่วนนี้และคำถามทั้งหมด?')" class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                <i class="ri-delete-bin-line"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Questions List -->
                    <div class="p-6 bg-white min-h-[100px]">
                        <?php if (empty($section['questions'])): ?>
                            <div class="text-center py-6 border-2 border-dashed border-gray-100 rounded-lg text-gray-400 text-sm">
                                ยังไม่มีคำถามในส่วนนี้
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($section['questions'] as $q): ?>
                                    <div class="flex gap-4 p-4 border border-gray-100 rounded-lg hover:border-gray-200 hover:shadow-sm transition-all bg-gray-50/50">
                                        <div class="flex-shrink-0 w-8 text-center pt-1 font-medium text-gray-500">
                                            Q<?= $q['question_order'] ?>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between">
                                                <div class="text-gray-800 font-medium mb-2 pr-4"><?= nl2br(htmlspecialchars($q['question_text'])) ?></div>
                                                <a href="index.php?controller=question&action=delete&id=<?= $q['question_id'] ?>&test_id=<?= $test['test_id'] ?>" onclick="return confirm('ลบคำถามนี้?')" class="text-gray-400 hover:text-red-500 transition-colors">
                                                    <i class="ri-close-circle-line text-lg"></i>
                                                </a>
                                            </div>
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-600 font-medium border border-blue-100">
                                                    <?= $q['question_type'] == 'single_choice' ? 'ตัวเลือกเดียว' : ($q['question_type'] == 'multiple_choice' ? 'หลายตัวเลือก' : 'เติมคำตอบ') ?>
                                                </span>
                                                <span class="px-2 py-0.5 rounded text-xs bg-amber-50 text-amber-600 font-medium border border-amber-100">
                                                    <?= floatval($q['points']) ?> คะแนน
                                                </span>
                                            </div>

                                            <!-- Options Area (Placeholder) -->
                                            <div class="mt-3 pl-4 border-l-2 border-gray-200">
                                                <button onclick="addOption(<?= $q['question_id'] ?>)" class="text-xs text-primary hover:underline flex items-center gap-1">
                                                    <i class="ri-add-circle-line"></i> จัดการตัวเลือก (เร็วๆนี้)
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Section Modal (Tailwind) -->
<div id="addSectionModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="document.getElementById('addSectionModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="index.php?controller=section&action=store" method="post">
                <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="ri-layout-line text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">เพิ่มส่วนใหม่ (Section)</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">ชื่อส่วน</label>
                                    <input type="text" name="section_title" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="Part 1: General Knowledge">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">คำชี้แจง</label>
                                    <textarea name="instructions" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">บันทึก</button>
                    <button type="button" onclick="document.getElementById('addSectionModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Question Modal (Tailwind) -->
<div id="addQuestionModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75" onclick="document.getElementById('addQuestionModal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="index.php?controller=question&action=store" method="post">
                <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
                <input type="hidden" name="section_id" id="modal_section_id">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">เพิ่มคำถาม</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">คำถาม</label>
                            <textarea name="question_text" required rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ประเภท</label>
                                <select name="question_type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                    <option value="single_choice">เลือกตอบ (Single)</option>
                                    <option value="multiple_choice">หลายตัวเลือก (Multi)</option>
                                    <option value="short_answer">เติมคำ (Text)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">คะแนน</label>
                                <input type="number" name="points" value="1" step="0.5" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark sm:ml-3 sm:w-auto sm:text-sm">บันทึก</button>
                    <button type="button" onclick="document.getElementById('addQuestionModal').classList.add('hidden')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function addQuestion(sectionId) {
        document.getElementById('modal_section_id').value = sectionId;
        document.getElementById('addQuestionModal').classList.remove('hidden');
    }

    function addOption(questionId) {
        alert("ยังไม่เปิดให้ใช้งาน (ส่วนเสริม)");
    }
</script>