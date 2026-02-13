<?php

/**
 * Car Booking - Manage Approvals View
 * Migrated to Tailwind CSS
 */

// Allow access for managers OR L06+ approvers
if (!$canManage && !$canApprove) {
    echo '<div class="text-center py-12 text-gray-500"><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p></div>';
    return;
}

require_once __DIR__ . '/../Controllers/BookingController.php';

$controller = new BookingController($user);

if ($canManage) {
    // Managers see ALL pending bookings (supervisor + IPCD level)
    $pendingSuper = $controller->listPendingSupervisor();
    $pendingManager = $controller->listPendingManager();
    $allPending = array_merge($pendingSuper, $pendingManager);
} else {
    // L06+ approvers see ONLY bookings assigned to them
    $pendingSuper = $controller->listMyPendingApprovals();
    $pendingManager = [];
    $allPending = $pendingSuper;
}

usort($allPending, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$cars = [];
$fleetCards = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT id, name, brand, model, license_plate, capacity, status FROM cb_cars WHERE status = 'active' ORDER BY name ASC");
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $conn->query("SELECT id, card_number, department, credit_limit FROM cb_fleet_cards WHERE status = 'active' ORDER BY card_number ASC");
    $fleetCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
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

<?php if ($canManage): ?>
    <!-- Tabs (Manager only - filter by supervisor/manager level) -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="pending-tab inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-medium transition-colors" data-tab="all" onclick="switchTab('all')">
            ทั้งหมด <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs"><?= count($allPending) ?></span>
        </button>
        <button class="pending-tab inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg font-medium transition-colors" data-tab="supervisor" onclick="switchTab('supervisor')">
            รอหัวหน้า <span class="px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?= count($pendingSuper) ?></span>
        </button>
        <button class="pending-tab inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg font-medium transition-colors" data-tab="manager" onclick="switchTab('manager')">
            รอสายงาน IPCD <span class="px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?= count($pendingManager) ?></span>
        </button>
    </div>
<?php endif; ?>

<!-- Pending List -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-checkbox-circle-line text-primary"></i>
            คำขอรออนุมัติ
        </h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full" id="pendingTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ขอ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่ใช้รถ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ปลายทาง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($allPending)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                            <i class="ri-checkbox-circle-line text-4xl mb-2 block"></i>
                            <p>ไม่มีคำขอรออนุมัติ</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allPending as $b): ?>
                        <tr data-type="<?= $b['status'] === 'pending_supervisor' ? 'supervisor' : 'manager' ?>" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= $b['id'] ?></td>
                            <td class="px-4 py-3">
                                <strong class="text-gray-900"><?= htmlspecialchars($b['fullname'] ?? $b['username'] ?? '-') ?></strong>
                                <br><small class="text-gray-400"><?= htmlspecialchars($b['email'] ?? '') ?></small>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <?= date('d/m/Y H:i', strtotime($b['start_time'])) ?>
                                <br><small class="text-gray-400">ถึง <?= date('d/m/Y H:i', strtotime($b['end_time'])) ?></small>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($b['destination']) ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?= $statusBadges[$b['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= $statusLabels[$b['status']] ?? $b['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-sm font-medium transition-colors" onclick='openApprovalModal(<?= json_encode($b) ?>)'>
                                        <i class="ri-check-line"></i> อนุมัติ
                                    </button>
                                    <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-colors" onclick='openRejectModal(<?= $b['id'] ?>)'>
                                        <i class="ri-close-line"></i> ปฏิเสธ
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Approval Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="approvalModal">
    <div class="bg-white rounded-xl w-full max-w-xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 font-semibold text-gray-900"><i class="ri-checkbox-circle-line text-primary"></i> อนุมัติคำขอ</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeApprovalModal()">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto" id="approvalBookingDetail"></div>

        <?php if ($canManage): ?>
            <!-- Car Assign Section (Manager Only) -->
            <div id="carAssignSection" class="hidden px-6 pb-6 space-y-4 border-t border-gray-100 pt-4">
                <h5 class="font-semibold text-gray-700">การจัดการรถ/น้ำมัน</h5>

                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="allocationType" value="car" checked onclick="toggleAllocationType()">
                        <span>จัดรถบริษัท</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="allocationType" value="fleet" onclick="toggleAllocationType()">
                        <span>ให้บัตรเติมน้ำมัน</span>
                    </label>
                </div>

                <div id="carSelectGroup">
                    <label class="block text-sm font-medium text-gray-700 mb-2">เลือกรถ</label>
                    <select id="assignCarId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">-- เลือกรถ --</option>
                    </select>
                </div>

                <div id="fleetSelectGroup" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">เลือกบัตร Fleet Card</label>
                        <select id="assignFleetCardId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- เลือกบัตร --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วงเงิน (บาท)</label>
                        <input type="number" id="assignFleetAmount" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="ระบุวงเงิน (ถ้ามี)">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeApprovalModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors" onclick="confirmApproval()">
                <i class="ri-check-line"></i> ยืนยันอนุมัติ
            </button>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="rejectModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 font-semibold text-red-600"><i class="ri-close-circle-line"></i> ปฏิเสธคำขอ</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeRejectModal()">&times;</button>
        </div>
        <div class="p-6">
            <input type="hidden" id="rejectBookingId">
            <label class="block text-sm font-medium text-gray-700 mb-2">กรุณาระบุเหตุผล <span class="text-red-500">*</span></label>
            <textarea id="rejectReason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" rows="3" placeholder="เช่น รถไม่ว่าง, เอกสารไม่ครบถ้วน"></textarea>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeRejectModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors" onclick="confirmReject()">
                <i class="ri-close-line"></i> ยืนยันปฏิเสธ
            </button>
        </div>
    </div>
</div>

<style>
    #approvalModal.active,
    #rejectModal.active {
        opacity: 1;
        visibility: visible;
    }

    .pending-tab[data-tab].active {
        background: #10b981;
        color: white;
    }

    .pending-tab[data-tab].active span {
        background: rgba(255, 255, 255, 0.2);
    }
</style>

<script>
    let currentApprovalBooking = null;
    let isSubmitting = false;

    function switchTab(tab) {
        document.querySelectorAll('.pending-tab').forEach(t => {
            t.classList.remove('active', 'bg-primary', 'text-white');
            t.classList.add('bg-gray-100', 'text-gray-700');
        });
        const activeTab = document.querySelector(`.pending-tab[data-tab="${tab}"]`);
        activeTab.classList.add('active', 'bg-primary', 'text-white');
        activeTab.classList.remove('bg-gray-100', 'text-gray-700');

        document.querySelectorAll('#pendingTable tbody tr[data-type]').forEach(row => {
            row.style.display = (tab === 'all' || row.dataset.type === tab) ? '' : 'none';
        });
    }

    function openApprovalModal(booking) {
        currentApprovalBooking = booking;

        let passengersHtml = '-';
        if (booking.passengers_detail) {
            try {
                const list = typeof booking.passengers_detail === 'string' ? JSON.parse(booking.passengers_detail) : booking.passengers_detail;
                if (Array.isArray(list) && list.length) {
                    passengersHtml = list.map(p => `<div class="text-sm">- ${(typeof p === 'object' ? (p.name || p.email) : p)}</div>`).join('');
                }
            } catch (e) {}
        }

        document.getElementById('approvalBookingDetail').innerHTML = `
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500 block">รหัสคำขอ</span><span class="font-medium">#${booking.id}</span></div>
                <div><span class="text-gray-500 block">ผู้ขอ</span><span class="font-medium">${booking.fullname || booking.username || '-'}</span></div>
                <div><span class="text-gray-500 block">วันเวลา</span><span class="font-medium">${formatDateTime(booking.start_time)} - ${formatDateTime(booking.end_time)}</span></div>
                <div><span class="text-gray-500 block">ปลายทาง</span><span class="font-medium">${booking.destination}</span></div>
                <div class="col-span-2"><span class="text-gray-500 block">คนขับ</span><span class="font-medium">${booking.driver_name || booking.driver_email || '-'}</span></div>
                <div class="col-span-2"><span class="text-gray-500 block">ผู้โดยสาร</span>${passengersHtml}</div>
                <div class="col-span-2"><span class="text-gray-500 block">วัตถุประสงค์</span><span class="font-medium">${booking.purpose || '-'}</span></div>
                ${booking.supervisor_approved_by ? `
                <div class="col-span-2 p-3 bg-emerald-50 rounded-lg">
                    <span class="text-emerald-600 font-medium">✅ อนุมัติโดยหัวหน้างาน</span>
                    <div class="text-emerald-700 text-sm mt-1">${booking.supervisor_approved_by} (${formatDateTime(booking.supervisor_approved_at)})</div>
                </div>` : ''}
            </div>
        `;

        const carSection = document.getElementById('carAssignSection');
        if (carSection) {
            if (booking.status === 'pending_manager') {
                carSection.classList.remove('hidden');
                loadAvailableAssets(booking.start_time, booking.end_time);
            } else {
                carSection.classList.add('hidden');
            }
        }

        document.getElementById('approvalModal').classList.add('active');
    }

    async function loadAvailableAssets(startTime, endTime) {
        const carSelect = document.getElementById('assignCarId');
        const fleetSelect = document.getElementById('assignFleetCardId');

        carSelect.innerHTML = '<option>กำลังโหลด...</option>';
        fleetSelect.innerHTML = '<option>กำลังโหลด...</option>';

        try {
            const res = await fetch(`${API_BASE}?controller=bookings&action=getAvailableAssets&start=${startTime}&end=${endTime}&exclude_id=${currentApprovalBooking.id}`);
            const data = await res.json();

            carSelect.innerHTML = '<option value="">-- เลือกรถ --</option>';
            if (data.cars?.length) {
                data.cars.forEach(car => {
                    const disabled = !car.is_available ? 'disabled' : '';
                    const statusText = car.is_available ? '✅ ว่าง' : `❌ ${car.reason || 'ไม่ว่าง'}`;
                    const text = `${car.name || (car.brand + ' ' + car.model)} (${car.license_plate}) [${statusText}]`;
                    carSelect.innerHTML += `<option value="${car.id}" ${disabled}>${text}</option>`;
                });
            }

            fleetSelect.innerHTML = '<option value="">-- เลือกบัตร Fleet Card --</option>';
            if (data.fleet_cards?.length) {
                data.fleet_cards.forEach(fc => {
                    const disabled = !fc.is_available ? 'disabled' : '';
                    const statusText = fc.is_available ? '✅ ว่าง' : `❌ ${fc.reason || 'ไม่ว่าง'}`;
                    const text = `${fc.card_number} - ${fc.department} [${statusText}]`;
                    fleetSelect.innerHTML += `<option value="${fc.id}" ${disabled}>${text}</option>`;
                });
            }
        } catch (e) {
            carSelect.innerHTML = '<option>โหลดล้มเหลว</option>';
            fleetSelect.innerHTML = '<option>โหลดล้มเหลว</option>';
        }
    }

    function closeApprovalModal() {
        document.getElementById('approvalModal').classList.remove('active');
        currentApprovalBooking = null;
    }

    function toggleAllocationType() {
        const type = document.querySelector('input[name="allocationType"]:checked').value;
        document.getElementById('carSelectGroup').classList.toggle('hidden', type !== 'car');
        document.getElementById('fleetSelectGroup').classList.toggle('hidden', type !== 'fleet');
    }

    async function confirmApproval() {
        if (!currentApprovalBooking || isSubmitting) return;
        const submitBtn = document.querySelector('#approvalModal .bg-emerald-500');
        const originalText = submitBtn.innerHTML;
        const isManagerApproval = currentApprovalBooking.status === 'pending_manager';
        let body = {
            id: currentApprovalBooking.id
        };

        if (isManagerApproval) {
            const allocationType = document.querySelector('input[name="allocationType"]:checked').value;
            if (allocationType === 'car') {
                const carId = document.getElementById('assignCarId').value;
                if (!carId) {
                    showToast('กรุณาเลือกรถ', 'error');
                    return;
                }
                body.car_id = carId;
            } else {
                const fleetCardId = document.getElementById('assignFleetCardId').value;
                if (!fleetCardId) {
                    showToast('กรุณาเลือกบัตร Fleet Card', 'error');
                    return;
                }
                body.fleet_card_id = fleetCardId;
                body.fleet_amount = document.getElementById('assignFleetAmount').value;
            }
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังประมวลผล...';

        const bookingId = currentApprovalBooking.id;
        let printWindow = isManagerApproval ? window.open('', '_blank') : null;
        if (printWindow) printWindow.document.write('กำลังสร้างเอกสาร...');

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showToast('อนุมัติสำเร็จ', 'success');
                closeApprovalModal();
                if (isManagerApproval && printWindow) printWindow.location.href = MODULE_URL + '/public/print_request.php?id=' + bookingId;

                fetch(`${API_BASE}?controller=bookings&action=sendEmailNotification`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: bookingId,
                        type: isManagerApproval ? 'approval' : 'supervisor_approval'
                    })
                }).catch(e => console.error('Email failed', e));

                setTimeout(() => location.reload(), 1500);
            } else {
                if (printWindow) printWindow.close();
                showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            if (printWindow) printWindow.close();
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    function openRejectModal(bookingId) {
        document.getElementById('rejectBookingId').value = bookingId;
        document.getElementById('rejectReason').value = '';
        document.getElementById('rejectModal').classList.add('active');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }

    async function confirmReject() {
        if (isSubmitting) return;
        const bookingId = document.getElementById('rejectBookingId').value;
        const reason = document.getElementById('rejectReason').value.trim();
        const submitBtn = document.querySelector('#rejectModal .bg-red-500');
        const originalText = submitBtn.innerHTML;

        if (!reason) {
            showToast('กรุณาระบุเหตุผล', 'error');
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังส่ง...';

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    booking_id: bookingId,
                    reason
                })
            });
            const result = await response.json();

            if (response.ok) {
                showToast('ปฏิเสธสำเร็จ', 'success');
                fetch(`${API_BASE}?controller=bookings&action=sendEmailNotification`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: bookingId,
                        type: 'rejection'
                    })
                }).catch(e => console.error('Email failed', e));
                closeRejectModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeApprovalModal();
            closeRejectModal();
        }
    });

    function printRequest(id) {
        window.open(MODULE_URL + '/public/print_request.php?id=' + id, '_blank');
    }
</script>