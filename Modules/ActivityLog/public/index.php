<?php

/**
 * Activity Dashboard - User Activity Monitoring
 * Shows login history, top users, top actions, and activity timeline
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
    $basePath = preg_replace('#/Modules/ActivityLog/public$#i', '', $scriptDir);
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
$activityPerm = userHasModuleAccess('ACTIVITY_DASHBOARD', (int)$user['role_id']);

// Check Activity Dashboard permission
if (empty($activityPerm['can_view'])) {
    header('Location: ' . $linkBase . 'Modules/HRServices/public/index.php?error=no_permission');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Dashboard | MyHR Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="<?= $assetBase ?>assets/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
    </script>
    <script src="<?= $assetBase ?>assets/js/i18n.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/../../../public/includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <i class="ri-dashboard-line text-primary"></i>
                    Activity Dashboard
                </h1>
                <p class="text-gray-500 text-sm mt-1">ดูประวัติการใช้งานและสถิติของผู้ใช้ในระบบ</p>
            </div>
            <a href="<?= $linkBase ?>Modules/HRServices/public/index.php" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                <i class="ri-arrow-left-line"></i> กลับ
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">กิจกรรมวันนี้</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" id="stat-activities-today">-</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="ri-pulse-line text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">ผู้ใช้ Active วันนี้</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" id="stat-active-users">-</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="ri-user-line text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Login วันนี้</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" id="stat-logins-today">-</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <i class="ri-login-box-line text-2xl text-amber-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">กิจกรรม 7 วัน</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" id="stat-activities-week">-</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="ri-calendar-line text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Activity Trend Chart -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-line-chart-line text-primary"></i>
                    แนวโน้มกิจกรรม 7 วันล่าสุด
                </h3>
                <div class="h-64">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Top Actions Chart -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-pie-chart-line text-primary"></i>
                    กิจกรรมที่ทำบ่อยที่สุด
                </h3>
                <div class="h-64">
                    <canvas id="actionsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Users -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-trophy-line text-amber-500"></i>
                        ผู้ใช้งานมากที่สุด (7 วัน)
                    </h3>
                </div>
                <div class="p-4" id="top-users-container">
                    <div class="text-center py-8 text-gray-400">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </div>
                </div>
            </div>

            <!-- Login History -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-time-line text-blue-500"></i>
                        ประวัติ Login/Logout ล่าสุด
                    </h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-[400px] overflow-y-auto" id="login-history-container">
                    <div class="text-center py-8 text-gray-400">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="ri-history-line text-primary"></i>
                    Activity Timeline
                </h3>
                <button onclick="loadTimeline()" class="text-primary hover:underline text-sm">
                    <i class="ri-refresh-line"></i> รีเฟรช
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เวลา</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ใช้</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        </tr>
                    </thead>
                    <tbody id="timeline-tbody" class="divide-y divide-gray-100">
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <span id="timeline-info" class="text-sm text-gray-500"></span>
                <div id="timeline-pagination" class="flex items-center gap-2"></div>
            </div>
        </div>
    </div>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php/activity';

        let trendChart = null;
        let actionsChart = null;
        let currentPage = 1;

        // Load dashboard data
        async function loadDashboard() {
            try {
                const res = await fetch(`${API_BASE_URL}?action=dashboard-stats`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    const s = data.data;
                    document.getElementById('stat-activities-today').textContent = s.activities_today?.toLocaleString() || '0';
                    document.getElementById('stat-active-users').textContent = s.active_users_today?.toLocaleString() || '0';
                    document.getElementById('stat-logins-today').textContent = s.logins_today?.toLocaleString() || '0';
                    document.getElementById('stat-activities-week').textContent = s.activities_week?.toLocaleString() || '0';

                    // Render trend chart
                    renderTrendChart(s.activity_trend || []);
                }
            } catch (e) {
                console.error('Dashboard error:', e);
            }
        }

        function renderTrendChart(data) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            if (trendChart) trendChart.destroy();

            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => {
                        const date = new Date(d.date);
                        return date.toLocaleDateString('th-TH', {
                            day: 'numeric',
                            month: 'short'
                        });
                    }),
                    datasets: [{
                        label: 'กิจกรรม',
                        data: data.map(d => d.count),
                        borderColor: '#A21D21',
                        backgroundColor: 'rgba(162, 29, 33, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        async function loadTopActions() {
            try {
                const res = await fetch(`${API_BASE_URL}?action=top-actions&days=7&limit=6`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    const ctx = document.getElementById('actionsChart').getContext('2d');
                    if (actionsChart) actionsChart.destroy();

                    const colors = ['#A21D21', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];

                    actionsChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.data.map(d => getActionLabel(d.action)),
                            datasets: [{
                                data: data.data.map(d => d.count),
                                backgroundColor: colors,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right'
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Top actions error:', e);
            }
        }

        async function loadTopUsers() {
            const container = document.getElementById('top-users-container');
            try {
                const res = await fetch(`${API_BASE_URL}?action=top-users&days=7&limit=5`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.map((u, i) => `
                        <div class="flex items-center gap-4 p-3 ${i > 0 ? 'border-t border-gray-100' : ''}">
                            <div class="w-8 h-8 bg-gradient-to-br from-primary to-primary-light rounded-full flex items-center justify-center text-white font-bold text-sm">
                                ${i + 1}
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${escapeHtml(u.user_name)}</div>
                                <div class="text-xs text-gray-500">${escapeHtml(u.department || '-')}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-primary">${u.activity_count}</div>
                                <div class="text-xs text-gray-400">activities</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="text-center py-8 text-gray-400">ไม่มีข้อมูล</div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center py-8 text-red-400">เกิดข้อผิดพลาด</div>';
            }
        }

        async function loadLoginHistory() {
            const container = document.getElementById('login-history-container');
            try {
                const res = await fetch(`${API_BASE_URL}?action=login-history&limit=10`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.map(l => `
                        <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50">
                            <div class="w-8 h-8 ${l.action === 'login' ? 'bg-green-100' : 'bg-gray-100'} rounded-full flex items-center justify-center">
                                <i class="${l.action === 'login' ? 'ri-login-box-line text-green-600' : 'ri-logout-box-r-line text-gray-500'}"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">${escapeHtml(l.user_name || 'Unknown')}</div>
                                <div class="text-xs text-gray-500">${l.ip_address || '-'}</div>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded text-xs font-medium ${l.action === 'login' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}">${l.action}</span>
                                <div class="text-xs text-gray-400 mt-1">${formatDateTime(l.created_at)}</div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<div class="text-center py-8 text-gray-400">ไม่มีข้อมูล</div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center py-8 text-red-400">เกิดข้อผิดพลาด</div>';
            }
        }

        async function loadTimeline(page = 1) {
            currentPage = page;
            const tbody = document.getElementById('timeline-tbody');
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div></td></tr>';

            try {
                const res = await fetch(`${API_BASE_URL}?action=activity-timeline&page=${page}&limit=15`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    tbody.innerHTML = data.data.map(a => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600">${formatDateTime(a.created_at)}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">${escapeHtml(a.user_name)}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium ${getActionClass(a.action)}">${getActionLabel(a.action)}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">${escapeHtml(a.entity_type || '-')}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">${a.ip_address || '-'}</td>
                        </tr>
                    `).join('');

                    // Pagination info
                    document.getElementById('timeline-info').textContent = `หน้า ${data.page} จาก ${data.total_pages} (${data.total} รายการ)`;

                    // Pagination controls
                    const pag = document.getElementById('timeline-pagination');
                    pag.innerHTML = `
                        <button onclick="loadTimeline(${page - 1})" ${page <= 1 ? 'disabled' : ''} class="px-3 py-1 rounded border ${page <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}">
                            <i class="ri-arrow-left-s-line"></i>
                        </button>
                        <button onclick="loadTimeline(${page + 1})" ${page >= data.total_pages ? 'disabled' : ''} class="px-3 py-1 rounded border ${page >= data.total_pages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}">
                            <i class="ri-arrow-right-s-line"></i>
                        </button>
                    `;
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">ไม่มีข้อมูล</td></tr>';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-red-400">เกิดข้อผิดพลาด</td></tr>';
            }
        }

        // Helpers
        function formatDateTime(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleString('th-TH', {
                day: 'numeric',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        function getActionLabel(action) {
            const map = {
                'login': 'เข้าสู่ระบบ',
                'logout': 'ออกจากระบบ',
                'create_booking': 'สร้างคำขอ',
                'approve_request': 'อนุมัติ',
                'reject_request': 'ปฏิเสธ',
                'cancel_request': 'ยกเลิก',
                'create_room': 'สร้างห้อง',
                'update_room': 'แก้ไขห้อง',
                'check_in': 'เช็คอิน',
                'check_out': 'เช็คเอาท์'
            };
            return map[action] || action;
        }

        function getActionClass(action) {
            if (action === 'login') return 'bg-green-100 text-green-700';
            if (action === 'logout') return 'bg-gray-100 text-gray-600';
            if (action.includes('create') || action.includes('check_in')) return 'bg-emerald-100 text-emerald-700';
            if (action.includes('update') || action.includes('approve')) return 'bg-blue-100 text-blue-700';
            if (action.includes('delete') || action.includes('cancel') || action.includes('reject')) return 'bg-red-100 text-red-700';
            return 'bg-gray-100 text-gray-600';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboard();
            loadTopActions();
            loadTopUsers();
            loadLoginHistory();
            loadTimeline();
        });
    </script>
</body>

</html>