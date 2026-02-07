<?php
// request_history.php - View only
if (!checkViewPermission($canView, 'ระบบหอพัก')) return;

require_once __DIR__ . '/../Controllers/BookingController.php';
$controller = new BookingController();
$data = $controller->getRequestHistoryData();
extract($data);

$types = ['move_in' => 'ขอเข้าพัก', 'move_out' => 'ขอย้ายออก', 'change_room' => 'ขอย้ายห้อง'];
$statusColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700', 'cancelled' => 'bg-gray-100 text-gray-700'];
$statusLabels = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ถูกปฏิเสธ', 'cancelled' => 'ยกเลิก'];
$borderColors = ['pending' => 'border-l-yellow-400', 'approved' => 'border-l-green-500', 'rejected' => 'border-l-red-500', 'cancelled' => 'border-l-gray-400'];

$mtStatusColors = [
    'open' => 'bg-red-100 text-red-700',
    'assigned' => 'bg-yellow-100 text-yellow-700',
    'in_progress' => 'bg-blue-100 text-blue-700',
    'pending_parts' => 'bg-orange-100 text-orange-700',
    'resolved' => 'bg-green-100 text-green-700',
    'closed' => 'bg-gray-100 text-gray-700',
    'cancelled' => 'bg-gray-300 text-gray-700'
];
$mtStatusLabels = [
    'open' => 'รอดำเนินการ',
    'assigned' => 'มอบหมายแล้ว',
    'in_progress' => 'กำลังดำเนินการ',
    'pending_parts' => 'รออะไหล่',
    'resolved' => 'เสร็จสิ้น',
    'closed' => 'ปิดงาน',
    'cancelled' => 'ยกเลิก'
];
$mtBorderColors = ['open' => 'border-l-red-500', 'in_progress' => 'border-l-blue-500', 'resolved' => 'border-l-green-500', 'closed' => 'border-l-gray-400'];
?>

<div class="mb-6">
    <h1 class="text-xl md:text-2xl font-semibold text-gray-900">ประวัติคำขอ</h1>
</div>

<div class="space-y-6">

    <!-- Booking History -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center gap-2 px-4 md:px-6 py-3 md:py-4 border-b border-gray-100">
            <i class="ri-hotel-bed-fill text-xl text-indigo-600"></i>
            <h2 class="text-base md:text-lg font-semibold text-gray-800">ประวัติการจอง/ย้ายห้องพัก</h2>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden divide-y divide-gray-100">
            <?php if (empty($myRequests)): ?>
                <div class="py-12 text-center text-gray-400">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="ri-inbox-line text-3xl"></i>
                    </div>
                    <p class="font-medium">ยังไม่มีประวัติคำขอ</p>
                </div>
            <?php else: ?>
                <?php foreach ($myRequests as $req):
                    $s = $req['status'];
                ?>
                    <div class="p-4 <?= $borderColors[$s] ?? 'border-l-gray-300' ?> border-l-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-gray-900"><?= $types[$req['request_type']] ?? $req['request_type'] ?></span>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?= $statusColors[$s] ?? 'bg-gray-100' ?>">
                                <?= $statusLabels[$s] ?? $s ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <i class="ri-calendar-line"></i>
                            <span><?= date('d/m/Y', strtotime($req['created_at'])) ?></span>
                        </div>
                        <?php if (!empty($req['admin_remark'] ?? $req['cancel_reason'])): ?>
                            <div class="mt-2 text-xs text-gray-400 truncate">
                                <i class="ri-chat-3-line"></i> <?= htmlspecialchars($req['admin_remark'] ?? $req['cancel_reason']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-semibold text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-3">วันที่</th>
                        <th class="p-3">ประเภท</th>
                        <th class="p-3">สถานะ</th>
                        <th class="p-3">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (empty($myRequests)): ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-400">
                                <i class="ri-inbox-line text-4xl mb-2 block opacity-50"></i>
                                ยังไม่มีประวัติคำขอ
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myRequests as $req): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3 text-gray-600"><?= date('d/m/Y', strtotime($req['created_at'])) ?></td>
                                <td class="p-3 font-medium text-gray-800"><?= $types[$req['request_type']] ?? $req['request_type'] ?></td>
                                <td class="p-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$req['status']] ?? 'bg-gray-100' ?>">
                                        <?= $statusLabels[$req['status']] ?? $req['status'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-gray-500 truncate max-w-[200px]">
                                    <?= htmlspecialchars($req['admin_remark'] ?? $req['cancel_reason'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Maintenance History -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center gap-2 px-4 md:px-6 py-3 md:py-4 border-b border-gray-100">
            <i class="ri-tools-fill text-xl text-indigo-600"></i>
            <h2 class="text-base md:text-lg font-semibold text-gray-800">ประวัติการแจ้งซ่อม</h2>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden divide-y divide-gray-100">
            <?php if (empty($myMaintenanceRequests)): ?>
                <div class="py-12 text-center text-gray-400">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="ri-inbox-line text-3xl"></i>
                    </div>
                    <p class="font-medium">ยังไม่มีประวัติการแจ้งซ่อม</p>
                </div>
            <?php else: ?>
                <?php foreach ($myMaintenanceRequests as $mt):
                    $st = $mt['status'];
                ?>
                    <div class="p-4 <?= $mtBorderColors[$st] ?? 'border-l-gray-300' ?> border-l-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-gray-900 truncate mr-2"><?= htmlspecialchars($mt['title']) ?></span>
                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap <?= $mtStatusColors[$st] ?? 'bg-gray-100' ?>">
                                <?= $mtStatusLabels[$st] ?? $st ?>
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                <i class="ri-calendar-line"></i>
                                <?= date('d/m/Y', strtotime($mt['created_at'])) ?>
                            </span>
                            <span class="flex items-center gap-1 text-xs font-mono text-gray-400">
                                <i class="ri-hashtag"></i>
                                <?= htmlspecialchars($mt['ticket_number']) ?>
                            </span>
                        </div>
                        <?php if (!empty($mt['category_name'])): ?>
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-xs text-gray-600">
                                    <i class="ri-folder-line"></i> <?= htmlspecialchars($mt['category_name']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-500 font-semibold text-xs uppercase tracking-wider border-b border-gray-200">
                    <tr>
                        <th class="p-3">วันที่แจ้ง</th>
                        <th class="p-3">เลขที่ใบงาน</th>
                        <th class="p-3">หมวดหมู่</th>
                        <th class="p-3">เรื่อง</th>
                        <th class="p-3">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if (empty($myMaintenanceRequests)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-400">
                                <i class="ri-inbox-line text-4xl mb-2 block opacity-50"></i>
                                ยังไม่มีประวัติการแจ้งซ่อม
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myMaintenanceRequests as $mt): ?>
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3 text-gray-600"><?= date('d/m/Y', strtotime($mt['created_at'])) ?></td>
                                <td class="p-3 text-gray-500 font-mono text-xs"><?= htmlspecialchars($mt['ticket_number']) ?></td>
                                <td class="p-3 text-gray-800"><?= htmlspecialchars($mt['category_name']) ?></td>
                                <td class="p-3 text-gray-800 font-medium"><?= htmlspecialchars($mt['title']) ?></td>
                                <td class="p-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $mtStatusColors[$mt['status']] ?? 'bg-gray-100' ?>">
                                        <?= $mtStatusLabels[$mt['status']] ?? $mt['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>