<?php
// settings.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>
<!-- Settings View - Migrated to Tailwind -->
<div class="flex flex-col gap-6 max-w-4xl">
    <!-- Utility Rates -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
            <i class="ri-flashlight-line text-xl text-warning"></i>
            <h3 class="text-lg font-semibold text-gray-900">อัตราค่าสาธารณูปโภค</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" id="ratesGrid">
                <div class="flex items-center justify-center py-8">
                    <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm" onclick="saveRates()">
                <i class="ri-save-line"></i>
                บันทึกอัตราค่าบริการ
            </button>
        </div>
    </div>

    <!-- Maintenance Categories -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <i class="ri-tools-line text-xl text-primary"></i>
                <h3 class="text-lg font-semibold text-gray-900">หมวดหมู่งานซ่อม</h3>
            </div>
            <button class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors" onclick="addCategory()">
                <i class="ri-add-line"></i>
                เพิ่มหมวดหมู่
            </button>
        </div>
        <div class="p-6 space-y-3" id="categoriesList">
            <div class="flex items-center justify-center py-8">
                <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
            </div>
        </div>
    </div>

    <!-- Room Types -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <i class="ri-door-open-line text-xl text-success"></i>
                <h3 class="text-lg font-semibold text-gray-900">ประเภทห้องพัก</h3>
            </div>
            <button class="inline-flex items-center gap-2 px-3 py-1.5 bg-success hover:bg-emerald-600 text-white rounded-lg text-sm font-medium transition-colors" onclick="addRoomType()">
                <i class="ri-add-line"></i>
                เพิ่มประเภท
            </button>
        </div>
        <div class="p-6" id="roomTypesList">
            <div class="flex items-center justify-center py-8">
                <div class="w-8 h-8 border-4 border-gray-200 border-t-success rounded-full animate-spin"></div>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
            <i class="ri-settings-3-line text-xl text-gray-500"></i>
            <h3 class="text-lg font-semibold text-gray-900">ตั้งค่าบิล</h3>
        </div>
        <div class="p-6 space-y-4 max-w-md">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">วันครบกำหนดชำระ (วันที่ของเดือน)</label>
                <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="dueDateDay" value="15" min="1" max="28">
                <small class="text-gray-500 mt-1 block">บิลจะครบกำหนดชำระภายในวันนี้ของแต่ละเดือน</small>
            </div>
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">คำนำหน้าเลขที่บิล</label>
                <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="invoicePrefix" value="INV-" maxlength="10">
            </div>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm" onclick="saveInvoiceSettings()">
                <i class="ri-save-line"></i>
                บันทึก
            </button>
        </div>
    </div>

    <!-- Booking Settings -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
            <i class="ri-user-add-line text-xl text-success"></i>
            <h3 class="text-lg font-semibold text-gray-900">ตั้งค่าการขอเข้าพัก</h3>
        </div>
        <div class="p-6 space-y-4 max-w-md">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">จำนวนญาติที่อนุญาตให้นำเข้าพักสูงสุด</label>
                <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="maxRelatives" value="" min="1" max="10">
                <small class="text-gray-500 mt-1 block">จำกัดจำนวนญาติที่พนักงานสามารถขอนำเข้าพักได้ในแต่ละครั้ง</small>
            </div>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm" onclick="saveBookingSettings()">
                <i class="ri-save-line"></i>
                บันทึก
            </button>
        </div>
    </div>

    <!-- Email Notifications -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
            <i class="ri-mail-send-line text-xl text-info"></i>
            <h3 class="text-lg font-semibold text-gray-900">ตั้งค่าการแจ้งเตือน</h3>
        </div>
        <div class="p-6 space-y-6 max-w-xl">
            <!-- Admin Email Section -->
            <div>
                <label class="flex items-center gap-2 mb-1 text-sm font-medium text-gray-700">
                    <i class="ri-admin-line text-primary"></i>
                    อีเมลผู้ดูแลระบบ
                </label>
                <small class="text-gray-500 block mb-3">ระบบจะส่งแจ้งเตือนไปยังอีเมลนี้เมื่อมีการแจ้งชำระเงิน หรือแจ้งซ่อมใหม่</small>

                <div class="flex flex-wrap gap-2 p-3 min-h-[50px] bg-gray-50 border border-gray-200 rounded-lg mb-3" id="adminEmailTags"></div>

                <div class="relative">
                    <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="adminEmailSearch"
                        placeholder="ค้นหาชื่อหรืออีเมลพนักงาน..." autocomplete="off"
                        oninput="searchAdminEmail(this.value)">
                    <div class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-[250px] overflow-y-auto z-50 hidden" id="adminEmailResults"></div>
                </div>
            </div>

            <!-- CC Email Section -->
            <div>
                <label class="flex items-center gap-2 mb-1 text-sm font-medium text-gray-700">
                    <i class="ri-mail-send-line text-info"></i>
                    อีเมล CC
                </label>
                <small class="text-gray-500 block mb-3">อีเมลที่จะถูก CC ในทุกการแจ้งเตือน (เพิ่มได้หลายอีเมล)</small>

                <div class="flex flex-wrap gap-2 p-3 min-h-[50px] bg-gray-50 border border-gray-200 rounded-lg mb-3" id="ccEmailTags"></div>

                <div class="relative">
                    <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="ccEmailSearch"
                        placeholder="ค้นหาชื่อหรืออีเมลพนักงาน..." autocomplete="off"
                        oninput="searchCcEmail(this.value)">
                    <div class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-[250px] overflow-y-auto z-50 hidden" id="ccEmailResults"></div>
                </div>
            </div>

            <div class="flex gap-3">
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm" onclick="saveNotificationSettings()">
                    <i class="ri-save-line"></i>
                    บันทึก
                </button>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="testNotificationEmail()">
                    <i class="ri-send-plane-line"></i>
                    ทดสอบส่งอีเมล
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Room Type Modal -->
<div class="modal-overlay fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5" id="roomTypeModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900" id="roomTypeModalTitle">เพิ่มประเภทห้องพัก</h3>
            <button class="text-gray-400 hover:text-gray-600 text-xl" onclick="closeRoomTypeModal()">&times;</button>
        </div>
        <form id="roomTypeForm" class="p-5 space-y-4">
            <input type="hidden" name="id" id="rtId">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">ชื่อประเภท *</label>
                <input type="text" name="name" id="rtName" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-success focus:border-success" required placeholder="เช่น ห้องเดี่ยว">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">รหัส (ภาษาอังกฤษ)</label>
                <input type="text" name="code" id="rtCode" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-success focus:border-success" placeholder="เช่น single">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">ความจุ (คน)</label>
                    <input type="number" name="capacity" id="rtCapacity" min="1" value="1" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-success focus:border-success">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">ค่าเช่า/เดือน</label>
                    <input type="number" name="monthly_rent" id="rtRent" min="0" value="0" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-success focus:border-success">
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">คำอธิบาย</label>
                <textarea name="description" id="rtDesc" rows="2" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-success focus:border-success resize-none" placeholder="รายละเอียดเพิ่มเติม"></textarea>
            </div>
            <div id="rtStatusField" class="hidden">
                <label class="block mb-1 text-sm font-medium text-gray-700">สถานะ</label>
                <select name="status" id="rtStatus" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-success focus:border-success">
                    <option value="active">ใช้งาน</option>
                    <option value="inactive">ไม่ใช้งาน</option>
                </select>
            </div>
        </form>
        <div class="flex justify-end gap-3 px-5 py-4 bg-gray-50 rounded-b-xl">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeRoomTypeModal()">ยกเลิก</button>
            <button class="px-4 py-2 bg-success hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors" onclick="saveRoomType()">บันทึก</button>
        </div>
    </div>
