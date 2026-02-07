<?php

/**
 * Car Booking - Calendar View
 * Migrated to Tailwind CSS
 */

// View only
if (!checkViewPermission($canView, 'ระบบจองรถ')) return;

require_once __DIR__ . '/../Controllers/BookingController.php';

$controller = new BookingController($user);
$allBookings = $controller->listAll();
// Show bookings that are confirmed/active: approved, in_use, pending_return, completed
$activeStatuses = ['approved', 'in_use', 'pending_return', 'completed'];
$approvedBookings = array_filter($allBookings, fn($b) => in_array($b['status'], $activeStatuses));

$calendarEvents = [];
foreach ($approvedBookings as $b) {
    $calendarEvents[] = [
        'id' => $b['id'],
        'title' => $b['destination'],
        'start' => $b['start_time'],
        'end' => $b['end_time'],
        'requester' => $b['fullname'] ?? $b['username'] ?? '',
        'car' => trim(($b['assigned_car_brand'] ?? '') . ' ' . ($b['assigned_car_model'] ?? '')),
        'plate' => $b['assigned_car_plate'] ?? ''
    ];
}
?>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex items-center gap-2">
            <button class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors" onclick="prevMonth()">
                <i class="ri-arrow-left-s-line"></i>
            </button>
            <button class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition-colors" onclick="today()">วันนี้</button>
            <button class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors" onclick="nextMonth()">
                <i class="ri-arrow-right-s-line"></i>
            </button>
        </div>
        <h3 id="calendarTitle" class="text-lg font-semibold text-gray-900"></h3>
        <div class="w-28"></div>
    </div>

    <!-- Week Header -->
    <div class="grid grid-cols-7 bg-gray-100">
        <div class="py-3 text-center text-xs font-semibold text-red-500 uppercase">อา</div>
        <div class="py-3 text-center text-xs font-semibold text-gray-500 uppercase">จ</div>
        <div class="py-3 text-center text-xs font-semibold text-gray-500 uppercase">อ</div>
        <div class="py-3 text-center text-xs font-semibold text-gray-500 uppercase">พ</div>
        <div class="py-3 text-center text-xs font-semibold text-gray-500 uppercase">พฤ</div>
        <div class="py-3 text-center text-xs font-semibold text-gray-500 uppercase">ศ</div>
        <div class="py-3 text-center text-xs font-semibold text-blue-500 uppercase">ส</div>
    </div>

    <!-- Calendar Body -->
    <div class="grid grid-cols-7" id="calendarBody"></div>
</div>

<!-- Event Detail Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="eventModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900" id="eventModalTitle">รายละเอียดการจอง</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeEventModal()">&times;</button>
        </div>
        <div class="p-6" id="eventModalContent"></div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeEventModal()">ปิด</button>
        </div>
    </div>
</div>

