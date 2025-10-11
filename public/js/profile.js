/* ======================================== */
/* PROFILE JAVASCRIPT */
/* ======================================== */
/* Extracted from profile edit.blade.php for better code organization */

// Form toggle functionality
function toggleForm(formId) {
    // Hide all forms first
    const forms = ['usernameForm', 'emailForm', 'passwordForm'];
    forms.forEach(id => {
        const form = document.getElementById(id);
        if (form && id !== formId) {
            form.style.display = 'none';
        }
    });

    // Toggle the selected form
    const targetForm = document.getElementById(formId);
    if (targetForm) {
        if (targetForm.style.display === 'none' || targetForm.style.display === '') {
            targetForm.style.display = 'block';
            targetForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            // Focus on first input after a short delay
            setTimeout(() => {
                const firstInput = targetForm.querySelector('input, select');
                if (firstInput) firstInput.focus();
            }, 300);
        } else {
            targetForm.style.display = 'none';
        }
    }
}

function hideForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
    }
}

function confirmDelete() {
    const userConfirm = confirm('⚠️ WARNING: This action cannot be undone!\n\nAre you absolutely sure you want to delete your account?\n\nThis will permanently:\n• Delete your profile\n• Remove your watchlist\n• Clear your viewing history\n• Cancel any subscriptions');

    if (userConfirm) {
        const finalConfirm = prompt('To confirm, please type "DELETE" in capital letters:');
        if (finalConfirm === 'DELETE') {
            const password = prompt('Enter your current password to confirm account deletion:');
            if (password) {
                deleteAccount(password);
            } else {
                alert('Account deletion cancelled - password is required.');
            }
        } else {
            alert('Account deletion cancelled - confirmation text did not match.');
        }
    }
}

function deleteAccount(password) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.profileConfig.deleteUrl;
    form.style.display = 'none';

    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfInput);

    // Add method override for DELETE
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);

    // Add password
    const passwordInput = document.createElement('input');
    passwordInput.type = 'hidden';
    passwordInput.name = 'current_password';
    passwordInput.value = password;
    form.appendChild(passwordInput);

    // Add confirmation
    const confirmationInput = document.createElement('input');
    confirmationInput.type = 'hidden';
    confirmationInput.name = 'confirmation';
    confirmationInput.value = 'DELETE';
    form.appendChild(confirmationInput);

    document.body.appendChild(form);
    form.submit();
}

// Form validation and loading states
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
                submitBtn.disabled = true;

                // Re-enable after 3 seconds in case of issues
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
}

// Real-time password validation for profile edit
function initializePasswordValidation() {
    const profilePasswordField = document.getElementById('profile_password');
    if (profilePasswordField) {
        profilePasswordField.addEventListener('input', function() {
            const password = this.value;

            // Check uppercase letters
            const hasUppercase = /[A-Z]/.test(password);
            const uppercaseCheck = document.getElementById('profile-check-uppercase');
            if (uppercaseCheck) uppercaseCheck.style.color = hasUppercase ? '#28a745' : '#adb5bd';

            // Check lowercase letters
            const hasLowercase = /[a-z]/.test(password);
            const lowercaseCheck = document.getElementById('profile-check-lowercase');
            if (lowercaseCheck) lowercaseCheck.style.color = hasLowercase ? '#28a745' : '#adb5bd';

            // Check numbers
            const hasNumber = /[0-9]/.test(password);
            const numberCheck = document.getElementById('profile-check-number');
            if (numberCheck) numberCheck.style.color = hasNumber ? '#28a745' : '#adb5bd';

            // Check special characters
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            const specialCheck = document.getElementById('profile-check-special');
            if (specialCheck) specialCheck.style.color = hasSpecial ? '#28a745' : '#adb5bd';
        });
    }
}

// Initialize profile page functionality
function initializeProfile(config) {
    // Store config globally for access in other functions
    window.profileConfig = config;

    
    // Initialize form validation
    initializeFormValidation();

    // Initialize password validation
    initializePasswordValidation();
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize basic functionality even without explicit config
    initializeFormValidation();
    initializePasswordValidation();
});