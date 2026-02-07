<?php
// Test List View
?>
<div class="space-y-6">
    <!-- Actions Row -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search -->
        <form action="index.php" method="get" class="relative w-full md:w-80">
            <input type="hidden" name="controller" value="test">
            <input type="hidden" name="action" value="index">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="search" class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="ค้นหาแบบทดสอบ..." value="<?= htmlspecialchars($search) ?>">
        </form>

        <!-- Add Button -->
        <a href="index.php?controller=test&action=create" class="flex items-center gap-2 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm shadow-primary/30">
            <i class="ri-add-line"></i>
            <span>สร้างแบบทดสอบใหม่</span>
        </a>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 w-16">#</th>
                        <th class="px-6 py-3">ชื่อแบบทดสอบ</th>
                        <th class="px-6 py-3 text-center">สถานะ</th>
                        <th class="px-6 py-3 text-center w-24">ระยะเวลา</th>
                        <th class="px-6 py-3 text-center w-24">คะแนนผ่าน</th>
                        <th class="px-6 py-3">ผู้สร้าง</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($tests)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <i class="ri-inbox-line text-4xl block mb-2"></i>
                                ไม่พบข้อมูลแบบทดสอบ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tests as $i => $test): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 text-gray-500 text-sm"><?= ($offset ?? 0) + $i + 1 ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($test['test_name']) ?></div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs"><?= htmlspecialchars($test['description']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($test['is_published']): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            เผยแพร่
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            ร่าง
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    <?= $test['duration_minutes'] == 0 ? 'ไม่จำกัด' : $test['duration_minutes'] . ' น.' ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    <?= floatval($test['min_passing_score']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                                            <?= mb_substr($test['created_by_name'] ?? 'U', 0, 1) ?>
                                        </div>
                                        <span class="truncate max-w-[100px]"><?= htmlspecialchars($test['created_by_name'] ?? '-') ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="index.php?controller=test&action=structure&id=<?= $test['test_id'] ?>" class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="จัดการโครงสร้าง (Sections/Questions)">
                                            <i class="ri-node-tree"></i>
                                        </a>
                                        <a href="index.php?controller=test&action=edit&id=<?= $test['test_id'] ?>" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="แก้ไข">
                                            <i class="ri-edit-line"></i>
                                        </a>
                                        <a href="javascript:void(0)" onclick="if(confirm('ยืนยันการลบแบบทดสอบนี้? ข้อมูลการสอบที่เกี่ยวข้องจะถูกลบด้วย')) location.href='index.php?controller=test&action=delete&id=<?= $test['test_id'] ?>'" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบ">
                                            <i class="ri-delete-bin-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-center">
                <nav class="flex gap-1" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="index.php?controller=test&action=index&p=<?= $i ?>&search=<?= urlencode($search) ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-lg text-sm font-medium transition-colors
                       <?= $page == $i
                            ? 'bg-primary text-white shadow-sm shadow-primary/30'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-primary' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>