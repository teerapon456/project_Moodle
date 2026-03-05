<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Dashboard - MyHR Portal</title>
    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <!-- Remixicons (Local) -->
    <link rel="stylesheet" href="assets/css/remixicon.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8fafc;
        }

        .status-dot {
            width: 10px !important;
            height: 10px !important;
            min-width: 10px !important;
            min-height: 10px !important;
            border-radius: 50% !important;
            display: inline-block !important;
            flex-shrink: 0;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.1);
        }

        .status-dot-green {
            background-color: #22c55e !important;
        }

        .status-dot-red {
            background-color: #ef4444 !important;
        }

        .status-dot-yellow {
            background-color: #eab308 !important;
        }

        .card-enter {
            opacity: 0;
            transform: translateY(10px);
            animation: fade-in-up 0.5s ease forwards;
        }

        @keyframes fade-in-up {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e6e6e6 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .premium-shadow {
            box-shadow: 0 4px 15px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        .premium-shadow:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.07), 0 10px 10px -5px rgba(0, 0, 0, 0.03);
        }

        .portal-nav {
            background: #A21D21;
            /* Portal Red */
            border-bottom: 3px solid #7f1d1d;
        }
    </style>
</head>

<body class="text-gray-800 antialiased min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="portal-nav sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-md border border-white/20">
                        <img src="assets/images/brand/inteqc-logo.png" alt="logo" class="w-7 h-7" onerror="this.src='https://cdn-icons-png.flaticon.com/512/822/822143.png'">
                    </div>
                    <div>
                        <span class="text-white text-lg font-bold tracking-tight block leading-tight">MyHR Dashboard</span>
                        <span class="text-red-100 text-xs font-light block opacity-90 tracking-wide">Infrastructure Health Monitor</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div id="overall-status-badge" class="hidden items-center gap-2 px-3 py-1.5 rounded-full text-sm font-bold border-2 shadow-sm">
                        <!-- Filled dynamically -->
                    </div>
                    <button onclick="fetchHealthData()" id="refresh-btn" class="text-white/80 hover:text-white transition-colors p-2 rounded-xl hover:bg-white/10 focus:outline-none" title="Refresh Now">
                        <i class="ri-refresh-line text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

        <!-- Header Info -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Microservices Status</h1>
                <p class="text-gray-500 text-sm">Real-time monitoring of all decoupled containers and core infrastructure.</p>
            </div>
            <div class="flex items-center gap-3 text-sm bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100">
                <div class="flex items-center gap-1.5 text-gray-500">
                    <i class="ri-time-line"></i>
                    <span>Last checked: <strong id="last-updated" class="text-gray-800">--:--:--</strong></span>
                </div>
                <div class="w-px h-4 bg-gray-300"></div>
                <div class="flex items-center gap-1.5 text-gray-400">
                    <i class="ri-history-line"></i>
                    <span>Auto-refresh: <strong id="countdown-timer" class="text-red-600 font-bold">5s</strong></span>
                </div>
            </div>
        </div>

        <!-- System Overview Banner // Error State -->
        <div id="error-banner" class="hidden mb-8 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="ri-error-warning-fill text-red-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">API Connection Failed</h3>
                    <div class="mt-1 text-sm text-red-700">
                        <p>Unable to connect to the standalone health monitoring API.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Container -->
        <div id="services-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Skeleton items for initial load -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 w-full">
                <div class="h-10 w-10 skeleton rounded-lg mb-4"></div>
                <div class="h-5 w-32 skeleton rounded mb-2"></div>
                <div class="h-4 w-24 skeleton rounded mb-4"></div>
                <div class="h-px bg-gray-100 w-full mb-4"></div>
                <div class="flex justify-between">
                    <div class="h-6 w-16 skeleton rounded-full"></div>
                    <div class="h-6 w-16 skeleton rounded"></div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 w-full">
                <div class="h-10 w-10 skeleton rounded-lg mb-4"></div>
                <div class="h-5 w-32 skeleton rounded mb-2"></div>
                <div class="h-4 w-24 skeleton rounded mb-4"></div>
                <div class="h-px bg-gray-100 w-full mb-4"></div>
                <div class="flex justify-between">
                    <div class="h-6 w-16 skeleton rounded-full"></div>
                    <div class="h-6 w-16 skeleton rounded"></div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 w-full">
                <div class="h-10 w-10 skeleton rounded-lg mb-4"></div>
                <div class="h-5 w-32 skeleton rounded mb-2"></div>
                <div class="h-4 w-24 skeleton rounded mb-4"></div>
                <div class="h-px bg-gray-100 w-full mb-4"></div>
                <div class="flex justify-between">
                    <div class="h-6 w-16 skeleton rounded-full"></div>
                    <div class="h-6 w-16 skeleton rounded"></div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 w-full">
                <div class="h-10 w-10 skeleton rounded-lg mb-4"></div>
                <div class="h-5 w-32 skeleton rounded mb-2"></div>
                <div class="h-4 w-24 skeleton rounded mb-4"></div>
                <div class="h-px bg-gray-100 w-full mb-4"></div>
                <div class="flex justify-between">
                    <div class="h-6 w-16 skeleton rounded-full"></div>
                    <div class="h-6 w-16 skeleton rounded"></div>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between">
            <p class="text-sm text-gray-500">
                &copy; <script>
                    document.write(new Date().getFullYear())
                </script> MyHR Portal Infrastructure.
            </p>
            <div class="flex items-center gap-1.5 mt-2 md:mt-0 text-xs text-gray-400">
                <i class="ri-shield-check-line"></i>
                Decoupled Architecture
            </div>
        </div>
    </footer>

    <script>
        const REFRESH_INTERVAL_SEC = 5;
        let countdown = REFRESH_INTERVAL_SEC;
        let timerInterval;
        let adminSecret = sessionStorage.getItem('health_secret') || '';

        async function promptForSecret() {
            if (adminSecret) return true;
            const secret = prompt("Please enter the Health Admin Secret to control services:");
            if (secret) {
                adminSecret = secret;
                sessionStorage.setItem('health_secret', secret);
                return true;
            }
            return false;
        }

        async function controlService(serviceName, action) {
            if (!await promptForSecret()) return;

            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Processing...';

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Health-Secret': adminSecret
                    },
                    body: JSON.stringify({
                        service: serviceName,
                        action: action
                    })
                });

                const result = await response.json();
                if (result.status === 'success') {
                    showNotification('Success: ' + result.message, 'success');
                    // Wait 2 seconds before refreshing to let the service actually start its ports
                    setTimeout(() => fetchHealthData(), 2000);
                } else {
                    showNotification('Error: ' + result.message, 'error');
                    if (result.message === 'Unauthorized') {
                        adminSecret = '';
                        sessionStorage.removeItem('health_secret');
                    }
                }
            } catch (error) {
                showNotification('Failed to contact API', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        function showNotification(message, type) {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-xl shadow-2xl font-bold text-white transform transition-all duration-300 translate-y-20 flex items-center gap-2 z-50 ${type === 'success' ? 'bg-emerald-500' : 'bg-red-600'}`;
            toast.innerHTML = `<i class="${type === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line'}"></i> ${message}`;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.remove('translate-y-20'), 100);
            setTimeout(() => {
                toast.classList.add('translate-y-20');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Custom Icons and Colors mapping for specific services
        const serviceMeta = {
            'Database': {
                icon: 'ri-database-2-fill',
                color: '#dc2626',
                bg: '#fef2f2'
            },
            'Moodle DB': {
                icon: 'ri-database-fill',
                color: '#ef4444',
                bg: '#fff5f5'
            },
            'Redis': {
                icon: 'ri-server-fill',
                color: '#b91c1c',
                bg: '#fff1f2'
            },
            'Gateway': {
                icon: 'ri-shield-keyhole-fill',
                color: '#991b1b',
                bg: '#fef2f2'
            },
            'Portal (Main)': {
                icon: 'ri-layout-masonry-fill',
                color: '#A21D21',
                bg: '#fff5f5'
            },
            'Moodle Frontend': {
                icon: 'ri-window-fill',
                color: '#dc2626',
                bg: '#fef2f2'
            },
            'Moodle LMS': {
                icon: 'ri-graduation-cap-fill',
                color: '#991b1b',
                bg: '#fff1f2'
            },
            'Car Booking': {
                icon: 'ri-car-fill',
                color: '#A21D21',
                bg: '#fff5f5'
            },
            'Dormitory': {
                icon: 'ri-home-smile-fill',
                color: '#dc2626',
                bg: '#fef2f2'
            },
            'Yearly Activity': {
                icon: 'ri-bar-chart-box-fill',
                color: '#ef4444',
                bg: '#fff5f5'
            },
            'IGA Module': {
                icon: 'ri-shield-user-fill',
                color: '#991b1b',
                bg: '#fef2f2'
            },
            'phpMyAdmin': {
                icon: 'ri-settings-5-fill',
                color: '#64748b',
                bg: '#f1f5f9'
            },
            'Health Check': {
                icon: 'ri-heart-pulse-line',
                color: '#A21D21',
                bg: '#fff5f5'
            },
            'default': {
                icon: 'ri-server-line',
                color: '#6b7280',
                bg: '#f9fafb'
            }
        };

        function getServiceMeta(name) {
            return serviceMeta[name] || serviceMeta['default'];
        }

        function createServiceCard(name, data, index) {
            const meta = getServiceMeta(name);
            const isOnline = data.status === 'online';
            const cardId = `service-card-${name.replace(/\s+/g, '-').toLowerCase()}`;

            const badgeBg = isOnline ? 'bg-emerald-100' : 'bg-red-100';
            const badgeText = isOnline ? 'text-emerald-700' : 'text-red-700';
            const badgeIcon = isOnline ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill';
            const dotClass = isOnline ? 'status-dot-green' : 'status-dot-red';
            const statusLabel = isOnline ? 'Online' : 'Offline';

            const latencyColor = isOnline ?
                (data.latency < 100 ? 'text-emerald-500' : (data.latency < 500 ? 'text-amber-500' : 'text-red-500')) :
                'text-gray-400';

            const latencyText = isOnline ? `${data.latency} ms` : '--';

            // Handle port-prefixed links (e.g., :8081/...)
            let finalLink = data.link;
            if (finalLink && finalLink.startsWith(':')) {
                // Force HTTP for port 8081 as requested by user
                const protocol = finalLink.startsWith(':8081') ? 'http:' : window.location.protocol;
                finalLink = protocol + '//' + window.location.hostname + finalLink;
            }

            return `
                <div id="${cardId}" class="card-enter bg-white rounded-2xl premium-shadow border border-gray-100 overflow-hidden group transition-all duration-300 transform hover:-translate-y-1" style="animation-delay: ${index * 0.05}s" data-online="${isOnline}">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-5">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-sm border border-black/5" style="background-color: ${meta.bg}; color: ${meta.color}">
                                <i class="${meta.icon} text-3xl"></i>
                            </div>
                            <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold ${isOnline ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-red-50 text-red-600 border-red-100'} border">
                                <i class="${badgeIcon}"></i>
                                ${statusLabel}
                            </div>
                        </div>
                        
                        <h3 class="text-lg font-bold text-gray-900 mb-1 line-clamp-1">${name}</h3>
                        
                        <div class="space-y-1 mb-4">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Started:</span>
                                <span class="font-medium text-gray-700 service-start">${data.started_at || '--'}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Uptime:</span>
                                <span class="font-medium text-emerald-600 service-uptime">${data.uptime || '--'}</span>
                            </div>
                        </div>
                        
                        <div class="h-px w-full bg-gray-100 mb-4"></div>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="status-dot ${dotClass}"></span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Status Check</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-sm font-bold ${latencyColor}">
                                <i class="ri-timer-line text-gray-400"></i>
                                <span class="service-latency">${latencyText}</span>
                            </div>
                        </div>

                        <div class="service-control-area">
                            ${!isOnline && name !== 'Health Check' ? `
                            <div class="mb-4 p-4 bg-red-50 rounded-2xl border border-red-200 shadow-inner">
                                <div class="flex items-center gap-2 text-xs text-red-700 font-extrabold mb-3">
                                    <i class="ri-error-warning-fill text-sm"></i>
                                    <span class="uppercase tracking-tight">${data.error || 'Connection Failed'}</span>
                                </div>
                                <button onclick="controlService('${name}', 'start')" 
                                        style="background-color: #000000 !important; color: #FFFFFF !important; padding: 16px !important; border-radius: 12px !important; font-weight: 900 !important; font-size: 16px !important; width: 100% !important; border: none !important; cursor: pointer !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; margin-top: 10px !important;"
                                        class="active:scale-95 transition-all shadow-2xl">
                                    <i class="ri-play-circle-fill" style="font-size: 20px !important;"></i>
                                    <span style="letter-spacing: 1px !important;">START SERVICE NOW</span>
                                </button>
                            </div>
                            ` : ''}
                        </div>

                        ${finalLink && finalLink !== '#' ? `
                        <a href="${finalLink}" target="_blank" class="w-full flex items-center justify-center gap-2 py-2.5 bg-gray-50 hover:bg-red-50 text-gray-700 hover:text-red-700 rounded-xl text-sm font-bold transition-all border border-gray-100 hover:border-red-100">
                            <span>Manage Service</span>
                            <i class="ri-external-link-line text-xs"></i>
                        </a>
                        ` : `
                        <div class="w-full py-2 bg-gray-50 text-gray-400 rounded-lg text-sm font-medium border border-gray-50 text-center">
                            Internal Service
                        </div>
                        `}
                    </div>
                </div>
            `;
        }

        async function fetchHealthData() {
            const btn = document.getElementById('refresh-btn');
            btn.classList.add('animate-spin');

            try {
                // Fetch from the API endpoint directly via its own container
                const response = await fetch('api.php', {
                    cache: 'no-store'
                });
                if (!response.ok) throw new Error('API Response Error');

                const data = await response.json();

                document.getElementById('error-banner').classList.add('hidden');

                // Update Timestamp
                const now = new Date();
                document.getElementById('last-updated').textContent = now.toLocaleTimeString('th-TH');

                // Update Overall Badge
                const overallBadge = document.getElementById('overall-status-badge');
                overallBadge.classList.remove('hidden', 'bg-emerald-500', 'bg-red-500', 'bg-yellow-500', 'border-emerald-400', 'border-red-400', 'text-white');

                if (data.status === 'healthy') {
                    overallBadge.classList.add('bg-emerald-500', 'border-emerald-400', 'text-white');
                    overallBadge.innerHTML = '<i class="ri-checkbox-circle-fill"></i> All Systems Operational';
                } else {
                    overallBadge.classList.add('bg-yellow-500', 'border-yellow-400', 'text-white');
                    overallBadge.innerHTML = '<i class="ri-error-warning-fill"></i> Partial Degradation';
                }

                // Update Grid without flashing
                const grid = document.getElementById('services-grid');
                let index = 0;

                const services = Object.entries(data.services);

                // Clear initial skeletons only if this is the first real load
                const hasSkeletons = grid.querySelector('.skeleton');
                if (hasSkeletons && services.length > 0) grid.innerHTML = '';

                for (const [serviceName, serviceData] of services) {
                    const cardId = `service-card-${serviceName.replace(/\s+/g, '-').toLowerCase()}`;
                    const existingCard = document.getElementById(cardId);

                    if (existingCard) {
                        const isOnline = serviceData.status === 'online';

                        // Update status badge
                        const badge = existingCard.querySelector('.rounded-full.text-xs');
                        if (badge) {
                            badge.className = `flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${isOnline ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200'} border`;
                            badge.innerHTML = `<i class="${isOnline ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'}"></i> ${isOnline ? 'Online' : 'Offline'}`;
                        }

                        // Update indicator
                        const indicator = existingCard.querySelector('.status-dot');
                        if (indicator) {
                            indicator.className = `status-dot ${isOnline ? 'status-dot-green' : 'status-dot-red'}`;
                        }

                        // Update latency
                        const latencySpan = existingCard.querySelector('.service-latency');
                        if (latencySpan) {
                            const latencyContainer = latencySpan.parentElement;
                            const latencyColor = isOnline ? (serviceData.latency < 50 ? 'text-emerald-600' : (serviceData.latency < 200 ? 'text-yellow-600' : 'text-orange-600')) : 'text-gray-400';
                            latencyContainer.className = `flex items-center gap-1.5 text-sm font-semibold ${latencyColor}`;
                            latencySpan.textContent = isOnline ? `${serviceData.latency} ms` : '--';
                        }

                        // Update Uptime/Start
                        existingCard.querySelector('.service-start').textContent = serviceData.started_at || '--';
                        const uptimeSpan = existingCard.querySelector('.service-uptime');
                        uptimeSpan.textContent = serviceData.uptime || '--';
                        uptimeSpan.className = `font-medium service-uptime ${isOnline ? 'text-emerald-600' : 'text-gray-400'}`;

                        // Update Control Area (Button)
                        const controlArea = existingCard.querySelector('.service-control-area');
                        if (controlArea) {
                            const oldStatus = existingCard.getAttribute('data-online');
                            const newStatus = isOnline.toString();
                            if (oldStatus !== newStatus || (!isOnline && controlArea.innerHTML.includes('...'))) {
                                // Update content if status changed or if we need to refresh error message
                                if (!isOnline && serviceName !== 'Health Check') {
                                    controlArea.innerHTML = `
                                        <div class="mb-4 p-4 bg-red-50 rounded-2xl border border-red-200 shadow-inner">
                                            <div class="flex items-center gap-2 text-xs text-red-700 font-extrabold mb-3">
                                                <i class="ri-error-warning-fill text-sm"></i>
                                                <span class="uppercase tracking-tight">${serviceData.error || 'Connection Failed'}</span>
                                            </div>
                                            <button onclick="controlService('${serviceName}', 'start')" 
                                                    style="background-color: #000000 !important; color: #FFFFFF !important; padding: 16px !important; border-radius: 12px !important; font-weight: 900 !important; font-size: 16px !important; width: 100% !important; border: none !important; cursor: pointer !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; margin-top: 10px !important;"
                                                    class="active:scale-95 transition-all shadow-2xl">
                                                <i class="ri-play-circle-fill" style="font-size: 20px !important;"></i>
                                                <span style="letter-spacing: 1px !important;">START SERVICE NOW</span>
                                            </button>
                                        </div>
                                    `;
                                } else {
                                    controlArea.innerHTML = '';
                                }
                                existingCard.setAttribute('data-online', newStatus);
                            }
                        }
                    } else {
                        grid.insertAdjacentHTML('beforeend', createServiceCard(serviceName, serviceData, index));
                    }
                    index++;
                }

            } catch (error) {
                console.error('Health Check Error:', error);
                document.getElementById('error-banner').classList.remove('hidden');
                document.getElementById('overall-status-badge').classList.add('hidden');
            } finally {
                setTimeout(() => btn.classList.remove('animate-spin'), 500);
                resetCountdown();
            }
        }

        function resetCountdown() {
            clearInterval(timerInterval);
            countdown = REFRESH_INTERVAL_SEC;
            document.getElementById('countdown-timer').textContent = `${countdown}s`;

            timerInterval = setInterval(() => {
                countdown--;
                if (countdown < 0) {
                    fetchHealthData();
                } else {
                    document.getElementById('countdown-timer').textContent = `${countdown}s`;
                }
            }, 1000);
        }

        // Initial Load
        document.addEventListener('DOMContentLoaded', fetchHealthData);
    </script>
</body>

</html>