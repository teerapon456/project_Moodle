<?php
// dashboard.php - Admin only
if (!checkAdminPermission($canView, $isAdmin, 'ระบบหอพัก')) return;
?>
<!-- Dashboard View - Migrated to Tailwind -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <!-- สถิติห้องพัก -->
    <div class="bg-white border-l-4 border-primary rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center text-primary text-2xl mb-3">
            <i class="ri-building-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900" id="totalBuildings">-</div>
        <div class="text-gray-500 text-sm">อาคาร</div>
    </div>

    <div class="bg-white border-l-4 border-success rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-emerald-50 flex items-center justify-content text-success text-2xl mb-3">
            <i class="ri-door-open-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900" id="availableRooms">-</div>
        <div class="text-gray-500 text-sm">ห้องว่าง</div>
    </div>

    <div class="bg-white border-l-4 border-info rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-info text-2xl mb-3">
            <i class="ri-group-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900" id="totalOccupants">-</div>
        <div class="text-gray-500 text-sm mb-2">ผู้พักอาศัยทั้งหมด</div>
        <div class="flex flex-wrap gap-2 text-xs">
            <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full">
                <i class="ri-user-star-line"></i>
                <span id="employeeOccupants">0</span> พนักงาน
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 rounded-full">
                <i class="ri-user-heart-line"></i>
                <span id="relativeOccupants">0</span> ญาติ
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-700 rounded-full">
                <i class="ri-user-received-line"></i>
                <span id="tempOccupants">0</span> ภายนอก
            </span>
        </div>
    </div>

    <div class="bg-white border-l-4 border-warning rounded-xl p-5 shadow-sm hover:shadow-md transition-shadow">
        <div class="w-12 h-12 rounded-lg bg-amber-50 flex items-center justify-center text-warning text-2xl mb-3">
            <i class="ri-tools-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900" id="pendingMaintenance">-</div>
        <div class="text-gray-500 text-sm">รอดำเนินการ</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- สรุปบิลเดือนนี้ -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-file-list-3-line text-primary"></i>
                สรุปบิลเดือนนี้
            </h3>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium" id="currentMonth"></span>
        </div>
        <div class="flex flex-col gap-3">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">ยอดเรียกเก็บ</span>
                <span class="font-semibold text-lg text-primary" id="totalBilling">฿0</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">ชำระแล้ว</span>
                <span class="font-semibold text-lg text-success" id="paidAmount">฿0</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-600">ค้างชำระ</span>
                <span class="font-semibold text-lg text-danger" id="outstandingAmount">฿0</span>
            </div>
        </div>
        <div class="mt-5 pt-4 border-t border-gray-100">
            <a href="?page=invoices" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                <i class="ri-arrow-right-line"></i>
                ดูทั้งหมด
            </a>
        </div>
    </div>

    <!-- งานซ่อมล่าสุด -->
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-tools-line text-primary"></i>
                งานซ่อมล่าสุด
            </h3>
            <a href="?page=maintenance" class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">ดูทั้งหมด</a>
        </div>
        <div class="flex flex-col gap-3 min-h-[200px]" id="recentMaintenance">
            <div class="flex items-center justify-center h-32">
                <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
            </div>
        </div>
    </div>
</div>

