<?php

/**
 * Scheduled Reports - Configure automated email reports
 */

require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
// startOptimizedSession(); // Moved to Middleware

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';
// $user setup removed, waiting for linkBase

$basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/Modules/ScheduledReports/public$#i', '', $scriptDir);
}
if ($basePath === '') $basePath = '/';
$baseRoot = rtrim($basePath, '/');

// Determine asset base: check if DocumentRoot points to public/ folder (Docker) or htdocs (XAMPP)
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot && is_dir($docRoot . '/assets')) {
    $assetBase = ($baseRoot ? $baseRoot : '') . '/';
} else {
    $assetBase = ($baseRoot ? $baseRoot : '') . '/public/';
}
$linkBase = ($baseRoot ? $baseRoot . '/' : '/');

$user = AuthMiddleware::checkLogin($linkBase);
$isLoggedIn = !empty($user);

require_once __DIR__ . '/../../../core/Helpers/PermissionHelper.php';

// Auth checked by Middleware


$userPerms = userHasModuleAccess('HR_SERVICES', (int)$user['role_id']);
$hrNewsPerm = userHasModuleAccess('HR_NEWS', (int)$user['role_id']);
$permManage = userHasModuleAccess('PERMISSION_MANAGEMENT', (int)$user['role_id']);
$scheduledPerm = userHasModuleAccess('SCHEDULED_REPORTS', (int)$user['role_id']);

