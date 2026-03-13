<?php

/**
 * Car Booking - Audit Log View
 * Migrated to Tailwind CSS
 */

// Manager only
if (!checkManagerPermission($canView, $canManage, 'ระบบจองรถ')) return;
?>

<!-- Filters -->
<div class="flex flex-wrap items-center gap-3 mb-6">
    <div class="relative">
        <input type="text" id="searchInput" class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary w-48" placeholder="ค้นหา..." onkeyup="if(event.key === 'Enter') loadLogs(1)">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
    </div>
    <select id="filterAction" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">-- ทุก Action --</option>
    </select>
    <select id="filterEntityType" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">-- ทุกประเภท --</option>
    </select>
    <input type="date" id="startDate" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" title="วันที่เริ่มต้น">
    <input type="date" id="endDate" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" title="วันที่สิ้นสุด">
    <button class="p-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors" onclick="loadLogs(1)"><i class="ri-search-line"></i></button>
    <button class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors" onclick="resetFilters()" title="ล้างค่า"><i class="ri-refresh-line"></i></button>
</div>

<!-- Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-history-line text-primary"></i> Audit Log
        </h3>
        <span id="totalCount" class="text-sm text-gray-500"></span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">เวลา</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ใช้</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">IP</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">ดู</th>
                </tr>
            </thead>
            <tbody id="logsTableBody" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400"><i class="ri-loader-4-line animate-spin text-2xl"></i></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<div class="flex items-center justify-between mt-4">
    <div id="paginationInfo" class="text-sm text-gray-500"></div>
    <div id="paginationControls"></div>
</div>

<!-- Detail Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[1000] p-5 opacity-0 invisible transition-all" id="detailModal">
    <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 font-semibold text-gray-900"><i class="ri-information-line text-primary"></i> รายละเอียด Log</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeModal('detailModal')">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto" id="detailContent"></div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('detailModal')">ปิด</button>
        </div>
    </div>
</div>

