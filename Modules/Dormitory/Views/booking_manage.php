<?php
// booking_manage.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
            <input type="text" id="params_search" class="pl-9 pr-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-64" placeholder="ค้นหาชื่อ, แผนก..." onkeyup="if(event.key === 'Enter') loadRequests()">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
        <select id="params_status" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary min-w-[150px]" onchange="loadRequests()">
            <option value="pending">รอการอนุมัติ</option>
            <option value="approved">อนุมัติแล้ว</option>
            <option value="rejected">ปฏิเสธแล้ว</option>
            <option value="all">ทั้งหมด</option>
        </select>
        <button class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors" onclick="resetFilters()" title="ล้างค่า"><i class="ri-refresh-line"></i></button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 mb-1">รอการอนุมัติ</div>
            <div class="text-2xl font-bold text-yellow-600" id="statPending">0</div>
        </div>
        <div class="w-10 h-10 rounded-full bg-yellow-50 flex items-center justify-center text-yellow-600">
            <i class="ri-time-line text-xl"></i>
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 mb-1">อนุมัติเดือนนี้</div>
            <div class="text-2xl font-bold text-emerald-600" id="statApproved">0</div>
        </div>
        <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
            <i class="ri-check-line text-xl"></i>
        </div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 mb-1">คำขอทั้งหมด</div>
            <div class="text-2xl font-bold text-indigo-600" id="statTotal">0</div>
        </div>
        <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
            <i class="ri-file-list-line text-xl"></i>
        </div>
    </div>
</div>

<!-- Main Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">วันที่ขอ</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ผู้ขอ</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">ประเภท</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">รายละเอียด/เหตุผล</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">เอกสาร</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody id="requestsTableBody" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">กำลังโหลด...</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" id="paginationContainer">
        <!-- Injected by JS -->
    </div>
</div>

<!-- Approve Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="approveModal">
    <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl transform scale-95 transition-all">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-checkbox-circle-line text-emerald-500"></i> อนุมัติคำขอ
            </h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('approveModal')">&times;</button>
        </div>
        <form id="approveForm" onsubmit="submitApprove(event)">
            <div class="p-6 space-y-4">
                <input type="hidden" name="id" id="approve_id">
                <input type="hidden" name="request_type" id="approve_request_type">
                <input type="hidden" name="required_capacity" id="approve_required_capacity">

                <!-- Required Capacity Info -->
                <div id="capacityInfo" class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 mb-4">
                    <div class="flex items-center gap-2 text-indigo-800">
                        <i class="ri-group-line text-lg"></i>
                        <span class="font-medium">จำนวนคนที่ต้องการ:</span>
                        <span id="requiredCountDisplay" class="text-lg font-bold">1</span>
                        <span>คน</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">จัดสรรห้องพัก <span class="text-red-500">*</span></label>
                    <select name="room_id" id="room_select" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" required>
                        <option value="">กำลังโหลดห้องว่าง...</option>
                    </select>
                    <small class="text-gray-500 mt-1 block">แสดงเฉพาะห้องที่มีที่ว่างเพียงพอ</small>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">วันนัดรับกุญแจ/เข้าพัก <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="key_pickup_date" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ (ภายใน)</label>
                    <textarea name="admin_remark" rows="2" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" placeholder="บันทึกเพิ่มเติมสำหรับแอดมิน..."></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('approveModal')">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors shadow-sm">ยืนยันอนุมัติ</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="rejectModal">
    <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl transform scale-95 transition-all">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-close-circle-line text-red-500"></i> ปฏิเสธคำขอ
            </h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <form id="rejectForm" onsubmit="submitReject(event)">
            <div class="p-6 space-y-4">
                <input type="hidden" name="id" id="reject_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผลการปฏิเสธ <span class="text-red-500">*</span></label>
                    <textarea name="reject_reason" rows="3" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500" placeholder="ระบุเหตุผล..." required></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('rejectModal')">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors shadow-sm">ยืนยันปฏิเสธ</button>
            </div>
        </form>
    </div>
</div>

<style>
    .fixed.opacity-0.invisible[id$="Modal"].active {
        opacity: 1;
        visibility: visible;
    }

    .fixed.opacity-0.invisible[id$="Modal"].active>div {
        transform: scale(1);
    }
