/* Admin Forms JavaScript - Following workinginstruction.md separate JS file */
/* File: public/js/admin/forms.js */

/**
 * Initialize admin form functionality
 * @param {Object} config - Configuration object
 */
function initializeAdminForms(config) {
        
    // Initialize password generation
    initPasswordGeneration(config);
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize status toggles
    initStatusToggles();
    
    // Initialize confirmation dialogs
    initConfirmationDialogs();
}

/**
 * Initialize password generation functionality
 * @param {Object} config - Configuration object
 */
function initPasswordGeneration(config) {
    const generatePasswordBtn = document.getElementById('generate-password');
    const passwordInput = document.getElementById('password');
    
    if (generatePasswordBtn && passwordInput) {
        generatePasswordBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const btn = this;
            const originalText = btn.innerHTML;
            
            try {
                // Show loading state
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
                
                // Fetch new password
                const response = await fetch(config.generatePasswordUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });
                
                if (!response.ok) {
                    throw new Error('Failed to generate password');
                }
                
                const data = await response.json();
                
                if (data.success && data.password) {
                    passwordInput.value = data.password;
                    passwordInput.type = 'text'; // Show generated password
                    
                    // Show success message
                    showNotification('Password generated successfully!', 'success');
                    
                    // Hide password after 5 seconds
                    setTimeout(() => {
                        passwordInput.type = 'password';
                    }, 5000);
                } else {
                    throw new Error(data.message || 'Failed to generate password');
                }
                
            } catch (error) {
                                showNotification('Failed to generate password. Please try again.', 'error');
            } finally {
                // Restore button state
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    }
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    });
}

/**
 * Validate entire form
 * @param {HTMLFormElement} form - Form to validate
 * @returns {boolean} - Validation result
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Validate individual field
 * @param {HTMLElement} field - Field to validate
 * @returns {boolean} - Validation result
 */
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Check required fields
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required.';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address.';
        }
    }
    
    // Password validation
    if (field.name === 'password' && value) {
        if (value.length < 8) {
            isValid = false;
            errorMessage = 'Password must be at least 8 characters long.';
        }
    }
    
    // Username validation
    if (field.name === 'username' && value) {
        const usernameRegex = /^[a-zA-Z0-9_-]+$/;
        if (!usernameRegex.test(value)) {
            isValid = false;
            errorMessage = 'Username can only contain letters, numbers, underscores, and hyphens.';
        }
    }
    
    // Show/hide error message
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

/**
 * Show field error message
 * @param {HTMLElement} field - Field element
 * @param {string} message - Error message
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('border-red-500');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-400 text-sm mt-1 field-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Clear field error message
 * @param {HTMLElement} field - Field element
 */
function clearFieldError(field) {
    field.classList.remove('border-red-500');
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Initialize status toggle functionality
 */
function initStatusToggles() {
    const toggleButtons = document.querySelectorAll('[data-toggle="status"]');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const userId = this.dataset.userId;
            const currentStatus = this.dataset.currentStatus;
            const action = this.dataset.action;
            
            if (!userId || !action) {
                                return;
            }
            
            // Confirm action
            const actionText = action === 'ban' ? 'ban' : 'unban';
            if (!confirm(`Are you sure you want to ${actionText} this user?`)) {
                return;
            }
            
            const btn = this;
            const originalHtml = btn.innerHTML;
            
            try {
                // Show loading state
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Make request
                const response = await fetch(btn.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message, 'success');
                    
                    // Update button state
                    updateStatusButton(btn, data.new_status);
                    
                    // Optionally reload page after delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Action failed');
                }
                
            } catch (error) {
                                showNotification('Failed to update user status. Please try again.', 'error');
                
                // Restore button
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    });
}

/**
 * Update status button appearance
 * @param {HTMLElement} button - Button element
 * @param {string} newStatus - New user status
 */
function updateStatusButton(button, newStatus) {
    // Update button text and classes based on new status
    if (newStatus === 'banned') {
        button.innerHTML = '<i class="fas fa-unlock mr-2"></i>Unban User';
        button.className = 'btn btn-warning';
        button.dataset.action = 'unban';
        button.dataset.currentStatus = 'banned';
    } else {
        button.innerHTML = '<i class="fas fa-ban mr-2"></i>Ban User';
        button.className = 'btn btn-danger';
        button.dataset.action = 'ban';
        button.dataset.currentStatus = 'active';
    }
    
    button.disabled = false;
}

/**
 * Initialize confirmation dialogs
 */
function initConfirmationDialogs() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.dataset.confirm;
            if (message && !confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

/**
 * Show notification message
 * @param {string} message - Notification message
 * @param {string} type - Notification type (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification alert alert-${type} fixed top-4 right-4 z-50 max-w-md shadow-lg`;
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-${getIconForType(type)} mr-2"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="ml-4 text-lg" onclick="this.parentElement.parentElement.remove()">
                &times;
            </button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Get icon for notification type
 * @param {string} type - Notification type
 * @returns {string} - Icon class
 */
function getIconForType(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    
    return icons[type] || 'info-circle';
}

/**
 * Initialize on DOM ready
 */
document.addEventListener('DOMContentLoaded', function() {
    });

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeAdminForms,
        showNotification,
        validateForm,
        validateField
    };
}