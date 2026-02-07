<?php
// rooms.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>
<!-- Rooms Management View - Migrated to Tailwind -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap gap-3">
        <select class="min-w-[150px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterBuilding">
            <option value="">ทุกอาคาร</option>
        </select>
        <select class="min-w-[150px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterStatus">
            <option value="">ทุกสถานะ</option>
            <option value="available">ว่าง</option>
            <option value="occupied">มีผู้พัก</option>
            <option value="maintenance">ซ่อมบำรุง</option>
        </select>
        <select class="min-w-[150px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterType">
            <option value="">ทุกประเภท</option>
            <option value="single">ห้องเดี่ยว</option>
            <option value="double">ห้องคู่</option>
            <option value="family">ห้องครอบครัว</option>
            <option value="executive">ห้องผู้บริหาร</option>
            <option value="suite">ห้องชุด</option>
        </select>
    </div>
    <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-all shadow-sm hover:shadow-md" onclick="openAddRoomModal()">
        <i class="ri-add-line"></i>
        เพิ่มห้องพัก
    </button>
</div>

<!-- Room Stats -->
<div class="flex flex-wrap gap-4 mb-6">
    <div class="flex-1 min-w-[140px] flex items-center gap-4 p-4 bg-white rounded-lg border-l-4 border-success shadow-sm">
        <span class="text-3xl font-bold text-gray-900" id="statAvailable">0</span>
        <span class="text-gray-500 text-sm">ห้องว่าง</span>
    </div>
    <div class="flex-1 min-w-[140px] flex items-center gap-4 p-4 bg-white rounded-lg border-l-4 border-info shadow-sm">
        <span class="text-3xl font-bold text-gray-900" id="statOccupied">0</span>
        <span class="text-gray-500 text-sm">มีผู้พัก</span>
    </div>
    <div class="flex-1 min-w-[140px] flex items-center gap-4 p-4 bg-white rounded-lg border-l-4 border-warning shadow-sm">
        <span class="text-3xl font-bold text-gray-900" id="statMaintenance">0</span>
        <span class="text-gray-500 text-sm">ซ่อมบำรุง</span>
    </div>
</div>

<!-- Room Grid -->
<div class="grid grid-cols-[repeat(auto-fill,minmax(180px,1fr))] gap-4" id="roomGrid">
    <div class="flex items-center justify-center col-span-full py-12">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
    </div>
</div>

