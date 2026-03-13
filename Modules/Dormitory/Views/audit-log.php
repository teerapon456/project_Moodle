<?php

/**
 * Audit Log View - Migrated to Tailwind
 */

// Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative">
            <input type="text" id="searchInput" class="pl-9 pr-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-48" placeholder="ค้นหา..." onkeyup="if(event.key === 'Enter') loadLogs(1)">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
        <select id="filterAction" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary min-w-[150px]">
            <option value="">-- ทุก Action --</option>
        </select>
        <select id="filterEntityType" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary min-w-[140px]">
            <option value="">-- ทุกประเภท --</option>
        </select>
        <input type="date" id="startDate" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-36" title="วันที่เริ่มต้น">
        <input type="date" id="endDate" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-36" title="วันที่สิ้นสุด">
        <button class="p-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors" onclick="loadLogs(1)"><i class="ri-search-line"></i></button>
        <button class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors" onclick="resetFilters()" title="ล้างค่า"><i class="ri-refresh-line"></i></button>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-history-line text-primary"></i>
            Audit Log
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
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">IP Address</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">รายละเอียด</th>
                </tr>
            </thead>
            <tbody id="logsTableBody" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </td>
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
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[1000] opacity-0 invisible transition-all duration-200 p-5" id="detailModal">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900"><i class="ri-information-line text-primary"></i> รายละเอียด Log</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('detailModal')">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="detailContent"></div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
            <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('detailModal')">ปิด</button>
        </div>
    </div>
</div>

