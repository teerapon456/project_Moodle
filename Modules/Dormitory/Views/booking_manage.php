<?php
// booking_manage.php - Admin & Supervisor
if (!$isAdmin && (!isset($canApprove) || !$canApprove)) {
    echo "<div class='p-6 text-center text-red-600 bg-red-50 rounded-lg'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>";
    return;
}
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
            <input type="text" id="params_search" class="pl-9 pr-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-64" placeholder="ค้นหาชื่อ, แผนก..." onkeyup="if(event.key === 'Enter') loadRequests()">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
        <select id="params_type" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary min-w-[150px]" onchange="loadRequests()">
            <option value="all">ทุกประเภทคำขอ</option>
            <option value="move_in">ขอเข้าพัก</option>
            <option value="move_out">ขอย้ายออก</option>
            <option value="change_room">ขอย้ายห้อง</option>
            <option value="add_relative">ขอเพิ่มญาติ</option>
            <option value="remove_relative">ขอนำญาติออก</option>
        </select>
        <select id="params_status" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary min-w-[150px]" onchange="loadRequests()">
            <?php if ($isAdmin): ?>
                <option value="pending_supervisor">รอหัวหน้าอนุมัติ</option>
                <option value="pending_manager" selected>รอ IPCD อนุมัติ</option>
                <option value="approved">อนุมัติแล้ว</option>
                <option value="rejected_supervisor">หัวหน้าปฏิเสธ</option>
                <option value="rejected_manager">IPCD ปฏิเสธ</option>
                <option value="cancelled">ยกเลิกแล้ว</option>
                <option value="all">ทั้งหมด</option>
            <?php else: ?>
                <option value="pending_supervisor" selected>รอหัวหน้าอนุมัติ</option>
            <?php endif; ?>
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

