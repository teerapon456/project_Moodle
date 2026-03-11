<?php
// history.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>
<!-- History View - Migrated to Tailwind -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-history-line text-primary"></i> ประวัติการเข้าพัก
        </h3>
        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <input type="text" id="searchInput" class="pl-9 pr-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-48" placeholder="ค้นหา..." onkeyup="if(event.key === 'Enter') loadHistory(1)">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            <input type="date" id="startDate" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-36">
            <input type="date" id="endDate" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary w-36">
            <button class="p-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors" onclick="loadHistory(1)"><i class="ri-search-line"></i></button>
            <button class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors" onclick="resetFilters()" title="ล้างค่า"><i class="ri-refresh-line"></i></button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ห้องพัก</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้พักอาศัย</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หน่วยงาน</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่เข้าพัก</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่ออก</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                </tr>
            </thead>
            <tbody id="historyTableBody" class="divide-y divide-gray-100">
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
<div class="flex items-center justify-end mt-4" id="paginationControls"></div>

<script>
    let currentPage = 1;

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

    async function loadHistory(page = 1) {
        currentPage = page;
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const tbody = document.getElementById('historyTableBody');

        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center"><div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div></td></tr>`;

        try {
            const result = await apiCall('rooms', 'history', {
                p: page,
                search,
                start_date: startDate,
                end_date: endDate
            });
            renderTable(result.history);
            renderPagination(result);
        } catch (error) {
            console.error(error);
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-red-500 py-4">เกิดข้อผิดพลาด: ${error.message}</td></tr>`;
        }
    }

    function renderTable(data) {
        const tbody = document.getElementById('historyTableBody');

        if (!data || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                        <i class="ri-history-line text-3xl mb-2 block"></i>
                        <p>ไม่พบข้อมูลประวัติการเข้าพัก</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = data.map((item, index) => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-gray-500">${((currentPage - 1) * 20) + index + 1}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center text-primary">
                            <i class="ri-building-line"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">${item.building_code}${item.room_number}</div>
                            <div class="text-xs text-gray-500">${getRoomType(item.room_type)} — ชั้น ${item.floor}</div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg ${item.employee_id.startsWith('TEMP_') ? 'bg-amber-100 text-amber-700' : 'bg-primary text-white'} flex items-center justify-center font-semibold">
                            ${escapeHtml(item.employee_name || '').charAt(0) || '-'}
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">${escapeHtml(item.employee_name)}</div>
                            <div class="text-xs text-gray-500">${escapeHtml(item.employee_id)}</div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">${escapeHtml(item.department || '-')}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1.5 text-success">
                        <i class="ri-calendar-event-line"></i>
                        ${formatDate(item.check_in_date)}
                    </div>
                </td>
                <td class="px-4 py-3">
                    ${item.check_out_date && item.status === 'checked_out'
                        ? `<div class="flex items-center gap-1.5 text-danger"><i class="ri-logout-box-r-line"></i>${formatDate(item.check_out_date)}</div>` 
                        : (item.status === 'active' ? '<span class="text-success font-medium">ยังพักอยู่</span>' : '<span class="text-gray-400">-</span>')}
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${
                        item.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 
                        (item.status === 'relative_added' ? 'bg-purple-100 text-purple-800' : 
                        (item.status === 'relative_removed' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-600'))
                    }">
                        ${getStatusText(item.status)}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        const controls = document.getElementById('paginationControls');
        const total = parseInt(data.total);
        const page = parseInt(data.page);
        const pages = parseInt(data.total_pages);

        if (total === 0) {
            controls.innerHTML = '';
            return;
        }

        controls.innerHTML = `
            <div class="flex items-center gap-2 bg-white rounded-full px-2 py-1 shadow border border-gray-100">
                <button class="w-9 h-9 flex items-center justify-center rounded-lg ${page === 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}" ${page > 1 ? `onclick="loadHistory(${page - 1})"` : 'disabled'}>
                    <i class="ri-arrow-left-s-line text-lg"></i>
                </button>
                <span class="text-sm text-gray-500 px-2">หน้า ${page}/${pages}</span>
                <button class="w-9 h-9 flex items-center justify-center rounded-lg ${page === pages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}" ${page < pages ? `onclick="loadHistory(${page + 1})"` : 'disabled'}>
                    <i class="ri-arrow-right-s-line text-lg"></i>
                </button>
            </div>`;
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        loadHistory(1);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    function getStatusText(status) {
        if (status === 'active') return 'พักอยู่';
        if (status === 'relative_added') return 'เพิ่มญาติ/ผู้ติดตาม';
        if (status === 'relative_removed') return 'นำญาติออก';
        return 'ออกแล้ว';
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    document.addEventListener('DOMContentLoaded', () => loadHistory());
</script>