/**
 * CSRF Helper - Add CSRF token to all fetch requests
 * Include this script in your pages to automatically handle CSRF
 */

(function () {
    // Get CSRF token from meta tag or generate endpoint
    let csrfToken = null;

    // Try to get from meta tag first
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        csrfToken = metaTag.getAttribute('content');
    } else if (window.CSRF_TOKEN) {
        csrfToken = window.CSRF_TOKEN;
    }

    // Override fetch to automatically include CSRF token
    const originalFetch = window.fetch;
    window.fetch = function (url, options = {}) {
        options = options || {};

        // Only add CSRF for same-origin requests
        const isSameOrigin = url.startsWith('/') || url.startsWith(window.location.origin);

        if (isSameOrigin && ['POST', 'PUT', 'DELETE', 'PATCH'].includes((options.method || 'GET').toUpperCase())) {
            // Add CSRF token to headers
            options.headers = options.headers || {};

            // If headers is a Headers object, convert to plain object
            if (options.headers instanceof Headers) {
                const headerObj = {};
                options.headers.forEach((value, key) => {
                    headerObj[key] = value;
                });
                options.headers = headerObj;
            }

            if (csrfToken) {
                options.headers['X-CSRF-TOKEN'] = csrfToken;
            }
        }

        return originalFetch.call(this, url, options).then(async (response) => {
            // Handle Session Timeout (401) globally
            // Skip if this is a login attempt (which expects 401 on failure)
            if (response.status === 401 && !url.includes('action=login') && !url.includes('/auth/login')) {
                // Determine correct login path (Docker vs XAMPP)
                const basePath = (window.APP_BASE_PATH || '').replace(/\/$/, '');
                let loginUrl = '/index.php'; // Docker default

                if (basePath && basePath !== '/') {
                    loginUrl = `${basePath}/public/index.php`;
                }

                // Append error param
                if (loginUrl.includes('?')) {
                    loginUrl += '&error=session_expired';
                } else {
                    loginUrl += '?error=session_expired';
                }

                window.location.href = loginUrl;

                // Return a never-resolving promise to stop downstream error handling from firing weird alerts
                return new Promise(() => { });
            }
            return response;
        });
    };

    // Helper function to get CSRF token for forms
    window.getCSRFToken = function () {
        return csrfToken;
    };

    // Helper function to create CSRF hidden input
    window.createCSRFInput = function () {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_csrf_token';
        input.value = csrfToken || '';
        return input;
    };

    // Auto-add CSRF to all forms on page
    document.addEventListener('DOMContentLoaded', function () {
        if (csrfToken) {
            document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function (form) {
                if (!form.querySelector('input[name="_csrf_token"]')) {
                    form.appendChild(window.createCSRFInput());
                }
            });
        }
    });

    console.log('[CSRF] Protection initialized');
})();
