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
        'plate' => $b['assigned_car_plate'] ?? '',
        'car_id' => $b['assigned_car_id'] ?? null
    ];
}

$companyCarIds = $controller->getActiveCompanyCarIds();
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

<!-- Daily Events Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="dailyEventsModal">
    <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900" id="dailyEventsModalTitle">รายการจองวันที่</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeDailyEventsModal()">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto bg-gray-50 flex-1" id="dailyEventsModalContent">
            <!-- Events list will be populated here -->
        </div>
        <div class="flex justify-between items-center px-6 py-4 bg-white border-t border-gray-100">
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 hover:bg-primary/20 text-primary rounded-lg font-medium transition-colors" id="btnNewBookingDaily" onclick="closeDailyEventsModal(); openBookingModal(currentDailyDate)">
                <i class="ri-add-line"></i> จองรถวันนี้
            </button>
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeDailyEventsModal()">ปิด</button>
        </div>
    </div>
</div>

<style>
    #dailyEventsModal.active {
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
    const companyCarIds = <?= json_encode(array_map('intval', $companyCarIds)) ?>;
    const totalCompanyCars = companyCarIds.length;
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

            // Check company car availability for this day
            const startOfDay = new Date(`${dateStr}T00:00:00`).getTime();
            const endOfDay = new Date(`${dateStr}T23:59:59`).getTime();

            const bookedCompanyCars = new Set();
            events.forEach(e => {
                if (e.car_id && companyCarIds.includes(parseInt(e.car_id))) {
                    const eStart = new Date(e.start).getTime();
                    const eEnd = new Date(e.end).getTime();
                    if (eStart <= endOfDay && eEnd >= startOfDay) {
                        bookedCompanyCars.add(e.car_id);
                    }
                }
            });
            const availableCompanyCars = totalCompanyCars - bookedCompanyCars.size;

            let bgClass = dow === 0 ? 'bg-red-50' : dow === 6 ? 'bg-blue-50' : '';
            let dayClass = dow === 0 ? 'text-red-500' : dow === 6 ? 'text-blue-500' : 'text-gray-700';

            html += `<div class="calendar-cell p-2 border-r border-b border-gray-100 cursor-pointer ${bgClass} ${isToday ? 'bg-primary/5' : ''}" onclick="onCellClick('${dateStr}', event)">`;

            html += `<div class="flex justify-between items-start mb-1">`;
            if (isToday) {
                html += `<div class="w-7 h-7 flex items-center justify-center bg-primary text-white rounded-full text-sm font-medium">${day}</div>`;
            } else {
                html += `<div class="text-sm font-medium ${dayClass}">${day}</div>`;
            }

            if (totalCompanyCars > 0) {
                const availClass = availableCompanyCars > 0 ? 'text-emerald-600' : 'text-red-500';
                html += `<div class="text-sm font-normal flex items-center gap-0.5 ${availClass}" title="รถประจำว่าง ${availableCompanyCars} คัน"><i class="ri-car-fill"></i>${availableCompanyCars}</div>`;
            }
            html += `</div>`;

            dayEvents.slice(0, 3).forEach(evt => {
                html += `<div class="text-xs px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded mb-1 truncate">${evt.title}</div>`;
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
        // Find events for this day
        const dayEvents = events.filter(e => e.start.startsWith(dateStr));

        // Check if the click was on an event badge
        const clickedElement = event.target;
        if (clickedElement.classList.contains('bg-gray-100') && clickedElement.textContent.includes('อื่นๆ')) {
            // If "อื่นๆ" badge was clicked, show daily events
            showDailyEvents(dateStr);
            return;
        }

        // If no specific event badge was clicked, proceed with default cell click behavior
        if (dayEvents.length > 0) {
            // If there are events, show the list modal
            showDailyEvents(dateStr);
        } else {
            // Empty day, go straight to new booking if permitted
            if (!canEdit) {
                showToast('คุณไม่มีสิทธิ์สร้างคำขอ', 'error');
                return;
            }
            openBookingModal(dateStr);
        }
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

    let currentDailyDate = null;

    function showDailyEvents(dateStr) {
        currentDailyDate = dateStr;
        const dayEvents = events.filter(e => e.start.startsWith(dateStr));

        // Format Thai Datetime
        const dateObj = new Date(dateStr);
        const thaiDate = dateObj.toLocaleDateString('th-TH', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        document.getElementById('dailyEventsModalTitle').textContent = `รายการจองวันที่ ${thaiDate}`;

        if (!canEdit) {
            document.getElementById('btnNewBookingDaily').style.display = 'none';
        } else {
            document.getElementById('btnNewBookingDaily').style.display = 'inline-flex';
        }

        const contentDiv = document.getElementById('dailyEventsModalContent');

        if (dayEvents.length === 0) {
            contentDiv.innerHTML = `<div class="text-center py-8 text-gray-500"><i class="ri-calendar-event-line text-4xl mb-2 block"></i>ไม่มีรายการจองรถในวันนี้</div>`;
        } else {
            let html = '<div class="space-y-3">';
            dayEvents.forEach(evt => {
                const safeEvt = JSON.stringify(evt).replace(/'/g, "&apos;");
                const carText = evt.car ? `<i class="ri-car-fill"></i> ${evt.car} ${evt.plate ? '(' + evt.plate + ')' : ''}` : '';
                html += `
                    <div class="bg-white p-3 rounded-lg border border-gray-200 shadow-sm transition-all">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-medium text-gray-900">${evt.title}</h4>
                            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">#${evt.id}</span>
                        </div>
                        <div class="text-xs text-gray-600 space-y-1">
                            <div><i class="ri-user-line text-gray-400 mr-1"></i> ${evt.requester}</div>
                            <div><i class="ri-time-line text-gray-400 mr-1"></i> ${formatDateTime(evt.start)} - ${formatDateTime(evt.end)}</div>
                            ${carText ? `<div class="text-primary font-medium mt-1">${carText}</div>` : ''}
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            contentDiv.innerHTML = html;
        }

        const modal = document.getElementById('dailyEventsModal');
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        modal.classList.add('active');
    }

    function closeDailyEventsModal() {
        document.getElementById('dailyEventsModal').classList.remove('active');
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeDailyEventsModal();
            if (typeof closeBookingModal === 'function') closeBookingModal();
        }
    });

    init();
</script>

<?php include __DIR__ . '/partials/booking-modal.php'; ?>