<style>
    #detailModal.active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    let currentPage = 1;
    let logsData = [];

    document.addEventListener('DOMContentLoaded', () => loadLogs(1));

    async function loadLogs(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-12 text-center text-gray-400"><i class="ri-loader-4-line animate-spin text-2xl"></i></td></tr>`;

        const params = new URLSearchParams({
            p: page,
            search: document.getElementById('searchInput').value,
            action_filter: document.getElementById('filterAction').value,
            entity_type: document.getElementById('filterEntityType').value,
            start_date: document.getElementById('startDate').value,
            end_date: document.getElementById('endDate').value
        });

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=listAuditLogs&${params}`);
            const result = await response.json();

            if (result.success) {
                logsData = result.logs || [];
                renderTable(logsData);
                renderPagination(result);
                populateFilters(result.actions, result.entity_types);
                document.getElementById('totalCount').textContent = `(${result.total} รายการ)`;
            } else throw new Error(result.message || 'ไม่สามารถโหลดข้อมูลได้');
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-red-500">${error.message}</td></tr>`;
        }
    }

    function populateFilters(actions, entityTypes) {
        const actionSelect = document.getElementById('filterAction');
        const entitySelect = document.getElementById('filterEntityType');
        if (actionSelect.options.length <= 1) {
            (actions || []).forEach(a => {
                const opt = new Option(getActionLabel(a), a);
                actionSelect.appendChild(opt);
            });
        }
        if (entitySelect.options.length <= 1) {
            (entityTypes || []).forEach(e => {
                const opt = new Option(getEntityLabel(e), e);
                entitySelect.appendChild(opt);
            });
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('logsTableBody');
        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-12 text-center text-gray-400"><i class="ri-history-line text-3xl block mb-2"></i>ไม่พบข้อมูล Audit Log</td></tr>`;
            return;
        }

        tbody.innerHTML = data.map((log, idx) => `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-600">${formatDateTime(log.created_at)}</td>
                <td class="px-4 py-3 font-medium text-gray-900">${escapeHtml(log.display_name || log.user_name || 'System')}</td>
                <td class="px-4 py-3"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${getActionClass(log.action)}">${getActionLabel(log.action)}</span></td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">${getEntityLabel(log.entity_type)}</span></td>
                <td class="px-4 py-3"><code class="text-xs text-gray-500">${log.entity_id || '-'}</code></td>
                <td class="px-4 py-3 text-xs text-gray-400">${log.ip_address || '-'}</td>
                <td class="px-4 py-3"><button class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded transition-colors" onclick="viewDetail(${idx})"><i class="ri-eye-line"></i></button></td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        const controls = document.getElementById('paginationControls');
        const info = document.getElementById('paginationInfo');
        const total = parseInt(data.total),
            page = parseInt(data.page),
            pages = parseInt(data.total_pages);
        if (total === 0) {
            controls.innerHTML = '';
            info.innerHTML = '';
            return;
        }

        const start = (page - 1) * 20 + 1,
            end = Math.min(page * 20, total);
        info.innerHTML = `แสดง ${start}-${end} จาก ${total}`;
        controls.innerHTML = `
            <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-lg p-1 shadow-sm">
                <button class="p-2 ${page === 1 ? 'text-gray-300' : 'text-gray-600 hover:bg-gray-100'} rounded" ${page > 1 ? `onclick="loadLogs(${page - 1})"` : 'disabled'}><i class="ri-arrow-left-s-line"></i></button>
                <span class="px-3 text-sm text-gray-600">หน้า ${page}/${pages}</span>
                <button class="p-2 ${page === pages ? 'text-gray-300' : 'text-gray-600 hover:bg-gray-100'} rounded" ${page < pages ? `onclick="loadLogs(${page + 1})"` : 'disabled'}><i class="ri-arrow-right-s-line"></i></button>
            </div>
        `;
    }

    function viewDetail(idx) {
        const log = logsData[idx];
        if (!log) return;
        let oldValues, newValues;
        try {
            oldValues = log.old_values ? JSON.parse(log.old_values) : null;
        } catch (e) {
            oldValues = log.old_values;
        }
        try {
            newValues = log.new_values ? JSON.parse(log.new_values) : null;
        } catch (e) {
            newValues = log.new_values;
        }

        let html = `
            <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg mb-4 text-sm">
                <div><span class="text-gray-500 block">เวลา</span><span class="font-medium">${formatDateTime(log.created_at)}</span></div>
                <div><span class="text-gray-500 block">ผู้ใช้</span><span class="font-medium">${escapeHtml(log.display_name || log.user_name || 'System')}</span></div>
                <div><span class="text-gray-500 block">Action</span><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${getActionClass(log.action)}">${getActionLabel(log.action)}</span></div>
                <div><span class="text-gray-500 block">ประเภท / ID</span><span class="font-medium">${getEntityLabel(log.entity_type)} #${log.entity_id || '-'}</span></div>
                <div><span class="text-gray-500 block">IP Address</span><span class="font-medium">${log.ip_address || '-'}</span></div>
                <div><span class="text-gray-500 block">User ID</span><span class="font-medium">${log.user_id || '-'}</span></div>
            </div>
        `;

        if (oldValues || newValues) {
            html += `<div class="grid md:grid-cols-2 gap-4">`;
            if (oldValues) html += `<div class="bg-gray-50 border-l-4 border-red-400 p-3 rounded-r-lg"><h5 class="text-xs text-gray-500 mb-2"><i class="ri-arrow-left-line"></i> ค่าเดิม</h5><pre class="text-xs text-gray-700 whitespace-pre-wrap break-all">${JSON.stringify(oldValues, null, 2)}</pre></div>`;
            if (newValues) html += `<div class="bg-gray-50 border-l-4 border-emerald-400 p-3 rounded-r-lg"><h5 class="text-xs text-gray-500 mb-2"><i class="ri-arrow-right-line"></i> ค่าใหม่</h5><pre class="text-xs text-gray-700 whitespace-pre-wrap break-all">${JSON.stringify(newValues, null, 2)}</pre></div>`;
            html += `</div>`;
        }
        document.getElementById('detailContent').innerHTML = html;
        openModal('detailModal');
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterAction').value = '';
        document.getElementById('filterEntityType').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        loadLogs(1);
    }

    function getActionLabel(action) {
        const map = {
            'create_booking': 'สร้างคำขอ',
            'update_booking': 'แก้ไขคำขอ',
            'cancel_booking': 'ยกเลิกคำขอ',
            'supervisor_approve': 'หัวหน้าอนุมัติ',
            'supervisor_approve_token': 'หัวหน้าอนุมัติ (อีเมล)',
            'supervisor_reject': 'หัวหน้าไม่อนุมัติ',
            'supervisor_reject_token': 'หัวหน้าไม่อนุมัติ (อีเมล)',
            'manager_approve': 'IPCD อนุมัติ',
            'manager_approve_assign': 'อนุมัติและมอบหมาย',
            'manager_reject': 'IPCD ไม่อนุมัติ',
            'reject_booking': 'ปฏิเสธคำขอ',
            'revoke_booking': 'เพิกถอนคำขอ',
            'assign_car': 'มอบหมายรถ',
            'report_return': 'แจ้งคืนรถ',
            'confirm_return': 'ยืนยันคืนรถ',
            'complete_booking': 'เสร็จสิ้น',
            'create_car': 'เพิ่มรถ',
            'update_car': 'แก้ไขรถ',
            'delete_car': 'ลบรถ',
            'create_fleet_card': 'เพิ่มบัตรน้ำมัน',
            'update_fleet_card': 'แก้ไขบัตรน้ำมัน',
            'delete_fleet_card': 'ลบบัตรน้ำมัน',
            'update_default_supervisor': 'เปลี่ยนหัวหน้าเริ่มต้น',
            'update_settings': 'แก้ไขตั้งค่า',
            'login': 'เข้าสู่ระบบ',
            'logout': 'ออกจากระบบ'
        };
        return map[action] || action;
    }

    function getActionClass(action) {
        if (action.includes('create') || action.includes('login')) return 'bg-emerald-100 text-emerald-700';
        if (action.includes('update') || action.includes('assign') || action.includes('complete')) return 'bg-blue-100 text-blue-700';
        if (action.includes('delete') || action.includes('cancel') || action.includes('reject') || action.includes('logout')) return 'bg-red-100 text-red-700';
        if (action.includes('approve')) return 'bg-amber-100 text-amber-700';
        return 'bg-gray-100 text-gray-600';
    }

    function getEntityLabel(type) {
        const map = {
            'booking': 'คำขอจองรถ',
            'car': 'รถ',
            'fleet_card': 'บัตรน้ำมัน',
            'settings': 'ตั้งค่า',
            'user': 'ผู้ใช้',
            'approval': 'การอนุมัติ'
        };
        return map[type] || type || '-';
    }

    function escapeHtml(text) {
        return text ? String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;") : '';
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }
</script>