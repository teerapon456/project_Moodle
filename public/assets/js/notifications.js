/**
 * Real-time Notifications Client
 * ใช้ Server-Sent Events (SSE) สำหรับรับ notifications แบบ real-time
 */

class NotificationClient {
    constructor(options = {}) {
        this.streamUrl = options.streamUrl || '/public/api/notifications-stream.php';
        this.apiUrl = options.apiUrl || '/api/notifications';
        this.onNotification = options.onNotification || this.defaultHandler;
        this.onUnreadCount = options.onUnreadCount || null;
        this.eventSource = null;
        this.lastId = 0;
        this.reconnectDelay = 3000;
        this.maxReconnectDelay = 30000;
    }

    /**
     * Start listening for notifications
     */
    connect() {
        if (this.eventSource) {
            this.disconnect();
        }

        const url = `${this.streamUrl}?last_id=${this.lastId}`;
        this.eventSource = new EventSource(url);

        this.eventSource.addEventListener('init', (e) => {
            const data = JSON.parse(e.data);
            if (this.onUnreadCount) {
                this.onUnreadCount(data.unread_count);
            }
            console.log('[Notifications] Connected, unread:', data.unread_count);
        });

        this.eventSource.addEventListener('notification', (e) => {
            const notification = JSON.parse(e.data);
            this.lastId = Math.max(this.lastId, notification.id);
            this.onNotification(notification);
        });

        this.eventSource.addEventListener('reconnect', (e) => {
            const data = JSON.parse(e.data);
            this.lastId = data.last_id;
            this.reconnect();
        });

        this.eventSource.addEventListener('error', (e) => {
            console.warn('[Notifications] Connection error, reconnecting...');
            this.eventSource.close();
            setTimeout(() => this.connect(), this.reconnectDelay);
        });
    }

    /**
     * Disconnect from notification stream
     */
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }

    /**
     * Reconnect to stream
     */
    reconnect() {
        this.disconnect();
        setTimeout(() => this.connect(), 100);
    }

    /**
     * Default notification handler - show toast
     */
    defaultHandler(notification) {
        this.showToast(notification);
    }

    /**
     * Show toast notification
     */
    showToast(notification) {
        // Create toast container if not exists
        let container = document.getElementById('notification-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-toast-container';
            container.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column-reverse;
                gap: 10px;
            `;
            document.body.appendChild(container);
        }

        // Limit to 5 toasts - remove oldest if exceeds limit
        const existingToasts = container.querySelectorAll('.notification-toast');
        if (existingToasts.length >= 5) {
            const oldestToast = existingToasts[existingToasts.length - 1];
            oldestToast.remove();
        }

        // Create toast
        const toast = document.createElement('div');
        toast.className = `notification-toast notification-${notification.type}`;
        toast.style.cssText = `
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 16px;
            min-width: 300px;
            max-width: 400px;
            animation: slideIn 0.3s ease;
            cursor: pointer;
            border-left: 4px solid ${this.getTypeColor(notification.type)};
        `;

        toast.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                        ${this.escapeHtml(notification.title)}
                    </div>
                    <div style="color: #6b7280; font-size: 14px;">
                        ${this.escapeHtml(notification.message)}
                    </div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 18px;">
                    ×
                </button>
            </div>
        `;

        // Click to go to link
        if (notification.link) {
            toast.onclick = () => {
                this.markAsRead(notification.id);
                const properLink = this.getProperLink(notification.link);
                window.location.href = properLink;
            };
        }

        container.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    getTypeColor(type) {
        const colors = {
            'info': '#3b82f6',
            'success': '#10b981',
            'warning': '#f59e0b',
            'error': '#ef4444'
        };
        return colors[type] || colors.info;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Get proper link with basePath
     */
    getProperLink(link) {
        if (!link) return null;
        if (link.startsWith('http') || link.startsWith('//')) return link;
        
        const basePath = (window.APP_BASE_PATH !== undefined) ? window.APP_BASE_PATH.replace(/\/$/, '') : '';
        return `${basePath}/${link.replace(/^\//, '')}`;
    }

    /**
     * Mark notification as read
     */
    async markAsRead(id) {
        try {
            await fetch(`${this.apiUrl}?action=markAsRead`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
        } catch (e) {
            console.error('Failed to mark as read:', e);
        }
    }

    /**
     * Mark all as read
     */
    async markAllAsRead() {
        try {
            await fetch(`${this.apiUrl}?action=markAllAsRead`, {
                method: 'POST'
            });
            if (this.onUnreadCount) {
                this.onUnreadCount(0);
            }
        } catch (e) {
            console.error('Failed to mark all as read:', e);
        }
    }

    /**
     * Get all notifications
     */
    async getAll(page = 1) {
        try {
            const response = await fetch(`${this.apiUrl}?action=list&page=${page}`);
            return await response.json();
        } catch (e) {
            console.error('Failed to get notifications:', e);
            return { success: false, notifications: [] };
        }
    }
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Export for use
window.NotificationClient = NotificationClient;