</style>

<script>
    let currentRequests = [];

    document.addEventListener('DOMContentLoaded', () => {
        loadRequests();
        loadAvailableRooms();
    });

    async function loadRequests() {
        const status = document.getElementById('params_status').value;
        const search = document.getElementById('params_search').value;
        const tbody = document.getElementById('requestsTableBody');

        tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500"><i class="ri-loader-4-line animate-spin text-2xl mb-2 block"></i>กำลังโหลดข้อมูล...</td></tr>`;

        try {
            const result = await apiCall('booking', 'listRequests', {
                status,
                search
            }); // Assuming API supports search/status filtering
            currentRequests = result.requests || [];
            renderTable(currentRequests);
            updateStats(result.stats || {}); // Assuming API returns stats or we calculate valid ones
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-red-500">เกิดข้อผิดพลาด: ${error.message}</td></tr>`;
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('requestsTableBody');
        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <i class="ri-inbox-line text-4xl mb-2"></i>
                            <span>ไม่พบข้อมูลคำขอ</span>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = data.map(req => `
            <tr class="hover:bg-gray-50 transition-colors group">
                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                    ${formatDateTime(req.created_at)}
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xs">
                            ${req.fullname.charAt(0)}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">${req.fullname}</div>
                            <div class="text-xs text-gray-500">${req.department || '-'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    ${getRequestTypeBadge(req.request_type)}
                    ${req.has_relative && req.relative_details ? (() => {
                        try {
                            const relatives = JSON.parse(req.relative_details);
                            const count = Array.isArray(relatives) ? relatives.length : 0;
                            if (count > 0) {
                                return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 ml-1" title="รวมญาติ ${count} คน">
                                    <i class="ri-user-add-line"></i> +${count}
                                </span>`;
                            }
                        } catch(e) {}
                        return '';
                    })() : ''}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                    ${req.reason || '-'}
                </td>
                <td class="px-6 py-4">
                    ${renderDocuments(req.document_paths)}
                </td>
                <td class="px-6 py-4 text-right">
                    ${req.status === 'pending' ? `
                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick="openApprove(${req.id}, '${req.request_type}', '${req.check_in_date || ''}', ${req.has_relative ? ((() => { try { const r = JSON.parse(req.relative_details || '[]'); return Array.isArray(r) ? r.length : 0; } catch(e) { return 0; } })()) : 0})" class="p-1.5 bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200 transition-colors" title="อนุมัติ">
                                <i class="ri-check-line text-lg"></i>
                            </button>
                            <button onclick="openReject(${req.id})" class="p-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors" title="ปฏิเสธ">
                                <i class="ri-close-line text-lg"></i>
                            </button>
                        </div>
                    ` : renderStatusBadge(req.status)}
                </td>
            </tr>
        `).join('');
    }

    let allAvailableRooms = [];

    async function loadAvailableRooms(minCapacity = 1) {
        try {
            const res = await apiCall('booking', 'getAvailableRooms');
            if (res.success) {
                allAvailableRooms = res.data || [];
                filterRoomsByCapacity(minCapacity);
            }
        } catch (e) {
            console.error(e);
        }
    }

    function filterRoomsByCapacity(minCapacity) {
        const select = document.getElementById('room_select');
        select.innerHTML = '<option value="">เลือกห้องพัก...</option>';

        const filteredRooms = allAvailableRooms.filter(r => r.free_spots >= minCapacity);

        if (filteredRooms.length === 0) {
            select.innerHTML = '<option value="">ไม่มีห้องว่างเพียงพอสำหรับ ' + minCapacity + ' คน</option>';
            return;
        }

        filteredRooms.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = `ตึก ${r.building_name} - ${r.room_number} (${r.type} - ว่าง ${r.free_spots}/${r.capacity})`;
            select.appendChild(opt);
        });
    }

    function openApprove(id, type, checkIn, relativesCount = 0) {
        document.getElementById('approve_id').value = id;
        document.getElementById('approve_request_type').value = type;

        // Calculate required capacity (1 person + relatives)
        const requiredCapacity = 1 + relativesCount;
        document.getElementById('approve_required_capacity').value = requiredCapacity;
        document.getElementById('requiredCountDisplay').textContent = requiredCapacity;

        // Filter rooms by required capacity
        filterRoomsByCapacity(requiredCapacity);

        const minDate = checkIn ? new Date(checkIn).toISOString().slice(0, 16) : new Date().toISOString().slice(0, 16);
        document.querySelector('input[name="key_pickup_date"]').min = minDate;
        document.querySelector('input[name="key_pickup_date"]').value = minDate;

        openModal('approveModal');
    }

    async function submitApprove(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังบันทึก...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        try {
            await apiCall('booking', 'approve', data, 'POST');
            showToast('อนุมัติคำขอเรียบร้อยแล้ว', 'success');
            closeModal('approveModal');
            loadRequests();
        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    function openReject(id) {
        document.getElementById('reject_id').value = id;
        document.getElementById('rejectForm').reset();
        openModal('rejectModal');
    }

    async function submitReject(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังบันทึก...';

        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        try {
            await apiCall('booking', 'reject', data, 'POST');
            showToast('ปฏิเสธคำขอเรียบร้อยแล้ว', 'success');
            closeModal('rejectModal');
            loadRequests();
        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }

    function updateStats(stats) {
        // Implementation depends on if API sends usage stats. 
        // For now, let's just calculate from current list if 'all' is selected, or skip
        if (currentRequests) {
            const pending = currentRequests.filter(r => r.status === 'pending').length;
            const approved = currentRequests.filter(r => r.status === 'approved').length; // Maybe check date?

            // If data is paginated, these client-side stats are inaccurate. 
            // Better to assume API returns 'stats' object: { pending_count: 5, approved_month: 10, total: 100 }
            if (stats && stats.pending_count !== undefined) {
                document.getElementById('statPending').textContent = stats.pending_count;
                document.getElementById('statApproved').textContent = stats.approved_count;
                document.getElementById('statTotal').textContent = stats.total_count;
            }
        }
    }

    // --- Helpers ---
    function formatDateTime(str) {
        if (!str) return '-';
        return new Date(str).toLocaleString('th-TH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function getRequestTypeBadge(type) {
        const types = {
            'move_in': {
                label: 'ขอเข้าพัก',
                class: 'bg-emerald-100 text-emerald-800'
            },
            'move_out': {
                label: 'ขอย้ายออก',
                class: 'bg-red-100 text-red-800'
            },
            'add_relative': {
                label: 'ขอนำญาติ',
                class: 'bg-purple-100 text-purple-800'
            },
            'change_room': {
                label: 'ขอย้ายห้อง',
                class: 'bg-blue-100 text-blue-800'
            }
        };
        const t = types[type] || {
            label: type,
            class: 'bg-gray-100 text-gray-800'
        };
        return `<span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${t.class}">${t.label}</span>`;
    }

    function renderStatusBadge(status) {
        const statuses = {
            'pending': {
                label: 'รออนุมัติ',
                class: 'bg-yellow-100 text-yellow-800'
            },
            'approved': {
                label: 'อนุมัติ',
                class: 'bg-emerald-100 text-emerald-800'
            },
            'rejected': {
                label: 'ปฏิเสธ',
                class: 'bg-red-100 text-red-800'
            },
            'cancelled': {
                label: 'ยกเลิก',
                class: 'bg-gray-100 text-gray-600'
            }
        };
        const s = statuses[status] || {
            label: status,
            class: 'bg-gray-100 text-gray-800'
        };
        return `<span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium ${s.class}">${s.label}</span>`;
    }

    function renderDocuments(jsonStr) {
        if (!jsonStr) return '-';
        try {
            const files = JSON.parse(jsonStr);
            return `<div class="flex gap-2">
                ${Object.values(files).map(path => `
                    <a href="${path}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 transition"><i class="ri-file-text-line text-lg"></i></a>
                `).join('')}
            </div>`;
        } catch (e) {
            return '-';
        }
    }

    function resetFilters() {
        document.getElementById('params_search').value = '';
        document.getElementById('params_status').value = 'pending';
        loadRequests();
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }
</script>