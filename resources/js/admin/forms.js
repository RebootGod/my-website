/* ======================================== */
/* ADMIN FORMS JAVASCRIPT */
/* ======================================== */
/* Extracted from admin management pages (users/edit, invite-codes) for better code organization */

// Initialize admin forms functionality
function initializeAdminForms(config) {
    // Store config globally for access in other functions
    window.adminFormsConfig = config;

    console.log('Admin Forms initialized with config:', config);

    // Initialize form validation
    initializeFormValidation();

    // Initialize password generator
    initializePasswordGenerator();

    // Initialize invite code functionality
    initializeInviteCodeFeatures();

    // Initialize copy to clipboard
    initializeCopyFeatures();
}

// Password Generator Functionality
function generatePassword() {
    if (!window.adminFormsConfig || !window.adminFormsConfig.generatePasswordUrl) {
        console.error('Generate password URL not configured');
        return;
    }

    fetch(window.adminFormsConfig.generatePasswordUrl)
        .then(response => response.json())
        .then(data => {
            const passwordField = document.getElementById('password');
            const passwordConfirmField = document.getElementById('password_confirmation');

            if (passwordField) passwordField.value = data.password;
            if (passwordConfirmField) passwordConfirmField.value = data.password;

            alert('Generated password: ' + data.password + '\n\nMake sure to copy this password!');
        })
        .catch(error => {
            console.error('Error generating password:', error);
            alert('Failed to generate password. Please try again.');
        });
}

// Initialize password generator
function initializePasswordGenerator() {
    const generateBtn = document.getElementById('generatePasswordBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', generatePassword);
    }

    // Also look for any button with generate-password class
    const generateButtons = document.querySelectorAll('.generate-password-btn');
    generateButtons.forEach(btn => {
        btn.addEventListener('click', generatePassword);
    });
}

// Form validation functionality
function initializeFormValidation() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        // Add loading states to submit buttons
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;

                // Add loading spinner
                submitBtn.innerHTML = '<span class="admin-loading-spinner"></span> Processing...';
                submitBtn.disabled = true;

                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            }
        });

        // Real-time validation for specific fields
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });

            field.addEventListener('input', function() {
                // Remove error styling on input
                this.classList.remove('error');
                const errorMsg = this.parentNode.querySelector('.admin-form-error');
                if (errorMsg) errorMsg.remove();
            });
        });

        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateEmail(this);
            });
        });

        // Password confirmation validation
        const passwordField = form.querySelector('input[name="password"]');
        const passwordConfirmField = form.querySelector('input[name="password_confirmation"]');

        if (passwordField && passwordConfirmField) {
            passwordConfirmField.addEventListener('input', function() {
                validatePasswordConfirmation(passwordField, this);
            });
        }
    });
}

// Field validation functions
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';

    // Remove existing error
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.admin-form-error');
    if (existingError) existingError.remove();

    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required.';
    }

    // Minimum length validation
    const minLength = field.getAttribute('minlength');
    if (minLength && value.length < parseInt(minLength)) {
        isValid = false;
        errorMessage = `Minimum length is ${minLength} characters.`;
    }

    if (!isValid) {
        showFieldError(field, errorMessage);
    }

    return isValid;
}

function validateEmail(field) {
    const email = field.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Remove existing error
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.admin-form-error');
    if (existingError) existingError.remove();

    if (email && !emailRegex.test(email)) {
        showFieldError(field, 'Please enter a valid email address.');
        return false;
    }

    return true;
}

function validatePasswordConfirmation(passwordField, confirmField) {
    const password = passwordField.value;
    const confirm = confirmField.value;

    // Remove existing error
    confirmField.classList.remove('error');
    const existingError = confirmField.parentNode.querySelector('.admin-form-error');
    if (existingError) existingError.remove();

    if (confirm && password !== confirm) {
        showFieldError(confirmField, 'Passwords do not match.');
        return false;
    }

    return true;
}

function showFieldError(field, message) {
    field.classList.add('error');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'admin-form-error';
    errorDiv.textContent = message;

    field.parentNode.appendChild(errorDiv);
}