<!-- บิลค้างชำระ -->
<div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
    <div class="flex items-center gap-2 mb-5 pb-4 border-b border-gray-100">
        <i class="ri-error-warning-line text-primary text-lg"></i>
        <h3 class="text-base font-semibold text-gray-900">บิลค้างชำระ</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เลขที่บิล</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ห้อง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้พักอาศัย</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เดือน</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดรวม</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ครบกำหนด</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                </tr>
            </thead>
            <tbody id="overdueInvoices" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // โหลดข้อมูลสรุป
            const summary = await apiCall('dashboard', 'getSummary');

            // อัพเดทสถิติห้องพัก
            const rooms = summary.summary.rooms;
            document.getElementById('totalBuildings').textContent = rooms.total_buildings || 0;
            document.getElementById('availableRooms').textContent = rooms.available_rooms || 0;

            // คำนวณจำนวนผู้พักอาศัยทั้งหมด (พนักงาน + ญาติ + บุคคลภายนอก)
            const employees = parseInt(rooms.employee_occupants || 0);
            const relatives = parseInt(rooms.relative_occupants || 0);
            const temps = parseInt(rooms.temp_occupants || 0);
            const totalOccupants = employees + relatives + temps;

            document.getElementById('totalOccupants').textContent = totalOccupants;
            document.getElementById('employeeOccupants').textContent = employees;
            document.getElementById('relativeOccupants').textContent = relatives;
            document.getElementById('tempOccupants').textContent = temps;

            // อัพเดทสถิติงานซ่อม
            const maint = summary.summary.maintenance;
            document.getElementById('pendingMaintenance').textContent = (maint.open_count || 0) + (maint.in_progress_count || 0);

            // อัพเดทสรุปบิล
            const billing = summary.summary.billing;
            document.getElementById('currentMonth').textContent = billing.month_cycle;
            document.getElementById('totalBilling').textContent = formatCurrency(billing.total_amount || 0);
            document.getElementById('paidAmount').textContent = formatCurrency(billing.paid_amount || 0);
            document.getElementById('outstandingAmount').textContent = formatCurrency(billing.outstanding || 0);

            // โหลดงานซ่อมล่าสุด
            const maintenanceData = await apiCall('dashboard', 'getRecentMaintenance', {
                limit: 5
            });
            renderRecentMaintenance(maintenanceData.requests);

            // โหลดบิลค้างชำระ
            const overdueData = await apiCall('dashboard', 'getOverdueInvoices');
            renderOverdueInvoices(overdueData.invoices);

        } catch (error) {
            console.error('Failed to load dashboard:', error);
        }
    });

    function renderRecentMaintenance(requests) {
        const container = document.getElementById('recentMaintenance');

        if (!requests || requests.length === 0) {
            container.innerHTML = `
            <div class="flex flex-col items-center justify-center h-32 text-gray-400">
                <i class="ri-checkbox-circle-line text-3xl mb-2"></i>
                <p>ไม่มีงานซ่อมล่าสุด</p>
            </div>
        `;
            return;
        }

        const priorityColors = {
            'critical': 'border-l-red-500',
            'high': 'border-l-amber-500',
            'medium': 'border-l-blue-500',
            'low': 'border-l-gray-300'
        };

        container.innerHTML = requests.map(req => `
        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border-l-4 ${priorityColors[req.priority] || 'border-l-gray-300'}">
            <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center text-gray-500 shadow-sm">
                <i class="ri-tools-line"></i>
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 truncate">${escapeHtml(req.title)}</div>
                <div class="text-sm text-gray-500">
                    ${req.room_number ? `ห้อง ${req.building_code}${req.room_number}` : 'พื้นที่ส่วนกลาง'}
                    • ${getStatusText(req.status)}
                </div>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-medium ${getStatusBadgeClass(req.status)}">${getStatusText(req.status)}</span>
        </div>
    `).join('');
    }

    function renderOverdueInvoices(invoices) {
        const tbody = document.getElementById('overdueInvoices');

        if (!invoices || invoices.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                    <i class="ri-checkbox-circle-line text-3xl mb-2 block"></i>
                    <p>ไม่มีบิลค้างชำระ</p>
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = invoices.map(inv => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3"><strong class="text-gray-900">${inv.invoice_number}</strong></td>
            <td class="px-4 py-3 text-gray-600">${inv.building_code}${inv.room_number}</td>
            <td class="px-4 py-3 text-gray-600">${escapeHtml(inv.employee_name)}</td>
            <td class="px-4 py-3 text-gray-600">${inv.month_cycle}</td>
            <td class="px-4 py-3 font-medium text-gray-900">${formatCurrency(inv.total_amount)}</td>
            <td class="px-4 py-3 text-gray-600">${formatDate(inv.due_date)}</td>
            <td class="px-4 py-3">
                <span class="px-3 py-1 rounded-full text-xs font-medium ${inv.status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}">
                    ${getInvoiceStatus(inv.status)}
                </span>
            </td>
        </tr>
    `).join('');
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

    function getStatusBadgeClass(status) {
        const map = {
            'open': 'bg-red-100 text-red-800',
            'assigned': 'bg-amber-100 text-amber-800',
            'in_progress': 'bg-blue-100 text-blue-800',
            'pending_parts': 'bg-amber-100 text-amber-800',
            'resolved': 'bg-emerald-100 text-emerald-800',
            'closed': 'bg-gray-100 text-gray-600',
            'cancelled': 'bg-gray-100 text-gray-600'
        };
        return map[status] || 'bg-gray-100 text-gray-600';
    }

    function getInvoiceStatus(status) {
        const map = {
            'pending': 'รอชำระ',
            'partial': 'ชำระบางส่วน',
            'overdue': 'เกินกำหนด',
            'paid': 'ชำระแล้ว'
        };
        return map[status] || status;
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
</script>