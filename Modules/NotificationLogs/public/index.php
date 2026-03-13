<?php

/**
 * Notification Logs Viewer - View unified notification history (Email & In-App)
 */

require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';

$basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/Modules/NotificationLogs/public$#i', '', $scriptDir);
}
if ($basePath === '') $basePath = '/';
$baseRoot = rtrim($basePath, '/');

// Determine asset base
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

$userPerms = userHasModuleAccess('HR_SERVICES', (int)$user['role_id']);
$hrNewsPerm = userHasModuleAccess('HR_NEWS', (int)$user['role_id']);
$permManage = userHasModuleAccess('PERMISSION_MANAGEMENT', (int)$user['role_id']);
$activityPerm = userHasModuleAccess('ACTIVITY_DASHBOARD', (int)$user['role_id']);
$emailLogPerm = userHasModuleAccess('EMAIL_LOGS', (int)$user['role_id']);
$notifLogPerm = userHasModuleAccess('NOTIFICATION_LOGS', (int)$user['role_id']);
$scheduledPerm = userHasModuleAccess('SCHEDULED_REPORTS', (int)$user['role_id']);

// Check Notification Logs permission
if (empty($notifLogPerm['can_view'])) {
    header('Location: ' . $linkBase . 'Modules/HRServices/public/index.php?error=no_permission');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Logs | MyHR Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="<?= $assetBase ?>assets/css/tailwind.css">
    <script>
        window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
    </script>
    <script src="<?= $assetBase ?>assets/js/i18n.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        /* Custom Modern Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
            transition: background 0.2s;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* For Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f1f1;
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
                    <i class="ri-notification-3-line text-primary" style="color:#A21D21;"></i>
                    Notification History
                </h1>
                <p class="text-gray-500 text-sm mt-1">ประวัติการแจ้งเตือนภายในระบบ (In-App Notifications)</p>
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
                        <p class="text-gray-500 text-sm">การแจ้งเตือนทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" id="stat-total">-</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="ri-notification-line text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">อ่านแล้ว</p>
                        <p class="text-3xl font-bold text-green-600 mt-1" id="stat-success">-</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="ri-mail-open-line text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">ยังไม่อ่าน</p>
                        <p class="text-3xl font-bold text-red-600 mt-1" id="stat-failed">-</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="ri-mail-unread-line text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">วันนี้</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" id="stat-today">-</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                        <i class="ri-calendar-line text-2xl text-amber-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <input type="text" id="search-input" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" placeholder="ค้นหาหัวข้อ หรือข้อความ...">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <select id="filter-type" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                    <option value="">-- ทุกประเภท --</option>
                    <option value="info">Info</option>
                    <option value="success">Success</option>
                    <option value="warning">Warning</option>
                    <option value="error">Error</option>
                </select>
                <input type="date" id="filter-start" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                <input type="date" id="filter-end" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                <button onclick="loadLogs(1)" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors" style="background:#A21D21;">
                    <i class="ri-search-line"></i> ค้นหา
                </button>
                <button onclick="resetFilters()" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="ri-refresh-line"></i> ล้าง
                </button>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">เวลา</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">ความสำคัญ</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="max-width:280px;">หัวข้อ</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">ส่งถึง</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">สถานะการส่ง</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">สถานะการอ่าน</th>
                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody" class="divide-y divide-gray-100">
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <span id="logs-info" class="text-sm text-gray-500"></span>
                <div id="logs-pagination" class="flex items-center gap-2"></div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detail-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
                    <i class="ri-information-line text-primary" style="color:#A21D21;"></i>
                    รายละเอียดการแจ้งเตือน
                </h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto flex-1" id="detail-content"></div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 text-right">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300">ปิด</button>
            </div>
        </div>
    </div>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php/notification_logs';
        let currentPage = 1;

        // Load stats
        async function loadStats() {
            try {
                const res = await fetch(`${API_BASE_URL}?action=stats`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    // Update stats directly from backend
                    document.getElementById('stat-total').textContent = data.data.total?.toLocaleString() || '0';
                    document.getElementById('stat-success').textContent = data.data.success?.toLocaleString() || '0';
                    document.getElementById('stat-failed').textContent = data.data.failed?.toLocaleString() || '0';
                    document.getElementById('stat-today').textContent = data.data.today?.toLocaleString() || '0';
                }
            } catch (e) {
                console.error('Stats error:', e);
            }
        }

        // Load logs
        async function loadLogs(page = 1) {
            currentPage = page;
            const tbody = document.getElementById('logs-tbody');
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400"><div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div></td></tr>';

            const params = new URLSearchParams({
                action: 'list',
                page: page,
                search: document.getElementById('search-input').value,
                type: document.getElementById('filter-type').value,
                start_date: document.getElementById('filter-start').value,
                end_date: document.getElementById('filter-end').value
            });

            try {
                const res = await fetch(`${API_BASE_URL}?${params}`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    tbody.innerHTML = data.data.map(log => `
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-3 text-sm text-gray-600 font-mono whitespace-nowrap">${formatDateTime(log.created_at)}</td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${getTypeBadgeClass(log.type)}">
                                    ${log.type}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-sm font-medium text-gray-900" style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(log.title)}</td>
                            <td class="px-3 py-3 text-sm whitespace-nowrap">
                                <div class="font-medium text-gray-900">${escapeHtml(log.recipient_name || 'System')}</div>
                                <div class="text-[10px] text-gray-500 font-mono">${escapeHtml(log.recipient_code || '-')}</div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 whitespace-nowrap">สำเร็จ</span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap ${log.is_read ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700'}">
                                    ${log.is_read ? 'อ่านแล้ว' : 'ยังไม่อ่าน'}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <button onclick="viewDetail(${log.id})" class="p-1.5 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-lg">
                                    <i class="ri-eye-line"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');

                    document.getElementById('logs-info').textContent = `หน้า ${data.page} จาก ${data.total_pages} (${data.total} รายการ)`;

                    const pag = document.getElementById('logs-pagination');
                    pag.innerHTML = `
                        <button onclick="loadLogs(${page - 1})" ${page <= 1 ? 'disabled' : ''} class="px-3 py-1 rounded border ${page <= 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}">
                            <i class="ri-arrow-left-s-line"></i>
                        </button>
                        <button onclick="loadLogs(${page + 1})" ${page >= data.total_pages ? 'disabled' : ''} class="px-3 py-1 rounded border ${page >= data.total_pages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-100'}">
                            <i class="ri-arrow-right-s-line"></i>
                        </button>
                    `;
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400"><i class="ri-notification-3-line text-3xl block mb-2"></i>ไม่พบข้อมูล</td></tr>';
                    document.getElementById('logs-info').textContent = '';
                    document.getElementById('logs-pagination').innerHTML = '';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-400">เกิดข้อผิดพลาด</td></tr>';
            }
        }

        function getTypeBadgeClass(type) {
            const classes = {
                info: 'bg-blue-100 text-blue-700',
                success: 'bg-green-100 text-green-700',
                warning: 'bg-orange-100 text-orange-700',
                error: 'bg-red-100 text-red-700'
            };
            return classes[type] || 'bg-gray-100 text-gray-700';
        }

        async function viewDetail(id) {
            try {
                const res = await fetch(`${API_BASE_URL}?action=detail&id=${id}`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    const log = data.data;
                    let parsedData = {};
                    try {
                        parsedData = typeof log.data === 'string' ? JSON.parse(log.data) : (log.data || {});
                    } catch (e) {
                        console.error('Data parsing error:', e);
                    }

                    // Resolve link placeholders if any (e.g. {id} -> parsedData.id)
                    let finalLink = log.link;
                    if (finalLink && Object.keys(parsedData).length > 0) {
                        for (const key in parsedData) {
                            finalLink = finalLink.replace(new RegExp(`{${key}}`, 'g'), parsedData[key]);
                        }
                    }

                    document.getElementById('detail-content').innerHTML = `
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-xs text-gray-400 block uppercase mb-1">เวลาที่บันทึก (Created)</span>
                                    <span class="font-medium text-gray-900 font-mono">${formatDateTime(log.created_at)}</span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-xs text-gray-400 block uppercase mb-1">สถานะการอ่าน (Read Status)</span>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold ${log.is_read ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                            ${log.is_read ? 'READ' : 'UNREAD'}
                                        </span>
                                        ${log.read_at ? `<span class="text-[10px] text-gray-500 font-mono">${formatDateTime(log.read_at)}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-xs text-gray-400 block uppercase mb-1">ความสำคัญ (Type)</span>
                                    <span class="font-bold text-gray-700 uppercase flex items-center gap-1">
                                        <i class="ri-checkbox-blank-circle-fill text-[8px] ${getTypeBadgeClass(log.type).replace('bg-', 'text-').split(' ')[1]}"></i>
                                        ${log.type}
                                    </span>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-xs text-gray-400 block uppercase mb-1">สถานะการส่ง (Delivery)</span>
                                    <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                        SUCCESS
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-xs text-gray-400 block uppercase mb-1">ส่งถึง (Recipient)</span>
                                    <div class="font-bold text-gray-900">${escapeHtml(log.recipient_name || 'System')}</div>
                                    <div class="text-[10px] text-gray-500 font-mono">${escapeHtml(log.recipient_code || '-')}</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <span class="text-xs text-gray-400 block uppercase mb-1">ลิงก์ / ข้อมูล (Action/Data)</span>
                                    <div class="text-[10px] text-gray-500 font-mono truncate">ID: ${log.id}</div>
                                    <div class="text-[10px] text-gray-500 font-mono truncate">Linked: ${log.link ? 'Yes' : 'No'}</div>
                                </div>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <span class="text-xs text-gray-400 block uppercase mb-2 font-bold flex items-center gap-1">
                                    <i class="ri-text-snippet text-primary" style="color:#A21D21;"></i>
                                    หัวเรื่อง (Title)
                                </span>
                                <div class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-100 text-sm font-bold text-gray-900">
                                    ${escapeHtml(log.title || '-')}
                                </div>
                            </div>

                            <div class="pt-2">
                                <span class="text-xs text-gray-400 block uppercase mb-2 font-bold flex items-center gap-1">
                                    <i class="ri-message-3-line text-primary" style="color:#A21D21;"></i>
                                    เนื้อหา (Message Content)
                                </span>
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 text-sm text-gray-800 leading-relaxed min-h-[80px] whitespace-pre-wrap">
                                    ${escapeHtml(log.message || '-')}
                                </div>
                            </div>

                            ${finalLink ? `
                            <div class="pt-2">
                                <span class="text-xs text-gray-400 block uppercase mb-2 font-bold flex items-center gap-1">
                                    <i class="ri-link text-primary" style="color:#A21D21;"></i>
                                    ลิงก์ (Action Link)
                                </span>
                                <div class="space-y-2">
                                    <a href="${finalLink}" target="_blank" class="text-sm text-blue-600 hover:underline break-all block p-3 bg-blue-50 rounded-lg border border-blue-100">
                                        <i class="ri-external-link-line mr-1"></i> ${escapeHtml(finalLink)}
                                    </a>
                                    ${log.link !== finalLink ? `<div class="text-[10px] text-gray-400 italic font-mono">Original: ${escapeHtml(log.link)}</div>` : ''}
                                </div>
                            </div>
                            ` : ''}

                            ${Object.keys(parsedData).length > 0 ? `
                            <div class="pt-2">
                                <span class="text-xs text-gray-400 block uppercase mb-2 font-bold flex items-center gap-1">
                                    <i class="ri-database-2-line text-primary" style="color:#A21D21;"></i>
                                    ข้อมูลเพิ่มเติม (Data)
                                </span>
                                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <pre class="text-[11px] text-gray-700 font-mono overflow-x-auto whitespace-pre-wrap">${escapeHtml(JSON.stringify(parsedData, null, 2))}</pre>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;
                    document.getElementById('detail-modal').classList.remove('hidden');
                }
            } catch (e) {
                console.error('Detail error:', e);
            }
        }

        function closeModal() {
            document.getElementById('detail-modal').classList.add('hidden');
        }

        function resetFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('filter-type').value = '';
            document.getElementById('filter-status').value = '';
            document.getElementById('filter-start').value = '';
            document.getElementById('filter-end').value = '';
            loadLogs(1);
        }

        function formatDateTime(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleString('th-TH', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadLogs();
        });

        // Enter key search
        document.getElementById('search-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') loadLogs(1);
        });
    </script>
</body>

</html>