<style>
    #eventModal.active {
        opacity: 1;
        visibility: visible;
    }

    .calendar-cell {
        min-height: 100px;
        transition: background 0.15s;
    }

    .calendar-cell:hover {
        background: #f9fafb;
    }

    /* Mobile Calendar Improvements */
    @media (max-width: 768px) {
        .calendar-cell {
            min-height: 60px;
            padding: 4px !important;
        }

        .calendar-cell>div:first-child {
            font-size: 0.75rem;
        }

        .calendar-cell .text-xs {
            font-size: 0.6rem;
            padding: 2px 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }
</style>

<script>
    const events = <?= json_encode(array_values($calendarEvents)) ?>;
    let currentYear, currentMonth;

    function init() {
        const now = new Date();
        currentYear = now.getFullYear();
        currentMonth = now.getMonth();
        renderCalendar();
    }

    function renderCalendar() {
        const monthNames = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        document.getElementById('calendarTitle').textContent = `${monthNames[currentMonth]} ${currentYear + 543}`;

        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const startDay = firstDay.getDay();
        const daysInMonth = lastDay.getDate();
        const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();

        let html = '';
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];

        // Prev month
        for (let i = startDay - 1; i >= 0; i--) {
            const day = prevMonthLastDay - i;
            const dow = (startDay - i - 1 + 7) % 7;
            const bgClass = dow === 0 ? 'bg-red-50' : dow === 6 ? 'bg-blue-50' : 'bg-gray-50';
            html += `<div class="calendar-cell p-2 border-r border-b border-gray-100 ${bgClass}"><div class="text-gray-300 text-sm">${day}</div></div>`;
        }

        // Current month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = dateStr === todayStr;
            const dayEvents = events.filter(e => e.start.startsWith(dateStr));
            const dow = new Date(currentYear, currentMonth, day).getDay();

            let bgClass = dow === 0 ? 'bg-red-50' : dow === 6 ? 'bg-blue-50' : '';
            let dayClass = dow === 0 ? 'text-red-500' : dow === 6 ? 'text-blue-500' : 'text-gray-700';

            html += `<div class="calendar-cell p-2 border-r border-b border-gray-100 cursor-pointer ${bgClass} ${isToday ? 'bg-primary/5' : ''}" onclick="onCellClick('${dateStr}', event)">`;

            if (isToday) {
                html += `<div class="w-7 h-7 flex items-center justify-center bg-primary text-white rounded-full text-sm font-medium mb-1">${day}</div>`;
            } else {
                html += `<div class="text-sm font-medium ${dayClass} mb-1">${day}</div>`;
            }

            dayEvents.slice(0, 3).forEach(evt => {
                html += `<div class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded mb-1 truncate cursor-pointer hover:bg-emerald-200" onclick='showEvent(${JSON.stringify(evt)}); event.stopPropagation();'>${evt.title}</div>`;
            });

            if (dayEvents.length > 3) {
                html += `<div class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded">+${dayEvents.length - 3} อื่นๆ</div>`;
            }

            html += '</div>';
        }

        // Next month
        const totalCells = startDay + daysInMonth;
        const nextMonthDays = 42 - totalCells;
        for (let day = 1; day <= nextMonthDays; day++) {
            const dow = (startDay + daysInMonth + day - 1) % 7;
            const bgClass = dow === 0 ? 'bg-red-50' : dow === 6 ? 'bg-blue-50' : 'bg-gray-50';
            html += `<div class="calendar-cell p-2 border-r border-b border-gray-100 ${bgClass}"><div class="text-gray-300 text-sm">${day}</div></div>`;
        }

        document.getElementById('calendarBody').innerHTML = html;
    }

    function onCellClick(dateStr, event) {
        if (event.target.classList.contains('bg-emerald-100') || event.target.classList.contains('bg-gray-100')) return;
        if (!canEdit) {
            showToast('คุณไม่มีสิทธิ์สร้างคำขอ', 'error');
            return;
        }
        openBookingModal(dateStr);
    }

    function prevMonth() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    }

    function nextMonth() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    }

    function today() {
        const now = new Date();
        currentYear = now.getFullYear();
        currentMonth = now.getMonth();
        renderCalendar();
    }

    function showEvent(evt) {
        document.getElementById('eventModalTitle').textContent = evt.title;
        document.getElementById('eventModalContent').innerHTML = `
            <div class="space-y-3 text-sm">
                <div><span class="text-gray-500 block">ผู้ขอ</span><span class="font-medium">${evt.requester}</span></div>
                <div><span class="text-gray-500 block">รถ</span><span class="font-medium">${evt.car || '-'} ${evt.plate ? '(' + evt.plate + ')' : ''}</span></div>
                <div><span class="text-gray-500 block">เริ่มต้น</span><span class="font-medium">${formatDateTime(evt.start)}</span></div>
                <div><span class="text-gray-500 block">สิ้นสุด</span><span class="font-medium">${formatDateTime(evt.end)}</span></div>
            </div>
        `;
        document.getElementById('eventModal').classList.add('active');
    }

    function closeEventModal() {
        document.getElementById('eventModal').classList.remove('active');
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeEventModal();
            if (typeof closeBookingModal === 'function') closeBookingModal();
        }
    });

    init();
</script>

<?php include __DIR__ . '/partials/booking-modal.php'; ?>