// Invite code functionality
function initializeInviteCodeFeatures() {
    // Auto-select invite code for easy copying
    const inviteCodeInputs = document.querySelectorAll('.admin-invite-code input, .invite-code-display');
    inviteCodeInputs.forEach(input => {
        input.addEventListener('click', function() {
            this.select();
        });
    });

    // Copy invite code buttons
    const copyButtons = document.querySelectorAll('.copy-invite-code');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const codeInput = this.parentNode.querySelector('input') ||
                            this.parentNode.querySelector('.invite-code-display') ||
                            document.querySelector('.admin-invite-code input');

            if (codeInput) {
                copyToClipboard(codeInput.value || codeInput.textContent);
            }
        });
    });
}

// Copy to clipboard functionality
function initializeCopyFeatures() {
    const copyButtons = document.querySelectorAll('[data-copy]');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy') ||
                             this.getAttribute('data-clipboard-text') ||
                             this.nextElementSibling?.textContent ||
                             this.previousElementSibling?.value;

            if (textToCopy) {
                copyToClipboard(textToCopy);
            }
        });
    });
}

function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Use modern clipboard API
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess();
        }).catch(err => {
            console.error('Failed to copy: ', err);
            fallbackCopyTextToClipboard(text);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopySuccess();
        } else {
            console.error('Fallback copy failed');
        }
    } catch (err) {
        console.error('Fallback copy failed: ', err);
    }

    document.body.removeChild(textArea);
}

function showCopySuccess() {
    // Create temporary success message
    const message = document.createElement('div');
    message.className = 'admin-success-message';
    message.style.position = 'fixed';
    message.style.top = '20px';
    message.style.right = '20px';
    message.style.zIndex = '9999';
    message.style.maxWidth = '300px';
    message.textContent = 'Copied to clipboard!';

    document.body.appendChild(message);

    // Remove after 3 seconds
    setTimeout(() => {
        if (message.parentNode) {
            message.parentNode.removeChild(message);
        }
    }, 3000);
}

// Role management functionality
function initializeRoleManagement() {
    const roleSelects = document.querySelectorAll('select[name="role"]');
    roleSelects.forEach(select => {
        select.addEventListener('change', function() {
            const selectedRole = this.value;
            const warningDiv = document.getElementById('role-warning');

            // Show warnings for sensitive roles
            if (selectedRole === 'super_admin') {
                showRoleWarning('Super Admin has full system access. Use with extreme caution!', 'danger');
            } else if (selectedRole === 'admin') {
                showRoleWarning('Admin has extensive system access. Assign carefully.', 'warning');
            } else if (warningDiv) {
                warningDiv.style.display = 'none';
            }
        });
    });
}

function showRoleWarning(message, type = 'warning') {
    let warningDiv = document.getElementById('role-warning');

    if (!warningDiv) {
        warningDiv = document.createElement('div');
        warningDiv.id = 'role-warning';

        const roleSelect = document.querySelector('select[name="role"]');
        if (roleSelect && roleSelect.parentNode) {
            roleSelect.parentNode.appendChild(warningDiv);
        }
    }

    warningDiv.className = type === 'danger' ? 'admin-error-message' : 'admin-success-message';
    warningDiv.textContent = message;
    warningDiv.style.display = 'block';
}

// Confirmation dialogs for dangerous actions
function confirmDangerousAction(message, callback) {
    const confirmed = confirm(message);
    if (confirmed && typeof callback === 'function') {
        callback();
    }
    return confirmed;
}

// Delete user confirmation
function confirmDeleteUser(username, deleteUrl) {
    const message = `Are you sure you want to delete user "${username}"?\n\nThis action cannot be undone and will:\n• Delete the user account\n• Remove all associated data\n• Clear viewing history\n\nType "DELETE" to confirm:`;

    const confirmation = prompt(message);
    if (confirmation === 'DELETE') {
        // Create form to submit DELETE request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = deleteUrl;
        form.style.display = 'none';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken.getAttribute('content');
            form.appendChild(csrfInput);
        }

        // Add method override
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);

        document.body.appendChild(form);
        form.submit();
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize basic functionality even without explicit config
    initializeFormValidation();
    initializePasswordGenerator();
    initializeInviteCodeFeatures();
    initializeCopyFeatures();
    initializeRoleManagement();

    // Set default config if not provided
    if (typeof window.adminFormsConfig === 'undefined') {
        window.adminFormsConfig = {
            generatePasswordUrl: '/admin/users/generate-password',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        };
    }
});