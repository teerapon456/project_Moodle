<?php
// meter-reading.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>
<!-- Meter Reading View - Migrated to Tailwind -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap gap-3">
        <select class="min-w-[150px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterBuilding">
            <option value="">ทุกอาคาร</option>
        </select>
        <input type="month" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterMonth">
    </div>
    <button class="inline-flex items-center gap-2 px-4 py-2.5 bg-success hover:bg-emerald-600 text-white rounded-lg font-medium transition-all shadow-sm" onclick="generateInvoices()">
        <i class="ri-file-text-line"></i>
        สร้างบิลประจำเดือน
    </button>
</div>

<!-- Progress -->
<div class="bg-white border border-gray-200 rounded-xl p-5 mb-5 shadow-sm">
    <div class="flex justify-between mb-2 text-sm text-gray-500">
        <span>ความคืบหน้า: <strong id="progressText" class="text-gray-900">0/0</strong> ห้อง</span>
        <span id="progressPercent" class="font-medium text-primary">0%</span>
    </div>
    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-primary to-red-500 rounded-full transition-all duration-300" id="progressFill" style="width: 0%"></div>
    </div>
</div>

<!-- Meter Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-dashboard-3-line text-primary"></i>
            บันทึกมิเตอร์ประจำเดือน
        </h3>
        <button class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium transition-colors" onclick="saveAllReadings()">
            <i class="ri-save-line"></i>
            บันทึกทั้งหมด
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase" rowspan="2">ห้อง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase" rowspan="2">ผู้พักอาศัย</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-amber-600 uppercase border-b border-gray-200" colspan="3">มิเตอร์ไฟฟ้า</th>
                    <th class="px-4 py-2 text-center text-xs font-medium text-blue-600 uppercase border-b border-gray-200" colspan="3">มิเตอร์น้ำ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase" rowspan="2">สถานะ</th>
                </tr>
                <tr class="bg-gray-50">
                    <th class="px-3 py-2 text-xs text-gray-400 font-normal">ก่อนหน้า</th>
                    <th class="px-3 py-2 text-xs text-gray-400 font-normal">ปัจจุบัน</th>
                    <th class="px-3 py-2 text-xs text-gray-400 font-normal">ใช้ไป</th>
                    <th class="px-3 py-2 text-xs text-gray-400 font-normal">ก่อนหน้า</th>
                    <th class="px-3 py-2 text-xs text-gray-400 font-normal">ปัจจุบัน</th>
                    <th class="px-3 py-2 text-xs text-gray-400 font-normal">ใช้ไป</th>
                </tr>
            </thead>
            <tbody id="meterTable" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    let readings = [];
    let currentMonth = '';

    document.addEventListener('DOMContentLoaded', async () => {
        const monthInput = document.getElementById('filterMonth');
        currentMonth = new Date().toISOString().slice(0, 7);
        monthInput.value = currentMonth;

        await loadBuildings();
        await loadReadings();

        document.getElementById('filterBuilding').addEventListener('change', loadReadings);
        monthInput.addEventListener('change', () => {
            currentMonth = monthInput.value;
            loadReadings();
        });
    });

    async function loadBuildings() {
        try {
            const result = await apiCall('buildings', 'list');
            const select = document.getElementById('filterBuilding');
            result.buildings.forEach(b => {
                const option = document.createElement('option');
                option.value = b.id;
                option.textContent = `${b.code} - ${b.name}`;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load buildings:', error);
        }
    }

    async function loadReadings() {
        try {
            const buildingId = document.getElementById('filterBuilding').value;
            const params = {
                month: currentMonth
            };
            if (buildingId) params.building_id = buildingId;

            const result = await apiCall('billing', 'getMeterReadings', params);
            readings = result.readings || [];
            renderTable();
            updateProgress();
        } catch (error) {
            console.error('Failed to load readings:', error);
        }
    }

    function renderTable() {
        const tbody = document.getElementById('meterTable');

        if (readings.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                    <i class="ri-inbox-line text-3xl mb-2 block"></i>
                    <p>ไม่มีห้องที่ต้องบันทึกมิเตอร์</p>
                </td>
            </tr>`;
            return;
        }

        tbody.innerHTML = readings.map((r, idx) => {
            const elecUsage = (r.curr_electricity || 0) - (r.prev_electricity || 0);
            const waterUsage = (r.curr_water || 0) - (r.prev_water || 0);
            const isSaved = r.reading_id ? true : false;

            return `
            <tr class="hover:bg-gray-50 transition-colors" data-room-id="${r.room_id}">
                <td class="px-4 py-3 font-semibold text-gray-900">${r.building_code}${r.room_number}</td>
                <td class="px-4 py-3 text-gray-600">${escapeHtml(r.occupant_name) || '-'}</td>
                <td class="px-3 py-3 text-gray-500 text-sm">${formatNumber(r.prev_electricity || 0)}</td>
                <td class="px-3 py-3">
                    <input type="number" class="w-20 px-2 py-1.5 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary elec-input" 
                           value="${r.curr_electricity || ''}" 
                           data-prev="${r.prev_electricity || 0}"
                           onchange="updateUsage(this, 'elec', ${idx})">
                </td>
                <td class="px-3 py-3 font-semibold elec-usage ${elecUsage > 500 ? 'text-red-600' : 'text-gray-900'}">${elecUsage > 0 ? formatNumber(elecUsage) : '-'}</td>
                <td class="px-3 py-3 text-gray-500 text-sm">${formatNumber(r.prev_water || 0)}</td>
                <td class="px-3 py-3">
                    <input type="number" class="w-20 px-2 py-1.5 text-right border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary water-input" 
                           value="${r.curr_water || ''}"
                           data-prev="${r.prev_water || 0}"
                           onchange="updateUsage(this, 'water', ${idx})">
                </td>
                <td class="px-3 py-3 font-semibold water-usage ${waterUsage > 500 ? 'text-red-600' : 'text-gray-900'}">${waterUsage > 0 ? formatNumber(waterUsage) : '-'}</td>
                <td class="px-4 py-3">
                    ${isSaved 
                        ? '<span class="text-success flex items-center gap-1"><i class="ri-check-line"></i> บันทึกแล้ว</span>' 
                        : '<span class="text-warning flex items-center gap-1"><i class="ri-time-line"></i> รอบันทึก</span>'}
                </td>
            </tr>`;
        }).join('');
    }

    function updateUsage(input, type, idx) {
        const row = input.closest('tr');
        const prev = parseFloat(input.dataset.prev) || 0;
        const curr = parseFloat(input.value) || 0;
        const usage = curr - prev;

        const usageCell = row.querySelector(`.${type}-usage`);
        usageCell.textContent = usage > 0 ? formatNumber(usage) : '-';
        usageCell.classList.toggle('text-red-600', usage > 500);
        usageCell.classList.toggle('text-gray-900', usage <= 500);

        if (type === 'elec') {
            readings[idx].curr_electricity = curr;
        } else {
            readings[idx].curr_water = curr;
        }
    }

    function updateProgress() {
        const total = readings.length;
        const saved = readings.filter(r => r.reading_id).length;
        const percent = total > 0 ? Math.round((saved / total) * 100) : 0;

        document.getElementById('progressText').textContent = `${saved}/${total}`;
        document.getElementById('progressPercent').textContent = `${percent}%`;
        document.getElementById('progressFill').style.width = `${percent}%`;
    }

    async function saveAllReadings() {
        const dataToSave = readings.filter(r => r.curr_electricity || r.curr_water).map(r => ({
            room_id: r.room_id,
            month_cycle: currentMonth,
            prev_electricity: r.prev_electricity || 0,
            curr_electricity: r.curr_electricity || 0,
            prev_water: r.prev_water || 0,
            curr_water: r.curr_water || 0
        }));

        if (dataToSave.length === 0) {
            showToast('กรุณากรอกค่ามิเตอร์อย่างน้อย 1 ห้อง', 'error');
            return;
        }

        try {
            const result = await apiCall('billing', 'saveBulkMeterReadings', {
                readings: dataToSave
            }, 'POST');
            showToast(result.message, 'success');
            await loadReadings();
        } catch (error) {}
    }

    async function generateInvoices() {
        const confirmed = await showConfirm(`ยืนยันสร้างบิลประจำเดือน ${currentMonth}?`, 'สร้างบิล');
        if (!confirmed) return;

        try {
            const result = await apiCall('billing', 'generateInvoices', {
                month_cycle: currentMonth
            }, 'POST');
            showToast(result.message, 'success');
        } catch (error) {}
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('th-TH').format(num);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>