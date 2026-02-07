<?php
// my-room.php - View only
if (!checkViewPermission($canView, 'ระบบหอพัก')) return;
?>
<!-- My Room View - Migrated to Tailwind -->
<div class="mb-6 flex items-center justify-between">
    <h2 class="text-xl md:text-2xl font-semibold text-gray-900">ห้องของฉัน</h2>
</div>

<!-- Room Info Card -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6" id="roomInfoCard">
    <div class="flex items-center gap-3 px-4 md:px-6 py-3 md:py-4 border-b border-gray-100">
        <i class="ri-home-fill text-xl text-primary"></i>
        <h3 class="text-base md:text-lg font-semibold text-gray-900">ข้อมูลห้องพัก</h3>
    </div>
    <div id="roomInfoBody">
        <div class="flex items-center justify-center py-12">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
        </div>
    </div>
</div>

<!-- Quick Actions (Mobile-optimized) -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="flex items-center gap-3 px-4 md:px-6 py-3 md:py-4 border-b border-gray-100">
        <i class="ri-apps-fill text-xl text-primary"></i>
        <h3 class="text-base md:text-lg font-semibold text-gray-900">เมนูด่วน</h3>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4">
        <a href="?page=maintenance-form" class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-red-50 to-orange-50 hover:from-primary hover:to-red-500 hover:text-white rounded-xl transition-all group border border-red-100 hover:border-transparent">
            <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-2xl text-primary group-hover:bg-white/20 group-hover:text-white transition-all">
                <i class="ri-tools-fill"></i>
            </div>
            <span class="font-medium text-sm text-center">แจ้งซ่อม</span>
        </a>
        <a href="?page=invoices" class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 hover:from-primary hover:to-red-500 hover:text-white rounded-xl transition-all group border border-blue-100 hover:border-transparent">
            <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-2xl text-blue-500 group-hover:bg-white/20 group-hover:text-white transition-all">
                <i class="ri-secure-payment-fill"></i>
            </div>
            <span class="font-medium text-sm text-center">ชำระบิล</span>
        </a>
        <a href="?page=payments" class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-emerald-50 to-green-50 hover:from-primary hover:to-red-500 hover:text-white rounded-xl transition-all group border border-emerald-100 hover:border-transparent">
            <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-2xl text-emerald-500 group-hover:bg-white/20 group-hover:text-white transition-all">
                <i class="ri-money-dollar-circle-fill"></i>
            </div>
            <span class="font-medium text-sm text-center">ประวัติชำระ</span>
        </a>
        <a href="?page=<?= $isAdmin ? 'maintenance' : 'request_history' ?>" class="flex flex-col items-center gap-2 p-4 bg-gradient-to-br from-purple-50 to-pink-50 hover:from-primary hover:to-red-500 hover:text-white rounded-xl transition-all group border border-purple-100 hover:border-transparent">
            <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-2xl text-purple-500 group-hover:bg-white/20 group-hover:text-white transition-all">
                <i class="ri-list-check-2"></i>
            </div>
            <span class="font-medium text-sm text-center">รายการแจ้งซ่อม</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Current Bills -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 md:px-6 py-3 md:py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 text-base md:text-lg font-semibold text-gray-900">
                <i class="ri-file-list-3-fill text-primary"></i>
                บิลของฉัน
            </h3>
            <a href="?page=invoices" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs md:text-sm font-medium transition-colors">ดูทั้งหมด</a>
        </div>
        <div class="p-4 min-h-[150px] space-y-3" id="myBills">
            <div class="flex items-center justify-center py-8">
                <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
            </div>
        </div>
    </div>

    <!-- My Maintenance Requests -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-4 md:px-6 py-3 md:py-4 border-b border-gray-100">
            <h3 class="flex items-center gap-2 text-base md:text-lg font-semibold text-gray-900">
                <i class="ri-tools-fill text-primary"></i>
                การแจ้งซ่อมของฉัน
            </h3>
            <a href="?page=maintenance-form" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary hover:bg-primary-dark text-white rounded-lg text-xs md:text-sm font-medium transition-colors">
                <i class="ri-add-line"></i>
                <span class="hidden sm:inline">แจ้งซ่อมใหม่</span>
                <span class="sm:hidden">แจ้ง</span>
            </a>
        </div>
        <div class="p-4 min-h-[150px] space-y-3" id="myMaintenance">
            <div class="flex items-center justify-center py-8">
                <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
            </div>
        </div>
    </div>