<style>
    .fixed.opacity-0.invisible[id$="Modal"].active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    let currentPage = 1;
    let logsData = [];

    const actionBadges = {
        'create': 'bg-emerald-100 text-emerald-800',
        'update': 'bg-blue-100 text-blue-800',
        'delete': 'bg-red-100 text-red-800',
        'other': 'bg-gray-100 text-gray-600'
    };

    document.addEventListener('DOMContentLoaded', () => loadLogs(1));

    async function loadLogs(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('logsTableBody');
        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div></td></tr>`;

        const params = {
            p: page,
            search: document.getElementById('searchInput').value,
            action_filter: document.getElementById('filterAction').value,
            entity_type: document.getElementById('filterEntityType').value,
            start_date: document.getElementById('startDate').value,
            end_date: document.getElementById('endDate').value
        };

        try {
            const result = await apiCall('base', 'listAuditLogs', params);
            logsData = result.logs || [];
            renderTable(logsData);
            renderPagination(result);
            populateFilters(result.actions, result.entity_types);
            document.getElementById('totalCount').textContent = `(${result.total} รายการ)`;
        } catch (error) {
            console.error(error);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-4">เกิดข้อผิดพลาด: ${error.message}</td></tr>`;
        }
    }

    function populateFilters(actions, entityTypes) {
        const actionSelect = document.getElementById('filterAction');
        const entitySelect = document.getElementById('filterEntityType');
        const currentAction = actionSelect.value;
        const currentEntity = entitySelect.value;

        if (actionSelect.options.length <= 1) {
            actions.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a;
                opt.textContent = getActionLabel(a);
                actionSelect.appendChild(opt);
            });
        }

        if (entitySelect.options.length <= 1) {
            entityTypes.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e;
                opt.textContent = getEntityLabel(e);
                entitySelect.appendChild(opt);
            });
        }

        actionSelect.value = currentAction;
        entitySelect.value = currentEntity;
    }

    function getActionBadgeClass(action) {
        if (action.includes('create') || action.includes('check_in')) return actionBadges.create;
        if (action.includes('update') || action.includes('save') || action.includes('record')) return actionBadges.update;
        if (action.includes('delete') || action.includes('cancel') || action.includes('check_out')) return actionBadges.delete;
        return actionBadges.other;
    }

    function renderTable(data) {
        const tbody = document.getElementById('logsTableBody');

        if (!data || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                        <i class="ri-history-line text-3xl mb-2 block"></i>
                        <p>ไม่พบข้อมูล Audit Log</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = data.map((log, index) => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-sm text-gray-600">${formatDateTime(log.created_at)}</td>
                <td class="px-4 py-3 font-medium text-gray-900">${escapeHtml(log.display_name || log.user_name || 'System')}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${getActionBadgeClass(log.action)}">${getActionLabel(log.action)}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">${getEntityLabel(log.entity_type)}</span>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-gray-500">${log.entity_id || '-'}</td>
                <td class="px-4 py-3 text-xs text-gray-500">${log.ip_address || '-'}</td>
                <td class="px-4 py-3">
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" onclick="viewDetail(${index})" title="ดูรายละเอียด">
                        <i class="ri-eye-line"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        const controls = document.getElementById('paginationControls');
        const info = document.getElementById('paginationInfo');
        const total = parseInt(data.total);
        const page = parseInt(data.page);
        const pages = parseInt(data.total_pages);

        if (total === 0) {
            controls.innerHTML = '';
            info.innerHTML = '';
            return;
        }

        const start = (page - 1) * 10 + 1;
        const end = Math.min(page * 10, total);
        info.innerHTML = `แสดง ${start}-${end} จาก ${total}`;

        controls.innerHTML = `
            <div class="flex items-center gap-2 bg-white rounded-full px-2 py-1 shadow border border-gray-100">
                <button class="w-9 h-9 flex items-center justify-center rounded-lg ${page === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}" ${page > 1 ? `onclick="loadLogs(${page - 1})"` : 'disabled'}>
                    <i class="ri-arrow-left-s-line text-lg"></i>
                </button>
                <span class="text-sm text-gray-500 px-2">หน้า ${page}/${pages}</span>
                <button class="w-9 h-9 flex items-center justify-center rounded-lg ${page === pages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}" ${page < pages ? `onclick="loadLogs(${page + 1})"` : 'disabled'}>
                    <i class="ri-arrow-right-s-line text-lg"></i>
                </button>
            </div>`;
    }

    function viewDetail(index) {
        const log = logsData[index];
        if (!log) return;

        let oldValues = null,
            newValues = null;
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
            <div class="grid grid-cols-2 gap-3 p-4 bg-gray-50 rounded-lg">
                <div><span class="text-xs text-gray-400 block">เวลา</span><span class="font-medium text-gray-900">${formatDateTime(log.created_at)}</span></div>
                <div><span class="text-xs text-gray-400 block">ผู้ใช้</span><span class="font-medium text-gray-900">${escapeHtml(log.display_name || log.user_name || 'System')}</span></div>
                <div><span class="text-xs text-gray-400 block">Action</span><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${getActionBadgeClass(log.action)}">${getActionLabel(log.action)}</span></div>
                <div><span class="text-xs text-gray-400 block">ประเภท / ID</span><span class="font-medium text-gray-900">${getEntityLabel(log.entity_type)} #${log.entity_id || '-'}</span></div>
                <div><span class="text-xs text-gray-400 block">IP Address</span><span class="font-medium text-gray-900">${log.ip_address || '-'}</span></div>
                <div><span class="text-xs text-gray-400 block">User ID</span><span class="font-medium text-gray-900">${log.user_id || '-'}</span></div>
            </div>`;

        if (oldValues || newValues) {
            html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">`;
            if (oldValues) {
                html += `<div class="p-4 bg-gray-50 rounded-lg border-l-4 border-red-400">
                    <h5 class="text-xs font-medium text-gray-500 mb-2 flex items-center gap-1"><i class="ri-arrow-left-line"></i> ค่าเดิม</h5>
                    <pre class="text-xs text-gray-700 whitespace-pre-wrap break-all">${JSON.stringify(oldValues, null, 2)}</pre>
                </div>`;
            }
            if (newValues) {
                html += `<div class="p-4 bg-gray-50 rounded-lg border-l-4 border-emerald-400">
                    <h5 class="text-xs font-medium text-gray-500 mb-2 flex items-center gap-1"><i class="ri-arrow-right-line"></i> ค่าใหม่</h5>
                    <pre class="text-xs text-gray-700 whitespace-pre-wrap break-all">${JSON.stringify(newValues, null, 2)}</pre>
                </div>`;
            }
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
            'create_building': 'สร้างอาคาร',
            'update_building': 'แก้ไขอาคาร',
            'delete_building': 'ลบอาคาร',
            'create_room': 'สร้างห้อง',
            'update_room': 'แก้ไขห้อง',
            'delete_room': 'ลบห้อง',
            'check_in': 'เช็คอิน',
            'check_out': 'เช็คเอาท์',
            'create_maintenance': 'แจ้งซ่อม',
            'update_maintenance_status': 'อัพเดทสถานะซ่อม',
            'assign_maintenance': 'มอบหมายงานซ่อม',
            'save_meter': 'บันทึกมิเตอร์',
            'generate_invoices': 'สร้างบิล',
            'record_payment': 'บันทึกชำระเงิน',
            'cancel_invoice': 'ยกเลิกบิล',
            'update_rates': 'อัพเดทอัตราค่าสาธารณูปโภค',
            'create_request': 'สร้างคำขอเข้าพัก',
            'approve_request': 'อนุมัติคำขอ',
            'reject_request': 'ปฏิเสธคำขอ',
            'cancel_request': 'ยกเลิกคำขอ'
        };
        return map[action] || action;
    }

    function getEntityLabel(type) {
        const map = {
            'building': 'อาคาร',
            'room': 'ห้อง',
            'occupancy': 'การเข้าพัก',
            'maintenance': 'แจ้งซ่อม',
            'meter_reading': 'มิเตอร์',
            'invoice': 'บิล',
            'payment': 'ชำระเงิน',
            'rates': 'อัตราค่าบริการ'
        };
        return map[type] || type || '-';
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleString('th-TH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }
</script>