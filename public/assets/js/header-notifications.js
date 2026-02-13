/**
 * Header Notification Logic
 * Integrates NotificationClient (SSE) with the Header UI
 */

document.addEventListener('DOMContentLoaded', () => {
    const bell = document.getElementById('notification-bell');
    const dropdown = document.getElementById('notification-dropdown');
    const badge = document.getElementById('notification-badge');
    const list = document.getElementById('notification-list');
    const markAllBtn = document.getElementById('mark-all-read-btn');
    // Use APP_BASE_PATH from header.php if available, otherwise fallback
    const basePath = (window.APP_BASE_PATH !== undefined) ? window.APP_BASE_PATH.replace(/\/$/, '') : '';

    // Declare client early to avoid TDZ
    let client;

    // Determine asset and link base logic similarly to PHP UrlHelper
    // If basePath ends with /public (often in XAMPP), we may not need to append /public again for assets if the structure is flat, 
    // BUT our PHP UrlHelper usually returns basePath without /public for project root. 
    // Let's rely on how the PHP side sets APP_BASE_PATH.
    // If APP_BASE_PATH includes /public, we use it directly. 

    // Helper to construct asset URL
    const getAssetUrl = (path) => {
        // Remove leading slash from path
        const p = path.replace(/^\//, '');
        return `${basePath}/public/${p}`;
    };

    // Helper to construct API URL
    const getApiUrl = (path) => {
        const p = path.replace(/^\//, '');
        return `${basePath}/routes.php/${p}`;
    };

    if (!bell || !dropdown) return;

    // Load Notification Client script dynamically if not present
    if (!window.NotificationClient) {
        const script = document.createElement('script');
        // Use ASSET_BASE if available (passed from header.php), otherwise construct
        const assetBase = window.ASSET_BASE || `${basePath}/public/`;
        // Ensure we don't double slash if assetBase already has trailing slash (it usually does from PHP)
        // PHP UrlHelper guarantees trailing slash for getAssetBase()
        script.src = `${assetBase}assets/js/notifications.js`;
        script.onload = startService;
        script.onerror = () => console.error('Failed to load notifications.js');
        document.head.appendChild(script);
    } else {
        startService();
    }


    // Common renderer function
    const getLink = (link) => {
        if (!link) return null;
        if (link.startsWith('http') || link.startsWith('//')) return link;
        return `${basePath}/${link.replace(/^\//, '')}`;
    };

    // Handle notification click properly
    window.handleNotificationClick = function (link, event) {
        event.preventDefault();
        event.stopPropagation();

        // Mark as read before navigating
        const notificationItem = event.currentTarget;
        const notificationId = notificationItem.dataset.id;

        if (client && notificationId) {
            client.markAsRead(notificationId).catch(e => console.error('Mark as read error:', e));
        }

        // Navigate to the link
        window.location.href = link;
    };

    // Check if mobile device
    const isMobile = () => {
        return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    };

    // ... (colors and icons map remains the same) ...
    const typeColors = {
        'info': 'bg-blue-100 text-blue-600',
        'success': 'bg-green-100 text-green-600',
        'warning': 'bg-yellow-100 text-yellow-600',
        'error': 'bg-red-100 text-red-600'
    };

    const typeIcons = {
        'info': 'ri-information-line',
        'success': 'ri-check-line',
        'warning': 'ri-alert-line',
        'error': 'ri-close-circle-line'
    };

    function createNotificationItemHTML(n) {
        const link = getLink(n.link);
        const clickHandler = link ? `onclick="handleNotificationClick('${link}', event)"` : '';
        return `
            <div class="notification-item flex gap-3 px-4 py-3 border-b border-gray-50 hover:bg-gray-50 cursor-pointer ${n.is_read ? 'opacity-60' : ''} animate-fade-in" 
                 data-id="${n.id}" ${clickHandler}>
                <div class="w-8 h-8 rounded-full ${typeColors[n.type] || typeColors.info} flex items-center justify-center flex-shrink-0">
                    <i class="${typeIcons[n.type] || typeIcons.info}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-gray-900 truncate">${escapeHtml(n.title)}</div>
                    <div class="text-xs text-gray-500 truncate">${escapeHtml(n.message)}</div>
                    <div class="text-xs text-gray-400 mt-1">${formatTime(n.created_at)}</div>
                </div>
            </div>
        `;
    }

    function startService() {
        if (typeof NotificationClient === 'undefined') {
            console.error('NotificationClient not loaded');
            return;
        }

        const assetBase = window.ASSET_BASE || ((basePath) ? `${basePath}/public/` : '/');

        client = new NotificationClient({
            streamUrl: `${assetBase}api/notifications-stream.php`,
            apiUrl: `${basePath}/routes.php/notifications`,
            onUnreadCount: (count) => {
                updateBadge(count);
            },
            onNotification: (n) => {
                // Always animate bell on new notification
                animateBell();

                // Check if mobile - only show bell animation, no toast
                if (!isMobile()) {
                    // Desktop: Show toast
                    if (client && client.showToast) {
                        client.showToast(n);
                    }
                }

                // Update badge locally
                let current = parseInt(badge.textContent) || 0;
                if (badge.classList.contains('hidden')) current = 0;
                updateBadge(current + 1);

                // Prepend to list if rendered
                if (list) {
                    // Remove "No notifications" placeholder if exists
                    const emptyState = list.querySelector('.text-center.text-gray-400');
                    if (emptyState) emptyState.remove();

                    // Create and Prepend
                    const newItemHTML = createNotificationItemHTML(n);
                    list.insertAdjacentHTML('afterbegin', newItemHTML);

                    // Optional: remove oldest if list > 20 items to prevent bloat?
                    // For now, keep it simple.
                }
            }
        });
        client.connect();

        // Immediately fetch unread count on page load
        fetchUnreadCount();
    }

    // Fetch unread count without opening dropdown
    async function fetchUnreadCount() {
        if (!client) return;
        try {
            const data = await client.getAll(1);
            if (data.success) {
                const count = data.unread_count || 0;
                updateBadge(count);
                if (count > 0) {
                    setTimeout(animateBell, 500); // Slight delay for visual effect
                }
            }
        } catch (e) {
            console.error('Fetch unread count error:', e);
        }
    }

    // Toggle dropdown
    let isOpen = false;
    bell.addEventListener('click', (e) => {
        e.stopPropagation();
        isOpen = !isOpen;
        if (isOpen) {
            dropdown.classList.remove('hidden');
            fetchList(); // Still fetch fresh list to ensure sync
        } else {
            dropdown.classList.add('hidden');
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (dropdown && !dropdown.contains(e.target) && !bell.contains(e.target)) {
            dropdown.classList.add('hidden');
            isOpen = false;
        }
    });

    // Update badge UI
    function updateBadge(count) {
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    // Animate bell for mobile notifications
    function animateBell() {
        if (!bell) return;

        // Add animation class
        bell.classList.add('bell-bounce');

        // Remove animation after it completes
        setTimeout(() => {
            bell.classList.remove('bell-bounce');
        }, 600);
    }

    // Fetch list for dropdown
    async function fetchList() {
        // ... (rest same as before) ...
        if (!client) return;
        try {
            const data = await client.getAll(1);
            if (data.success && data.notifications) {
                renderNotifications(data.notifications);
                // Update badge to match server truth
                updateBadge(data.unread_count || 0);
            }
        } catch (e) {
            console.error('Fetch list error:', e);
        }
    }

    // Render notifications in dropdown
    function renderNotifications(notifications) {
        if (!list) return;
        if (!notifications.length) {
            list.innerHTML = '<div class="text-center py-8 text-gray-400 text-sm">ไม่มีการแจ้งเตือน</div>';
            return;
        }

        list.innerHTML = notifications.map(n => createNotificationItemHTML(n)).join('');
    }

    // Mark all as read
    markAllBtn?.addEventListener('click', async () => {
        if (client) {
            await client.markAllAsRead();
            updateBadge(0);
            fetchList();
        }
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function formatTime(datetime) {
        if (!datetime) return '';
        // MySQL returns 'YYYY-MM-DD HH:MM:SS' which new Date() often treats as UTC if no 'T' or 'Z'
        // If the server is in UTC+7 and returns "2024-02-12 10:00:00", new Date("...") might treat it as UTC.
        // If the browser is also UTC+7, new Date("2024-02-12 10:00:00") might work if it parses as local.
        // However, Safari/some browsers fail on space. Replace with T is safer.

        // Fix: Treat server time as potentially same-zone or force logic.
        // Better approach: Calculate diff based on timestamps explicitly.

        // Convert SQL format to compatible string
        const t = datetime.split(/[- :]/);
        // Apply to Date.UTC? No, that assumes input is UTC.
        // If input "10:00" is actually Thailand time, and we are in Thailand.

        let date = new Date(datetime.replace(' ', 'T'));
        // If invalid, try raw
        if (isNaN(date.getTime())) {
            date = new Date(datetime);
        }

        // Adjust for timezone offset if strictly necessary? 
        // The issue "7 hours ago" suggests:
        // System time: 17:00. Notification time: 10:00 (stored as UTC? or treated as such).
        // If server stored NOW() as 17:00 (UTC+7). Return "17:00".
        // JS new Date("17:00") -> treated as Local 17:00 -> Diff 0. Correct.
        // JS new Date("17:00Z") -> treated as UTC 17:00 -> Local 00:00 (Next day) -> Diff negative.
        // Wait, "7 hours ago" means notification is OLDER than now.
        // If Now is 17:00. Notification is 10:00.
        // If server is UTC, it stores 10:00. Returns 10:00.
        // JS sees 10:00. Diff = 7 hours.
        // So the Server is storing UTC, but displaying as if it was local time?
        // OR Server stores Local (17:00), sends "17:00". JS treats "17:00" as UTC (17:00 UTC = 24:00 Thai). Wait.

        // Let's rely on a simpler "Time Ago" that ignores timezone shifts if possible, or forces local interpretation.
        // User says "Time zone is wrong" (likely showing 7 hours ago when it just happened).
        // This usually happens when Server sends "YYYY-MM-DD HH:mm:ss" (UTC time) and JS parses it as Local time? No.
        // It happens when Server sends UTC "10:00", User is "17:00". JS parses "10:00" as local 10:00? No.

        // Most likely: Database stores UTC. PHP returns UTC string "10:00".
        // User is GMT+7. "now" is 17:00.
        // JS parses "10:00" as generic date.
        // Diff = 17:00 - 10:00 = 7 hours.
        // Fix: Treat the incoming string as UTC by appending 'Z', then JS converts to local (17:00) -> Diff 0.

        // Try appending Z if missing
        if (!datetime.includes('Z') && !datetime.includes('+')) {
            date = new Date(datetime.replace(' ', 'T') + 'Z');
        } else {
            date = new Date(datetime);
        }

        const now = new Date();
        const diff = (now - date) / 1000; // seconds

        // If diff is negative (future), it means our 'Z' fix might have been wrong (server was already local).
        // Or clocks slightly off.

        if (diff < 60) return 'เมื่อสักครู่';
        if (diff < 3600) return Math.floor(diff / 60) + ' นาทีที่แล้ว';
        if (diff < 86400) return Math.floor(diff / 3600) + ' ชั่วโมงที่แล้ว';
        return date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
    }

    // Add CSS for fade-in animation and bell bounce
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        
        @keyframes bellBounce {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-15deg); }
            50% { transform: rotate(15deg); }
            75% { transform: rotate(-5deg); }
        }
        .bell-bounce {
            animation: bellBounce 0.6s ease-in-out;
            transform-origin: center top;
        }
    `;
    document.head.appendChild(style);
});
