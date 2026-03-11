<?php
$pageTitle = "Organization Chart";

require_once __DIR__ . '/../../core/Config/SessionConfig.php';
require_once __DIR__ . '/../../core/Database/Database.php';
require_once __DIR__ . '/../../core/Config/Env.php';
require_once __DIR__ . '/../../core/Security/AuthMiddleware.php';

$basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/Modules/OrgChart$#i', '', $scriptDir);
}

if ($basePath === '') $basePath = '/';
$baseRoot = rtrim($basePath, '/');
$linkBase = ($baseRoot ? $baseRoot . '/' : '/');

// This will automatically redirect if not logged in
$currentUser = AuthMiddleware::checkLogin($linkBase);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | INTEQC Group</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e3a8a',
                        secondary: '#1e40af',
                        accent: '#3b82f6',
                        background: '#f8fafc',
                    }
                }
            }
        }
    </script>
    <!-- Remix Icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet"> <!-- Include D3 and OrgChart Library -->
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/d3-org-chart@3"></script>
    <script src="https://cdn.jsdelivr.net/npm/d3-flextree@2.1.2/build/d3-flextree.js"></script>

    <style>
        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: #f8fafc;
        }

        .main-content {
            min-height: calc(100vh - 64px);
        }
    </style>
</head>