<!-- Approve Modal Styles -->
<style>
    /* Custom Scrollbar for Modal */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .custom-scrollbar:hover::-webkit-scrollbar-thumb {
        background: #94a3b8;
    }

    /* Card classes specifically for the modal */
    .room-card.selected-card {
        border-color: #10b981 !important;
        background-color: rgba(16, 185, 129, 0.04) !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
    }

    .room-card .card-check {
        top: 12px !important;
        right: 12px !important;
        background-color: white;
        border-color: #e2e8f0;
    }

    .room-card.selected-card .card-check {
        background-color: #10b981 !important;
        border-color: #10b981 !important;
    }

    .room-card .card-check i {
        opacity: 0 !important;
        transform: scale(0.5) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .room-card.selected-card .card-check i {
        opacity: 1 !important;
        transform: scale(1) !important;
        color: white !important;
    }
</style>
<!-- Approve Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[1000] opacity-0 invisible transition-all duration-200 p-5" id="approveModal">
    <div class="bg-white rounded-xl w-full max-w-5xl shadow-2xl transform scale-95 transition-all flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-checkbox-circle-line text-emerald-500"></i> อนุมัติคำขอ
            </h3>
            <button type="button" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('approveModal')">&times;</button>
        </div>
        <form id="approveForm" onsubmit="submitApprove(event)" class="flex flex-col overflow-hidden">
            <div class="p-6 overflow-y-auto custom-scrollbar space-y-6 flex-grow">
                <input type="hidden" name="id" id="approve_id">
                <input type="hidden" name="request_type" id="approve_request_type">
                <input type="hidden" name="required_capacity" id="approve_required_capacity">
                <input type="hidden" name="room_id" id="selected_room_id">
                <input type="hidden" name="status" id="approve_status">

                <!-- Required Capacity Info -->
                <div id="capacityInfo" class="bg-indigo-50 border border-indigo-100 rounded-lg p-3 hidden">
                    <div class="flex items-center gap-2 text-indigo-800">
                        <i class="ri-group-line text-lg"></i>
                        <span class="font-medium">จำนวนคนที่ต้องการ:</span>
                        <span id="requiredCountDisplay" class="text-lg font-bold">1</span>
                        <span>คน</span>
                    </div>
                </div>

                <!-- NEW Grid-based Room Selection -->
                <div id="roomSelectContainer" class="hidden flex-col gap-4 !mt-2">
                    <h4 class="font-medium text-gray-900">ค้นหาและจัดสรรห้องพัก <span class="text-red-500">*</span></h4>

                    <!-- Filters -->
                    <div class="flex flex-wrap items-end gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="flex-grow min-w-[200px]">
                            <label class="block text-sm text-gray-600 mb-1">กรองด้วยอาคาร</label>
                            <select id="building_select" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" onchange="handleBuildingChange()">
                                <option value="">เลือกอาคารทั้งหมด...</option>
                            </select>
                        </div>
                        <div class="flex-grow min-w-[200px]">
                            <label class="block text-sm text-gray-600 mb-1">กรองด้วยชั้น</label>
                            <select id="floor_select" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" onchange="handleFloorChange()" disabled>
                                <option value="">เลือกชั้นทั้งหมด...</option>
                            </select>
                        </div>
                    </div>

                    <!-- Room Cards Grid -->
                    <div id="roomsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 max-h-[40vh] min-h-[150px] overflow-y-auto custom-scrollbar p-1">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div id="dateContainer" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1" id="dateLabel">วันนัดรับกุญแจ/เข้าพัก <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="key_pickup_date" id="key_pickup_date" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div id="remarkContainer" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ (ภายใน)</label>
                        <input type="text" name="admin_remark" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" placeholder="บันทึกเพิ่มเติมสำหรับแอดมิน...">
                    </div>
                </div>

                <div id="supervisorConfirmText" class="text-center py-4 text-emerald-700 font-medium hidden">
                    คุณแน่ใจหรือไม่ที่จะอนุมัติคำขอนี้?
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 flex-shrink-0 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('approveModal')">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors shadow-sm">ยืนยันอนุมัติ</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[1000] opacity-0 invisible transition-all duration-200 p-5" id="rejectModal">
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
    const SITE_URL = '<?php echo \Core\Helpers\UrlHelper::getBaseUrl(); ?>';
    let currentRequests = [];


    document.addEventListener('DOMContentLoaded', () => {
        loadRequests();
        loadAvailableRooms();
    });

    async function loadRequests() {
        const status = document.getElementById('params_status').value;
        const search = document.getElementById('params_search').value;
        const type = document.getElementById('params_type').value;
        const tbody = document.getElementById('requestsTableBody');

        tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500"><i class="ri-loader-4-line animate-spin text-2xl mb-2 block"></i>กำลังโหลดข้อมูล...</td></tr>`;

        try {
            const result = await apiCall('booking', 'listRequests', {
                status,
                search,
                type
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
                    ${(() => {
                        // For change_room/move_out: show relatives from current occupancy
                        // For move_in/add_relative: show relatives from request
                        let relCount = 0;
                        if (['change_room', 'move_out'].includes(req.request_type)) {
                            relCount = parseInt(req.occupancy_accompanying_persons || 0);
                        } else if (req.has_relative && req.relative_details) {
                            try {
                                const relatives = JSON.parse(req.relative_details);
                                relCount = Array.isArray(relatives) ? relatives.length : 0;
                            } catch(e) {}
                        }
                        let badges = '';
                        if (relCount > 0) {
                            badges += '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 ml-1" title="รวมญาติ ' + relCount + ' คน"><i class="ri-user-add-line"></i> +' + relCount + '</span>';
                        }
                        // Show current room for change_room/move_out
                        if (['change_room', 'move_out'].includes(req.request_type) && req.current_room_number) {
                            badges += '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 ml-1" title="ห้องปัจจุบัน"><i class="ri-home-line"></i> ' + (req.current_building_name || '') + ' ' + req.current_room_number + '</span>';
                        }
                        return badges;
                    })()}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                    ${req.reason || '-'}
                </td>
                <td class="px-6 py-4">
                    ${renderDocuments(req.document_paths)}
                </td>
                <td class="px-6 py-4 text-right">
                    ${(isAdmin && ['pending_manager', 'pending_supervisor'].includes(req.status)) || (!isAdmin && req.status === 'pending_supervisor') ? `
                        <div class="flex items-center justify-end gap-2 transition-opacity">
                            <button onclick="openApprove(${req.id}, '${req.request_type}', '${req.check_in_date || ''}', ${(() => {
                                // For change_room/move_out: count from current occupancy
                                if (['change_room', 'move_out'].includes(req.request_type)) {
                                    return parseInt(req.occupancy_accompanying_persons || 0);
                                }
                                // For move_in/add_relative: count from request
                                if (req.has_relative && req.relative_details) {
                                    try { const r = JSON.parse(req.relative_details || '[]'); return Array.isArray(r) ? r.length : 0; } catch(e) { return 0; }
                                }
                                return 0;
                            })()}, '${req.status}')" class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded hover:bg-emerald-200 transition-colors flex items-center gap-1" title="อนุมัติ">
                                <i class="ri-check-line text-lg"></i><span class="text-sm font-medium">อนุมัติ</span>
                            </button>
                            <button onclick="openReject(${req.id})" class="px-3 py-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors flex items-center gap-1" title="ปฏิเสธ">
                                <i class="ri-close-line text-lg"></i><span class="text-sm font-medium">ปฏิเสธ</span>
                            </button>
                        </div>
                    ` : renderStatusBadge(req.status)}
                </td>
            </tr>
        `).join('');
    }

    let allAvailableRooms = [];
    let currentCapacityNeed = 1;

    async function loadAvailableRooms(minCapacity = 1) {
        if (!isAdmin) return;
        try {
            const res = await apiCall('booking', 'getAvailableRooms');
            if (res.success) {
                allAvailableRooms = res.data || [];
                // We don't filter immediately anymore, we wait for openApprove to pass the correct capacity
            }
        } catch (e) {
            console.error(e);
        }
    }

    function filterRoomsByCapacity(minCapacity) {
        currentCapacityNeed = minCapacity;
        const bSelect = document.getElementById('building_select');
        const fSelect = document.getElementById('floor_select');
        const grid = document.getElementById('roomsGrid');

        // Reset
        bSelect.innerHTML = '<option value="">เลือกอาคารทั้งหมด...</option>';
        fSelect.innerHTML = '<option value="">เลือกชั้นทั้งหมด...</option>';
        fSelect.disabled = true;
        grid.innerHTML = '';
        document.getElementById('selected_room_id').value = '';

        const filteredRooms = allAvailableRooms.filter(r => r.free_spots >= minCapacity);

        if (filteredRooms.length === 0) {
            grid.innerHTML = `<div class="col-span-1 sm:col-span-2 xl:col-span-3 text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                <i class="ri-hotel-bed-line text-5xl mb-3 text-gray-300"></i><br>
                ไม่มีห้องว่างเพียงพอสำหรับ ${minCapacity} คน
            </div>`;
            return;
        }

        // Attach parsed floor to all loaded rooms once
        filteredRooms.forEach(r => {
            let floorStr;
            const rn = r.room_number.toString();
            if (rn.includes('-')) {
                floorStr = rn.split('-')[0];
            } else if (rn.length === 3) {
                floorStr = rn.substring(0, 1);
            } else if (rn.length === 4) {
                floorStr = rn.substring(0, 2);
            } else {
                floorStr = Array.from(rn)[0];
            }
            r._floorParsed = floorStr;
        });

        // Extract unique buildings
        const buildings = new Set();
        filteredRooms.forEach(r => buildings.add(r.building_name));

        buildings.forEach(bName => {
            const opt = document.createElement('option');
            opt.value = bName;
            opt.textContent = bName;
            bSelect.appendChild(opt);
        });

        // Initial render without filters
        renderRoomsGrid(filteredRooms);
    }

    function handleBuildingChange() {
        const selectedBuilding = document.getElementById('building_select').value;
        const fSelect = document.getElementById('floor_select');
        document.getElementById('selected_room_id').value = '';

        fSelect.innerHTML = '<option value="">เลือกชั้นทั้งหมด...</option>';

        if (!selectedBuilding) {
            fSelect.disabled = true;
            // Show all that match capacity
            renderRoomsGrid(allAvailableRooms.filter(r => r.free_spots >= currentCapacityNeed));
            return;
        }

        fSelect.disabled = false;

        const matchingRooms = allAvailableRooms.filter(r => r.building_name === selectedBuilding && r.free_spots >= currentCapacityNeed);

        const floors = new Set();
        matchingRooms.forEach(r => floors.add(r._floorParsed));

        const sortedFloors = Array.from(floors).sort((a, b) => parseInt(a) - parseInt(b));
        sortedFloors.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = `ชั้น ${f}`;
            fSelect.appendChild(opt);
        });

        renderRoomsGrid(matchingRooms);
    }

    function handleFloorChange() {
        const selectedBuilding = document.getElementById('building_select').value;
        const selectedFloor = document.getElementById('floor_select').value;
        document.getElementById('selected_room_id').value = '';

        let matchingRooms = allAvailableRooms.filter(r => r.building_name === selectedBuilding && r.free_spots >= currentCapacityNeed);

        if (selectedFloor) {
            matchingRooms = matchingRooms.filter(r => r._floorParsed === selectedFloor);
        }

        renderRoomsGrid(matchingRooms);
    }

    function renderRoomsGrid(rooms) {
        const grid = document.getElementById('roomsGrid');
        grid.innerHTML = '';

        if (rooms.length === 0) {
            grid.innerHTML = `<div class="col-span-1 sm:col-span-2 xl:col-span-3 text-center py-12 text-gray-500 bg-gray-50 rounded-xl border border-dashed border-gray-300">ไม่พบห้องที่ตรงกับเงื่อนไข</div>`;
            return;
        }

        // Sort by building then room number
        rooms.sort((a, b) => {
            if (a.building_name !== b.building_name) return a.building_name.localeCompare(b.building_name);
            return parseInt(a.room_number) - parseInt(b.room_number);
        });

        rooms.forEach(r => {
            const isFull = r.free_spots === 0;
            const progress = (r.current_occupants / r.capacity) * 100;
            const progressColor = progress > 80 ? 'bg-red-500' : (progress > 50 ? 'bg-amber-500' : 'bg-emerald-500');

            // Format occupant names
            let namesHtml = '';
            if (r.occupant_names) {
                const names = r.occupant_names.split(',');
                namesHtml = `<div class="mt-3 pt-3 border-t border-gray-100 flex flex-col gap-1.5">`;
                names.forEach(n => {
                    namesHtml += `<div class="text-[13px] text-gray-600 flex items-center gap-2"><div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0"><i class="ri-user-line text-xs text-gray-400"></i></div> <span class="truncate">${n.trim()}</span></div>`;
                });
                namesHtml += `</div>`;
            } else {
                namesHtml = `<div class="mt-3 pt-3 border-t border-gray-100 text-[13px] text-emerald-600/80 font-medium flex items-center gap-2"><div class="w-6 h-6 rounded-full bg-emerald-50 flex items-center justify-center"><i class="ri-door-open-line text-xs"></i></div> ห้องว่างทั้งหมด</div>`;
            }

            const card = document.createElement('div');
            // Using CSS classes for the card layout instead of pure JS hover toggles for cleaner code
            card.className = `room-card relative p-5 rounded-2xl border-2 transition-all duration-200 cursor-pointer bg-white overflow-hidden shadow-sm hover:shadow-md group ${isFull ? 'opacity-50 pointer-events-none grayscale border-gray-100' : 'border-gray-100 hover:border-emerald-500 hover:bg-emerald-50/10'}`;
            card.dataset.roomId = r.id;
            card.onclick = () => selectRoom(card, r.id);

            card.innerHTML = `
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition-colors icon-wrapper">
                            <i class="ri-door-fill text-xl"></i>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-gray-900 group-hover:text-emerald-700 transition-colors leading-tight card-title">${r.room_number}</div>
                            <div class="text-xs font-medium text-gray-500 mt-0.5">อาคาร ${r.building_name}</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-end mb-2.5">
                    <div class="flex flex-col">
                       <span class="text-xs text-gray-500 mb-0.5">ผู้เข้าพัก ${r.current_relatives > 0 ? `<span class="text-purple-600">(+ญาติ ${r.current_relatives} คน)</span>` : ''}</span>
                       <span class="text-sm font-semibold text-gray-900">${r.current_occupants} <span class="text-gray-400 font-normal">/ ${r.capacity}</span></span>
                    </div>
                    <div class="text-right">
                       <div class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-1 rounded-md inline-block">ว่าง ${r.free_spots} ที่</div>
                    </div>
                </div>
                
                <div class="w-full bg-gray-100 rounded-full h-1.5 mb-1 overflow-hidden">
                    <div class="${progressColor} h-1.5 rounded-full" style="width: ${progress}%"></div>
                </div>

                ${namesHtml}

                <div class="absolute w-6 h-6 rounded-full border-2 flex items-center justify-center card-check transition-all shadow-sm">
                    <i class="ri-check-line text-white text-sm transition-all font-bold"></i>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    function selectRoom(cardElement, roomId) {
        // Deselect all
        document.querySelectorAll('.room-card').forEach(c => {
            c.classList.remove('selected-card');
        });

        // Select chosen
        cardElement.classList.add('selected-card');

        document.getElementById('selected_room_id').value = roomId;
    }

    function openApprove(id, type, checkIn, relativesCount = 0, status = 'pending_supervisor') {
        document.getElementById('approve_id').value = id;
        document.getElementById('approve_request_type').value = type;
        document.getElementById('approve_status').value = status;

        const roomSelectContainer = document.getElementById('roomSelectContainer');
        const dateContainer = document.getElementById('dateContainer');
        const dateInput = document.getElementById('key_pickup_date');
        const remarkContainer = document.getElementById('remarkContainer');
        const capacityInfo = document.getElementById('capacityInfo');
        const supervisorConfirmText = document.getElementById('supervisorConfirmText');
        const dateLabel = document.getElementById('dateLabel');
        const modalTitle = document.querySelector('#approveModal h3');

        // Reset display
        roomSelectContainer.classList.add('hidden');
        roomSelectContainer.classList.remove('flex');
        dateContainer.classList.add('hidden');
        remarkContainer.classList.add('hidden');
        capacityInfo.classList.add('hidden');
        supervisorConfirmText.classList.add('hidden');
        dateInput.required = false;

        // If it's a supervisor approval, they don't pick anything, they just confirm
        if (status === 'pending_supervisor') {
            supervisorConfirmText.classList.remove('hidden');
            modalTitle.innerHTML = '<i class="ri-checkbox-circle-line text-emerald-500"></i> อนุมัติคำขอ (หัวหน้างาน)';
        } else {
            // IPCD Approval - Need Room, Date, Remarks depending on type
            dateContainer.classList.remove('hidden');
            remarkContainer.classList.remove('hidden');
            dateInput.required = true;

            if (type === 'move_out') {
                // Move Out: no room needed, just date
                dateLabel.innerHTML = 'วันที่ย้ายออก <span class="text-red-500">*</span>';
                modalTitle.innerHTML = '<i class="ri-logout-box-line text-red-500"></i> อนุมัติย้ายออก';
            } else if (type === 'add_relative') {
                // Add Relative: no room needed (auto-uses current room), just date
                dateLabel.innerHTML = 'วันที่เพิ่มญาติ <span class="text-red-500">*</span>';
                modalTitle.innerHTML = '<i class="ri-user-add-line text-purple-500"></i> อนุมัติเพิ่มญาติ';
            } else if (type === 'change_room' || type === 'move_in') {
                // Change Room or Move In: need room
                roomSelectContainer.classList.remove('hidden');
                roomSelectContainer.classList.add('flex');

                capacityInfo.classList.remove('hidden');

                if (type === 'change_room') {
                    dateLabel.innerHTML = 'วันที่ย้ายห้อง <span class="text-red-500">*</span>';
                    modalTitle.innerHTML = '<i class="ri-swap-line text-blue-500"></i> อนุมัติย้ายห้อง';
                } else {
                    dateLabel.innerHTML = 'วันนัดรับกุญแจ/เข้าพัก <span class="text-red-500">*</span>';
                    modalTitle.innerHTML = '<i class="ri-checkbox-circle-line text-emerald-500"></i> อนุมัติคำขอ';
                }

                const requiredCapacity = 1; // ไม่นับญาติ
                document.getElementById('approve_required_capacity').value = requiredCapacity;
                document.getElementById('requiredCountDisplay').textContent = requiredCapacity;
                filterRoomsByCapacity(requiredCapacity);
            }

            const minDate = checkIn ? new Date(checkIn).toISOString().slice(0, 16) : new Date().toISOString().slice(0, 16);
            dateInput.value = minDate;
        }

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

        // Validation for hidden input
        if (['move_in', 'change_room'].includes(data.request_type) && !data.room_id && data.status !== 'pending_supervisor') {
            showToast('กรุณาเลือกจัดสรรห้องพัก', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }

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
        document.getElementById('rejectForm').reset();
        document.getElementById('reject_id').value = id;
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
            'pending_supervisor': {
                label: 'รอหัวหน้าอนุมัติ',
                class: 'bg-amber-100 text-amber-800'
            },
            'pending_manager': {
                label: 'รอ IPCD อนุมัติ',
                class: 'bg-yellow-100 text-yellow-800'
            },
            'approved': {
                label: 'อนุมัติแล้ว',
                class: 'bg-emerald-100 text-emerald-800'
            },
            'rejected_supervisor': {
                label: 'หัวหน้าปฏิเสธ',
                class: 'bg-red-100 text-red-800'
            },
            'rejected_manager': {
                label: 'IPCD ปฏิเสธ',
                class: 'bg-red-200 text-red-900'
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
                ${Object.values(files).map(path => {
                    // Fix path: remove 'public/' prefix if present to avoid duplication with docroot
                    const cleanPath = path.replace(/^public\//, '');
                    const fullUrl = `${SITE_URL}/${cleanPath}`;
                    return `<a href="${fullUrl}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-800 transition"><i class="ri-file-text-line text-lg"></i></a>`;
                }).join('')}

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