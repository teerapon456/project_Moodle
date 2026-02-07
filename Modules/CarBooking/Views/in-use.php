<?php

/**
 * Car Booking - In Use / Pending Return View
 * หน้าสำหรับ IPCD ดูรายการรถที่กำลังใช้งาน และยืนยันการคืนรถ
 */

// Manager only
if (!checkManagerPermission($canView, $canManage, 'ระบบจองรถ')) return;

require_once __DIR__ . '/../Controllers/BookingController.php';

$controller = new BookingController($user);
$inUseBookings = $controller->listInUse();
$pendingReturnCount = count(array_filter($inUseBookings, fn($b) => $b['status'] === 'pending_return'));
$inUseCount = count(array_filter($inUseBookings, fn($b) => $b['status'] === 'in_use'));
$approvedCount = count(array_filter($inUseBookings, fn($b) => $b['status'] === 'approved'));

$statusBadges = [
    'approved' => 'bg-emerald-100 text-emerald-800',
    'in_use' => 'bg-purple-100 text-purple-800',
    'pending_return' => 'bg-amber-100 text-amber-800',
    'completed' => 'bg-teal-100 text-teal-800'
];

$statusLabels = [
    'approved' => 'รอใช้งาน',
    'in_use' => 'กำลังใช้งาน',
    'pending_return' => 'รอยืนยันคืน',
    'completed' => 'เสร็จสิ้น'
];
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <i class="ri-car-line text-primary"></i>
            รถที่กำลังใช้งาน
        </h2>
        <p class="text-gray-500 mt-1">ติดตามรถที่ถูกยืมและยืนยันการคืน</p>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="ri-car-fill text-purple-600 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-900"><?= $inUseCount ?></div>
                <div class="text-sm text-gray-500">กำลังใช้งาน</div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i class="ri-time-line text-emerald-600 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-900"><?= $approvedCount ?></div>
                <div class="text-sm text-gray-500">รอใช้งาน</div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="ri-arrow-go-back-line text-amber-600 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-900"><?= $pendingReturnCount ?></div>
                <div class="text-sm text-gray-500">รอยืนยันคืน</div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="ri-checkbox-circle-line text-blue-600 text-xl"></i>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-900"><?= count($inUseBookings) ?></div>
                <div class="text-sm text-gray-500">ทั้งหมด</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="flex flex-wrap gap-2 mb-6">
    <button class="usage-tab inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors" data-tab="all" onclick="switchUsageTab('all')">
        ทั้งหมด <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs"><?= count($inUseBookings) ?></span>
    </button>
    <button class="usage-tab inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg font-medium transition-colors" data-tab="in_use" onclick="switchUsageTab('in_use')">
        <i class="ri-car-line"></i> กำลังใช้งาน <span class="px-2 py-0.5 bg-purple-200 text-purple-800 rounded-full text-xs"><?= $inUseCount ?></span>
    </button>
    <button class="usage-tab inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg font-medium transition-colors" data-tab="pending_return" onclick="switchUsageTab('pending_return')">
        <i class="ri-time-line"></i> รอยืนยันคืน <span class="px-2 py-0.5 bg-amber-200 text-amber-800 rounded-full text-xs"><?= $pendingReturnCount ?></span>
    </button>
    <button class="usage-tab inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg font-medium transition-colors" data-tab="approved" onclick="switchUsageTab('approved')">
        <i class="ri-hourglass-line"></i> รอใช้งาน <span class="px-2 py-0.5 bg-emerald-200 text-emerald-800 rounded-full text-xs"><?= $approvedCount ?></span>
    </button>
</div>