<body class="bg-gray-50 flex flex-col min-h-screen">

    <!-- Top Nav -->
    <nav class="bg-white border-b border-gray-200 fixed w-full z-30 top-0">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <a href="/public/index.php" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                            <i class="ri-blaze-line text-3xl text-primary"></i>
                            <span class="font-bold text-xl text-gray-800 tracking-tight">INTEQC<span class="text-primary"> HRPortal</span></span>
                        </a>
                    </div>
                </div>
                <!-- Right nav items -->
                <div class="flex items-center gap-4">
                    <a href="/public/index.php" class="text-gray-500 hover:text-primary transition-colors flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-blue-50">
                        <i class="ri-home-4-line text-xl"></i>
                        <span class="hidden sm:inline font-medium text-sm">หน้าแรก</span>
                    </a>

                    <!-- Profile dropdown -->
                    <div class="relative group ml-2">
                        <button class="flex items-center max-w-xs bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary p-1 hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                                <?php echo strtoupper(substr($currentUser['firstname'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="ml-3 hidden md:block text-sm font-medium text-gray-700">
                                <?php echo htmlspecialchars($currentUser['firstname'] ?? 'User'); ?>
                            </span>
                            <i class="ri-arrow-down-s-line ml-1 text-gray-400 hidden md:block"></i>
                        </button>

                        <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-xl shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 hidden group-hover:block transition-all">
                            <a href="/public/core/profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary">
                                <i class="ri-user-line text-lg"></i> โปรไฟล์
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="/public/auth/logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                <i class="ri-logout-box-r-line text-lg"></i> ออกจากระบบ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Layout -->
    <div class="pt-16 flex-1 flex flex-col overflow-hidden">
        <main class="flex-1 overflow-y-auto bg-gray-50 p-6 md:p-8 main-content">
            <div class="max-w-7xl mx-auto">

                <!-- Page Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-4 border-b border-gray-200">
                    <div>
                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                            <a href="/public/index.php" class="hover:text-primary transition-colors cursor-pointer"><i class="ri-home-line"></i> Home</a>
                            <i class="ri-arrow-right-s-line"></i>
                            <a href="/Modules/HRServices/public/index.php" class="hover:text-primary transition-colors cursor-pointer">HR Services</a>
                            <i class="ri-arrow-right-s-line"></i>
                            <span class="text-gray-800 font-medium">Organization Chart</span>
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i class="ri-team-line text-2xl"></i>
                            </div>
                            Organization Chart
                        </h1>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 min-h-[500px]">
                    <div id="loadingIndicator" class="flex flex-col items-center justify-center h-64 text-gray-500">
                        <i class="ri-loader-4-line text-4xl animate-spin mb-3 text-primary"></i>
                        <p>กำลังโหลดข้อมูลแผนผังองค์กร...</p>
                    </div>

                    <div id="errorIndicator" class="hidden flex-col items-center justify-center h-64 text-red-500">
                        <i class="ri-error-warning-line text-4xl mb-3"></i>
                        <p id="errorText">เกิดข้อผิดพลาดในการดึงข้อมูล</p>
                        <button onclick="loadTree()" class="mt-4 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">ลองใหม่อีกครั้ง</button>
                    </div>

                    <div id="treeContainer" class="hidden">
                        <!-- Search Bar -->
                        <div class="mb-6 flex flex-col md:flex-row gap-4 items-center">
                            <div class="relative w-full max-w-md">
                                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="searchInput" placeholder="ค้นหาตำแหน่ง, ทรวง, แผนก..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-sm">
                            </div>
                            <a href="/Modules/OrgChart/public/api.php?action=get-export-excel" target="_blank" class="px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl shadow-sm flex items-center gap-2 transition-colors text-sm font-medium whitespace-nowrap">
                                <i class="ri-file-excel-line text-lg"></i>
                                Export Excel
                            </a>
                        </div>
                        <!-- Chart Container -->
                        <div id="chart-container" class="w-full bg-slate-50 border border-gray-200 shadow-sm rounded-xl overflow-hidden mt-6" style="height: 600px;"></div>
                    </div>
                </div>

                <!-- Personnel Modal -->
                <div id="personnelModal" class="fixed inset-0 z-50 hidden">
                    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">

                        <div class="flex items-center justify-between p-6 border-b border-gray-100">
                            <div>
                                <h3 id="modalPositionName" class="text-xl font-bold text-gray-900">ชื่อตำแหน่ง</h3>
                                <p id="modalDepartment" class="text-sm text-gray-500 mt-1">แผนก</p>
                            </div>
                            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100 transition-colors">
                                <i class="ri-close-line text-2xl"></i>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto" id="modalPersonnelList">
                            <!-- Personnel cards will go here -->
                        </div>

                    </div>
                </div>

            </div>
        </main>

        <!-- Script for Tree Logic -->
        <script>
            let treeData = [];
            let chart; // Keep a reference to the chart instance
            let searchHighlightIds = [];

            document.addEventListener('DOMContentLoaded', () => {
                loadTree();

                document.getElementById('searchInput').addEventListener('input', (e) => {
                    handleSearch(e.target.value.toLowerCase());
                });
            });

            async function loadTree() {
                const loading = document.getElementById('loadingIndicator');
                const error = document.getElementById('errorIndicator');
                const container = document.getElementById('treeContainer');

                loading.classList.remove('hidden');
                loading.classList.add('flex');
                error.classList.add('hidden');
                error.classList.remove('flex');
                container.classList.add('hidden');

                try {
                    const response = await fetch('/Modules/OrgChart/public/api.php?action=get-tree');

                    if (response.status === 401) {
                        window.location.href = '/public/auth/login.php?error=session_expired';
                        return;
                    }

                    const json = await response.json();

                    if (json.success) {
                        treeData = json.data;

                        loading.classList.add('hidden');
                        loading.classList.remove('flex');
                        container.classList.remove('hidden');

                        renderTree();
                    } else {
                        throw new Error(json.error || 'Unknown error');
                    }
                } catch (e) {
                    loading.classList.add('hidden');
                    loading.classList.remove('flex');
                    document.getElementById('errorText').textContent = 'เกิดข้อผิดพลาด: ' + e.message;
                    error.classList.remove('hidden');
                    error.classList.add('flex');
                }
            }

            function renderTree() {
                if (!chart) {
                    chart = new d3.OrgChart()
                        .container('#chart-container')
                        .data(treeData)
                        .nodeWidth(d => 250)
                        .initialZoom(0.8)
                        .nodeHeight(d => 120)
                        .childrenMargin(d => 50)
                        .compactMarginBetween(d => 25)
                        .compactMarginPair(d => 25)
                        .nodeContent(function(d, i, arr, state) {
                            const bgColor = d.data.id === 'ROOT' ? 'bg-amber-500' : (d.depth % 2 === 0 ? 'bg-blue-600' : 'bg-teal-600');
                            const headcount = parseInt(d.data.headcount);
                            const isVacant = d.data.id !== 'ROOT' && headcount === 0;

                            const borderClass = isVacant ? 'border-red-400 border-2' : 'border-gray-200 border';
                            const badgeClass = isVacant ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200';
                            const badgeText = isVacant ? 'ตำแหน่งว่าง' : `${headcount} ตำแหน่ง`;

                            // We wrap the content in html so d3 renders native elements. The onclick fires the personnel modal securely.
                            return `
                                <div class="pt-3 h-full w-full">
                                    <div class="h-full w-full bg-white flex flex-col items-center justify-between rounded shadow-sm hover:shadow relative ${borderClass} transition-shadow cursor-pointer overflow-hidden" onclick="showPersonnel('${d.data.id}')">

                                        <!-- Top Bar (Colored by Depth/Root) -->
                                        <div class="w-full h-12 ${bgColor} text-white font-bold flex flex-col items-center justify-center p-2 text-center shrink-0">
                                            <h3 class="text-sm truncate w-full" title="${d.data.title}">${d.data.title}</h3>
                                        </div>

                                        <!-- Body Status -->
                                        <div class="w-full flex-1 flex flex-col items-center justify-center px-4">
                                            <span class="text-xs text-gray-500 mb-2 truncate max-w-full">${d.data.code || 'ไม่มีรหัส'}</span>
                                            ${d.data.id !== 'ROOT' ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border ${badgeClass}">
                                                ${badgeText}
                                            </span>` : ''}
                                        </div>

                                        <!-- Expand button (If children exist) -->
                                        ${d.hasChild ? `
                                            <div class="absolute bottom-[-10px] bg-white border border-gray-300 rounded-full w-6 h-6 flex items-center justify-center text-gray-500 shadow-sm" style="left: calc(50% - 12px);">
                                                <i class="${d.data._expanded ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'} text-xs"></i>
                                            </div>
                                        ` : ''}

                                    </div>
                                </div>
                            `;
                        })
                        .render();

                    // By default expand one layer deep
                    chart.expandAll();
                } else {
                    // Update existing chart
                    chart.data(treeData).render();
                }
            }

            function handleSearch(query) {
                if (!chart) return;

                if (query.trim() === '') {
                    chart.clearHighlighting();
                    chart.render();
                    return;
                }

                // Highlight matching nodes using D3 Org Chart built in
                // Find matching data nodes
                const matches = treeData.filter(d =>
                    (d.title && d.title.toLowerCase().includes(query)) ||
                    (d.code && d.code.toLowerCase().includes(query))
                );

                if (matches.length > 0) {
                    chart.clearHighlighting();

                    // Set highlighted style function temporarily or use setHighlighted if we just color it.
                    matches.forEach(m => chart.setHighlighted(m.id));

                    // Render and auto center to first match
                    chart.render();

                    if (matches.length > 0) {
                        try {
                            const firstMatch = matches[0];
                            // Use setCentered or fit function from d3-org-chart
                            chart.setCentered(firstMatch.id).render();
                        } catch (e) {
                            console.error('Error centering on node:', e);
                        }
                    }
                } else {
                    chart.clearHighlighting();
                    chart.render();
                }
            }

            async function showPersonnel(id) {
                // Find the node data from treeData
                const node = treeData.find(n => n.id === id);
                if (!node) return;

                document.getElementById('modalPositionName').textContent = node.title;
                document.getElementById('modalDepartment').textContent = node.code || '';

                const list = document.getElementById('modalPersonnelList');
                list.innerHTML = `
                    <div class="flex justify-center items-center py-12 text-gray-400">
                        <i class="ri-loader-4-line animate-spin text-3xl"></i>
                    </div>
                `;

                const modal = document.getElementById('personnelModal');
                modal.classList.remove('hidden');

                try {
                    const response = await fetch(`/Modules/OrgChart/public/api.php?action=get-personnel&position=${id}`);

                    if (response.status === 401) {
                        window.location.href = '/public/auth/login.php?error=session_expired';
                        return;
                    }

                    const json = await response.json();

                    if (json.success) {
                        if (json.data.length === 0) {
                            list.innerHTML = `
                                <div class="text-center py-12 bg-gray-50 rounded-xl border border-gray-100 border-dashed">
                                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto shadow-sm text-gray-300 mb-4">
                                        <i class="ri-user-unfollow-line text-2xl"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">ยังไม่มีผู้ดำรงตำแหน่งนี้ (ว่าง)</p>
                                </div>
                            `;
                            return;
                        }

                        let html = '<div class="grid grid-cols-1 gap-4">';
                        json.data.forEach(user => {
                            const initials = (user.fullname || 'U').substring(0, 2).toUpperCase();

                            // Calculate Age & Tenure
                            let ageText = '-';
                            let tenureText = '-';
                            const today = new Date();

                            if (user.BirthDate) {
                                const bd = new Date(user.BirthDate);
                                let years = today.getFullYear() - bd.getFullYear();
                                let months = today.getMonth() - bd.getMonth();
                                if (months < 0 || (months === 0 && today.getDate() < bd.getDate())) {
                                    years--;
                                    months += 12;
                                }
                                ageText = `${years} ปี ${months} เดือน`;
                            }

                            if (user.StartDate) {
                                const sd = new Date(user.StartDate);
                                let years = today.getFullYear() - sd.getFullYear();
                                let months = today.getMonth() - sd.getMonth();
                                if (months < 0 || (months === 0 && today.getDate() < sd.getDate())) {
                                    years--;
                                    months += 12;
                                }
                                tenureText = `${years} ปี ${months} เดือน`;
                            }

                            html += `
                                <div class="flex items-center gap-4 p-4 border border-gray-100 rounded-xl hover:border-blue-200 hover:shadow-sm transition-all bg-white group relative">
                                    <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xl shrink-0">
                                        ${initials}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-base font-bold text-gray-900 truncate group-hover:text-primary transition-colors">${user.fullname}</div>
                                        <div class="text-sm text-gray-500 truncate mt-0.5"><i class="ri-mail-line mr-1 align-middle"></i> ${user.email || 'ไม่มีอีเมล'}</div>
                                        <div class="flex items-center gap-4 mt-2">
                                            <div class="text-xs px-2 py-1 bg-gray-50 text-gray-600 rounded-md border border-gray-100 flex items-center gap-1.5" title="อายุ">
                                                <i class="ri-user-smile-line text-primary"></i> ${ageText}
                                            </div>
                                            <div class="text-xs px-2 py-1 bg-gray-50 text-gray-600 rounded-md border border-gray-100 flex items-center gap-1.5" title="อายุงาน">
                                                <i class="ri-briefcase-line text-blue-500"></i> ${tenureText}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        list.innerHTML = html;
                    }
                } catch (e) {
                    list.innerHTML = `<div class="text-red-500 text-center py-8">Error: ${e.message}</div>`;
                }
            }

            function closeModal() {
                document.getElementById('personnelModal').classList.add('hidden');
            }
        </script>

    </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-sm text-gray-500">
            <div>&copy; <?php echo date('Y'); ?> INTEQC Group. All rights reserved.</div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-primary transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-primary transition-colors">Terms of Service</a>
            </div>
        </div>
    </footer>

    </div>

</body>

</html>