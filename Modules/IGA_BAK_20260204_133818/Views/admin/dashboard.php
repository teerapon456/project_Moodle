<?php
// Admin Dashboard View
?>
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-gray-500 text-uppercase">แบบทดสอบทั้งหมด</div>
                <div class="text-3xl font-bold text-gray-800 mt-2"><?= $stats['total_tests'] ?? 0 ?></div>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center text-2xl">
                <i class="ri-file-list-3-line"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-gray-500 text-uppercase">เผยแพร่แล้ว</div>
                <div class="text-3xl font-bold text-success mt-2"><?= $stats['active_tests'] ?? 0 ?></div>
            </div>
            <div class="w-12 h-12 bg-green-50 text-green-600 rounded-lg flex items-center justify-center text-2xl">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-gray-500 text-uppercase">ผู้เข้าสอบวันนี้</div>
                <div class="text-3xl font-bold text-gray-800 mt-2"><?= $stats['today_attempts'] ?? 0 ?></div>
            </div>
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-lg flex items-center justify-center text-2xl">
                <i class="ri-user-follow-line"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-gray-500 text-uppercase">ผลสอบรอตรวจ</div>
                <div class="text-3xl font-bold text-warning mt-2"><?= $stats['pending_reviews'] ?? 0 ?></div>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-2xl">
                <i class="ri-time-line"></i>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">การสอบล่าสุด</h3>
            <a href="index.php?controller=report&action=index" class="text-sm text-primary hover:text-primary-dark font-medium">ดูทั้งหมด</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-3">ผู้สอบ</th>
                        <th class="px-6 py-3">แบบทดสอบ</th>
                        <th class="px-6 py-3">คะแนน</th>
                        <th class="px-6 py-3 text-right">เวลา</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($recent_attempts)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">ยังไม่มีการสอบล่าสุด</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_attempts as $attempt): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($attempt['fullname']) ?></td>
                                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($attempt['test_name']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    <?= $attempt['is_passed'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                        <?= floatval($attempt['total_score']) ?> คะแนน
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-500 text-sm">
                                    <?= date('d/m/Y H:i', strtotime($attempt['end_time'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>