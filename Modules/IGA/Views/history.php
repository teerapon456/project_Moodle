<?php

/**
 * IGA Test History - Content Fragment
 * Loaded inside index.php layout via ?page=history
 * Variables available: $pdo, $user, $isApplicant
 */

$userId = $user['id'] ?? null;
if (!$userId) {
    echo '<div class="alert alert-warning">กรุณาเข้าสู่ระบบก่อน</div>';
    return;
}

// Fetch test history
$attempts = [];
try {
    $stmt = $pdo->prepare("
        SELECT uta.attempt_id, uta.test_id, uta.start_time, uta.end_time,
               uta.is_completed, uta.total_score, uta.time_spent_seconds,
               t.test_name, t.duration_minutes
        FROM iga_user_test_attempts uta
        JOIN iga_tests t ON uta.test_id = t.test_id
        WHERE uta.user_id = :uid
        ORDER BY uta.start_time DESC
        LIMIT 50
    ");
    $stmt->execute([':uid' => $userId]);
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
    $attempts = [];
}

$mid = isset($_GET['mid']) ? '&mid=' . $_GET['mid'] : '';
?>

<?php if (!empty($attempts)): ?>
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="ri-history-line text-primary"></i>
                ประวัติการทำแบบทดสอบ
            </h3>
            <span class="text-xs font-medium text-gray-400 bg-gray-50 px-3 py-1 rounded-full border border-gray-100 italic">
                แสดงล่าสุด 50 รายการ
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-8 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">แบบทดสอบ</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">วันที่ทำ</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">สถานะ</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">คะแนน</th>
                        <th class="px-6 py-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100">เวลาที่ใช้</th>
                        <th class="px-8 py-4 border-b border-gray-100"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($attempts as $att): ?>
                        <tr class="group hover:bg-gray-50/50 transition-colors">
                            <td class="px-8 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 group-hover:text-primary transition-colors"><?= htmlspecialchars($att['test_name']) ?></span>
                                    <span class="text-[10px] text-gray-400">ID: AT-<?= str_pad($att['attempt_id'], 6, '0', STR_PAD_LEFT) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600"><?= date('d M Y', strtotime($att['start_time'])) ?></span>
                                    <span class="text-[10px] text-gray-400"><?= date('H:i น.', strtotime($att['start_time'])) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <?php if ($att['is_completed']): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-wider">
                                        <i class="ri-checkbox-circle-fill"></i> เสร็จสิ้น
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 text-amber-600 text-[10px] font-bold uppercase tracking-wider">
                                        <i class="ri-time-fill animate-pulse"></i> กำลังทำ
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <?php if ($att['is_completed'] && isset($att['total_score'])): ?>
                                    <div class="flex flex-col items-center">
                                        <span class="text-sm font-bold text-gray-900"><?= number_format($att['total_score'], 1) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-300 text-sm italic">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5">
                                <?php if ($att['time_spent_seconds']): ?>
                                    <div class="flex items-center gap-1.5 text-sm text-gray-600">
                                        <i class="ri-time-line text-gray-400"></i>
                                        <span><?= floor($att['time_spent_seconds'] / 60) ?> นาที</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-300 text-sm italic">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <?php if ($att['is_completed']): ?>
                                    <a href="?page=results&attempt_id=<?= $att['attempt_id'] ?><?= $mid ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-primary hover:text-white text-gray-600 rounded-xl text-xs font-bold transition-all active:scale-95 group/btn">
                                        <i class="ri-file-list-3-line"></i> ดูผลสอบ
                                        <i class="ri-arrow-right-s-line group-hover/btn:translate-x-1 transition-transform"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?page=take_test&attempt_id=<?= $att['attempt_id'] ?>&test_id=<?= $att['test_id'] ?><?= $mid ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-maroon-800 text-white rounded-xl text-xs font-bold shadow-lg shadow-red-200 transition-all active:scale-95 group/btn">
                                        <i class="ri-play-fill text-lg"></i> ทำต่อ
                                        <i class="ri-arrow-right-s-line group-hover/btn:translate-x-1 transition-transform"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="bg-white border border-gray-100 rounded-[2.5rem] p-16 text-center shadow-sm">
        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300">
            <i class="ri-history-line text-5xl"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">ยังไม่มีประวัติการสอบ</h3>
        <p class="text-gray-500 max-w-sm mx-auto">ท่านยังไม่เคยทำแบบทดสอบใดๆ ในระบบ เลือกแบบทดสอบที่น่าสนใจเพื่อเริ่มทำได้ทันที</p>
        <a href="?page=tests<?= $mid ?>" class="mt-8 inline-flex items-center gap-2 px-8 py-3 bg-primary hover:bg-maroon-800 text-white rounded-2xl font-bold shadow-lg shadow-red-200 transition-all active:scale-95">
            ไปที่หน้าแบบทดสอบ
        </a>
    </div>
<?php endif; ?>