<!-- Room Detail Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="roomModal">
    <div class="bg-white rounded-xl w-full max-w-[700px] max-h-[calc(100vh-40px)] flex flex-col shadow-2xl transform -translate-y-5 transition-transform">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900" id="roomModalTitle">รายละเอียดห้อง</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('roomModal')">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="roomModalBody">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="addRoomModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">เพิ่มห้องพักใหม่</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('addRoomModal')">&times;</button>
        </div>
        <form id="addRoomForm" onsubmit="handleAddRoom(event)">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">อาคาร *</label>
                    <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="building_id" required>
                        <option value="">เลือกอาคาร</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ชั้น *</label>
                        <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="floor" required min="1" max="50">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">เลขห้อง *</label>
                        <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="room_number" required placeholder="เช่น 205">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ประเภทห้อง *</label>
                        <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="room_type" id="addRoomTypeSelect" required onchange="updateRoomDefaults(this.value)">
                            <option value="">กำลังโหลด...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ความจุ (คน) *</label>
                        <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="capacity" id="roomCapacity" required min="1" value="1">
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">ค่าเช่า/เดือน (บาท)</label>
                    <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="monthly_rent" id="roomRent" value="0" min="0">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">รายละเอียดเพิ่มเติม</label>
                    <textarea class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-y" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('addRoomModal')">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="editRoomModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">แก้ไขข้อมูลห้องพัก</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('editRoomModal')">&times;</button>
        </div>
        <form id="editRoomForm" onsubmit="handleEditRoom(event)">
            <input type="hidden" name="id">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">อาคาร</label>
                    <input type="text" class="w-full px-3 py-2.5 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-500" id="editRoomBuildingName" disabled>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ชั้น *</label>
                        <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="floor" required min="1" max="50">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">เลขห้อง *</label>
                        <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="room_number" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ประเภทห้อง *</label>
                        <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="room_type" id="editRoomTypeSelect" required>
                            <option value="">กำลังโหลด...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ความจุ (คน) *</label>
                        <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="capacity" required min="1">
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">สถานะ *</label>
                    <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="status" required>
                        <option value="available">ว่าง</option>
                        <option value="occupied">มีผู้พัก</option>
                        <option value="maintenance">ซ่อมบำรุง</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">ค่าเช่า/เดือน (บาท)</label>
                    <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="monthly_rent" id="editRoomRent" min="0">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">รายละเอียดเพิ่มเติม</label>
                    <textarea class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-y" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('editRoomModal')">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Check-in Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="checkInModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Check-in ผู้พักอาศัย</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('checkInModal')">&times;</button>
        </div>
        <form id="checkInForm" onsubmit="handleCheckIn(event)">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <input type="hidden" id="checkInRoomId" name="room_id">
                <input type="hidden" id="occupantType" name="occupant_type" value="employee">
                <input type="hidden" id="selectedEmployeeId" name="employee_id">
                <input type="hidden" id="selectedEmployeeName" name="employee_name">
                <input type="hidden" id="selectedEmployeeEmail" name="employee_email">
                <input type="hidden" id="selectedDepartment" name="department">

                <!-- Occupant Type Tabs -->
                <div class="flex gap-2 p-1 bg-gray-100 rounded-lg">
                    <button type="button" class="flex-1 py-2.5 px-4 rounded-md font-medium text-sm flex items-center justify-center gap-2 transition-all tab-btn active" onclick="switchOccupantType('employee')">
                        <i class="ri-user-star-line"></i>
                        พนักงาน
                    </button>
                    <button type="button" class="flex-1 py-2.5 px-4 rounded-md font-medium text-sm flex items-center justify-center gap-2 transition-all tab-btn" onclick="switchOccupantType('temporary')">
                        <i class="ri-user-received-line"></i>
                        ชั่วคราว (คนนอก)
                    </button>
                </div>

                <!-- Employee Search (for พนักงาน) -->
                <div id="employeeForm">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ค้นหาพนักงาน *</label>
                        <div class="relative">
                            <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="employeeSearch"
                                placeholder="พิมพ์รหัสพนักงาน หรือ ชื่อ-นามสกุล"
                                autocomplete="off"
                                oninput="searchEmployee(this.value)">
                            <div class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-[200px] overflow-y-auto z-50 hidden" id="employeeResults"></div>
                        </div>
                    </div>

                    <!-- Selected Employee Display -->
                    <div class="hidden mt-4 p-4 bg-green-50 border border-green-300 rounded-lg" id="selectedEmployeeBox">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <i class="ri-user-line text-2xl text-success"></i>
                                <div>
                                    <div class="font-semibold text-gray-900" id="displayName"></div>
                                    <div class="text-sm text-gray-500" id="displayDetail"></div>
                                </div>
                            </div>
                            <button type="button" class="p-1 text-gray-400 hover:text-red-500 text-xl" onclick="clearSelectedEmployee()">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Manual Input (for ชั่วคราว) -->
                <div id="temporaryForm" class="hidden space-y-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ชื่อ-นามสกุล *</label>
                        <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="tempName" placeholder="ชื่อ-นามสกุล">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">เบอร์โทรศัพท์</label>
                            <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="tempPhone" placeholder="เบอร์โทร">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">บัตรประชาชน</label>
                            <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="tempIdCard" placeholder="เลขบัตร">
                        </div>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">หน่วยงาน/บริษัท</label>
                        <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="tempCompany" placeholder="หน่วยงาน/บริษัท">
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">วันที่ Check-in *</label>
                    <input type="date" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="check_in_date" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">หมายเหตุ</label>
                    <textarea class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-y" name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('checkInModal')">ยกเลิก</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-success hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors shadow-sm">
                    <i class="ri-login-box-line"></i>
                    Check-in
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Check-out Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="checkOutModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Check-out ผู้พักอาศัย</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('checkOutModal')">&times;</button>
        </div>
        <form id="checkOutForm" onsubmit="handleCheckOutSubmit(event)">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <p class="text-gray-600">เลือกผู้ที่ต้องการ Check-out:</p>
                <div id="checkOutOccupantsList" class="flex flex-col gap-3">
                    <!-- Checkboxes injected by JS -->
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">วันที่ Check-out</label>
                    <input type="date" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="check_out_date" required>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('checkOutModal')">ยกเลิก</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-danger hover:bg-red-600 text-white rounded-lg font-medium transition-colors shadow-sm">
                    <i class="ri-logout-box-line"></i>
                    ยืนยัน Check-out
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Modal active state - Tailwind doesn't handle this well inline */
    .fixed.opacity-0.invisible[id$="Modal"].active {
        opacity: 1;
        visibility: visible;
    }

    .fixed.opacity-0.invisible[id$="Modal"].active>div {
        transform: translateY(0);
    }

    /* Tab button active state */
    .tab-btn {
        background: transparent;
        color: #6b7280;
    }

    .tab-btn:hover {
        color: #374151;
    }

    .tab-btn.active {
        background: white;
        color: #A21D21;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
    let rooms = [];
    let buildings = [];
    let roomTypes = [];

    document.addEventListener('DOMContentLoaded', async () => {
        await loadBuildings();
        await loadRoomTypes();

        // Check for URL params
        const urlParams = new URLSearchParams(window.location.search);
        const buildingId = urlParams.get('building_id');
        if (buildingId) {
            const select = document.getElementById('filterBuilding');
            if (select.querySelector(`option[value="${buildingId}"]`)) {
                select.value = buildingId;
            }
        }

        await loadRooms();

        document.getElementById('filterBuilding').addEventListener('change', loadRooms);
        document.getElementById('filterStatus').addEventListener('change', loadRooms);
        document.getElementById('filterType').addEventListener('change', loadRooms);

        // Set default check-in date to today
        document.querySelector('input[name="check_in_date"]').value = new Date().toISOString().split('T')[0];
    });

    async function loadRoomTypes() {
        try {
            const result = await apiCall('settings', 'getRoomTypes');
            roomTypes = result.room_types || [];
            populateRoomTypeDropdowns();
        } catch (error) {
            console.error('Failed to load room types:', error);
        }
    }

    function populateRoomTypeDropdowns() {
        const selects = [
            document.getElementById('addRoomTypeSelect'),
            document.getElementById('editRoomTypeSelect'),
            document.getElementById('filterType')
        ];

        const activeTypes = roomTypes.filter(rt => rt.status === 'active');

        selects.forEach(select => {
            if (!select) return;

            // Preserve current value for edit modal
            const currentValue = select.value;

            select.innerHTML = select.id === 'filterType' ?
                '<option value="">ทุกประเภท</option>' :
                '<option value="">เลือกประเภท...</option>';

            activeTypes.forEach(rt => {
                const opt = document.createElement('option');
                opt.value = rt.code || rt.id;
                opt.textContent = rt.name + (rt.capacity > 1 ? ` (${rt.capacity} คน)` : '');
                opt.dataset.capacity = rt.capacity;
                opt.dataset.rent = rt.monthly_rent;
                select.appendChild(opt);
            });

            // Restore value if it exists
            if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
                select.value = currentValue;
            }

            // Add change listener to auto-populate rent
            if (select.id === 'addRoomTypeSelect' || select.id === 'editRoomTypeSelect') {
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const rent = selectedOption.dataset.rent;
                    const targetInputId = this.id === 'addRoomTypeSelect' ? 'roomRent' : 'editRoomRent';
                    const targetInput = document.getElementById(targetInputId);

                    if (targetInput && rent !== undefined) {
                        targetInput.value = rent;
                    }
                });
            }
        });
    }


    async function loadBuildings() {
        try {
            const result = await apiCall('buildings', 'list');
            buildings = result.buildings;

            const select = document.getElementById('filterBuilding');
            buildings.forEach(b => {
                const option = document.createElement('option');
                option.value = b.id;
                option.textContent = `${b.code} - ${b.name}`;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load buildings:', error);
        }
    }

    async function loadRooms() {
        try {
            const buildingId = document.getElementById('filterBuilding').value;
            const status = document.getElementById('filterStatus').value;
            const type = document.getElementById('filterType').value;

            const params = {};
            if (buildingId) params.building_id = buildingId;
            if (status) params.status = status;
            if (type) params.room_type = type;

            const result = await apiCall('rooms', 'list', params);
            rooms = result.rooms;
            renderRooms();
            updateStats();
        } catch (error) {
            console.error('Failed to load rooms:', error);
        }
    }

    function updateStats() {
        const stats = {
            available: 0,
            occupied: 0,
            maintenance: 0
        };
        rooms.forEach(r => {
            if (stats[r.status] !== undefined) stats[r.status]++;
        });

        document.getElementById('statAvailable').textContent = stats.available;
        document.getElementById('statOccupied').textContent = stats.occupied;
        document.getElementById('statMaintenance').textContent = stats.maintenance;
    }

    function renderRooms() {
        const grid = document.getElementById('roomGrid');

        if (rooms.length === 0) {
            grid.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
                <i class="ri-door-open-line text-4xl mb-3"></i>
                <p>ไม่พบห้องพัก</p>
            </div>
        `;
            return;
        }

        const statusColors = {
            'available': 'border-t-success',
            'occupied': 'border-t-info',
            'maintenance': 'border-t-warning'
        };

        grid.innerHTML = rooms.map(room => {
            // Calculate total occupants including accompanying persons
            let totalOccupants = 0;
            if (room.occupants && room.occupants.length > 0) {
                room.occupants.forEach(occ => {
                    totalOccupants += 1 + parseInt(occ.accompanying_persons || 0);
                });
            }

            return `
            <div class="bg-white border border-gray-200 rounded-xl p-4 cursor-pointer transition-all hover:border-primary hover:-translate-y-0.5 hover:shadow-md border-t-[3px] ${statusColors[room.status] || ''}" onclick="showRoomDetail(${room.id})">
                <div class="flex items-center justify-between mb-1">
                    <div class="text-lg font-semibold text-gray-900">${room.building_code}${room.room_number}</div>
                    ${totalOccupants > 0 ? `<span class="text-xs text-gray-500">${totalOccupants}/${room.capacity}</span>` : ''}
                </div>
                <div class="text-sm text-gray-500 mb-3">${getRoomType(room.room_type)} • ชั้น ${room.floor}</div>
                ${room.occupants && room.occupants.length > 0 ? room.occupants.map(occ => `
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="ri-user-line text-primary"></i>
                        <span class="truncate">${escapeHtml(occ.employee_name)}</span>
                        ${parseInt(occ.accompanying_persons || 0) > 0 ? `<span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">+${occ.accompanying_persons} ญาติ</span>` : ''}
                    </div>
                `).join('') : `
                    <span class="inline-flex px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium">ว่าง</span>
                `}
            </div>
        `
        }).join('');
    }

    function getRoomType(type) {
        const map = {
            'single': 'ห้องเดี่ยว',
            'double': 'ห้องคู่',
            'family': 'ห้องครอบครัว',
            'executive': 'ห้องผู้บริหาร',
            'suite': 'ห้องชุด'
        };
        return map[type] || type;
    }

    async function showRoomDetail(roomId) {
        try {
            const result = await apiCall('rooms', 'get', {
                id: roomId
            });
            const room = result.room;
            window.currentRoomDetail = room;

            document.getElementById('roomModalTitle').textContent = `ห้อง ${room.building_code}${room.room_number}`;

            const statusColors = {
                'available': 'bg-emerald-100 text-success',
                'occupied': 'bg-blue-100 text-info',
                'maintenance': 'bg-amber-100 text-warning'
            };

            const statusBadgeColors = {
                'available': 'bg-emerald-100 text-emerald-800',
                'occupied': 'bg-blue-100 text-blue-800',
                'maintenance': 'bg-amber-100 text-amber-800'
            };

            document.getElementById('roomModalBody').innerHTML = `
            <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl ${statusColors[room.status] || 'bg-gray-100 text-gray-500'}">
                    <i class="ri-door-${room.status === 'available' ? 'open' : 'closed'}-line"></i>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-gray-900">${room.building_name} - ห้อง ${room.room_number}</h4>
                    <div class="text-sm text-gray-500">
                        ${getRoomType(room.room_type)} • ชั้น ${room.floor} • 
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${statusBadgeColors[room.status] || 'bg-gray-100 text-gray-600'}">
                            ${getStatusText(room.status)}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">ข้อมูลห้อง</h5>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="text-xs text-gray-400 mb-1">ความจุ</div>
                        <div class="font-medium text-gray-900">${room.capacity} คน</div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="text-xs text-gray-400 mb-1">ค่าห้อง/เดือน</div>
                        <div class="font-medium text-gray-900">${formatCurrency(room.monthly_rent || 0)}</div>
                    </div>
                </div>
            </div>
            
            ${room.current_occupants && room.current_occupants.length > 0 ? `
                <div class="mb-6">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">ผู้พักอาศัย</h5>
                    <div class="space-y-3">
                        ${room.current_occupants.map(occupant => {
                            // Parse relatives data
                            let relatives = [];
                            try {
                                if (occupant.accompanying_details) {
                                    relatives = JSON.parse(occupant.accompanying_details);
                                }
                            } catch(e) {}
                            
                            return `
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white">
                                        <i class="ri-user-line"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 truncate">
                                            ${escapeHtml(occupant.employee_name)}
                                            ${parseInt(occupant.accompanying_persons || 0) > 0 ? `<span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded ml-1">+${occupant.accompanying_persons} ญาติ</span>` : ''}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            รหัส: ${occupant.employee_id} • 
                                            Check-in: ${formatDate(occupant.check_in_date)}
                                        </div>
                                    </div>
                                </div>
                                ${relatives.length > 0 ? `
                                    <div class="mt-3 pl-4 border-l-2 border-purple-200">
                                        <div class="text-xs text-purple-600 font-medium mb-2">
                                            <i class="ri-user-add-line"></i> ผู้ติดตาม (${relatives.length})
                                        </div>
                                        <div class="space-y-2">
                                            ${relatives.map((r, idx) => `
                                                <div class="flex items-center justify-between bg-white p-2 rounded border border-purple-100">
                                                    <div class="flex items-center gap-2 text-sm">
                                                        <i class="ri-user-heart-line text-purple-500"></i>
                                                        <span class="font-medium text-gray-800">${escapeHtml(r.name || '')}</span>
                                                        ${r.age ? `<span class="text-gray-400">อายุ ${r.age} ปี</span>` : ''}
                                                        ${r.relation ? `<span class="bg-purple-50 text-purple-600 px-1.5 py-0.5 rounded text-xs">${escapeHtml(r.relation)}</span>` : ''}
                                                    </div>
                                                    <button onclick="removeRelative(${occupant.id}, ${idx}, '${escapeHtml(r.name || 'ผู้ติดตาม')}')" class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors" title="ลบผู้ติดตาม">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        `
        }).join('')
    } < /div> </div >
    ` : ''}
            
            <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100">
                ${(room.current_occupants || []).length < room.capacity && room.status !== 'maintenance' ? `
                    <button class="inline-flex items-center gap-2 px-4 py-2 bg-success hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors" onclick="openCheckInModal(${room.id})">
                        <i class="ri-login-box-line"></i>
                        Check-in
                    </button>
                ` : ''}
                ${room.current_occupants && room.current_occupants.length > 0 ? `
                    <button class="inline-flex items-center gap-2 px-4 py-2 bg-danger hover:bg-red-600 text-white rounded-lg font-medium transition-colors" onclick="handleCheckOut(window.currentRoomDetail.id, window.currentRoomDetail.current_occupants)">
                        <i class="ri-logout-box-line"></i>
                        Check-out
                    </button>
                ` : ''}
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-warning hover:bg-amber-500 text-white rounded-lg font-medium transition-colors" onclick="openEditRoomModal()">
                    <i class="ri-edit-line"></i>
                    แก้ไข
                </button>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('roomModal')">ปิด</button>
            </div>
        `;

    openModal('roomModal');
    }
    catch (error) {
        console.error('Room Detail Error:', error);
        showToast('ไม่สามารถโหลดข้อมูลห้องได้: ' + (error.message || error), 'error');
    }
    }

    function getStatusText(status) {
        const map = {
            'available': 'ว่าง',
            'occupied': 'มีผู้พัก',
            'maintenance': 'ซ่อมบำรุง',
            'reserved': 'จอง'
        };
        return map[status] || status;
    }

    function openCheckInModal(roomId) {
        document.getElementById('checkInRoomId').value = roomId;
        document.getElementById('checkInForm').reset();
        document.querySelector('input[name="check_in_date"]').value = new Date().toISOString().split('T')[0];
        closeModal('roomModal');
        openModal('checkInModal');
    }

    function switchOccupantType(type) {
        document.getElementById('occupantType').value = type;
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');

        if (type === 'employee') {
            document.getElementById('employeeForm').classList.remove('hidden');
            document.getElementById('temporaryForm').classList.add('hidden');
        } else {
            document.getElementById('employeeForm').classList.add('hidden');
            document.getElementById('temporaryForm').classList.remove('hidden');
        }
    }

    let searchTimeout;
    async function searchEmployee(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('employeeResults');

        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const result = await apiCall('rooms', 'searchEmployee', {
                    query
                });
                const employees = result.employees || [];

                if (employees.length === 0) {
                    resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-400">ไม่พบข้อมูล</div>';
                } else {
                    resultsDiv.innerHTML = employees.map(emp => `
                        <div class="flex items-center gap-3 p-3 cursor-pointer border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors" onclick='selectEmployee(${JSON.stringify(emp)})'>
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-red-500 flex items-center justify-center text-white text-sm font-medium">
                                ${emp.name.charAt(0)}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate">${emp.name}</div>
                                <div class="text-xs text-gray-500">${emp.code} • ${emp.department || '-'}</div>
                            </div>
                        </div>
                    `).join('');
                }
                resultsDiv.classList.remove('hidden');
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    function selectEmployee(emp) {
        document.getElementById('selectedEmployeeId').value = emp.code;
        document.getElementById('selectedEmployeeName').value = emp.name;
        document.getElementById('selectedEmployeeEmail').value = emp.email;
        document.getElementById('selectedDepartment').value = emp.department;

        document.getElementById('displayName').textContent = emp.name;
        document.getElementById('displayDetail').textContent = `${emp.code} • ${emp.department || '-'}`;

        document.getElementById('employeeSearch').value = '';
        document.getElementById('employeeResults').classList.add('hidden');
        document.getElementById('employeeSearch').classList.add('hidden');
        document.getElementById('selectedEmployeeBox').classList.remove('hidden');
    }

    function clearSelectedEmployee() {
        document.getElementById('selectedEmployeeId').value = '';
        document.getElementById('selectedEmployeeName').value = '';
        document.getElementById('selectedEmployeeEmail').value = '';
        document.getElementById('selectedDepartment').value = '';
        document.getElementById('employeeSearch').classList.remove('hidden');
        document.getElementById('selectedEmployeeBox').classList.add('hidden');
    }

    async function handleCheckIn(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        const type = document.getElementById('occupantType').value;

        if (type === 'employee') {
            if (!data.employee_id) {
                showToast('กรุณาเลือกพนักงาน', 'error');
                return;
            }
        } else {
            const tempName = document.getElementById('tempName').value;
            const tempPhone = document.getElementById('tempPhone').value;
            if (!tempName) {
                showToast('กรุณากรอกชื่อ-นามสกุล', 'error');
                return;
            }
            data.employee_name = tempName;
            data.employee_id = 'TEMP_' + Date.now();
            data.notes = (data.notes || '') + `\n[ข้อมูลผู้พักชั่วคราว]\nโทร: ${tempPhone}\nหน่วยงาน: ${document.getElementById('tempCompany').value}`;
        }

        try {
            await apiCall('rooms', 'checkIn', data, 'POST');
            showToast('Check-in สำเร็จ', 'success');
            closeModal('checkInModal');
            await loadRooms();
        } catch (error) {}
    }

    async function handleCheckOut(roomId, occupants) {
        if (occupants && occupants.length > 1) {
            const list = document.getElementById('checkOutOccupantsList');
            list.innerHTML = occupants.map(occ => `
                <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer border border-gray-200 hover:border-primary transition-colors">
                    <input type="checkbox" name="occupancy_ids[]" value="${occ.id}" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">${escapeHtml(occ.employee_name)}</div>
                        <div class="text-xs text-gray-500">${occ.employee_id} • Check-in: ${formatDate(occ.check_in_date)}</div>
                    </div>
                </label>
            `).join('');

            document.querySelector('#checkOutForm input[name="check_out_date"]').value = new Date().toISOString().split('T')[0];
            openModal('checkOutModal');
        } else {
            const occupancyId = occupants[0]?.id || roomId;
            const confirmed = await showConfirm('ยืนยันการ Check-out?', 'Check-out');
            if (!confirmed) return;

            try {
                await apiCall('rooms', 'checkOut', {
                    occupancy_id: occupancyId
                }, 'POST');
                showToast('Check-out สำเร็จ', 'success');
                closeModal('roomModal');
                await loadRooms();
            } catch (error) {}
        }
    }

    async function handleCheckOutSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const occupancyIds = formData.getAll('occupancy_ids[]');
        const checkOutDate = formData.get('check_out_date');

        if (occupancyIds.length === 0) {
            showToast('กรุณาเลือกผู้ที่ต้องการ Check-out อย่างน้อย 1 คน', 'error');
            return;
        }

        const confirmed = await showConfirm(`ยืนยันการ Check-out จำนวน ${occupancyIds.length} คน?`, 'ยืนยัน Check-out');
        if (!confirmed) return;

        try {
            await apiCall('rooms', 'checkOut', {
                occupancy_ids: occupancyIds,
                check_out_date: checkOutDate
            }, 'POST');
            showToast('Check-out สำเร็จ', 'success');
            closeModal('checkOutModal');
            closeModal('roomModal');
            await loadRooms();
        } catch (error) {}
    }

    async function removeRelative(occupancyId, relativeIndex, relativeName) {
        const confirmed = await showConfirm(`ยืนยันการลบ "${relativeName}" ออกจากผู้ติดตาม?`, 'ลบผู้ติดตาม');
        if (!confirmed) return;

        try {
            await apiCall('rooms', 'removeRelative', {
                occupancy_id: occupancyId,
                relative_index: relativeIndex
            }, 'POST');
            showToast('ลบผู้ติดตามสำเร็จ', 'success');
            // Refresh room detail
            if (window.currentRoomDetail) {
                showRoomDetail(window.currentRoomDetail.id);
            }
            await loadRooms();
        } catch (error) {
            console.error('Remove relative error:', error);
        }
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('th-TH', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function openAddRoomModal() {
        document.getElementById('addRoomForm').reset();
        const select = document.querySelector('#addRoomForm select[name="building_id"]');
        select.innerHTML = '<option value="">เลือกอาคาร</option>';
        buildings.forEach(b => {
            const option = document.createElement('option');
            option.value = b.id;
            option.textContent = `${b.code} - ${b.name}`;
            select.appendChild(option);
        });
        openModal('addRoomModal');
    }

    function updateRoomDefaults(type) {
        const capacityInput = document.getElementById('roomCapacity');
        let capacity = 1;
        switch (type) {
            case 'single':
                capacity = 1;
                break;
            case 'double':
                capacity = 2;
                break;
            case 'family':
                capacity = 4;
                break;
            case 'executive':
                capacity = 1;
                break;
            case 'suite':
                capacity = 2;
                break;
        }
        capacityInput.value = capacity;
    }

    async function handleAddRoom(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        try {
            await apiCall('rooms', 'create', data, 'POST');
            showToast('เพิ่มห้องพักสำเร็จ', 'success');
            closeModal('addRoomModal');
            await loadRooms();
        } catch (error) {}
    }

    function openEditRoomModal() {
        if (!window.currentRoomDetail) return;
        const room = window.currentRoomDetail;
        const form = document.getElementById('editRoomForm');

        form.querySelector('input[name="id"]').value = room.id;
        document.getElementById('editRoomBuildingName').value = room.building_name;
        form.querySelector('input[name="floor"]').value = room.floor;
        form.querySelector('input[name="room_number"]').value = room.room_number;
        form.querySelector('select[name="room_type"]').value = room.room_type;
        form.querySelector('input[name="capacity"]').value = room.capacity;
        form.querySelector('select[name="status"]').value = room.status;
        form.querySelector('input[name="monthly_rent"]').value = room.monthly_rent;
        form.querySelector('textarea[name="description"]').value = room.description || '';

        closeModal('roomModal');
        openModal('editRoomModal');
    }

    async function handleEditRoom(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        try {
            await apiCall('rooms', 'update', data, 'POST');
            showToast('แก้ไขข้อมูลห้องพักสำเร็จ', 'success');
            closeModal('editRoomModal');
            await loadRooms();
            showRoomDetail(data.id);
        } catch (error) {}
    }
</script>