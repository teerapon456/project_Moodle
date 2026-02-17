/**
 * Global Notifications Utility
 * Provides a consistent window.notify function using SweetAlert2
 */

(function () {
    /**
     * @param {string} msg - Message to display
     * @param {string} type - 'success', 'error', 'warning', 'info' (default: 'info')
     * @param {number} duration - Duration in ms (default: 3000)
     */
    window.notify = function (msg, type = 'info', duration = 3000) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: type,
                title: msg,
                showConfirmButton: false,
                timer: duration,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        } else {
            // Fallback to standard alert if Swal is missing
            console.warn('SweetAlert2 (Swal) is not loaded. Falling back to alert().');
            alert(msg);
        }
    };
})();