if (empty($scheduledPerm['can_view'])) {
    header('Location: ' . $linkBase . 'Modules/HRServices/public/index.php?error=no_permission');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Reports | MyHR Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="<?= $assetBase ?>assets/css/tailwind.css">
    <script>
        window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
        window.CAN_MANAGE = <?= json_encode(!empty($scheduledPerm['can_manage'])) ?>;
    </script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/../../../public/includes/header.php'; ?>

    <div class="max-w-6xl mx-auto px-6 py-8">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <i class="ri-calendar-schedule-line text-primary"></i>
                    Scheduled Reports
                </h1>
                <p class="text-gray-500 text-sm mt-1">ตั้งรายงานอัตโนมัติส่ง Email</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="openModal()" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="ri-add-line"></i> สร้างรายงาน
                </button>
                <a href="<?= $linkBase ?>Modules/HRServices/public/index.php" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">
                    <i class="ri-arrow-left-line"></i> กลับ
                </a>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อรายงาน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ความถี่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้รับ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ส่งล่าสุด</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32"></th>
                    </tr>
                </thead>
                <tbody id="reports-tbody" class="divide-y divide-gray-100">
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="report-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-lg text-gray-900" id="modal-title">สร้างรายงานใหม่</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form id="report-form" class="p-6 space-y-4">
                <input type="hidden" id="report-id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อรายงาน</label>
                    <input type="text" id="report-name" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทรายงาน</label>
                    <select id="report-type" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary" required>
                        <option value="">-- เลือก --</option>
                        <optgroup label="ระบบ">
                            <option value="activity_summary">Activity Summary (สรุปกิจกรรม)</option>
                            <option value="login_report">Login Report (รายงาน Login)</option>
                            <option value="email_stats">Email Stats (สถิติ Email)</option>
                        </optgroup>
                        <optgroup label="โมดูล">
                            <option value="car_booking_summary">Car Booking Summary (สรุปการใช้รถ)</option>
                            <option value="dormitory_summary">Dormitory Summary (สรุปหอพัก)</option>
                        </optgroup>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ความถี่</label>
                        <select id="schedule-type" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary" required onchange="updateDayField()">
                            <option value="daily">รายวัน</option>
                            <option value="weekly">รายสัปดาห์</option>
                            <option value="monthly">รายเดือน</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เวลาส่ง</label>
                        <input type="time" id="schedule-time" value="08:00" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary" required>
                    </div>
                </div>
                <div id="day-field" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1" id="day-label">วัน</label>
                    <select id="schedule-day" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary"></select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ผู้รับรายงาน</label>
                    <div id="recipientTags" class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <span class="text-gray-400 text-sm">ยังไม่มีผู้รับ</span>
                    </div>
                    <div class="relative">
                        <input type="text" id="recipientSearch" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary" placeholder="ค้นหาชื่อหรืออีเมล..." autocomplete="off" oninput="searchRecipient(this.value)">
                        <div id="recipientResults" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">พิมพ์ค้นหาแล้วเลือกจากรายการ</p>
                </div>
            </form>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300">ยกเลิก</button>
                <button onclick="saveReport()" class="px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark">บันทึก</button>
            </div>
        </div>
    </div>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php/scheduled_reports';
        const SEARCH_API = BASE_PATH + '/Modules/CarBooking/api.php?controller=bookings&action=searchEmployee';

        let currentRecipients = [];
        let searchTimeout;

        const reportTypeLabels = {
            'activity_summary': 'Activity Summary',
            'login_report': 'Login Report',
            'email_stats': 'Email Stats',
            'car_booking_summary': 'Car Booking Summary',
            'dormitory_summary': 'Dormitory Summary'
        };
        const scheduleLabels = {
            'daily': 'รายวัน',
            'weekly': 'รายสัปดาห์',
            'monthly': 'รายเดือน'
        };
        const dayNames = ['', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์'];

        async function loadReports() {
            const tbody = document.getElementById('reports-tbody');
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400"><div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div></td></tr>';

            try {
                const res = await fetch(`${API_BASE_URL}?action=list`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    tbody.innerHTML = data.data.map(r => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">${escapeHtml(r.name)}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">${reportTypeLabels[r.report_type] || r.report_type}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">${scheduleLabels[r.schedule_type]} ${r.schedule_time?.substring(0,5) || ''}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">${r.recipients?.length || 0} คน</td>
                            <td class="px-4 py-3 text-sm text-gray-500">${r.last_sent_at ? formatDateTime(r.last_sent_at) : '-'}</td>
                            <td class="px-4 py-3">
                                <button onclick="toggleStatus(${r.id})" class="relative w-10 h-5 rounded-full transition-colors ${r.is_active ? 'bg-green-500' : 'bg-gray-300'}">
                                    <span class="absolute top-0.5 ${r.is_active ? 'right-0.5' : 'left-0.5'} w-4 h-4 bg-white rounded-full shadow transition-all"></span>
                                </button>
                            </td>
                            <td class="px-4 py-3 flex items-center gap-1">
                                <button onclick="runNow(${r.id})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="ส่งทันที">
                                    <i class="ri-send-plane-line"></i>
                                </button>
                                <button onclick="editReport(${r.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="แก้ไข">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button onclick="deleteReport(${r.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="ลบ">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400"><i class="ri-calendar-schedule-line text-4xl block mb-2"></i>ยังไม่มีรายงานที่ตั้งไว้<br><button onclick="openModal()" class="mt-3 text-primary hover:underline">+ สร้างรายงานใหม่</button></td></tr>';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-400">เกิดข้อผิดพลาด</td></tr>';
            }
        }

        function openModal(report = null) {
            document.getElementById('modal-title').textContent = report ? 'แก้ไขรายงาน' : 'สร้างรายงานใหม่';
            document.getElementById('report-id').value = report?.id || '';
            document.getElementById('report-name').value = report?.name || '';
            document.getElementById('report-type').value = report?.report_type || '';
            document.getElementById('schedule-type').value = report?.schedule_type || 'daily';
            document.getElementById('schedule-time').value = report?.schedule_time?.substring(0, 5) || '08:00';
            // Clone the array to avoid modifying the original report data by reference
            currentRecipients = report?.recipients ? [...report.recipients] : [];
            renderRecipientTags();
            document.getElementById('recipientSearch').value = '';
            document.getElementById('recipientResults').classList.add('hidden');
            updateDayField();
            if (report?.schedule_day) document.getElementById('schedule-day').value = report.schedule_day;
            document.getElementById('report-modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('report-modal').classList.add('hidden');
        }

        function updateDayField() {
            const type = document.getElementById('schedule-type').value;
            const dayField = document.getElementById('day-field');
            const daySelect = document.getElementById('schedule-day');
            const dayLabel = document.getElementById('day-label');

            if (type === 'daily') {
                dayField.classList.add('hidden');
            } else {
                dayField.classList.remove('hidden');
                daySelect.innerHTML = '';

                if (type === 'weekly') {
                    dayLabel.textContent = 'วันในสัปดาห์';
                    for (let i = 1; i <= 7; i++) {
                        daySelect.innerHTML += `<option value="${i}">${dayNames[i]}</option>`;
                    }
                } else { // monthly
                    dayLabel.textContent = 'วันที่';
                    for (let i = 1; i <= 28; i++) {
                        daySelect.innerHTML += `<option value="${i}">${i}</option>`;
                    }
                }
            }
        }

        async function saveReport() {
            const id = document.getElementById('report-id').value;
            const name = document.getElementById('report-name').value.trim();
            const reportType = document.getElementById('report-type').value;

            // Validation
            if (!name) {
                alert('กรุณาใส่ชื่อรายงาน');
                return;
            }
            if (!reportType) {
                alert('กรุณาเลือกประเภทรายงาน');
                return;
            }
            if (currentRecipients.length === 0) {
                alert('กรุณาเพิ่มอย่างน้อย 1 ผู้รับ');
                return;
            }

            const payload = {
                id: id || undefined,
                name: name,
                report_type: reportType,
                schedule_type: document.getElementById('schedule-type').value,
                schedule_time: document.getElementById('schedule-time').value,
                schedule_day: document.getElementById('schedule-type').value !== 'daily' ? document.getElementById('schedule-day').value : null,
                recipients: currentRecipients
            };

            try {
                const res = await fetch(`${API_BASE_URL}?action=${id ? 'update' : 'create'}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload),
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    closeModal();
                    loadReports();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                alert('Error saving report: ' + e.message);
            }
        }

        async function editReport(id) {
            try {
                const res = await fetch(`${API_BASE_URL}?action=list`, {
                    credentials: 'include'
                });
                const data = await res.json();
                const report = data.data.find(r => r.id === id);
                if (report) openModal(report);
            } catch (e) {}
        }

        async function deleteReport(id) {
            if (!confirm('ต้องการลบรายงานนี้?')) return;

            try {
                const res = await fetch(`${API_BASE_URL}?action=delete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id
                    }),
                    credentials: 'include'
                });
                const data = await res.json();
                if (data.success) loadReports();
            } catch (e) {}
        }

        async function toggleStatus(id) {
            try {
                await fetch(`${API_BASE_URL}?action=toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id
                    }),
                    credentials: 'include'
                });
                loadReports();
            } catch (e) {}
        }

        async function runNow(id) {
            const result = await Swal.fire({
                title: 'ยืนยันการส่งรายงาน?',
                text: "รายงานจะถูกส่งทางอีเมลทันที",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#A21D21',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ส่งทันที',
                cancelButtonText: 'ยกเลิก'
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'กำลังส่งข้อมูล...',
                text: 'กรุณารอสักครู่ ระบบกำลังสร้างรายงานและส่งอีเมล',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const res = await fetch(`${API_BASE_URL}?action=run`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id
                    }),
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    await Swal.fire({
                        title: 'ส่งสำเร็จ!',
                        text: `ส่งรายงานให้ ${data.sent_count}/${data.total_recipients} คนเรียบร้อยแล้ว`,
                        icon: 'success',
                        confirmButtonColor: '#A21D21'
                    });
                    loadReports();
                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#A21D21'
                    });
                }
            } catch (e) {
                console.error(e);
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ หรือเกิดข้อผิดพลาดภายใน (ตรวจสอบ Console)',
                    icon: 'error',
                    confirmButtonColor: '#A21D21'
                });
            }
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
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        // Recipient tag functions
        function renderRecipientTags() {
            const container = document.getElementById('recipientTags');
            if (currentRecipients.length === 0) {
                container.innerHTML = '<span class="text-gray-400 text-sm">ยังไม่มีผู้รับ</span>';
            } else {
                container.innerHTML = currentRecipients.map((email, idx) => `
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-full text-sm">
                        <i class="ri-mail-line text-primary"></i>
                        ${escapeHtml(email)}
                        <button type="button" class="text-gray-400 hover:text-red-500" onclick="removeRecipient(${idx})">&times;</button>
                    </span>
                `).join('');
            }
        }

        function searchRecipient(query) {
            clearTimeout(searchTimeout);
            const resultsDiv = document.getElementById('recipientResults');

            if (!query || query.length < 2) {
                resultsDiv.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(async () => {
                try {
                    const res = await fetch(`${SEARCH_API}&query=${encodeURIComponent(query)}`, {
                        credentials: 'include'
                    });
                    const data = await res.json();

                    if (data.success && data.employees && data.employees.length > 0) {
                        const filtered = data.employees.filter(emp => !currentRecipients.includes(emp.email));
                        if (filtered.length > 0) {
                            resultsDiv.innerHTML = filtered.map(emp => `
                                <div class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick='addRecipient("${emp.email}", "${emp.name || emp.email}")'>
                                    <div class="w-8 h-8 bg-gradient-to-br from-primary to-red-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">${emp.name || emp.email}</div>
                                        <div class="text-xs text-gray-500">${emp.email}</div>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded ${emp.source === 'microsoft' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'}">${emp.source === 'microsoft' ? 'MS' : 'DB'}</span>
                                </div>
                            `).join('');
                            resultsDiv.classList.remove('hidden');
                        } else {
                            resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">ผู้รับทั้งหมดถูกเพิ่มแล้ว</div>';
                            resultsDiv.classList.remove('hidden');
                        }
                    } else {
                        resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">ไม่พบข้อมูล</div>';
                        resultsDiv.classList.remove('hidden');
                    }
                } catch (e) {
                    console.error('Search error:', e);
                }
            }, 300);
        }

        function addRecipient(email, name) {
            if (currentRecipients.includes(email)) return;
            currentRecipients.push(email);
            document.getElementById('recipientSearch').value = '';
            document.getElementById('recipientResults').classList.add('hidden');
            renderRecipientTags();
        }

        function removeRecipient(index) {
            currentRecipients.splice(index, 1);
            renderRecipientTags();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.relative')) {
                document.getElementById('recipientResults')?.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', loadReports);
    </script>
</body>

</html>