<?php
// buildings.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>
<!-- Buildings View - Migrated to Tailwind -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap gap-3"></div>
    <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-all shadow-sm hover:shadow-md" onclick="openAddModal()">
        <i class="ri-add-line"></i>
        เพิ่มอาคาร
    </button>
</div>

<div class="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-5" id="buildingsGrid">
    <div class="flex items-center justify-center col-span-full py-12">
        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[1000] opacity-0 invisible transition-all duration-200 p-5" id="buildingModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">เพิ่มอาคาร</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('buildingModal')">&times;</button>
        </div>
        <form id="buildingForm" onsubmit="handleSave(event)">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <input type="hidden" name="id" id="buildingId">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">ชื่ออาคาร *</label>
                    <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="name" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">รหัสอาคาร *</label>
                    <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="code" placeholder="เช่น A, B, C" required maxlength="10">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">จำนวนชั้น</label>
                    <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="total_floors" value="4" min="1">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">คำอธิบาย</label>
                    <textarea class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-y" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('buildingModal')">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<style>
    .fixed.opacity-0.invisible[id$="Modal"].active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    let buildings = [];

    document.addEventListener('DOMContentLoaded', loadBuildings);

    async function loadBuildings() {
        try {
            const result = await apiCall('buildings', 'list');
            buildings = result.buildings || [];
            renderBuildings();
        } catch (error) {
            console.error('Failed to load buildings:', error);
        }
    }

    function renderBuildings() {
        const grid = document.getElementById('buildingsGrid');

        if (buildings.length === 0) {
            grid.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
                <i class="ri-building-line text-4xl mb-3"></i>
                <p class="mb-4">ยังไม่มีอาคาร</p>
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="openAddModal()">
                    <i class="ri-add-line"></i> เพิ่มอาคาร
                </button>
            </div>`;
            return;
        }

        grid.innerHTML = buildings.map(b => `
        <div class="bg-white border border-gray-200 rounded-xl p-6 transition-all hover:shadow-lg hover:border-primary">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center text-3xl text-primary">
                    <i class="ri-building-2-line"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">${escapeHtml(b.name)}</h3>
                    <span class="text-sm text-gray-500">รหัส: ${b.code} • ${b.total_floors || 1} ชั้น</span>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3 mb-5">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-xl font-bold text-gray-900">${b.total_rooms || 0}</div>
                    <div class="text-xs text-gray-500">ห้องทั้งหมด</div>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-xl font-bold text-success">${b.available_rooms || 0}</div>
                    <div class="text-xs text-gray-500">ว่าง</div>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-xl font-bold text-info">${b.occupied_rooms || 0}</div>
                    <div class="text-xs text-gray-500">มีผู้พัก</div>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors" onclick="editBuilding(${b.id})">
                    <i class="ri-edit-line"></i> แก้ไข
                </button>
                <a href="?page=rooms&building_id=${b.id}" class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="ri-door-line"></i> ดูห้อง
                </a>
            </div>
        </div>
    `).join('');
    }

    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'เพิ่มอาคาร';
        document.getElementById('buildingForm').reset();
        document.getElementById('buildingId').value = '';
        openModal('buildingModal');
    }

    async function editBuilding(id) {
        try {
            const result = await apiCall('buildings', 'get', {
                id
            });
            const b = result.building;

            document.getElementById('modalTitle').textContent = 'แก้ไขอาคาร';
            document.getElementById('buildingId').value = b.id;
            document.querySelector('input[name="name"]').value = b.name;
            document.querySelector('input[name="code"]').value = b.code;
            document.querySelector('input[name="code"]').readOnly = true;
            document.querySelector('input[name="total_floors"]').value = b.total_floors || 1;
            document.querySelector('textarea[name="description"]').value = b.description || '';

            openModal('buildingModal');
        } catch (error) {
            showToast('ไม่สามารถโหลดข้อมูลได้', 'error');
        }
    }

    async function handleSave(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        const action = data.id ? 'update' : 'create';

        try {
            await apiCall('buildings', action, data, 'POST');
            showToast(data.id ? 'แก้ไขอาคารสำเร็จ' : 'เพิ่มอาคารสำเร็จ', 'success');
            closeModal('buildingModal');
            await loadBuildings();
        } catch (error) {}
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        document.querySelector('input[name="code"]').readOnly = false;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>