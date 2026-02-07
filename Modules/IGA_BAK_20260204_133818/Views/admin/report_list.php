<?php
// Admin Report List View
?>
<div class="space-y-6">
    <!-- Header/Filter -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">รายงานผลสอบ</h2>
            <p class="text-sm text-gray-500">ตรวจสอบคะแนนและผลการประเมินของผู้ใช้งาน</p>
        </div>

        <form action="index.php" method="get" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <input type="hidden" name="controller" value="report">
            <input type="hidden" name="action" value="index">

            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ค้นหาชื่อ..." class="pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm w-full sm:w-64">
            </div>

            <select name="test_id" onchange="this.form.submit()" class="pl-4 pr-8 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm bg-white cursor-pointer">
                <option value="">ทุกแบบทดสอบ</option>
                <?php foreach ($tests as $t): ?>
                    <option value="<?= $t['test_id'] ?>" <?= $test_id == $t['test_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['test_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-blue-50 rounded-xl p-6 border border-blue-100 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-blue-600 uppercase tracking-wide">ผู้เข้าสอบทั้งหมด</div>
                <div class="text-3xl font-bold text-gray-800 mt-1"><?= $total_items ?></div>
            </div>
            <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center text-blue-500 text-2xl shadow-sm">
                <i class="ri-group-line"></i>
            </div>
        </div>

        <!-- Add more stats here if needed (e.g. Pass Rate, Average Score) -->
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">ผู้สอบ</th>
                        <th class="px-6 py-4">แบบทดสอบ</th>
                        <th class="px-6 py-4 text-center">คะแนน</th>
                        <th class="px-6 py-4 text-center">สถานะ</th>
                        <th class="px-6 py-4 text-center">วันที่สอบ</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($attempts)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="ri-inbox-line text-4xl mb-2"></i>
                                    <span>ไม่พบข้อมูลการสอบ</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attempts as $row): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($row['fullname']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($row['email'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?= htmlspecialchars($row['test_name']) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-bold text-gray-800"><?= floatval($row['total_score']) ?></span>
                                    <span class="text-gray-400 text-xs">/ <?= floatval($row['max_score'] ?? 0) ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($row['is_passed']): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                                            <i class="ri-checkbox-circle-line"></i> ผ่าน
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                            <i class="ri-close-circle-line"></i> ไม่ผ่าน
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    <div class="flex flex-col">
                                        <span><?= date('d/m/Y', strtotime($row['end_time'])) ?></span>
                                        <span class="text-xs text-gray-400"><?= date('H:i', strtotime($row['end_time'])) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="index.php?controller=report&action=result&id=<?= $row['attempt_id'] ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="ดูรายละเอียด">
                                        <i class="ri-article-line"></i>
                                    </a>
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
                        <a href="index.php?controller=report&action=index&p=<?= $i ?>&search=<?= urlencode($search) ?>&test_id=<?= $test_id ?>"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-colors
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