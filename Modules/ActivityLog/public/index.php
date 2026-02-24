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
$emailLogPerm = userHasModuleAccess('EMAIL_LOGS', (int)$user['role_id']);
$scheduledPerm = userHasModuleAccess('SCHEDULED_REPORTS', (int)$user['role_id']);

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

        /* Modal Glassmorphism */
        .modal-overlay {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.2s ease-out forwards;
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
                        <p class="text-gray-500 text-sm">กิจกรรมวันนี้ (รวม)</p>
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
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Login สำเร็จ (วันนี้)</p>
                        <p class="text-3xl font-bold text-emerald-600 mt-1" id="stat-logins-today">-</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center">
                        <i class="ri-checkbox-circle-line text-2xl text-emerald-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Login ล้มเหลว (วันนี้)</p>
                        <div class="flex items-baseline gap-2">
                            <p class="text-3xl font-bold text-red-600 mt-1" id="stat-failed-logins">-</p>
                            <span class="text-xs text-red-400 font-medium">attempts</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                        <i class="ri-error-warning-line text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-stretch">
            <!-- Activity Trend Chart -->
            <div class="lg:col-span-2 bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex flex-col h-full">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-line-chart-line text-primary"></i>
                    แนวโน้มกิจกรรม 7 วันล่าสุด
                </h3>
                <div class="flex-1 min-h-[300px] w-full relative">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- Device Distribution Chart -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex flex-col h-full">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="ri-pie-chart-line text-primary"></i>
                    สัดส่วนอุปกรณ์ (30 วัน)
                </h3>
                <div class="flex-1 flex flex-col justify-center">
                    <div class="h-64 relative w-full">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
                <!-- Custom Legend -->
                <div id="device-legend" class="mt-4 grid grid-cols-2 gap-3"></div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Top Users -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-trophy-line text-amber-500"></i>
                        ผู้ใช้งานที่มีกิจกรรมสูงสุด (7 วัน)
                    </h3>
                </div>
                <div class="p-4" id="top-users-container">
                    <div class="text-center py-8 text-gray-400">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </div>
                </div>
            </div>

            <!-- System Activity Feed (Audit Log) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i class="ri-history-line text-blue-500"></i>
                        รายการแจ้งเตือนกิจกรรมระบบ (System Feed)
                    </h3>
                </div>
                <div class="p-4 max-h-[400px] overflow-y-auto" id="system-feed-container">
                    <div class="text-center py-8 text-gray-400">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Timeline -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="ri-login-box-line text-primary"></i>
                    Login Timeline
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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address / Device / Location</th>
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

    <!-- Device Details Modal -->
    <div id="deviceModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="fixed inset-0 modal-overlay" onclick="closeDeviceModal()"></div>
        <div class="relative w-full max-w-sm rounded-2xl modal-content border border-white p-6 animate-fade-in">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div id="modalDeviceIcon" class="w-12 h-12 flex items-center justify-center rounded-xl shadow-sm"></div>
                    <div>
                        <h4 class="text-lg font-bold text-gray-900" id="modalDeviceTitle">Device Details</h4>
                        <p class="text-xs text-gray-500" id="modalDeviceSub">Detailed technical information</p>
                    </div>
                </div>
                <button onclick="closeDeviceModal()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition-colors">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Brand & Model</p>
                    <p class="text-sm text-gray-800 font-medium" id="modalValueDevice">-</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Operating System</p>
                    <p class="text-sm text-gray-800 font-medium" id="modalValueOS">-</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Browser / Client</p>
                    <p class="text-sm text-gray-800 font-medium" id="modalValueClient">-</p>
                </div>
            </div>

            <button onclick="closeDeviceModal()" class="w-full mt-6 py-2.5 bg-primary text-white rounded-xl font-medium hover:bg-primary-dark transition-all active:scale-[0.98] shadow-lg shadow-primary/20">
                ตกลง
            </button>
        </div>
    </div>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php/activity';

        let trendChart = null;
        let deviceChart = null;
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
                    document.getElementById('stat-failed-logins').textContent = s.failed_logins_today?.toLocaleString() || '0';

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

        async function loadDeviceStats() {
            try {
                const res = await fetch(`${API_BASE_URL}?action=device-stats&days=30`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    const ctx = document.getElementById('deviceChart').getContext('2d');
                    if (deviceChart) deviceChart.destroy();

                    // Modern muted color palette (Pastel/Soft to match legend backgrounds)
                    const colorMap = {
                        'desktop': '#bfdbfe', // Blue-200
                        'smartphone': '#fecaca', // Red-200
                        'tablet': '#fde68a', // Amber-200
                        'bot': '#e5e7eb', // Gray-200
                        'unknown': '#e5e7eb' // Gray-200
                    };

                    const iconMap = {
                        'desktop': 'ri-computer-fill',
                        'smartphone': 'ri-smartphone-fill',
                        'tablet': 'ri-tablet-fill',
                        'bot': 'ri-robot-fill',
                        'unknown': 'ri-question-fill'
                    };

                    const styleMap = {
                        'desktop': 'bg-blue-50 text-[#3b82f6] border-blue-100',
                        'smartphone': 'bg-red-50 text-[#A21D21] border-red-100',
                        'tablet': 'bg-amber-50 text-[#f59e0b] border-amber-100',
                        'bot': 'bg-gray-50 text-[#6b7280] border-gray-200',
                        'unknown': 'bg-gray-50 text-[#9ca3af] border-gray-200'
                    };

                    const labels = data.data.map(d => d.device_type);
                    const bgColors = labels.map(l => colorMap[l] || colorMap['unknown']);

                    deviceChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data.data.map(d => d.count),
                                backgroundColor: bgColors,
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    display: false // Hide default legend
                                }
                            }
                        }
                    });

                    // Render Custom Legend
                    renderDeviceLegend(data.data, styleMap, iconMap);
                }
            } catch (e) {
                console.error('Device stats error:', e);
            }
        }

        function renderDeviceLegend(data, styleMap, iconMap) {
            const container = document.getElementById('device-legend');
            const total = data.reduce((sum, item) => sum + item.count, 0);

            container.innerHTML = data.map(item => {
                const type = item.device_type;
                const style = styleMap[type] || styleMap['unknown'];
                const icon = iconMap[type] || iconMap['unknown'];
                const percent = Math.round((item.count / total) * 100);
                const label = type.charAt(0).toUpperCase() + type.slice(1);

                return `
                    <div class="flex items-center gap-3 p-2 rounded-lg bg-gray-50 border border-gray-100">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center border shadow-sm ${style}">
                            <i class="${icon}"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500">${label}</p>
                            <p class="text-sm font-bold text-gray-900">${percent}% <span class="text-xs font-normal text-gray-400">(${item.count})</span></p>
                        </div>
                    </div>
                `;
            }).join('');
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

        async function loadSystemFeed() {
            const container = document.getElementById('system-feed-container');
            try {
                const res = await fetch(`${API_BASE_URL}?action=system-audit-summary&limit=10`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    container.innerHTML = `
                        <div class="relative pl-4 space-y-6 before:absolute before:left-[21px] before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-100">
                            ${data.data.map(item => `
                                <div class="flex gap-4 relative">
                                    <div class="mt-1.5 w-3 h-3 rounded-full bg-white border-2 ${getAuditColor(item.action)} flex-shrink-0 z-10"></div>
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm font-semibold text-gray-900 capitalize">${item.action.replace('_', ' ')}: ${item.entity_type}</span>
                                            <span class="text-[10px] text-gray-400 font-medium whitespace-nowrap">${formatDateTime(item.performed_at)}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 line-clamp-1">Performed by <span class="text-gray-700 font-medium">${item.performed_by || 'System'}</span></p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    container.innerHTML = '<div class="text-center py-8 text-gray-400">ไม่มีความเคลื่อนไหวในระบบ</div>';
                }
            } catch (e) {
                container.innerHTML = '<div class="text-center py-8 text-red-400">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        }

        function getAuditColor(action) {
            if (action.includes('delete') || action.includes('remove')) return 'border-red-500';
            if (action.includes('update') || action.includes('edit')) return 'border-blue-500';
            if (action.includes('create') || action.includes('add')) return 'border-emerald-500';
            return 'border-gray-400';
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
                            <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">${formatDateTime(a.created_at)}</td>
                            <td class="px-4 py-3 font-medium text-gray-900 max-w-[150px] md:max-w-[250px] truncate" title="${escapeHtml(a.user_name)}">
                                ${escapeHtml(a.user_name)}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-xs font-medium ${getActionClass(a.action)}">${getActionLabel(a.action)}</span>
                                ${a.action === 'login_failed' && a.details ? `<div class="text-[11px] text-red-500 mt-1 font-medium truncate max-w-[200px]" title="${escapeHtml(a.details)}">${escapeHtml(a.details)}</div>` : ''}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                <div class="flex items-center gap-4">
                                    <span class="inline-block w-[110px] font-mono text-gray-600 font-medium truncate" title="${a.ip_address || ''}">${a.ip_address || '-'}</span>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-7 h-7 flex items-center justify-center">${getDeviceIcon(a)}</div>
                                        <div class="w-7 h-7 flex items-center justify-center">${getLocationLink(a.latitude, a.longitude)}</div>
                                    </div>
                                </div>
                            </td>
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
            try {
                const d = new Date(dateStr.replace(/-/g, '/'));
                return d.toLocaleString('th-TH', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return dateStr;
            }
        }

        function getDeviceIcon(a) {
            const deviceType = a.device_type;
            if (!deviceType) return '<div class="w-7 h-7 flex items-center justify-center rounded-lg bg-gray-50 text-gray-300 border border-gray-100"><i class="ri-question-fill text-xs"></i></div>';

            const type = deviceType.toLowerCase();
            let icon = 'ri-device-fill';
            let colorClass = 'bg-gray-50 text-gray-500 border-gray-100';
            let label = deviceType;

            if (type.includes('smartphone') || type.includes('mobile')) {
                icon = 'ri-smartphone-fill';
                // smartphone: #A21D21 (Red)
                colorClass = 'bg-red-50 text-[#A21D21] border-red-100';
                label = 'Mobile';
            } else if (type.includes('tablet')) {
                icon = 'ri-tablet-fill';
                // tablet: #f59e0b (Amber)
                colorClass = 'bg-amber-50 text-[#f59e0b] border-amber-100';
                label = 'Tablet';
            } else if (type.includes('desktop') || type.includes('pc')) {
                icon = 'ri-computer-fill';
                // desktop: #3b82f6 (Blue)
                colorClass = 'bg-blue-50 text-[#3b82f6] border-blue-100';
                label = 'Desktop';
            } else if (type.includes('bot')) {
                icon = 'ri-robot-fill';
                // bot: #6b7280 (Gray)
                colorClass = 'bg-gray-50 text-[#6b7280] border-gray-200';
                label = 'Bot';
            } else {
                // unknown: #9ca3af (Light Gray)
                colorClass = 'bg-gray-50 text-[#9ca3af] border-gray-200';
                label = 'Unknown';
            }

            const details = {
                brand: a.device_brand || '',
                model: a.device_model || '',
                os_name: a.os_name || '',
                os_version: a.os_version || '',
                client_name: a.client_name || '',
                client_version: a.client_version || '',
                icon: icon,
                colorClass: colorClass
            };

            const detailsStr = JSON.stringify(details).replace(/"/g, '&quot;');

            return `
                <div class="w-7 h-7 flex items-center justify-center rounded-lg ${colorClass} border transition-all shadow-sm hover:scale-110 active:scale-95 device-details-trigger" 
                     title="Click for details" 
                     data-details="${detailsStr}"
                     style="cursor: pointer;">
                    <i class="${icon} text-sm"></i>
                </div>
            `;
        }

        // Delegate click for device details
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('.device-details-trigger');
            if (trigger) {
                const detailsJson = trigger.getAttribute('data-details');
                showDeviceDetails(detailsJson);
            }
        });

        window.showDeviceDetails = function(detailsJson) {
            const d = JSON.parse(detailsJson);

            const modal = document.getElementById('deviceModal');
            const iconContainer = document.getElementById('modalDeviceIcon');

            iconContainer.className = `w-12 h-12 flex items-center justify-center rounded-xl shadow-sm ${d.colorClass}`;
            iconContainer.innerHTML = `<i class="${d.icon} text-2xl"></i>`;

            document.getElementById('modalDeviceTitle').textContent = d.brand || 'Unknown Device';
            document.getElementById('modalValueDevice').textContent = (d.brand + ' ' + d.model).trim() || '-';
            document.getElementById('modalValueOS').textContent = (d.os_name + ' ' + d.os_version).trim() || '-';
            document.getElementById('modalValueClient').textContent = (d.client_name + ' ' + d.client_version).trim() || '-';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        };

        window.closeDeviceModal = function() {
            const modal = document.getElementById('deviceModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        };

        function getLocationLink(lat, lng) {
            if (!lat || !lng) return '<div class="w-7 h-7"></div>'; // Empty track for alignment
            return `
                <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" 
                   class="w-7 h-7 flex items-center justify-center rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-all hover:scale-110 shadow-sm border border-emerald-500"
                   title="ดูตำแหน่งใน Google Maps">
                    <i class="ri-map-pin-2-fill text-xs"></i>
                </a>
            `;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        function getActionLabel(action) {
            const map = {
                'login': 'เข้าสู่ระบบ (สำเร็จ)',
                'login_failed': 'เข้าสู่ระบบ (ล้มเหลว)',
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
            if (action === 'login_failed') return 'bg-red-100 text-red-700';
            if (action === 'logout') return 'bg-gray-100 text-gray-600';
            if (action.includes('create') || action.includes('check_in')) return 'bg-emerald-100 text-emerald-700';
            if (action.includes('update') || action.includes('approve')) return 'bg-blue-100 text-blue-700';
            if (action.includes('delete') || action.includes('cancel') || action.includes('reject')) return 'bg-red-100 text-red-700';
            return 'bg-gray-100 text-gray-600';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboard();
            loadDeviceStats();
            loadTopUsers();
            loadSystemFeed();
            loadTimeline();
        });
    </script>
</body>

</html>