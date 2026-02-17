<?php

/**
 * Email Logs Viewer - View email sending history
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
    $basePath = preg_replace('#/Modules/EmailLogs/public$#i', '', $scriptDir);
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

// Check Email Logs permission
if (empty($emailLogPerm['can_view'])) {
    header('Location: ' . $linkBase . 'Modules/HRServices/public/index.php?error=no_permission');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Logs | MyHR Portal</title>
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
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/../../../public/includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <i class="ri-mail-send-line text-primary"></i>
                    Email Logs
                </h1>
                <p class="text-gray-500 text-sm mt-1">ดูประวัติการส่งอีเมลทั้งหมดในระบบ</p>
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
                        <p class="text-gray-500 text-sm">อีเมลทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1" id="stat-total">-</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="ri-mail-line text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">ส่งสำเร็จ</p>
                        <p class="text-3xl font-bold text-green-600 mt-1" id="stat-success">-</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="ri-check-line text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">ส่งไม่สำเร็จ</p>
                        <p class="text-3xl font-bold text-red-600 mt-1" id="stat-failed">-</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="ri-close-line text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">ส่งวันนี้</p>
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
                    <input type="text" id="search-input" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" placeholder="ค้นหา email หรือ subject...">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <select id="filter-status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="sent">สำเร็จ</option>
                    <option value="failed">ไม่สำเร็จ</option>
                </select>
                <input type="date" id="filter-start" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                <input type="date" id="filter-end" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary">
                <button onclick="loadLogs(1)" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors">
                    <i class="ri-search-line"></i> ค้นหา
                </button>
                <button onclick="resetFilters()" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="ri-refresh-line"></i> ล้าง
                </button>
            </div>
        </div>

        <!-- Email Logs Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">เวลา</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้รับ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หัวข้อ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">สถานะ</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20"></th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody" class="divide-y divide-gray-100">
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400">
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
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[80vh] flex flex-col shadow-2xl mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
                    <i class="ri-mail-line text-primary"></i>
                    รายละเอียด Email
                </h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto flex-1" id="detail-content"></div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300">ปิด</button>
            </div>
        </div>
    </div>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE_URL = BASE_PATH + '/routes.php/email_logs';
        let currentPage = 1;

        // Load stats
        async function loadStats() {
            try {
                const res = await fetch(`${API_BASE_URL}?action=stats`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
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
            tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div></td></tr>';

            const params = new URLSearchParams({
                action: 'list',
                page: page,
                search: document.getElementById('search-input').value,
                status: document.getElementById('filter-status').value,
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
                            <td class="px-4 py-3 text-sm text-gray-600">${formatDateTime(log.created_at)}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${escapeHtml(log.recipient_email)}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="${escapeHtml(log.subject)}">${escapeHtml(log.subject)}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium ${['success','sent'].includes(log.status) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                    ${['success','sent'].includes(log.status) ? 'สำเร็จ' : 'ไม่สำเร็จ'}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button onclick="viewDetail(${log.id})" class="p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-lg">
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
                    tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><i class="ri-mail-line text-3xl block mb-2"></i>ไม่พบข้อมูล</td></tr>';
                    document.getElementById('logs-info').textContent = '';
                    document.getElementById('logs-pagination').innerHTML = '';
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-red-400">เกิดข้อผิดพลาด</td></tr>';
            }
        }

        async function viewDetail(id) {
            try {
                const res = await fetch(`${API_BASE_URL}?action=detail&id=${id}`, {
                    credentials: 'include'
                });
                const data = await res.json();

                if (data.success) {
                    const log = data.data;
                    document.getElementById('detail-content').innerHTML = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <span class="text-xs text-gray-400 block">เวลา</span>
                                    <span class="font-medium">${formatDateTime(log.created_at)}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 block">สถานะ</span>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${['success','sent'].includes(log.status) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                        ${['success','sent'].includes(log.status) ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ'}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block mb-1">ผู้รับ</span>
                                <span class="font-medium">${escapeHtml(log.recipient_email)}</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block mb-1">หัวข้อ</span>
                                <span class="font-medium">${escapeHtml(log.subject)}</span>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block mb-1">ตัวอย่างเนื้อหา</span>
                                <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-600">${escapeHtml(log.body_preview || '-')}</div>
                            </div>
                            ${log.error_message ? `
                            <div>
                                <span class="text-xs text-gray-400 block mb-1">Error</span>
                                <div class="p-3 bg-red-50 rounded-lg text-sm text-red-600">${escapeHtml(log.error_message)}</div>
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
                minute: '2-digit'
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