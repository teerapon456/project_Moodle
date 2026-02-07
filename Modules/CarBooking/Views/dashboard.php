<?php

/**
 * Car Booking - Dashboard View
 * Migrated to Tailwind CSS
 */

// View only
if (!checkViewPermission($canView, 'ระบบจองรถ')) return;

require_once __DIR__ . '/../Controllers/BookingController.php';

$controller = new BookingController($user);

// Get statistics
$myBookings = $canManage ? $controller->listAll() : $controller->listMine();
$pendingCount = 0;
$approvedCount = 0;
$inUseCount = 0;
$pendingReturnCount = 0;
$completedCount = 0;
$rejectedCount = 0;
$cancelledCount = 0;

foreach ($myBookings as $b) {
    switch ($b['status']) {
        case 'pending_supervisor':
        case 'pending_manager':
            $pendingCount++;
            break;
        case 'approved':
            $approvedCount++;
            break;
        case 'in_use':
            $inUseCount++;
            break;
        case 'pending_return':
            $pendingReturnCount++;
            break;
        case 'completed':
            $completedCount++;
            break;
        case 'rejected_supervisor':
        case 'rejected_manager':
            $rejectedCount++;
            break;
        case 'cancelled':
        case 'revoked':
            $cancelledCount++;
            break;
    }
}

// Get pending for manager
$pendingManagerCount = 0;
$pendingSupervisorCount = 0;
if ($canManage) {
    $pendingSup = $controller->listPendingSupervisor();
    $pendingMgr = $controller->listPendingManager();
    $pendingSupervisorCount = count($pendingSup);
    $pendingManagerCount = count($pendingMgr);
}

$statusBadges = [
    'pending_supervisor' => 'bg-amber-100 text-amber-800',
    'pending_manager' => 'bg-blue-100 text-blue-800',
    'approved' => 'bg-emerald-100 text-emerald-800',
    'in_use' => 'bg-purple-100 text-purple-800',
    'pending_return' => 'bg-orange-100 text-orange-800',
    'completed' => 'bg-teal-100 text-teal-800',
    'rejected_supervisor' => 'bg-red-100 text-red-800',
    'rejected_manager' => 'bg-red-100 text-red-800',
    'cancelled' => 'bg-gray-100 text-gray-600',
    'revoked' => 'bg-rose-100 text-rose-800'
];

$statusLabels = [
    'pending_supervisor' => 'รอหัวหน้าอนุมัติ',
    'pending_manager' => 'รอ IPCD อนุมัติ',
    'approved' => 'อนุมัติแล้ว',
    'in_use' => 'กำลังใช้งาน',
    'pending_return' => 'รอยืนยันคืน',
    'completed' => 'เสร็จสิ้น',
    'rejected_supervisor' => 'ปฏิเสธ (หัวหน้า)',
    'rejected_manager' => 'ปฏิเสธ (IPCD)',
    'cancelled' => 'ยกเลิก',
    'revoked' => 'เพิกถอน'
];
?>