</div>

<script>
    let rates = [];
    let categories = [];
    let adminEmails = [];
    let ccEmails = [];
    let roomTypes = [];
    let searchTimeout;

    document.addEventListener('DOMContentLoaded', async () => {
        await loadRates();
        await loadCategories();
        await loadRoomTypes();
        await loadInvoiceSettings();
        setTimeout(() => loadNotificationSettings(), 100);
    });

    // Close search results on click outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            document.querySelectorAll('#adminEmailResults, #ccEmailResults').forEach(el => el.classList.add('hidden'));
        }
    });

    async function loadRates() {
        try {
            const result = await apiCall('billing', 'getRates');
            rates = result.rates || [];
            renderRates();
        } catch (error) {
            console.error('Failed to load rates:', error);
        }
    }

    function renderRates() {
        const grid = document.getElementById('ratesGrid');

        if (rates.length === 0) {
            grid.innerHTML = `
            <div class="p-5 bg-gray-50 border border-gray-200 rounded-lg">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-lg bg-amber-100 flex items-center justify-center text-xl text-amber-600"><i class="ri-flashlight-line"></i></div>
                    <span class="font-medium text-gray-900">ค่าไฟฟ้า</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="number" class="flex-1 px-3 py-2.5 text-right border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" id="electricRate" value="8" step="0.01">
                    <span class="text-gray-500 text-sm whitespace-nowrap">บาท/หน่วย</span>
                </div>
            </div>
            <div class="p-5 bg-gray-50 border border-gray-200 rounded-lg">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-lg bg-blue-100 flex items-center justify-center text-xl text-blue-600"><i class="ri-drop-line"></i></div>
                    <span class="font-medium text-gray-900">ค่าน้ำประปา</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="number" class="flex-1 px-3 py-2.5 text-right border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" id="waterRate" value="20" step="0.01">
                    <span class="text-gray-500 text-sm whitespace-nowrap">บาท/หน่วย</span>
                </div>
            </div>`;
            return;
        }

        grid.innerHTML = rates.map(r => `
        <div class="p-5 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-lg ${r.utility_type === 'electric' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600'} flex items-center justify-center text-xl">
                    <i class="ri-${r.utility_type === 'electric' ? 'flashlight-line' : 'drop-line'}"></i>
                </div>
                <span class="font-medium text-gray-900">${r.utility_type === 'electric' ? 'ค่าไฟฟ้า' : 'ค่าน้ำประปา'}</span>
            </div>
            <div class="flex items-center gap-2">
                <input type="number" class="flex-1 px-3 py-2.5 text-right border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" 
                       id="${r.utility_type}Rate" 
                       value="${r.rate_per_unit}" 
                       step="0.01"
                       data-id="${r.id}">
                <span class="text-gray-500 text-sm whitespace-nowrap">บาท/หน่วย</span>
            </div>
        </div>
    `).join('');
    }

    async function saveRates() {
        try {
            const electricRate = document.getElementById('electricRate')?.value;
            const waterRate = document.getElementById('waterRate')?.value;

            const updates = [];
            if (electricRate) {
                updates.push({
                    id: document.getElementById('electricRate')?.dataset.id ? parseInt(document.getElementById('electricRate').dataset.id) : null,
                    utility_type: 'electric',
                    rate_per_unit: parseFloat(electricRate)
                });
            }
            if (waterRate) {
                updates.push({
                    id: document.getElementById('waterRate')?.dataset.id ? parseInt(document.getElementById('waterRate').dataset.id) : null,
                    utility_type: 'water',
                    rate_per_unit: parseFloat(waterRate)
                });
            }

            for (const rate of updates) {
                await apiCall('billing', 'updateRates', rate, 'POST');
            }

            showToast('บันทึกอัตราค่าบริการสำเร็จ', 'success');
            await loadRates();
        } catch (error) {}
    }

    async function loadCategories() {
        try {
            const result = await apiCall('maintenance', 'getCategories');
            categories = result.categories || [];
            renderCategories();
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    function renderCategories() {
        const list = document.getElementById('categoriesList');

        if (categories.length === 0) {
            list.innerHTML = `<div class="text-center py-8 text-gray-400"><p>ยังไม่มีหมวดหมู่งานซ่อม</p></div>`;
            return;
        }

        list.innerHTML = categories.map(c => `
        <div class="flex items-center gap-3 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center text-primary">
                <i class="ri-${c.icon || 'tools-line'}"></i>
            </div>
            <div class="flex-1">
                <div class="font-medium text-gray-900">${escapeHtml(c.name)}</div>
                ${c.description ? `<div class="text-sm text-gray-500">${escapeHtml(c.description)}</div>` : ''}
            </div>
        </div>
    `).join('');
    }

    function addCategory() {
        showToast('ฟังก์ชันเพิ่มหมวดหมู่กำลังพัฒนา', 'info');
    }

    // ===================== ROOM TYPES =====================

    async function loadRoomTypes() {
        try {
            const result = await apiCall('settings', 'getRoomTypes');
            roomTypes = result.room_types || [];
            renderRoomTypes();
        } catch (error) {
            console.error('Failed to load room types:', error);
        }
    }

    function renderRoomTypes() {
        const list = document.getElementById('roomTypesList');

        if (roomTypes.length === 0) {
            list.innerHTML = `<div class="text-center py-8 text-gray-400"><p>ยังไม่มีประเภทห้องพัก</p></div>`;
            return;
        }

        list.innerHTML = `
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">
                            <th class="px-4 py-3">ชื่อ</th>
                            <th class="px-4 py-3">รหัส</th>
                            <th class="px-4 py-3 text-center">ความจุ</th>
                            <th class="px-4 py-3 text-right">ค่าเช่า/เดือน</th>
                            <th class="px-4 py-3 text-center">สถานะ</th>
                            <th class="px-4 py-3 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        ${roomTypes.map(rt => `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">${escapeHtml(rt.name)}</td>
                                <td class="px-4 py-3 text-gray-500 text-sm">${escapeHtml(rt.code || '-')}</td>
                                <td class="px-4 py-3 text-center">${rt.capacity || 1} คน</td>
                                <td class="px-4 py-3 text-right">${formatCurrency(rt.monthly_rent || 0)}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${rt.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'}">
                                        ${rt.status === 'active' ? 'ใช้งาน' : 'ไม่ใช้งาน'}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button onclick="editRoomType(${rt.id})" class="p-1.5 text-gray-500 hover:text-primary hover:bg-gray-100 rounded" title="แก้ไข">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button onclick="deleteRoomType(${rt.id}, '${escapeHtml(rt.name)}')" class="p-1.5 text-gray-500 hover:text-danger hover:bg-red-50 rounded" title="ลบ">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function addRoomType() {
        document.getElementById('roomTypeModalTitle').textContent = 'เพิ่มประเภทห้องพัก';
        document.getElementById('roomTypeForm').reset();
        document.getElementById('rtId').value = '';
        document.getElementById('rtStatusField').classList.add('hidden');
        document.getElementById('roomTypeModal').classList.add('active');
    }

    function editRoomType(id) {
        const rt = roomTypes.find(r => r.id == id);
        if (!rt) return;

        document.getElementById('roomTypeModalTitle').textContent = 'แก้ไขประเภทห้องพัก';
        document.getElementById('rtId').value = rt.id;
        document.getElementById('rtName').value = rt.name || '';
        document.getElementById('rtCode').value = rt.code || '';
        document.getElementById('rtCapacity').value = rt.capacity || 1;
        document.getElementById('rtRent').value = rt.monthly_rent || 0;
        document.getElementById('rtDesc').value = rt.description || '';
        document.getElementById('rtStatus').value = rt.status || 'active';
        document.getElementById('rtStatusField').classList.remove('hidden');
        document.getElementById('roomTypeModal').classList.add('active');
    }

    function closeRoomTypeModal() {
        document.getElementById('roomTypeModal').classList.remove('active');
    }

    async function saveRoomType() {
        const id = document.getElementById('rtId').value;
        const name = document.getElementById('rtName').value.trim();

        if (!name) {
            showToast('กรุณาระบุชื่อประเภทห้อง', 'error');
            return;
        }

        const data = {
            name: name,
            code: document.getElementById('rtCode').value.trim(),
            capacity: parseInt(document.getElementById('rtCapacity').value) || 1,
            monthly_rent: parseFloat(document.getElementById('rtRent').value) || 0,
            description: document.getElementById('rtDesc').value.trim()
        };

        try {
            if (id) {
                data.id = id;
                data.status = document.getElementById('rtStatus').value;
                await apiCall('settings', 'updateRoomType', data, 'POST');
                showToast('อัพเดทประเภทห้องสำเร็จ', 'success');
            } else {
                await apiCall('settings', 'createRoomType', data, 'POST');
                showToast('เพิ่มประเภทห้องสำเร็จ', 'success');
            }
            closeRoomTypeModal();
            await loadRoomTypes();
        } catch (error) {
            showToast(error.message || 'เกิดข้อผิดพลาด', 'error');
        }
    }

    async function deleteRoomType(id, name) {
        const confirmed = await showConfirm(`ต้องการลบประเภทห้อง "${name}" หรือไม่?`, 'ยืนยันการลบ');
        if (!confirmed) return;

        try {
            await apiCall('settings', 'deleteRoomType', {
                id: id
            }, 'POST');
            showToast('ลบประเภทห้องสำเร็จ', 'success');
            await loadRoomTypes();
        } catch (error) {
            showToast(error.message || 'เกิดข้อผิดพลาด', 'error');
        }
    }

    async function loadInvoiceSettings() {
        try {
            const result = await apiCall('settings', 'getSettings');
            const settings = result.settings || {};
            document.getElementById('dueDateDay').value = settings.due_date_day || '15';
            document.getElementById('invoicePrefix').value = settings.invoice_prefix || 'INV-';
            document.getElementById('maxRelatives').value = settings.max_relatives || '5';
        } catch (error) {
            console.error('Failed to load invoice settings:', error);
        }
    }

    async function saveInvoiceSettings() {
        const dueDateDay = document.getElementById('dueDateDay').value;
        const invoicePrefix = document.getElementById('invoicePrefix').value.trim();
        const day = parseInt(dueDateDay);

        if (isNaN(day) || day < 1 || day > 28) {
            showToast('วันครบกำหนดต้องอยู่ระหว่าง 1-28', 'error');
            return;
        }

        try {
            await apiCall('settings', 'saveSettings', {
                settings: {
                    due_date_day: dueDateDay,
                    invoice_prefix: invoicePrefix
                }
            }, 'POST');
            showToast('บันทึกการตั้งค่าบิลสำเร็จ', 'success');
        } catch (error) {}
    }

    async function saveBookingSettings() {
        const maxRelatives = document.getElementById('maxRelatives').value;
        const max = parseInt(maxRelatives);

        if (isNaN(max) || max < 1 || max > 10) {
            showToast('จำนวนญาติสูงสุดต้องอยู่ระหว่าง 1-10', 'error');
            return;
        }

        try {
            await apiCall('settings', 'saveSettings', {
                settings: {
                    max_relatives: maxRelatives
                }
            }, 'POST');
            showToast('บันทึกการตั้งค่าการขอเข้าพักสำเร็จ', 'success');
        } catch (error) {}
    }

    async function loadNotificationSettings() {
        try {
            const result = await apiCall('settings', 'getSettings');
            const settings = result.settings || {};

            const adminEmailStr = settings.admin_email || settings.admin_emails || '';
            adminEmails = adminEmailStr ? adminEmailStr.split(',').map(e => e.trim()).filter(e => e) : [];

            const ccEmailStr = settings.cc_email || settings.cc_emails || '';
            ccEmails = ccEmailStr ? ccEmailStr.split(',').map(e => e.trim()).filter(e => e) : [];

            renderEmailTags();
        } catch (error) {
            console.error('Failed to load notification settings:', error);
        }
    }

    function renderEmailTags() {
        document.getElementById('adminEmailTags').innerHTML = adminEmails.length === 0 ?
            '<span class="text-gray-400 text-sm">ยังไม่มีอีเมลผู้ดูแล</span>' :
            adminEmails.map((email, idx) => `<span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded-full text-sm text-gray-700"><i class="ri-mail-line text-primary"></i>${email}<button class="text-gray-400 hover:text-red-500" onclick="removeAdminEmail(${idx})">&times;</button></span>`).join('');

        document.getElementById('ccEmailTags').innerHTML = ccEmails.length === 0 ?
            '<span class="text-gray-400 text-sm">ยังไม่มีอีเมล CC</span>' :
            ccEmails.map((email, idx) => `<span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded-full text-sm text-gray-700"><i class="ri-mail-line text-info"></i>${email}<button class="text-gray-400 hover:text-red-500" onclick="removeCcEmail(${idx})">&times;</button></span>`).join('');
    }

    async function searchAdminEmail(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('adminEmailResults');

        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                // Fetch from current module's API
                const res = await fetch(`${API_BASE}?action=searchEmail&query=${encodeURIComponent(query)}`);
                const data = await res.json();

                const allUsers = data.success ? data.users : [];

                if (allUsers.length > 0) {
                    const filtered = allUsers.filter(emp => !adminEmails.includes(emp.email));
                    if (filtered.length > 0) {
                        resultsDiv.innerHTML = filtered.map(emp => `
                            <div class="flex items-center gap-3 p-3 cursor-pointer border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors" onclick='addAdminEmail("${emp.email}", "${emp.name || emp.email}")'>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-red-500 flex items-center justify-center text-white text-xs font-medium">${(emp.name || '?').charAt(0)}</div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 truncate">${emp.name || emp.email}</div>
                                    <div class="text-xs text-gray-500">${emp.email}</div>
                                </div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 uppercase">${emp.type || 'MS'}</span>
                            </div>
                        `).join('');
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-400">อีเมลทั้งหมดถูกเพิ่มแล้ว</div>';
                        resultsDiv.classList.remove('hidden');
                    }
                } else {
                    resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-400">ไม่พบข้อมูล</div>';
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    async function searchCcEmail(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('ccEmailResults');

        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                // Fetch from current module's API
                const res = await fetch(`${API_BASE}?action=searchEmail&query=${encodeURIComponent(query)}`);
                const data = await res.json();

                const allUsers = data.success ? data.users : [];

                if (allUsers.length > 0) {
                    const filtered = allUsers.filter(emp => !ccEmails.includes(emp.email));
                    if (filtered.length > 0) {
                        resultsDiv.innerHTML = filtered.map(emp => `
                            <div class="flex items-center gap-3 p-3 cursor-pointer border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors" onclick='addCcEmail("${emp.email}", "${emp.name || emp.email}")'>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary to-red-500 flex items-center justify-center text-white text-xs font-medium">${(emp.name || '?').charAt(0)}</div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 truncate">${emp.name || emp.email}</div>
                                    <div class="text-xs text-gray-500">${emp.email}</div>
                                </div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 uppercase">${emp.type || 'MS'}</span>
                            </div>
                        `).join('');
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-400">อีเมลทั้งหมดถูกเพิ่มแล้ว</div>';
                        resultsDiv.classList.remove('hidden');
                    }
                } else {
                    resultsDiv.innerHTML = '<div class="p-4 text-center text-gray-400">ไม่พบข้อมูล</div>';
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    function addAdminEmail(email, name) {
        if (adminEmails.includes(email)) {
            showToast('อีเมลนี้มีอยู่แล้ว', 'error');
            return;
        }
        adminEmails.push(email);
        document.getElementById('adminEmailSearch').value = '';
        document.getElementById('adminEmailResults').classList.add('hidden');
        renderEmailTags();
        showToast(`เพิ่ม ${name} แล้ว`, 'success');
    }

    function addCcEmail(email, name) {
        if (ccEmails.includes(email)) {
            showToast('อีเมลนี้มีอยู่แล้ว', 'error');
            return;
        }
        ccEmails.push(email);
        document.getElementById('ccEmailSearch').value = '';
        document.getElementById('ccEmailResults').classList.add('hidden');
        renderEmailTags();
        showToast(`เพิ่ม ${name} แล้ว`, 'success');
    }

    function removeAdminEmail(index) {
        adminEmails.splice(index, 1);
        renderEmailTags();
    }

    function removeCcEmail(index) {
        ccEmails.splice(index, 1);
        renderEmailTags();
    }

    async function saveNotificationSettings() {
        try {
            await apiCall('settings', 'saveSettings', {
                settings: {
                    admin_email: adminEmails.join(','),
                    cc_email: ccEmails.join(',')
                }
            }, 'POST');
            showToast('บันทึกอีเมลสำเร็จ', 'success');
        } catch (error) {}
    }

    async function testNotificationEmail() {
        const emails = adminEmails.length > 0 ? adminEmails : ccEmails;
        const testEmail = emails[0] || '';

        if (!testEmail) {
            showToast('กรุณาเพิ่มอีเมลก่อนทดสอบ', 'error');
            return;
        }

        try {
            await apiCall('settings', 'testEmail', {
                email: testEmail
            }, 'POST');
            showToast('ส่งอีเมลทดสอบสำเร็จ', 'success');
        } catch (error) {}
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>