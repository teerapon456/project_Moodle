<?php
// maintenance.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>
<!-- Maintenance View - Migrated to Tailwind -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap gap-3">
        <select class="min-w-[150px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterStatus">
            <option value="">ทุกสถานะ</option>
            <option value="open">รอดำเนินการ</option>
            <option value="assigned">มอบหมายแล้ว</option>
            <option value="in_progress">กำลังดำเนินการ</option>
            <option value="resolved">เสร็จสิ้น</option>
            <option value="closed">ปิดงาน</option>
        </select>
        <select class="min-w-[150px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterPriority">
            <option value="">ทุก Priority</option>
            <option value="critical">ด่วนมาก</option>
            <option value="high">ด่วน</option>
            <option value="medium">ปกติ</option>
            <option value="low">ไม่ด่วน</option>
        </select>
    </div>
    <a href="?page=maintenance-form" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-all shadow-sm hover:shadow-md">
        <i class="ri-add-line"></i>
        แจ้งซ่อมใหม่
    </a>
</div>

<!-- Stats -->
<div class="flex flex-wrap gap-4 mb-6">
    <div class="flex-1 min-w-[160px] flex items-center gap-4 p-5 bg-white rounded-lg border-l-4 border-danger shadow-sm">
        <i class="ri-error-warning-line text-2xl text-danger"></i>
        <div>
            <span class="text-2xl font-bold text-gray-900 block" id="statOpen">0</span>
            <span class="text-gray-500 text-sm">รอดำเนินการ</span>
        </div>
    </div>
    <div class="flex-1 min-w-[160px] flex items-center gap-4 p-5 bg-white rounded-lg border-l-4 border-info shadow-sm">
        <i class="ri-loader-4-line text-2xl text-info"></i>
        <div>
            <span class="text-2xl font-bold text-gray-900 block" id="statProgress">0</span>
            <span class="text-gray-500 text-sm">กำลังดำเนินการ</span>
        </div>
    </div>
    <div class="flex-1 min-w-[160px] flex items-center gap-4 p-5 bg-white rounded-lg border-l-4 border-success shadow-sm">
        <i class="ri-checkbox-circle-line text-2xl text-success"></i>
        <div>
            <span class="text-2xl font-bold text-gray-900 block" id="statResolved">0</span>
            <span class="text-gray-500 text-sm">เสร็จสิ้นเดือนนี้</span>
        </div>
    </div>
</div>