<!-- Quick Actions for Mobile -->
<div class="md:hidden mb-6">
    <?php if ($canEdit): ?>
        <button class="w-full py-4 bg-gradient-to-r from-primary to-red-500 hover:from-primary-dark hover:to-red-600 text-white rounded-xl text-lg font-semibold shadow-lg flex items-center justify-center gap-3 transition-all active:scale-98" onclick="openBookingModal()">
            <i class="ri-add-circle-fill text-2xl"></i> สร้างคำขอจองรถใหม่
        </button>
    <?php endif; ?>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5 mb-6">
    <div class="bg-white border-l-4 border-primary rounded-xl p-4 md:p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-red-50 flex items-center justify-center text-primary text-xl md:text-2xl">
                <i class="ri-file-list-3-fill"></i>
            </div>
            <div>
                <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= count($myBookings) ?></div>
                <div class="text-gray-500 text-xs md:text-sm">คำขอทั้งหมด</div>
            </div>
        </div>
    </div>
    <div class="bg-white border-l-4 border-warning rounded-xl p-4 md:p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-amber-50 flex items-center justify-center text-warning text-xl md:text-2xl">
                <i class="ri-time-fill"></i>
            </div>
            <div>
                <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pendingCount ?></div>
                <div class="text-gray-500 text-xs md:text-sm">รออนุมัติ</div>
            </div>
        </div>
    </div>
    <div class="bg-white border-l-4 border-success rounded-xl p-4 md:p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-emerald-50 flex items-center justify-center text-success text-xl md:text-2xl">
                <i class="ri-checkbox-circle-fill"></i>
            </div>
            <div>
                <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $approvedCount ?></div>
                <div class="text-gray-500 text-xs md:text-sm">อนุมัติแล้ว</div>
            </div>
        </div>
    </div>
    <div class="bg-white border-l-4 border-info rounded-xl p-4 md:p-5 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-blue-50 flex items-center justify-center text-info text-xl md:text-2xl">
                <i class="ri-flag-fill"></i>
            </div>
            <div>
                <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $completedCount ?></div>
                <div class="text-gray-500 text-xs md:text-sm">เสร็จสิ้น</div>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
    <!-- Manager Stats -->
    <div class="grid grid-cols-2 gap-3 md:gap-5 mb-6">
        <div class="bg-white border-l-4 border-danger rounded-xl p-4 md:p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-red-50 flex items-center justify-center text-danger text-xl md:text-2xl">
                    <i class="ri-user-star-fill"></i>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pendingSupervisorCount ?></div>
                    <div class="text-gray-500 text-xs md:text-sm">รอหัวหน้าอนุมัติ</div>
                </div>
            </div>
        </div>
        <div class="bg-white border-l-4 border-info rounded-xl p-4 md:p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-blue-50 flex items-center justify-center text-info text-xl md:text-2xl">
                    <i class="ri-admin-fill"></i>
                </div>
                <div>
                    <div class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pendingManagerCount ?></div>
                    <div class="text-gray-500 text-xs md:text-sm">รอผู้จัดการอนุมัติ</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Recent Bookings -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-4 md:px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-history-line text-primary"></i>
            คำขอล่าสุด
        </h3>
        <div class="hidden md:flex items-center gap-2">
            <?php if ($canEdit): ?>
                <a href="?page=bookings" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">ดูทั้งหมด</a>
                <button class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium transition-colors" onclick="openBookingModal()">
                    <i class="ri-add-line"></i> สร้างคำขอใหม่
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-gray-100">
        <?php
        $recentBookings = array_slice($myBookings, 0, 5);
        if (empty($recentBookings)):
        ?>
            <div class="py-12 text-center text-gray-400">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-line text-3xl"></i>
                </div>
                <p class="text-lg font-medium">ยังไม่มีคำขอ</p>
                <p class="text-sm mt-1">เริ่มต้นสร้างคำขอใหม่ได้เลย</p>
            </div>
        <?php else: ?>
            <?php foreach ($recentBookings as $b):
                $status = $b['status'];
                $resourceLabel = '';
                if (!empty($b['assigned_car_id'])) {
                    $carName = $b['assigned_car_name'] ?: (($b['assigned_car_brand'] ?? '') . ' ' . ($b['assigned_car_model'] ?? ''));
                    $resourceLabel = $carName;
                } elseif (!empty($b['fleet_card_number'])) {
                    $resourceLabel = 'Fleet: ' . $b['fleet_card_number'];
                }

                $borderColors = [
                    'pending_supervisor' => 'border-l-amber-400',
                    'pending_manager' => 'border-l-blue-400',
                    'approved' => 'border-l-emerald-500',
                    'in_use' => 'border-l-purple-500',
                    'completed' => 'border-l-teal-500',
                ];
                $borderColor = $borderColors[$status] ?? 'border-l-gray-300';
            ?>
                <div class="p-4 <?= $borderColor ?> border-l-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900">#<?= $b['id'] ?></span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadges[$status] ?? 'bg-gray-100 text-gray-600' ?>">
                                <?= $statusLabels[$status] ?? $status ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                        <i class="ri-map-pin-2-fill text-primary"></i>
                        <span class="font-medium"><?= htmlspecialchars($b['destination']) ?></span>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span><i class="ri-calendar-event-fill text-blue-500"></i> <?= date('d/m/Y H:i', strtotime($b['start_time'])) ?></span>
                        <?php if ($resourceLabel): ?>
                            <span><i class="ri-car-fill text-emerald-500"></i> <?= $resourceLabel ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="p-4">
                <a href="?page=bookings" class="block w-full py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-center font-medium transition-colors">
                    ดูคำขอทั้งหมด <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่ใช้รถ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ปลายทาง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">รถ/บัตรที่ได้รับ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $recentBookings = array_slice($myBookings, 0, 5);
                if (empty($recentBookings)):
                ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                            <i class="ri-calendar-line text-3xl mb-2 block"></i>
                            <p>ยังไม่มีคำขอ</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentBookings as $b):
                        $status = $b['status'];
                        $carLabel = '-';
                        if (!empty($b['assigned_car_id'])) {
                            $carName = $b['assigned_car_name'] ?: (($b['assigned_car_brand'] ?? '') . ' ' . ($b['assigned_car_model'] ?? ''));
                            $carLabel = '<div class="flex flex-col"><span class="flex items-center gap-1"><i class="ri-taxi-fill text-primary"></i> ' . $carName . '</span>';
                            if ($b['assigned_car_plate']) {
                                $carLabel .= '<span class="text-xs text-gray-500">(' . $b['assigned_car_plate'] . ')</span></div>';
                            } else {
                                $carLabel .= '</div>';
                            }
                        } elseif (!empty($b['fleet_card_number'])) {
                            $carLabel = '<div class="flex flex-col"><span class="flex items-center gap-1"><i class="ri-bank-card-line text-primary"></i> ' . $b['fleet_card_number'] . '</span>';
                            if (!empty($b['fleet_amount'])) {
                                $carLabel .= '<span class="text-xs text-gray-500">(' . number_format($b['fleet_amount']) . ' บาท)</span></div>';
                            } else {
                                $carLabel .= '</div>';
                            }
                        }
                    ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= $b['id'] ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= date('d/m/Y H:i', strtotime($b['start_time'])) ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($b['destination']) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?= $statusBadges[$status] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= $statusLabels[$status] ?? $status ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= trim($carLabel) ?: '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include shared booking modal
include __DIR__ . '/partials/booking-modal.php';
?>