<!-- Booking List -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full" id="inUseTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ยืม</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">รถ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ช่วงเวลา</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ปลายทาง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($inUseBookings)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                            <i class="ri-car-line text-4xl mb-2 block"></i>
                            <p>ไม่มีรถที่กำลังใช้งาน</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inUseBookings as $b): ?>
                        <?php
                        $isOverdue = strtotime($b['end_time']) < time() && in_array($b['status'], ['approved', 'in_use']);
                        ?>
                        <tr data-status="<?= $b['status'] ?>" class="hover:bg-gray-50 transition-colors <?= $isOverdue ? 'bg-red-50' : '' ?>">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= $b['id'] ?></td>
                            <td class="px-4 py-3">
                                <strong class="text-gray-900"><?= htmlspecialchars($b['fullname'] ?? $b['username'] ?? '-') ?></strong>
                                <br><small class="text-gray-400"><?= htmlspecialchars($b['user_email'] ?? '') ?></small>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (!empty($b['assigned_car_id'])): ?>
                                    <?php
                                    $carName = $b['assigned_car_name'] ?: (($b['assigned_car_brand'] ?? '') . ' ' . ($b['assigned_car_model'] ?? ''));
                                    ?>
                                    <div class="flex flex-col">
                                        <span class="flex items-center gap-1"><i class="ri-taxi-fill text-primary"></i> <?= htmlspecialchars($carName) ?></span>
                                        <?php if (!empty($b['assigned_car_plate'])): ?>
                                            <span class="text-xs text-gray-500">(<?= htmlspecialchars($b['assigned_car_plate']) ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (!empty($b['fleet_card_number'])): ?>
                                    <div class="flex flex-col">
                                        <span class="flex items-center gap-1"><i class="ri-bank-card-line text-primary"></i> <?= htmlspecialchars($b['fleet_card_number']) ?></span>
                                        <?php if ($b['fleet_amount']): ?>
                                            <span class="text-xs text-gray-500">(<?= number_format($b['fleet_amount']) ?> บาท)</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <?= date('d/m/Y H:i', strtotime($b['start_time'])) ?>
                                <br><small class="text-gray-400">ถึง <?= date('d/m/Y H:i', strtotime($b['end_time'])) ?></small>
                                <?php if ($isOverdue): ?>
                                    <br><span class="text-red-600 text-xs font-medium"><i class="ri-error-warning-line"></i> เกินกำหนด</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($b['destination']) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?= $statusBadges[$b['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= $statusLabels[$b['status']] ?? $b['status'] ?>
                                </span>
                                <?php if ($b['status'] === 'pending_return' && !empty($b['user_reported_return_at'])): ?>
                                    <br><small class="text-gray-400">แจ้งคืน: <?= date('d/m H:i', strtotime($b['user_reported_return_at'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors"
                                    onclick='openConfirmReturnModal(<?= json_encode($b) ?>)'>
                                    <i class="ri-checkbox-circle-line"></i> ยืนยันคืน
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Confirm Return Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="confirmReturnModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 font-semibold text-blue-600"><i class="ri-checkbox-circle-line"></i> ยืนยันคืนรถ</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeConfirmReturnModal()">&times;</button>
        </div>
        <div class="p-6">
            <input type="hidden" id="confirmReturnBookingId">

            <div class="bg-blue-50 rounded-lg p-4 mb-4">
                <div id="confirmReturnBookingInfo"></div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">เวลาคืนจริง <span class="text-red-500">*</span></label>
                <input type="datetime-local" id="actualReturnTime" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <p class="text-xs text-gray-400 mt-1">กรุณาระบุวันเวลาที่รถถูกคืนจริง</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ (ถ้ามี)</label>
                <textarea id="confirmReturnNotes" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" rows="2" placeholder="เช่น สภาพรถ, เลขกิโลเมตร"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeConfirmReturnModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors" onclick="confirmReturn()">
                <i class="ri-checkbox-circle-line"></i> ยืนยันคืนรถ
            </button>
        </div>
    </div>
</div>

<style>
    #confirmReturnModal.active {
        opacity: 1;
        visibility: visible;
    }

    .usage-tab[data-tab].active {
        background: #10b981;
        color: white;
    }

    .usage-tab[data-tab].active span {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }
</style>

<script>
    let currentConfirmBooking = null;
    let isConfirmSubmitting = false;

    function switchUsageTab(tab) {
        document.querySelectorAll('.usage-tab').forEach(t => {
            t.classList.remove('active', 'bg-primary', 'text-white');
            t.classList.add('bg-gray-100', 'text-gray-700');
        });
        const activeTab = document.querySelector(`.usage-tab[data-tab="${tab}"]`);
        activeTab.classList.add('active', 'bg-primary', 'text-white');
        activeTab.classList.remove('bg-gray-100', 'text-gray-700');

        document.querySelectorAll('#inUseTable tbody tr[data-status]').forEach(row => {
            row.style.display = (tab === 'all' || row.dataset.status === tab) ? '' : 'none';
        });
    }

    function openConfirmReturnModal(booking) {
        currentConfirmBooking = booking;
        document.getElementById('confirmReturnBookingId').value = booking.id;

        const carInfo = booking.assigned_car_name ?
            `${booking.assigned_car_name} (${booking.assigned_car_plate})` :
            booking.fleet_card_number ?
            `บัตร ${booking.fleet_card_number}` :
            '-';

        document.getElementById('confirmReturnBookingInfo').innerHTML = `
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="text-gray-500">คำขอ #</span> <strong>${booking.id}</strong></div>
                <div><span class="text-gray-500">ผู้ยืม:</span> <strong>${booking.fullname || booking.username}</strong></div>
                <div><span class="text-gray-500">รถ/บัตร:</span> ${carInfo}</div>
                <div><span class="text-gray-500">ปลายทาง:</span> ${booking.destination}</div>
            </div>
            ${booking.status === 'pending_return' ? `<div class="mt-2 text-amber-600 text-sm"><i class="ri-time-line"></i> ผู้ยืมแจ้งคืนแล้ว</div>` : ''}
        `;

        document.getElementById('actualReturnTime').value = '';
        document.getElementById('confirmReturnNotes').value = '';
        document.getElementById('confirmReturnModal').classList.add('active');
    }

    function closeConfirmReturnModal() {
        document.getElementById('confirmReturnModal').classList.remove('active');
        currentConfirmBooking = null;
    }

    async function confirmReturn() {
        if (!currentConfirmBooking || isConfirmSubmitting) return;

        // Validate: ต้องกรอกเวลาคืนจริง
        const actualReturnTime = document.getElementById('actualReturnTime').value;
        if (!actualReturnTime) {
            showToast('กรุณาระบุเวลาคืนจริง', 'error');
            document.getElementById('actualReturnTime').focus();
            return;
        }

        const submitBtn = document.querySelector('#confirmReturnModal .bg-blue-500');
        const originalText = submitBtn.innerHTML;

        isConfirmSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังบันทึก...';

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=confirmReturn`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: currentConfirmBooking.id,
                    actual_return_time: document.getElementById('actualReturnTime').value || null,
                    notes: document.getElementById('confirmReturnNotes').value
                })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showToast('ยืนยันคืนรถสำเร็จ', 'success');
                closeConfirmReturnModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
                isConfirmSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            isConfirmSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeConfirmReturnModal();
        }
    });
</script>