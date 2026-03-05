<?php

/**
 * Car Booking - Bookings List View
 * Migrated to Tailwind CSS
 */

// View only
if (!checkViewPermission($canView, 'ระบบจองรถ')) return;

require_once __DIR__ . '/../Controllers/BookingController.php';

$controller = new BookingController($user);
$myBookings = $canManage ? $controller->listAll() : $controller->listMine();

// Load available assets for Edit Modal (if manager)
$cars = [];
$fleetCards = [];
if ($canManage) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->query("SELECT id, name, brand, model, license_plate, capacity, status FROM cb_cars WHERE status = 'available' ORDER BY name ASC");
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $conn->query("SELECT id, card_number, department, credit_limit FROM cb_fleet_cards WHERE status = 'active' ORDER BY card_number ASC");
        $fleetCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
    }
}

// Pagination
$perPage = 10;
$total = count($myBookings);
$currentPage = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$totalPages = $total > 0 ? ceil($total / $perPage) : 1;
$offset = ($currentPage - 1) * $perPage;
$pagedBookings = array_slice($myBookings, $offset, $perPage);

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

<!-- Page Actions -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" id="filterStatus" onchange="filterBookings()">
            <option value="">สถานะทั้งหมด</option>
            <option value="pending_supervisor">รอหัวหน้าอนุมัติ</option>
            <option value="pending_manager">รอ IPCD อนุมัติ</option>
            <option value="approved">อนุมัติแล้ว</option>
            <option value="in_use">กำลังใช้งาน</option>
            <option value="pending_return">รอยืนยันคืน</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="rejected_supervisor">ปฏิเสธ (หัวหน้า)</option>
            <option value="rejected_manager">ปฏิเสธ (IPCD)</option>
            <option value="cancelled">ยกเลิก</option>
            <option value="revoked">เพิกถอน</option>
        </select>
        <input type="text" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" id="searchInput" placeholder="ค้นหา..." onkeyup="filterBookings()">
    </div>
    <?php if ($canEdit): ?>
        <div class="flex items-center gap-2">
            <a href="?page=calendar" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                <i class="ri-calendar-line"></i> ปฏิทิน
            </a>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium transition-colors" onclick="openBookingModal()">
                <i class="ri-add-line"></i> สร้างคำขอใหม่
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Bookings Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-file-list-3-line text-primary"></i>
            รายการคำขอจองรถ
        </h3>
        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">ทั้งหมด <?= $total ?> รายการ</span>
    </div>

    <!-- Mobile Card View (visible on mobile only) -->
    <div class="md:hidden p-4 space-y-4" id="bookingsCardView">
        <?php if (empty($pagedBookings)): ?>
            <div class="py-12 text-center text-gray-400">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-line text-3xl"></i>
                </div>
                <p class="text-lg font-medium">ยังไม่มีคำขอ</p>
                <p class="text-sm mt-1">เริ่มต้นสร้างคำขอใหม่ได้เลย</p>
            </div>
        <?php else: ?>
            <?php foreach ($pagedBookings as $b):
                $status = $b['status'];
                $resourceLabel = '';
                $resourceIcon = 'ri-car-line';
                if (!empty($b['assigned_car_id'])) {
                    $carName = $b['assigned_car_name'] ?: (($b['assigned_car_brand'] ?? '') . ' ' . ($b['assigned_car_model'] ?? ''));
                    $resourceLabel = $carName . ($b['assigned_car_plate'] ? ' (' . $b['assigned_car_plate'] . ')' : '');
                    $resourceIcon = 'ri-roadster-fill';
                } elseif (!empty($b['fleet_card_id']) || !empty($b['fleet_card_number'])) {
                    $fcNum = $b['fleet_card_number'] ?? '#' . $b['fleet_card_id'];
                    $resourceLabel = 'Fleet: ' . $fcNum;
                    $resourceIcon = 'ri-bank-card-fill';
                }
                $canCancelRow = in_array($b['status'], ['pending_supervisor', 'pending_manager']) && ($b['user_id'] == $user['id'] || $canManage);
                $canPrint = in_array($b['status'], ['approved', 'in_use', 'pending_return', 'completed']);
                $canReportReturn = in_array($b['status'], ['approved', 'in_use']) && ($b['user_id'] == $user['id']);

                // Status colors for left border
                $borderColors = [
                    'pending_supervisor' => 'border-l-amber-400',
                    'pending_manager' => 'border-l-blue-400',
                    'approved' => 'border-l-emerald-500',
                    'in_use' => 'border-l-purple-500',
                    'pending_return' => 'border-l-orange-400',
                    'completed' => 'border-l-teal-500',
                    'rejected_supervisor' => 'border-l-red-500',
                    'rejected_manager' => 'border-l-red-500',
                    'cancelled' => 'border-l-gray-400',
                    'revoked' => 'border-l-rose-500'
                ];
                $borderColor = $borderColors[$status] ?? 'border-l-gray-300';
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 <?= $borderColor ?> border-l-4 overflow-hidden" data-status="<?= $b['status'] ?>">
                    <!-- Card Header -->
                    <div class="px-4 pt-4 pb-3">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-red-400 text-white flex items-center justify-center font-bold text-sm">
                                    #<?= $b['id'] ?>
                                </div>
                                <div>
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusBadges[$status] ?? 'bg-gray-100 text-gray-600' ?>">
                                        <?= $statusLabels[$status] ?? $status ?>
                                    </span>
                                </div>
                            </div>
                            <button class="w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-500 rounded-full transition-colors" onclick='viewBookingDetail(<?= json_encode($b) ?>)'>
                                <i class="ri-eye-line text-lg"></i>
                            </button>
                        </div>

                        <!-- Destination -->
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="ri-map-pin-2-fill"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide">ปลายทาง</p>
                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($b['destination']) ?></p>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center gap-2 text-sm">
                                <i class="ri-calendar-event-fill text-blue-500"></i>
                                <span class="text-gray-600"><?= date('d/m/Y H:i', strtotime($b['start_time'])) ?></span>
                            </div>
                            <?php if ($resourceLabel): ?>
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="<?= $resourceIcon ?> text-emerald-500"></i>
                                    <span class="text-gray-600 truncate"><?= $resourceLabel ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <?php if ($canPrint || $canReportReturn || $canCancelRow): ?>
                        <div class="px-4 py-3 bg-gray-50/50 border-t border-gray-100 flex gap-2">
                            <?php if ($canPrint): ?>
                                <button class="flex-1 py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium flex items-center justify-center gap-2 shadow-sm transition-all active:scale-95" onclick="printRequest(<?= $b['id'] ?>)">
                                    <i class="ri-printer-fill"></i> พิมพ์
                                </button>
                            <?php endif; ?>
                            <?php if ($canReportReturn): ?>
                                <button class="flex-1 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-medium flex items-center justify-center gap-2 shadow-sm transition-all active:scale-95" onclick='openReportReturnModal(<?= json_encode($b) ?>)'>
                                    <i class="ri-arrow-go-back-fill"></i> แจ้งคืน
                                </button>
                            <?php endif; ?>
                            <?php if ($canCancelRow): ?>
                                <button class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium flex items-center justify-center gap-2 shadow-sm transition-all active:scale-95" onclick="cancelBooking(<?= $b['id'] ?>)">
                                    <i class="ri-close-circle-fill"></i> ยกเลิก
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View (hidden on mobile) -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full" id="bookingsTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ขอ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่ใช้รถ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ปลายทาง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">รถ/บัตร</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($pagedBookings)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            <i class="ri-calendar-line text-3xl mb-2 block"></i>
                            <p>ยังไม่มีคำขอ</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pagedBookings as $b):
                        $status = $b['status'];
                        $resourceLabel = '-';
                        if (!empty($b['assigned_car_id'])) {
                            $carName = $b['assigned_car_name'] ?: (($b['assigned_car_brand'] ?? '') . ' ' . ($b['assigned_car_model'] ?? ''));
                            $resourceLabel = '<div class="flex flex-col"><span class="flex items-center gap-1"><i class="ri-taxi-fill text-primary"></i> ' . $carName . '</span>';
                            if ($b['assigned_car_plate']) {
                                $resourceLabel .= '<span class="text-xs text-gray-500">(' . $b['assigned_car_plate'] . ')</span></div>';
                            } else {
                                $resourceLabel .= '</div>';
                            }
                        } elseif (!empty($b['fleet_card_id']) || !empty($b['fleet_card_number'])) {
                            $fcNum = $b['fleet_card_number'] ?? '#' . $b['fleet_card_id'];
                            $resourceLabel = '<div class="flex flex-col"><span class="flex items-center gap-1"><i class="ri-bank-card-line text-primary"></i> ' . $fcNum . '</span>';
                            if (!empty($b['fleet_amount'])) {
                                $resourceLabel .= '<span class="text-xs text-gray-500">(' . number_format($b['fleet_amount']) . ' บ.)</span></div>';
                            } else {
                                $resourceLabel .= '</div>';
                            }
                        }
                        $canCancelRow = in_array($b['status'], ['pending_supervisor', 'pending_manager']) && ($b['user_id'] == $user['id'] || $canManage);
                        $canPrint = in_array($b['status'], ['approved', 'in_use', 'pending_return', 'completed']);
                        $canEditRow = $b['status'] === 'approved' && $canManage;
                        $canRevoke = in_array($b['status'], ['approved']) && $canManage;
                        $canReportReturn = in_array($b['status'], ['approved', 'in_use']) && ($b['user_id'] == $user['id']);
                        $canResend = $b['status'] === 'pending_supervisor' && ($b['user_id'] == $user['id'] || $canManage);
                    ?>
                        <tr data-status="<?= $b['status'] ?>" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= $b['id'] ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($b['fullname'] ?? $b['username'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-gray-600">
                                <?= date('d/m/Y H:i', strtotime($b['start_time'])) ?>
                                <small class="block text-gray-400">ถึง <?= date('d/m/Y H:i', strtotime($b['end_time'])) ?></small>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($b['destination']) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?= $statusBadges[$status] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= $statusLabels[$status] ?? $status ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= $resourceLabel ?: '-' ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <button class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded transition-colors" onclick='viewBookingDetail(<?= json_encode($b) ?>)' title="ดูรายละเอียด">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    <?php if ($canPrint): ?>
                                        <button class="p-1.5 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded transition-colors" onclick="printRequest(<?= $b['id'] ?>)" title="พิมพ์">
                                            <i class="ri-printer-line"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canResend): ?>
                                        <button class="p-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-600 rounded transition-colors" onclick="resendEmail(<?= $b['id'] ?>)" title="ส่งอีเมลอีกครั้ง">
                                            <i class="ri-mail-send-line"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canReportReturn): ?>
                                        <button class="p-1.5 bg-green-100 hover:bg-green-200 text-green-600 rounded transition-colors" onclick='openReportReturnModal(<?= json_encode($b) ?>)' title="แจ้งคืนรถ">
                                            <i class="ri-arrow-go-back-line"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canEditRow): ?>
                                        <button class="p-1.5 bg-amber-100 hover:bg-amber-200 text-amber-600 rounded transition-colors" onclick='openEditModal(<?= json_encode($b) ?>)' title="แก้ไข">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canRevoke): ?>
                                        <button class="p-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded transition-colors" onclick='openRevokeModal(<?= $b['id'] ?>)' title="ยกเลิก">
                                            <i class="ri-close-circle-line"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canCancelRow): ?>
                                        <button class="p-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded transition-colors" onclick="cancelBooking(<?= $b['id'] ?>)" title="ยกเลิก">
                                            <i class="ri-close-circle-line"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
            <span class="text-gray-500 text-sm">หน้า <?= $currentPage ?> จาก <?= $totalPages ?></span>
            <div class="flex gap-2">
                <a href="?page=bookings&p=<?= max(1, $currentPage - 1) ?>" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors <?= $currentPage <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">
                    <i class="ri-arrow-left-s-line"></i> ก่อนหน้า
                </a>
                <a href="?page=bookings&p=<?= min($totalPages, $currentPage + 1) ?>" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors <?= $currentPage >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">
                    ถัดไป <i class="ri-arrow-right-s-line"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Booking Detail Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="bookingDetailModal">
    <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">รายละเอียดการจอง</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeBookingDetailModal()">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto" id="bookingDetailContent"></div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50" id="bookingDetailFooter">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeBookingDetailModal()">ปิด</button>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="cancelModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">ยกเลิกคำขอจองรถ</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeCancelModal()">&times;</button>
        </div>
        <div class="p-6">
            <input type="hidden" id="cancelBookingId">
            <label class="block text-sm font-medium text-gray-700 mb-2">เหตุผลในการยกเลิก</label>
            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="cancelReason" rows="3" placeholder="ระบุเหตุผล (ไม่บังคับ)"></textarea>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeCancelModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors" onclick="confirmCancelBooking()">
                <i class="ri-close-circle-line"></i> ยืนยันยกเลิก
            </button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="editModal">
    <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">แก้ไขคำขอ</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto space-y-4">
            <input type="hidden" id="editBookingId">

            <!-- Info Section -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h4 class="flex items-center gap-2 text-sm text-gray-500 mb-3"><i class="ri-information-line"></i> รายละเอียดคำขอ</h4>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><strong>ผู้ขอ:</strong> <span id="editInfoRequester">-</span></div>
                    <div><strong>แผนก:</strong> <span id="editInfoDepartment">-</span></div>
                    <div><strong>จุดหมาย:</strong> <span id="editInfoDestination">-</span></div>
                    <div><strong>ผู้โดยสาร:</strong> <span id="editInfoPassengers">-</span></div>
                    <div class="col-span-2"><strong>วัตถุประสงค์:</strong> <span id="editInfoPurpose">-</span></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันเวลาเริ่มต้น</label>
                    <input type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="editStartTime" onblur="checkYear(this); loadAvailableEditAssets()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วันเวลาสิ้นสุด</label>
                    <input type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="editEndTime" onblur="checkYear(this); loadAvailableEditAssets()">
                </div>
            </div>

            <!-- Allocation Type -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h4 class="font-medium text-gray-700 mb-3">เลือกประเภทการจัดสรร</h4>
                <div class="flex gap-6 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="editAllocationType" value="car" checked onchange="toggleEditAllocationType()">
                        <span>รถบริษัท</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="editAllocationType" value="fleet" onchange="toggleEditAllocationType()">
                        <span>บัตรเติมน้ำมัน</span>
                    </label>
                </div>

                <div id="editCarGroup">
                    <label class="block text-sm font-medium text-gray-700 mb-2">รถที่มอบหมาย <span class="text-red-500">*</span></label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="editCarId">
                        <option value="">-- ไม่ระบุ --</option>
                        <?php foreach ($cars as $car): ?>
                            <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name'] ?: ($car['brand'] . ' ' . $car['model'])) ?> (<?= $car['license_plate'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="editFleetGroup" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">บัตรเติมน้ำมัน <span class="text-red-500">*</span></label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="editFleetCardId">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($fleetCards as $fc): ?>
                                <option value="<?= $fc['id'] ?>"><?= htmlspecialchars($fc['card_number']) ?> - <?= $fc['department'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วงเงินอนุมัติ (บาท)</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="editFleetAmount" placeholder="0">
                    </div>
                </div>
            </div>

        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeEditModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="confirmEdit()">
                <i class="ri-save-line"></i> บันทึก
            </button>
        </div>
    </div>
</div>

<!-- Revoke Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="revokeModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">ยกเลิกคำขอที่อนุมัติแล้ว</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeRevokeModal()">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="revokeBookingId">
            <div class="p-4 bg-red-50 rounded-lg">
                <strong class="text-red-600"><i class="ri-alert-line"></i> คำเตือน</strong>
                <p class="text-red-800 text-sm mt-1">การยกเลิกคำขอที่อนุมัติแล้วจะส่งอีเมลแจ้งผู้ขอทันที</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">เหตุผลในการยกเลิก *</label>
                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="revokeReason" rows="4" placeholder="กรุณาระบุเหตุผล..."></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeRevokeModal()">ปิด</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors" onclick="confirmRevoke()">
                <i class="ri-close-circle-line"></i> ยืนยันยกเลิก
            </button>
        </div>
    </div>
</div>

<!-- Report Return Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="reportReturnModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 font-semibold text-green-600"><i class="ri-arrow-go-back-line"></i> แจ้งคืนรถ</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeReportReturnModal()">&times;</button>
        </div>
        <div class="p-6 space-y-4">
            <div class="p-4 bg-green-50 rounded-lg" id="reportReturnBookingInfo"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ (ถ้ามี)</label>
                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="reportReturnNotes" rows="3" placeholder="เช่น สภาพรถ, น้ำมันเหลือ..."></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeReportReturnModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors" onclick="confirmReportReturn()">
                <i class="ri-arrow-go-back-line"></i> ยืนยันแจ้งคืน
            </button>
        </div>
    </div>
</div>

<!-- Resend Email Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="resendEmailModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 font-semibold text-indigo-600"><i class="ri-mail-send-line"></i> ส่งอีเมลอีกครั้ง</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeResendModal()">&times;</button>
        </div>
        <div class="p-6">
            <input type="hidden" id="resendBookingId">
            <div class="bg-indigo-50 rounded-lg p-4 mb-4">
                <div id="resendBookingInfo"></div>
            </div>
            <p class="text-gray-600 text-sm">ระบบจะส่งอีเมลแจ้งเตือนไปยังหัวหน้างานอีกครั้ง</p>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeResendModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg font-medium transition-colors" onclick="confirmResendEmail()">
                <i class="ri-mail-send-line"></i> ส่งอีเมล
            </button>
        </div>
    </div>
</div>

<style>
    #bookingDetailModal.active,
    #cancelModal.active,
    #editModal.active,
    #revokeModal.active,
    #reportReturnModal.active,
    #resendEmailModal.active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    function filterBookings() {
        const status = document.getElementById('filterStatus').value.toLowerCase();
        const search = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#bookingsTable tbody tr[data-status]').forEach(row => {
            const matchStatus = !status || row.dataset.status === status;
            const matchSearch = !search || row.textContent.toLowerCase().includes(search);
            row.style.display = (matchStatus && matchSearch) ? '' : 'none';
        });
    }

    function viewBookingDetail(booking) {
        const content = document.getElementById('bookingDetailContent');
        const footer = document.getElementById('bookingDetailFooter');
        const statusMap = {
            'pending_supervisor': {
                text: 'รอหัวหน้าอนุมัติ',
                class: 'bg-amber-100 text-amber-800'
            },
            'pending_manager': {
                text: 'รอผู้จัดการอนุมัติ',
                class: 'bg-blue-100 text-blue-800'
            },
            'approved': {
                text: 'อนุมัติแล้ว',
                class: 'bg-emerald-100 text-emerald-800'
            },
            'rejected': {
                text: 'ปฏิเสธ',
                class: 'bg-red-100 text-red-800'
            },
            'cancelled': {
                text: 'ยกเลิก',
                class: 'bg-gray-100 text-gray-600'
            },
            'completed': {
                text: 'เสร็จสิ้น',
                class: 'bg-blue-100 text-blue-800'
            }
        };
        const status = statusMap[booking.status] || {
            text: booking.status,
            class: 'bg-gray-100 text-gray-600'
        };

        let passengerText = '-';
        if (booking.passengers_detail) {
            try {
                const list = typeof booking.passengers_detail === 'string' ? JSON.parse(booking.passengers_detail) : booking.passengers_detail;
                if (Array.isArray(list) && list.length) {
                    const names = list.map(p => (typeof p === 'object' && p !== null) ? (p.name || p.email || '') : p).filter(Boolean);
                    if (names.length) passengerText = names.join(', ');
                }
            } catch (e) {}
        }

        let allocatedAsset = '-';
        if (booking.assigned_car_id) {
            allocatedAsset = booking.assigned_car_name || ((booking.assigned_car_brand || '') + ' ' + (booking.assigned_car_model || ''));
            if (booking.assigned_car_plate) allocatedAsset += ' (' + booking.assigned_car_plate + ')';
        } else if (booking.fleet_card_number || booking.fleet_card_id) {
            allocatedAsset = 'Fleet Card: ' + (booking.fleet_card_number || ('#' + booking.fleet_card_id));
            if (booking.fleet_amount) allocatedAsset += ' (' + Number(booking.fleet_amount).toLocaleString() + ' บาท)';
        }

        let approver = booking.approver_name || booking.approver_email || '-';
        if (booking.supervisor_approved_by) {
            let actualApprover = booking.supervisor_approved_name || booking.supervisor_approved_by;
            let isMatch = (booking.supervisor_approved_by === booking.approver_email);
            let colorClass = isMatch ? 'text-emerald-600' : 'text-red-500';
            approver += ` <span class="text-xs ${colorClass}">(${actualApprover})</span>`;
        }

        content.innerHTML = `
            <div class="space-y-3 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-xs text-gray-500 block">รหัสคำขอ</span><span class="font-semibold text-lg">#${booking.id}</span></div>
                    <div><span class="text-xs text-gray-500 block">สถานะ</span><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${status.class}">${status.text}</span></div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-xs text-gray-500 block">ผู้ขอ</span><span class="font-medium">${booking.fullname || booking.username || '-'}</span></div>
                    <div><span class="text-xs text-gray-500 block">ผู้อนุมัติ</span><span class="font-medium break-all">${approver}</span></div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><span class="text-xs text-gray-500 block">คนขับ</span><span class="font-medium">${booking.driver_name || '-'}</span></div>
                    <div><span class="text-xs text-gray-500 block">วันเวลาเริ่มต้น</span><span class="font-medium">${formatDateTime(booking.start_time)}</span></div>
                </div>
                
                <div>
                    <span class="text-xs text-gray-500 block">วันเวลาสิ้นสุด</span>
                    <span class="font-medium">${formatDateTime(booking.end_time)}</span>
                </div>
                
                <div>
                    <span class="text-xs text-gray-500 block">ปลายทาง</span>
                    <span class="font-medium">${booking.destination || '-'}</span>
                </div>
                
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <span class="text-xs text-gray-500 block mb-1">สิ่งที่คุณได้รับ (Allocated Asset)</span>
                    <span class="font-medium text-primary flex items-center gap-2">
                        ${allocatedAsset !== '-' ? '<i class="ri-car-line"></i>' : ''} ${allocatedAsset}
                    </span>
                </div>
                
                <div>
                    <span class="text-xs text-gray-500 block">วัตถุประสงค์</span>
                    <span class="font-medium">${booking.purpose || '-'}</span>
                </div>
                
                <div>
                    <span class="text-xs text-gray-500 block">ผู้โดยสาร</span>
                    <span class="font-medium">${passengerText}</span>
                </div>
            </div>
            ${booking.rejection_reason ? `<div class="mt-4 p-3 bg-red-50 border-l-4 border-red-500 rounded"><strong class="text-red-600">เหตุผลที่ปฏิเสธ:</strong> ${booking.rejection_reason}</div>` : ''}
        `;

        let footerHtml = '<button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeBookingDetailModal()">ปิด</button>';
        if (booking.status === 'approved') {
            footerHtml = `<button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors" onclick="printRequest(${booking.id})"><i class="ri-printer-line"></i> พิมพ์</button>` + footerHtml;
        }
        footer.innerHTML = footerHtml;
        document.getElementById('bookingDetailModal').classList.add('active');
    }

    function closeBookingDetailModal() {
        document.getElementById('bookingDetailModal').classList.remove('active');
    }

    function cancelBooking(id) {
        document.getElementById('cancelBookingId').value = id;
        document.getElementById('cancelReason').value = '';
        document.getElementById('cancelModal').classList.add('active');
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').classList.remove('active');
    }

    async function confirmCancelBooking() {
        const id = document.getElementById('cancelBookingId').value;
        const reasonInput = document.getElementById('cancelReason');
        const reason = reasonInput.value.trim();

        // Validation: Reason is required
        if (!reason) {
            showToast('กรุณาระบุเหตุผลในการยกเลิก', 'error');
            reasonInput.focus();
            return;
        }

        // Prevent double submit
        const submitBtn = document.querySelector('#cancelModal button[onclick="confirmCancelBooking()"]');
        if (submitBtn.disabled) return;

        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังดำเนินการ...';

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id,
                    reason
                })
            });
            const result = await response.json();
            if (result.success) {
                showToast('ยกเลิกคำขอสำเร็จ', 'success');
                closeCancelModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function printRequest(id) {
        window.open(MODULE_URL + '/public/print_request.php?id=' + id, '_blank');
    }

    let currentEditBooking = null;

    function toggleEditAllocationType() {
        const type = document.querySelector('input[name="editAllocationType"]:checked').value;
        document.getElementById('editCarGroup').classList.toggle('hidden', type !== 'car');
        document.getElementById('editFleetGroup').classList.toggle('hidden', type !== 'fleet');
    }

    function openEditModal(booking) {
        currentEditBooking = booking;
        document.getElementById('editBookingId').value = booking.id;

        let passengerText = '-';
        if (booking.passengers_detail) {
            try {
                const list = typeof booking.passengers_detail === 'string' ? JSON.parse(booking.passengers_detail) : booking.passengers_detail;
                if (Array.isArray(list) && list.length) {
                    const names = list.map(p => (typeof p === 'object' && p !== null) ? (p.name || p.email || '') : p).filter(Boolean);
                    if (names.length) passengerText = names.join(', ');
                }
            } catch (e) {}
        }

        document.getElementById('editInfoRequester').textContent = booking.requester_name || booking.fullname || '-';
        document.getElementById('editInfoDepartment').textContent = booking.department || '-';
        document.getElementById('editInfoDestination').textContent = booking.destination || '-';
        document.getElementById('editInfoPassengers').textContent = passengerText;
        document.getElementById('editInfoPurpose').textContent = booking.purpose || '-';

        const formatForInput = (dt) => dt ? dt.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('editStartTime').value = formatForInput(booking.start_time);
        document.getElementById('editEndTime').value = formatForInput(booking.end_time);

        if (booking.fleet_card_id) {
            document.querySelector('input[name="editAllocationType"][value="fleet"]').checked = true;
            document.getElementById('editFleetCardId').value = String(booking.fleet_card_id);
            document.getElementById('editFleetAmount').value = booking.fleet_amount || '';
            document.getElementById('editCarId').value = '';
        } else if (booking.assigned_car_id) {
            document.querySelector('input[name="editAllocationType"][value="car"]').checked = true;
            document.getElementById('editCarId').value = String(booking.assigned_car_id);
            document.getElementById('editFleetCardId').value = '';
            document.getElementById('editFleetAmount').value = '';
        } else {
            // Neither assigned - default to car selection
            document.querySelector('input[name="editAllocationType"][value="car"]').checked = true;
            document.getElementById('editCarId').value = '';
            document.getElementById('editFleetCardId').value = '';
            document.getElementById('editFleetAmount').value = '';
        }
        toggleEditAllocationType();
        loadAvailableEditAssets();
        document.getElementById('editModal').classList.add('active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
        currentEditBooking = null;
    }

    async function confirmEdit() {
        if (!currentEditBooking) return;
        const id = document.getElementById('editBookingId').value;
        const startTime = document.getElementById('editStartTime').value;
        const endTime = document.getElementById('editEndTime').value;
        const type = document.querySelector('input[name="editAllocationType"]:checked').value;
        let carId = null,
            fleetId = null,
            fleetAmount = null;

        if (type === 'car') {
            carId = document.getElementById('editCarId').value;
            if (!carId) {
                showToast('กรุณาเลือกรถ', 'error');
                return;
            }
        } else {
            fleetId = document.getElementById('editFleetCardId').value;
            fleetAmount = document.getElementById('editFleetAmount').value;
            if (!fleetId) {
                showToast('กรุณาเลือกบัตร Fleet Card', 'error');
                return;
            }
        }

        const submitBtn = document.querySelector('#editModal .bg-primary');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังบันทึก...';

        try {
            const res = await fetch(`${API_BASE}?controller=bookings&action=updateApproved`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id,
                    start_time: startTime,
                    end_time: endTime,
                    assigned_car_id: carId,
                    fleet_card_id: fleetId,
                    fleet_amount: fleetAmount
                })
            });
            const result = await res.json();
            if (result.success) {
                showToast('แก้ไขข้อมูลเรียบร้อย', 'success');
                closeEditModal();
                setTimeout(() => location.reload(), 1000);
            } else showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
        } catch (e) {
            showToast('เกิดข้อผิดพลาด: ' + e.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function openRevokeModal(id) {
        document.getElementById('revokeBookingId').value = id;
        document.getElementById('revokeReason').value = '';
        document.getElementById('revokeModal').classList.add('active');
    }

    function closeRevokeModal() {
        document.getElementById('revokeModal').classList.remove('active');
    }

    async function confirmRevoke() {
        const id = document.getElementById('revokeBookingId').value;
        const reason = document.getElementById('revokeReason').value;
        if (!reason) {
            showToast('กรุณาระบุเหตุผล', 'error');
            return;
        }

        try {
            const res = await fetch(`${API_BASE}?controller=bookings&action=revoke`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id,
                    reason
                })
            });
            const result = await res.json();
            if (result.success) {
                showToast('ยกเลิกคำขอเรียบร้อย', 'success');
                closeRevokeModal();
                setTimeout(() => location.reload(), 1000);
            } else showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
        } catch (e) {
            showToast('เกิดข้อผิดพลาด: ' + e.message, 'error');
        }
    }

    // ======================================
    // RESEND EMAIL FEATURE
    // ======================================
    let currentResendBooking = null;

    function resendEmail(bookingId) {
        // Find booking data from table row
        const row = document.querySelector(`tr[data-status] td:first-child`);
        document.getElementById('resendBookingId').value = bookingId;
        document.getElementById('resendBookingInfo').innerHTML = `
            <div class="text-sm">
                <p><span class="text-gray-500">คำขอ #</span> <strong>${bookingId}</strong></p>
                <p class="text-indigo-600 mt-1"><i class="ri-mail-line"></i> ส่งอีเมลไปยังหัวหน้างานเพื่อขออนุมัติ</p>
            </div>
        `;
        document.getElementById('resendEmailModal').classList.add('active');
    }

    function closeResendModal() {
        document.getElementById('resendEmailModal').classList.remove('active');
    }

    async function confirmResendEmail() {
        const bookingId = document.getElementById('resendBookingId').value;
        const submitBtn = document.querySelector('#resendEmailModal .bg-indigo-500');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังส่ง...';

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=resendEmail`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: bookingId
                })
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message || 'ส่งอีเมลเรียบร้อยแล้ว', 'success');
                closeResendModal();
            } else {
                showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
            }
        } catch (e) {
            showToast('เกิดข้อผิดพลาด: ' + e.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    // ======================================
    // REPORT RETURN FEATURE
    // ======================================
    let currentReportReturnBooking = null;

    function openReportReturnModal(booking) {
        currentReportReturnBooking = booking;

        const carInfo = booking.assigned_car_name ?
            `${booking.assigned_car_name} (${booking.assigned_car_plate})` :
            booking.fleet_card_number ?
            `บัตร ${booking.fleet_card_number}` :
            '-';

        document.getElementById('reportReturnBookingInfo').innerHTML = `
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="text-gray-500">คำขอ #</span> <strong>${booking.id}</strong></div>
                <div><span class="text-gray-500">รถ/บัตร:</span> ${carInfo}</div>
                <div><span class="text-gray-500">ปลายทาง:</span> ${booking.destination}</div>
                <div><span class="text-gray-500">สิ้นสุด:</span> ${formatDateTime(booking.end_time)}</div>
            </div>
        `;

        document.getElementById('reportReturnNotes').value = '';
        document.getElementById('reportReturnModal').classList.add('active');
    }

    function closeReportReturnModal() {
        document.getElementById('reportReturnModal').classList.remove('active');
        currentReportReturnBooking = null;
    }

    async function confirmReportReturn() {
        if (!currentReportReturnBooking) return;
        const submitBtn = document.querySelector('#reportReturnModal .bg-green-500');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังบันทึก...';

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=reportReturn`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: currentReportReturnBooking.id,
                    notes: document.getElementById('reportReturnNotes').value
                })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showToast('แจ้งคืนรถสำเร็จ รอ IPCD ยืนยัน', 'success');
                closeReportReturnModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    async function loadAvailableEditAssets() {
        const id = document.getElementById('editBookingId').value;
        const startTime = document.getElementById('editStartTime').value;
        const endTime = document.getElementById('editEndTime').value;
        if (!startTime || !endTime) return;

        const carSelect = document.getElementById('editCarId');
        const fleetSelect = document.getElementById('editFleetCardId');

        const currentCarId = carSelect.value;
        const currentFleetId = fleetSelect.value;

        carSelect.innerHTML = '<option>กำลังโหลด...</option>';
        fleetSelect.innerHTML = '<option>กำลังโหลด...</option>';

        try {
            const res = await fetch(`${API_BASE}?controller=bookings&action=getAvailableAssets&start=${startTime}&end=${endTime}&exclude_id=${id}`);
            const data = await res.json();

            carSelect.innerHTML = '<option value="">-- ไม่ระบุ --</option>';
            if (data.cars?.length) {
                data.cars.forEach(car => {
                    const disabled = !car.is_available ? 'disabled' : '';
                    const statusText = car.is_available ? '✅ ว่าง' : `❌ ${car.reason || 'ไม่ว่าง'}`;
                    const text = `${car.name || (car.brand + ' ' + car.model)} (${car.license_plate}) [${statusText}]`;
                    const selected = (car.id == currentCarId) ? 'selected' : '';
                    carSelect.innerHTML += `<option value="${car.id}" ${disabled} ${selected}>${text}</option>`;
                });
            }

            fleetSelect.innerHTML = '<option value="">-- ไม่ระบุ --</option>';
            if (data.fleet_cards?.length) {
                data.fleet_cards.forEach(fc => {
                    const disabled = !fc.is_available ? 'disabled' : '';
                    const statusText = fc.is_available ? '✅ ว่าง' : `❌ ${fc.reason || 'ไม่ว่าง'}`;
                    const text = `${fc.card_number} - ${fc.department} [${statusText}]`;
                    const selected = (fc.id == currentFleetId) ? 'selected' : '';
                    fleetSelect.innerHTML += `<option value="${fc.id}" ${disabled} ${selected}>${text}</option>`;
                });
            }
        } catch (e) {
            carSelect.innerHTML = '<option>โหลดล้มเหลว</option>';
            fleetSelect.innerHTML = '<option>โหลดล้มเหลว</option>';
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeBookingDetailModal();
            closeCancelModal();
            closeEditModal();
            closeRevokeModal();
            closeReportReturnModal();
            if (typeof closeBookingModal === 'function') closeBookingModal();
        }
    });

    function checkYear(input) {
        if (!input.value) return;
        const date = new Date(input.value);
        let year = date.getFullYear();
        if (year > 2400) {
            // Auto-correct to A.D.
            const correctedYear = year - 543;
            const newValue = input.value.replace(year.toString(), correctedYear.toString());
            input.value = newValue;
            showToast(`ปรับปีจาก ${year} เป็น ${correctedYear} (ค.ศ.) ให้แล้ว`, 'info');
        }
    }
</script>

<?php include __DIR__ . '/partials/booking-modal.php'; ?>