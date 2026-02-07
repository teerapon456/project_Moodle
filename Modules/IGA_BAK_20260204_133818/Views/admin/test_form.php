<?php
// Test Form View
?>
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="index.php?controller=test&action=index" class="text-gray-500 hover:text-gray-700 text-sm flex items-center gap-1 mb-1 transition-colors">
                <i class="ri-arrow-left-line"></i> กลับหน้ารายการ
            </a>
            <h2 class="text-2xl font-bold text-gray-800"><?= $test ? 'แก้ไขแบบทดสอบ' : 'สร้างแบบทดสอบใหม่' ?></h2>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <form action="index.php?controller=test&action=<?= $test ? 'update' : 'store' ?>" method="post" class="space-y-6">
            <?php if ($test): ?>
                <input type="hidden" name="test_id" value="<?= $test['test_id'] ?>">
            <?php endif; ?>

            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อแบบทดสอบ <span class="text-red-500">*</span></label>
                <input type="text" name="test_name" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-medium"
                    placeholder="เช่น แบบทดสอบวัดระดับภาษาอังกฤษ"
                    value="<?= htmlspecialchars($test['test_name'] ?? '') ?>">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียด / คำชี้แจง</label>
                <textarea name="description" rows="4"
                    class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm"
                    placeholder="ระบุรายละเอียดของแบบทดสอบ..."><?= htmlspecialchars($test['description'] ?? '') ?></textarea>
            </div>

            <!-- Grid Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ระยะเวลา (นาที)</label>
                    <div class="relative">
                        <input type="number" name="duration_minutes" min="0"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all pl-10"
                            value="<?= $test['duration_minutes'] ?? 0 ?>">
                        <i class="ri-timer-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">ใส่ 0 หากไม่ต้องการจับเวลา</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คะแนนผ่านเกณฑ์ (ขั้นต่ำ)</label>
                    <div class="relative">
                        <input type="number" name="min_passing_score" min="0" step="0.5"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all pl-10"
                            value="<?= $test['min_passing_score'] ?? 0 ?>">
                        <i class="ri-award-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Toggle Switch -->
            <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                <input type="checkbox" id="is_published" name="is_published" value="1" class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary" <?= ($test['is_published'] ?? 0) ? 'checked' : '' ?>>
                <label for="is_published" class="text-sm font-medium text-gray-700 select-none cursor-pointer">
                    เผยแพร่ทันที (ผู้ใช้งานจะเห็นแบบทดสอบนี้)
                </label>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-6">
                <a href="index.php?controller=test&action=index" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">ยกเลิก</a>
                <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white font-medium rounded-lg shadow-lg shadow-primary/30 transition-all transform hover:-translate-y-0.5">
                    <i class="ri-save-line mr-1"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>