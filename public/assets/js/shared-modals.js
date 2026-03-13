/**
 * Global Modal System Helper
 */
const MyHRModal = {
    confirm: function(options) {
        const modal = document.getElementById('global-confirm-modal');
        const content = document.getElementById('confirm-modal-content');
        const title = document.getElementById('confirm-modal-title');
        const message = document.getElementById('confirm-modal-message');
        const submitBtn = document.getElementById('confirm-modal-submit');
        const cancelBtn = document.getElementById('confirm-modal-cancel');
        const icon = document.getElementById('confirm-modal-icon');
        const iconContainer = document.getElementById('confirm-modal-icon-container');

        if (!modal) return;

        // Reset and set options
        title.textContent = options.title || 'ยืนยันการดำเนินการ';
        message.textContent = options.message || 'คุณแน่ใจหรือไม่?';
        submitBtn.textContent = options.submitText || 'ยืนยัน';
        cancelBtn.textContent = options.cancelText || 'ยกเลิก';

        // Styling based on type (danger, warning, success)
        const type = options.type || 'danger';
        if (type === 'danger') {
            icon.className = 'ri-error-warning-line text-3xl text-red-500';
            iconContainer.className = 'w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4';
            submitBtn.className = 'flex-1 px-4 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-200';
        } else if (type === 'warning') {
            icon.className = 'ri-alert-line text-3xl text-orange-500';
            iconContainer.className = 'w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center mx-auto mb-4';
            submitBtn.className = 'flex-1 px-4 py-2.5 bg-orange-500 text-white font-medium rounded-xl hover:bg-orange-600 transition-colors shadow-lg shadow-orange-200';
        }

        modal.classList.add('modal-show');
        setTimeout(() => content.classList.add('modal-animate-in'), 10);

        return new Promise((resolve) => {
            const handleClose = (result) => {
                content.classList.remove('modal-animate-in');
                setTimeout(() => {
                    modal.classList.remove('modal-show');
                    resolve(result);
                }, 300);
            };

            submitBtn.onclick = () => handleClose(true);
            cancelBtn.onclick = () => handleClose(false);
            modal.querySelector('.modal-overlay').onclick = () => handleClose(false);
        });
    },

    alert: function(options) {
        const modal = document.getElementById('global-alert-modal');
        const content = document.getElementById('alert-modal-content');
        const title = document.getElementById('alert-modal-title');
        const message = document.getElementById('alert-modal-message');
        const closeBtn = document.getElementById('alert-modal-close');
        const icon = document.getElementById('alert-modal-icon');

        if (!modal) return;

        title.textContent = options.title || 'ข้อมูล';
        message.textContent = options.message || '';
        
        modal.classList.add('modal-show');
        setTimeout(() => content.classList.add('modal-animate-in'), 10);

        return new Promise((resolve) => {
            closeBtn.onclick = () => {
                content.classList.remove('modal-animate-in');
                setTimeout(() => {
                    modal.classList.remove('modal-show');
                    resolve();
                }, 300);
            };
        });
    },

    prompt: function(options) {
        const modal = document.getElementById('global-alert-modal'); // Reusing alert for simple prompt or use global-confirm
        // Actually, let's use global-confirm-modal structure but swap message for input
        const confirmModal = document.getElementById('global-confirm-modal');
        const content = document.getElementById('confirm-modal-content');
        const title = document.getElementById('confirm-modal-title');
        const message = document.getElementById('confirm-modal-message');
        const submitBtn = document.getElementById('confirm-modal-submit');
        const cancelBtn = document.getElementById('confirm-modal-cancel');
        const iconContainer = document.getElementById('confirm-modal-icon-container');

        if (!confirmModal) return;

        title.textContent = options.title || 'กรุณากรอกข้อมูล';
        
        // Save original message content to restore later
        const originalMessageHTML = message.innerHTML;
        message.innerHTML = `
            <p class="mb-3 text-gray-500">${options.message || ''}</p>
            <input type="text" id="global-prompt-input" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" value="${options.defaultValue || ''}" placeholder="${options.placeholder || ''}">
        `;
        
        submitBtn.textContent = options.submitText || 'ตกลง';
        cancelBtn.textContent = options.cancelText || 'ยกเลิก';
        iconContainer.className = 'w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4';
        document.getElementById('confirm-modal-icon').className = 'ri-question-line text-3xl text-blue-500';
        submitBtn.className = 'flex-1 px-4 py-2.5 bg-gray-900 text-white font-medium rounded-xl hover:bg-gray-800 transition-colors shadow-lg';

        confirmModal.classList.add('modal-show');
        setTimeout(() => content.classList.add('modal-animate-in'), 10);
        setTimeout(() => document.getElementById('global-prompt-input')?.focus(), 200);

        return new Promise((resolve) => {
            const handleClose = (submit) => {
                const value = submit ? document.getElementById('global-prompt-input').value : null;
                content.classList.remove('modal-animate-in');
                setTimeout(() => {
                    confirmModal.classList.remove('modal-show');
                    message.innerHTML = originalMessageHTML; // Restore
                    resolve(value);
                }, 300);
            };

            submitBtn.onclick = () => handleClose(true);
            cancelBtn.onclick = () => handleClose(false);
            modal.querySelector('.modal-overlay').onclick = () => handleClose(false);
        });
    },

    /**
     * Set up a custom modal by moving it to body to escape stacking context
     * @param {string} id Modal element ID
     * @returns {HTMLElement|null} The modal element
     */
    setupCustomModal: function(id) {
        const modal = document.getElementById(id);
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
        return modal;
    }
};