</div>

<script>
    let myRoom = null;
    const userEmail = '<?= htmlspecialchars($user['email'] ?? '') ?>';

    const statusBadges = {
        'pending': 'bg-amber-100 text-amber-800',
        'paid': 'bg-emerald-100 text-emerald-800',
        'partial': 'bg-blue-100 text-blue-800',
        'overdue': 'bg-red-100 text-red-800'
    };

    const maintStatusBadges = {
        'open': 'bg-red-100 text-red-800',
        'assigned': 'bg-amber-100 text-amber-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'resolved': 'bg-emerald-100 text-emerald-800',
        'closed': 'bg-gray-100 text-gray-600'
    };

    document.addEventListener('DOMContentLoaded', loadMyRoom);

    async function loadMyRoom() {
        try {
            const result = await apiCall('rooms', 'getMyRoom', {
                email: userEmail
            });
            if (result.room) {
                myRoom = result.room;
                renderRoomInfo();
                await loadMyBills();
                await loadMyMaintenance();
            } else {
                showNoRoom();
            }
        } catch (error) {
            showNoRoom();
        }
    }

    function renderRoomInfo() {
        const roommates = myRoom.occupants || [];

        // Helper function to render accompanying persons
        function renderAccompanyingPersons(occupant) {
            if (!occupant.accompanying_persons || occupant.accompanying_persons == 0) return '';
            let relatives = [];
            try {
                if (occupant.accompanying_details) {
                    relatives = JSON.parse(occupant.accompanying_details);
                }
            } catch (e) {}

            if (relatives.length === 0) return '';

            return `
                <div class="mt-2 pl-4 border-l-2 border-purple-200">
                    <div class="text-xs text-purple-600 font-medium mb-1">
                        <i class="ri-user-add-line"></i> ผู้ติดตาม (${relatives.length})
                    </div>
                    ${relatives.map(r => `
                        <div class="text-xs text-gray-500 flex items-center gap-2">
                            <span class="font-medium">${escapeHtml(r.name)}</span>
                            ${r.age ? `<span class="text-gray-400">อายุ ${r.age} ปี</span>` : ''}
                            ${r.relation ? `<span class="bg-purple-50 text-purple-600 px-1.5 py-0.5 rounded">${escapeHtml(r.relation)}</span>` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Calculate total occupants including accompanying persons
        let totalOccupants = roommates.length;
        roommates.forEach(p => {
            totalOccupants += parseInt(p.accompanying_persons || 0);
        });

        document.getElementById('roomInfoBody').innerHTML = `
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-100">
                <div class="p-6 text-center">
                    <div class="text-sm text-gray-500 mb-2">ห้อง</div>
                    <div class="text-3xl font-bold text-primary">${myRoom.building_code}${myRoom.room_number}</div>
                </div>
                <div class="p-6 text-center">
                    <div class="text-sm text-gray-500 mb-2">อาคาร</div>
                    <div class="text-2xl font-semibold text-gray-900">${myRoom.building_name || '-'}</div>
                    <div class="text-xs text-primary mt-1 font-medium bg-primary/10 inline-block px-2 py-0.5 rounded-full">${myRoom.room_type_name || myRoom.room_type || '-'}</div>
                </div>
                <div class="p-6 text-center">
                    <div class="text-sm text-gray-500 mb-2">ชั้น</div>
                    <div class="text-2xl font-semibold text-gray-900">${myRoom.floor || '-'}</div>
                </div>
                <div class="p-6 text-center">
                    <div class="text-sm text-gray-500 mb-2">ค่าเช่า/เดือน</div>
                    <div class="text-2xl font-semibold text-gray-900">${formatCurrency(myRoom.monthly_rent || 0)}</div>
                </div>
            </div>
            ${roommates.length > 0 ? `
                <div class="p-6 border-t border-gray-100">
                    <h4 class="font-semibold text-gray-700 mb-4">
                        ผู้พักอาศัย (${totalOccupants} คน)
                        ${totalOccupants > roommates.length ? `<span class="text-xs text-purple-600 font-normal ml-2">รวมผู้ติดตาม ${totalOccupants - roommates.length} คน</span>` : ''}
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        ${roommates.map(p => `
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center ${p.employee_email === userEmail ? 'bg-emerald-100 text-success' : 'bg-blue-100 text-info'}">
                                        <i class="ri-user-line"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 truncate">
                                            ${escapeHtml(p.employee_name)}
                                            ${p.employee_email === userEmail ? '<span class="text-xs bg-emerald-100 text-success px-1.5 py-0.5 rounded ml-1">ฉัน</span>' : ''}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate">${p.employee_email}</div>
                                    </div>
                                </div>
                                ${renderAccompanyingPersons(p)}
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        `;
    }

    function showNoRoom() {
        document.getElementById('roomInfoBody').innerHTML = `
            <div class="text-center py-12 text-gray-400">
                <i class="ri-home-line text-5xl mb-4 block"></i>
                <p class="text-lg">ไม่พบข้อมูลห้องพักที่ลงทะเบียนกับ email ของคุณ</p>
                <p class="text-sm mt-2">กรุณาติดต่อเจ้าหน้าที่หอพัก</p>
            </div>
        `;
        document.getElementById('myBills').innerHTML = '<p class="text-center text-gray-400 py-8">ไม่มีข้อมูล</p>';
        document.getElementById('myMaintenance').innerHTML = '<p class="text-center text-gray-400 py-8">ไม่มีข้อมูล</p>';
    }

    async function loadMyBills() {
        if (!myRoom) return;
        try {
            const result = await apiCall('billing', 'listInvoices', {
                room_id: myRoom.id
            });
            const bills = result.invoices || [];

            if (bills.length === 0) {
                document.getElementById('myBills').innerHTML = `
                    <div class="text-center py-8 text-gray-400">
                        <i class="ri-checkbox-circle-line text-3xl mb-2 block"></i>
                        <p>ไม่มีบิลค้างชำระ</p>
                    </div>
                `;
                return;
            }

            document.getElementById('myBills').innerHTML = bills.slice(0, 3).map(bill => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border-l-4 ${bill.status === 'paid' ? 'border-success' : 'border-primary'}">
                    <div>
                        <div class="font-semibold text-gray-900">${bill.month_cycle}</div>
                        <div class="text-sm text-gray-500">${bill.invoice_number}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold ${bill.status === 'paid' ? 'text-success' : 'text-danger'}">${formatCurrency(bill.total_amount)}</div>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${statusBadges[bill.status] || 'bg-gray-100 text-gray-600'}">${getStatusText(bill.status)}</span>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            document.getElementById('myBills').innerHTML = '<p class="text-center text-red-500">เกิดข้อผิดพลาด</p>';
        }
    }

    async function loadMyMaintenance() {
        if (!myRoom) return;
        try {
            const result = await apiCall('maintenance', 'list', {
                room_id: myRoom.id,
                limit: 5
            });
            const requests = result.requests || [];

            if (requests.length === 0) {
                document.getElementById('myMaintenance').innerHTML = `
                    <div class="text-center py-8 text-gray-400">
                        <i class="ri-checkbox-circle-line text-3xl mb-2 block"></i>
                        <p>ไม่มีรายการแจ้งซ่อม</p>
                    </div>
                `;
                return;
            }

            const statusBorderColors = {
                'open': 'border-red-500',
                'in_progress': 'border-blue-500',
                'resolved': 'border-emerald-500'
            };

            document.getElementById('myMaintenance').innerHTML = requests.map(req => `
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border-l-4 ${statusBorderColors[req.status] || 'border-gray-300'}">
                    <div class="w-12 h-12 rounded-lg bg-white shadow-sm flex items-center justify-center text-xl text-gray-500">
                        <i class="ri-tools-line"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-gray-900 truncate">${escapeHtml(req.title)}</div>
                        <div class="text-sm text-gray-400">${req.ticket_number} • ${formatDate(req.created_at)}</div>
                    </div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${maintStatusBadges[req.status] || 'bg-gray-100 text-gray-600'}">${getMaintStatusText(req.status)}</span>
                </div>
            `).join('');
        } catch (error) {
            document.getElementById('myMaintenance').innerHTML = '<p class="text-center text-red-500">เกิดข้อผิดพลาด</p>';
        }
    }

    function getStatusText(status) {
        const map = {
            'pending': 'รอชำระ',
            'paid': 'ชำระแล้ว',
            'partial': 'ชำระบางส่วน',
            'overdue': 'เกินกำหนด'
        };
        return map[status] || status;
    }

    function getMaintStatusText(status) {
        const map = {
            'open': 'รอดำเนินการ',
            'assigned': 'มอบหมายแล้ว',
            'in_progress': 'กำลังดำเนินการ',
            'resolved': 'เสร็จสิ้น',
            'closed': 'ปิดงาน'
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