<!-- Request List -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เลขที่</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หัวข้อ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ห้อง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมวดหมู่</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่แจ้ง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody id="maintenanceList" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Detail Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="detailModal">
    <div class="bg-white rounded-xl w-full max-w-[700px] max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900" id="detailModalTitle">รายละเอียดแจ้งซ่อม</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('detailModal')">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="detailModalBody">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="statusModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">อัพเดทสถานะ</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <form id="statusForm" onsubmit="handleUpdateStatus(event)">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <input type="hidden" id="statusRequestId" name="id">
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">สถานะใหม่ *</label>
                    <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="status" required>
                        <option value="open">รอดำเนินการ</option>
                        <option value="assigned">มอบหมายแล้ว</option>
                        <option value="in_progress">กำลังดำเนินการ</option>
                        <option value="pending_parts">รออะไหล่</option>
                        <option value="resolved">เสร็จสิ้น</option>
                        <option value="closed">ปิดงาน</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">มอบหมายให้</label>
                    <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="assigned_to" placeholder="ชื่อผู้รับผิดชอบ">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">ความคิดเห็น</label>
                    <textarea class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-y" name="comment" rows="3"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('statusModal')">ยกเลิก</button>
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
    let requests = [];

    document.addEventListener('DOMContentLoaded', async () => {
        await loadRequests();
        await loadStats();

        document.getElementById('filterStatus').addEventListener('change', loadRequests);
        document.getElementById('filterPriority').addEventListener('change', loadRequests);
    });

    async function loadStats() {
        try {
            const result = await apiCall('maintenance', 'stats');
            const stats = result.stats;

            document.getElementById('statOpen').textContent =
                (stats.by_status?.open || 0) + (stats.by_status?.assigned || 0);
            document.getElementById('statProgress').textContent = stats.by_status?.in_progress || 0;
            document.getElementById('statResolved').textContent = stats.resolved_this_month || 0;
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    async function loadRequests() {
        try {
            const status = document.getElementById('filterStatus').value;
            const priority = document.getElementById('filterPriority').value;

            const params = {};
            if (status) params.status = status;
            if (priority) params.priority = priority;

            const result = await apiCall('maintenance', 'list', params);
            requests = result.requests;
            renderRequests();
        } catch (error) {
            console.error('Failed to load requests:', error);
        }
    }

    const priorityColors = {
        'critical': 'bg-red-100 text-red-800',
        'high': 'bg-amber-100 text-amber-800',
        'medium': 'bg-blue-100 text-blue-800',
        'low': 'bg-gray-100 text-gray-600'
    };

    const statusColors = {
        'open': 'bg-red-100 text-red-800',
        'assigned': 'bg-amber-100 text-amber-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'pending_parts': 'bg-amber-100 text-amber-800',
        'resolved': 'bg-emerald-100 text-emerald-800',
        'closed': 'bg-gray-100 text-gray-600',
        'cancelled': 'bg-gray-100 text-gray-600'
    };

    function renderRequests() {
        const tbody = document.getElementById('maintenanceList');

        if (requests.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                    <i class="ri-checkbox-circle-line text-3xl mb-2 block"></i>
                    <p>ไม่พบรายการแจ้งซ่อม</p>
                </td>
            </tr>`;
            return;
        }

        tbody.innerHTML = requests.map(req => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3"><span class="font-semibold text-primary">${req.ticket_number}</span></td>
            <td class="px-4 py-3 max-w-[200px] truncate text-gray-900">${escapeHtml(req.title)}</td>
            <td class="px-4 py-3 text-gray-600">${req.room_number ? `${req.building_code}${req.room_number}` : '-'}</td>
            <td class="px-4 py-3 text-gray-600">
                ${req.category_name ? `<span class="flex items-center gap-1"><i class="ri-tools-line text-gray-400"></i>${req.category_name}</span>` : '-'}
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${priorityColors[req.priority] || 'bg-gray-100 text-gray-600'}">
                    ${getPriorityText(req.priority)}
                </span>
            </td>
            <td class="px-4 py-3">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[req.status] || 'bg-gray-100 text-gray-600'}">
                    ${getStatusText(req.status)}
                </span>
            </td>
            <td class="px-4 py-3 text-gray-600">${formatDate(req.created_at)}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-1">
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" onclick="showDetail(${req.id})" title="ดูรายละเอียด">
                        <i class="ri-eye-line"></i>
                    </button>
                    <button class="p-2 text-primary hover:text-primary-dark hover:bg-red-50 rounded-lg transition-colors" onclick="openStatusModal(${req.id}, '${req.status}')" title="อัพเดทสถานะ">
                        <i class="ri-edit-line"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    }

    function getPriorityText(priority) {
        const map = {
            'critical': 'ด่วนมาก',
            'high': 'ด่วน',
            'medium': 'ปกติ',
            'low': 'ไม่ด่วน'
        };
        return map[priority] || priority;
    }

    function getStatusText(status) {
        const map = {
            'open': 'รอดำเนินการ',
            'assigned': 'มอบหมายแล้ว',
            'in_progress': 'กำลังดำเนินการ',
            'pending_parts': 'รออะไหล่',
            'resolved': 'เสร็จสิ้น',
            'closed': 'ปิดงาน',
            'cancelled': 'ยกเลิก'
        };
        return map[status] || status;
    }

    async function showDetail(id) {
        try {
            const result = await apiCall('maintenance', 'get', {
                id
            });
            const req = result.request;

            document.getElementById('detailModalTitle').textContent = req.ticket_number;
            document.getElementById('detailModalBody').innerHTML = `
            <div class="flex items-start justify-between mb-6 pb-4 border-b border-gray-100">
                <div>
                    <div class="text-primary text-sm font-medium mb-1">${req.ticket_number}</div>
                    <div class="text-xl font-semibold text-gray-900 mb-2">${escapeHtml(req.title)}</div>
                    <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1"><i class="ri-user-line"></i>${escapeHtml(req.requester_name)}</span>
                        ${req.room_number ? `<span class="flex items-center gap-1"><i class="ri-door-open-line"></i>${req.building_code}${req.room_number}</span>` : ''}
                        <span class="flex items-center gap-1"><i class="ri-calendar-line"></i>${formatDate(req.created_at)}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${priorityColors[req.priority] || 'bg-gray-100 text-gray-600'}">${getPriorityText(req.priority)}</span>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[req.status] || 'bg-gray-100 text-gray-600'}">${getStatusText(req.status)}</span>
                </div>
            </div>
            
            <div class="mb-5">
                <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">รายละเอียด</h5>
                <div class="p-4 bg-gray-50 rounded-lg text-gray-700 whitespace-pre-wrap">${escapeHtml(req.description)}</div>
            </div>
            
            ${req.assigned_to ? `
                <div class="mb-5">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">ผู้รับผิดชอบ</h5>
                    <p class="flex items-center gap-2 text-gray-700"><i class="ri-user-settings-line text-primary"></i>${escapeHtml(req.assigned_to)}</p>
                </div>
            ` : ''}
            
            ${req.updates && req.updates.length > 0 ? `
                <div class="mt-6">
                    <h5 class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">ประวัติการอัพเดท</h5>
                    <div class="border-l-2 border-gray-200 ml-2 pl-4 space-y-4">
                        ${req.updates.map(u => `
                            <div class="relative">
                                <div class="absolute -left-[21px] top-1 w-2 h-2 rounded-full bg-primary"></div>
                                <div class="text-gray-700 mb-1">
                                    ${u.update_type === 'status_change' 
                                        ? `เปลี่ยนสถานะเป็น <strong>${getStatusText(u.status_to)}</strong>` 
                                        : escapeHtml(u.comment)}
                                </div>
                                <div class="text-xs text-gray-400">${escapeHtml(u.updated_by)} • ${formatDateTime(u.created_at)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
            
            <div class="flex gap-3 mt-6 pt-4 border-t border-gray-100">
                <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="openStatusModal(${req.id}, '${req.status}')">
                    <i class="ri-edit-line"></i>
                    อัพเดทสถานะ
                </button>
                <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('detailModal')">ปิด</button>
            </div>
        `;

            openModal('detailModal');
        } catch (error) {
            showToast('ไม่สามารถโหลดข้อมูลได้', 'error');
        }
    }

    function openStatusModal(id, currentStatus) {
        document.getElementById('statusRequestId').value = id;
        document.querySelector('select[name="status"]').value = currentStatus;
        document.getElementById('statusForm').reset();
        document.getElementById('statusRequestId').value = id;
        closeModal('detailModal');
        openModal('statusModal');
    }

    async function handleUpdateStatus(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        try {
            await apiCall('maintenance', 'updateStatus', data, 'POST');
            showToast('อัพเดทสถานะสำเร็จ', 'success');
            closeModal('statusModal');
            await loadRequests();
            await loadStats();
        } catch (error) {}
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

    function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleString('th-TH', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>