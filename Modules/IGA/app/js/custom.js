document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle password visibility
    function setupPasswordToggles() {
        const togglePasswordButtons = document.querySelectorAll('.password-toggle');

        togglePasswordButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const passwordInput = document.getElementById(targetId);
                const eyeIcon = this.querySelector('i');

                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                eyeIcon.classList.toggle('fa-eye');
                eyeIcon.classList.toggle('fa-eye-slash');
            });
        });
    }

    // Function to show/hide password toggles based on alert presence
    function togglePasswordTogglesVisibility() {
        const alertElement = document.querySelector('.alert');
        const toggleElements = document.querySelectorAll('.password-toggle');
        
        if (alertElement) {
            // If there's an alert, hide all password toggles
            toggleElements.forEach(toggle => {
                toggle.style.display = 'none';
            });
        } else {
            // If no alert, show all password toggles
            toggleElements.forEach(toggle => {
                toggle.style.display = 'flex';
            });
        }
    }

    // Set up password toggles
    setupPasswordToggles();
    
    // Initial check for alerts
    togglePasswordTogglesVisibility();
    
    // Set up a mutation observer to watch for alert changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length || mutation.removedNodes.length) {
                togglePasswordTogglesVisibility();
            }
        });
    });
    
    // Start observing the document with the configured parameters
    observer.observe(document.body, { childList: true, subtree: true });
});
