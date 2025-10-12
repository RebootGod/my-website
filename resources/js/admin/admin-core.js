/**
 * ========================================
 * ADMIN CORE FUNCTIONALITY
 * Base JavaScript functionality for admin interface
 * ========================================
 */

// Admin namespace
window.Admin = window.Admin || {};

/**
 * Initialize admin interface
 */
Admin.init = function() {
    console.log('🔧 Admin Core: Initializing...');

    // Initialize components
    Admin.initAlerts();
    Admin.initNavigation();
    Admin.initUtilities();

    console.log('✅ Admin Core: Initialized successfully');
};

/**
 * Alert system management
 */
Admin.initAlerts = function() {
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.admin-alert');
    alerts.forEach(alert => {
        const closeBtn = alert.querySelector('.admin-alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                Admin.dismissAlert(alert);
            });
        }

        // Auto-dismiss after 5 seconds for success messages
        if (alert.classList.contains('admin-alert-success')) {
            setTimeout(() => {
                Admin.dismissAlert(alert);
            }, 5000);
        }
    });
};

/**
 * Dismiss alert with animation
 */
Admin.dismissAlert = function(alert) {
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-10px)';
    setTimeout(() => {
        alert.remove();
    }, 300);
};

/**
 * Show toast notification
 */
Admin.showToast = function(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `admin-toast admin-toast-${type}`;
    toast.innerHTML = `
        <div class="admin-toast-content">
            <span class="admin-toast-message">${message}</span>
            <button class="admin-toast-close">&times;</button>
        </div>
    `;

    // Add to page - use existing toast-container
    let container = document.querySelector('#toast-container');
    if (!container) {
        // Fallback: create container with correct ID
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container admin-toast-container';
        document.body.appendChild(container);
    }

    container.appendChild(toast);

    // Show animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Add close functionality
    const closeBtn = toast.querySelector('.admin-toast-close');
    closeBtn.addEventListener('click', () => {
        Admin.dismissToast(toast);
    });

    // Auto-dismiss
    if (duration > 0) {
        setTimeout(() => {
            Admin.dismissToast(toast);
        }, duration);
    }
};

/**
 * Dismiss toast notification
 */
Admin.dismissToast = function(toast) {
    toast.classList.remove('show');
    setTimeout(() => {
        toast.remove();
    }, 300);
};

/**
 * Navigation functionality
 */
Admin.initNavigation = function() {
    // Add active state to current page
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.admin-nav-item');

    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.startsWith(href)) {
            item.classList.add('active');
        }
    });
};

/**
 * Utility functions
 */
Admin.initUtilities = function() {
    // Add CSRF token to all AJAX requests
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        window.axios = window.axios || {};
        if (window.axios.defaults) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        }

        // For jQuery if available
        if (window.$) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token.content
                }
            });
        }
    }

    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Processing...';

                // Re-enable after 5 seconds to prevent permanent disable
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 5000);
            }
        });
    });
};

/**
 * Confirm dialog helper
 */
Admin.confirm = function(message, callback) {
    if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
        return true;
    }
    return false;
};

/**
 * Show loading overlay
 */
Admin.showLoading = function(target = 'body') {
    const targetEl = typeof target === 'string' ? document.querySelector(target) : target;
    if (!targetEl) return;

    const existing = targetEl.querySelector('.admin-loading-overlay');
    if (existing) return;

    const overlay = document.createElement('div');
    overlay.className = 'admin-loading-overlay';
    overlay.innerHTML = `
        <div class="admin-loading-spinner">
            <div class="admin-spinner"></div>
            <span>Loading...</span>
        </div>
    `;

    targetEl.style.position = 'relative';
    targetEl.appendChild(overlay);
};

/**
 * Hide loading overlay
 */
Admin.hideLoading = function(target = 'body') {
    const targetEl = typeof target === 'string' ? document.querySelector(target) : target;
    if (!targetEl) return;

    const overlay = targetEl.querySelector('.admin-loading-overlay');
    if (overlay) {
        overlay.remove();
    }
};

/**
 * Format numbers with commas
 */
Admin.formatNumber = function(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

/**
 * Debounce function for search inputs
 */
Admin.debounce = function(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Admin.init();
});

// Expose showToast to global scope for compatibility
window.showToast = Admin.showToast;

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Admin;
}