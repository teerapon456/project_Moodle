<?php
// User Dashboard
?>
<div class="space-y-8">
    <div class="flex items-center gap-3">
        <h2 class="text-2xl font-bold text-gray-800">การวัดประเมินที่เปิดอยู่</h2>
        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium">Active</span>
    </div>

    <?php if (empty($tests)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                <i class="ri-survey-line text-5xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">ไม่พบแบบทดสอบ</h3>
            <p class="text-gray-500 mt-1">ขณะนี้ยังไม่มีแบบทดสอบที่เปิดให้ทำ</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($tests as $test): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow group flex flex-col h-full">
                    <!-- Card Header -->
                    <div class="h-2 bg-gradient-to-r from-primary to-primary-light"></div>

                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                                <i class="ri-file-text-line"></i>
                            </div>
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Assessment</div>
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2 min-h-[3.5rem]">
                            <?= htmlspecialchars($test['test_name']) ?>
                        </h3>

                        <p class="text-gray-600 text-sm mb-6 line-clamp-3 min-h-[3.5rem] flex-1">
                            <?= htmlspecialchars($test['description'] ?? 'ไม่มีรายละเอียด') ?>
                        </p>

                        <!-- Meta -->
                        <div class="flex items-center gap-4 text-xs text-gray-500 mb-6 border-t border-gray-50 pt-4">
                            <div class="flex items-center gap-1">
                                <i class="ri-time-line text-gray-400"></i>
                                <span><?= $test['duration_minutes'] == 0 ? 'ไม่จำกัด' : $test['duration_minutes'] . ' นาที' ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <i class="ri-award-line text-gray-400"></i>
                                <span>ผ่าน: <?= floatval($test['min_passing_score']) ?></span>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="mt-auto">
                            <?php if (isset($test['last_attempt']) && $test['last_attempt']): ?>
                                <?php if ($test['last_attempt']['is_completed']): ?>
                                    <button disabled class="w-full py-2.5 bg-gray-100 text-gray-400 rounded-lg font-medium flex items-center justify-center gap-2 cursor-not-allowed">
                                        <i class="ri-checkbox-circle-line"></i> ทำแบบทดสอบแล้ว
                                    </button>
                                    <a href="index.php?controller=report&action=result&id=<?= $test['last_attempt']['attempt_id'] ?>" class="block text-center text-sm text-primary mt-3 hover:underline">ดูผลสอบ</a>
                                <?php else: ?>
                                    <a href="index.php?controller=exam&action=paper&attempt_id=<?= $test['last_attempt']['attempt_id'] ?>" class="grid w-full py-2.5 bg-warning hover:bg-yellow-500 text-white rounded-lg font-medium text-center transition-colors shadow-lg shadow-warning/30">
                                        ทำต่อให้เสร็จ
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="index.php?controller=exam&action=intro&id=<?= $test['test_id'] ?>" class="flex items-center justify-center gap-2 w-full py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-all shadow-lg shadow-primary/30 hover:-translate-y-0.5">
                                    <span>เริ่มทำแบบทดสอบ</span>
                                    <i class="ri-arrow-right-line"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>