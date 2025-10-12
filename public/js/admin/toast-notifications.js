/**
 * ========================================
 * TOAST NOTIFICATION SYSTEM
 * Modern replacement for alert() dialogs
 * ========================================
 * 
 * Features:
 * - Non-blocking notifications
 * - Multiple types (success, error, warning, info)
 * - Auto-dismiss with configurable timeout
 * - Stack multiple notifications
 * - Action buttons support
 * - Animations
 * 
 * Usage:
 * Toast.success('Movie saved successfully!');
 * Toast.error('Failed to save movie');
 * Toast.warning('This action cannot be undone');
 * Toast.info('New update available');
 */

class ToastNotification {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.defaultOptions = {
            duration: 5000, // 5 seconds
            position: 'top-right', // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
            maxToasts: 5,
            closeButton: true,
            progressBar: true,
            pauseOnHover: true,
            animation: 'slide' // slide, fade, bounce
        };
        
        this.init();
    }

    /**
     * Initialize toast container
     */
    init() {
        // Create container if doesn't exist
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }

    /**
     * Show success toast
     */
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    /**
     * Show error toast
     */
    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    /**
     * Show warning toast
     */
    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    /**
     * Show info toast
     */
    info(message, options = {}) {
        return this.show(message, 'info', options);
    }

    /**
     * Show toast notification
     */
    show(message, type = 'info', options = {}) {
        // Merge options with defaults
        const config = { ...this.defaultOptions, ...options };
        
        // Limit max toasts
        if (this.toasts.length >= config.maxToasts) {
            this.removeOldest();
        }

        // Create toast element
        const toast = this.createToast(message, type, config);
        
        // Add to container
        this.container.appendChild(toast);
        this.toasts.push(toast);

        // Trigger animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Auto dismiss if duration set
        if (config.duration > 0) {
            this.autoDismiss(toast, config.duration, config.pauseOnHover);
        }

        return toast;
    }

    /**
     * Create toast element
     */
    createToast(message, type, config) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} toast-animation-${config.animation}`;
        toast.setAttribute('role', 'alert');

        // Icon
        const icon = this.getIcon(type);

        // Close button
        const closeBtn = config.closeButton 
            ? `<button class="toast-close" aria-label="Close notification">&times;</button>`
            : '';

        // Progress bar
        const progressBar = config.progressBar && config.duration > 0
            ? `<div class="toast-progress"><div class="toast-progress-bar" style="animation-duration: ${config.duration}ms;"></div></div>`
            : '';

        // Actions
        const actions = config.actions 
            ? this.createActions(config.actions)
            : '';

        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">${icon}</div>
                <div class="toast-body">
                    <div class="toast-message">${this.escapeHtml(message)}</div>
                    ${actions}
                </div>
                ${closeBtn}
            </div>
            ${progressBar}
        `;

        // Close button event
        if (config.closeButton) {
            toast.querySelector('.toast-close').addEventListener('click', () => {
                this.dismiss(toast);
            });
        }

        // Action buttons events
        if (config.actions) {
            config.actions.forEach((action, index) => {
                const btn = toast.querySelector(`[data-action-index="${index}"]`);
                if (btn && action.callback) {
                    btn.addEventListener('click', () => {
                        action.callback();
                        if (action.closeOnClick !== false) {
                            this.dismiss(toast);
                        }
                    });
                }
            });
        }

        return toast;
    }

    /**
     * Create action buttons
     */
    createActions(actions) {
        if (!actions || actions.length === 0) return '';

        const buttons = actions.map((action, index) => {
            const className = action.primary ? 'toast-action-primary' : 'toast-action-secondary';
            return `<button class="toast-action ${className}" data-action-index="${index}">${this.escapeHtml(action.text)}</button>`;
        }).join('');

        return `<div class="toast-actions">${buttons}</div>`;
    }

    /**
     * Get icon for toast type
     */
    getIcon(type) {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-times-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        return icons[type] || icons.info;
    }

    /**
     * Auto dismiss toast after duration
     */
    autoDismiss(toast, duration, pauseOnHover) {
        let timeoutId;
        let remainingTime = duration;
        let startTime = Date.now();

        const dismiss = () => {
            this.dismiss(toast);
        };

        const startTimer = () => {
            startTime = Date.now();
            timeoutId = setTimeout(dismiss, remainingTime);
        };

        const pauseTimer = () => {
            clearTimeout(timeoutId);
            remainingTime -= (Date.now() - startTime);
        };

        if (pauseOnHover) {
            toast.addEventListener('mouseenter', pauseTimer);
            toast.addEventListener('mouseleave', startTimer);
        }

        startTimer();
    }

    /**
     * Dismiss toast
     */
    dismiss(toast) {
        toast.classList.remove('show');
        toast.classList.add('hide');

        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
            
            const index = this.toasts.indexOf(toast);
            if (index > -1) {
                this.toasts.splice(index, 1);
            }
        }, 300); // Wait for animation
    }

    /**
     * Remove oldest toast
     */
    removeOldest() {
        if (this.toasts.length > 0) {
            this.dismiss(this.toasts[0]);
        }
    }

    /**
     * Clear all toasts
     */
    clearAll() {
        [...this.toasts].forEach(toast => {
            this.dismiss(toast);
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Create global Toast instance
window.Toast = new ToastNotification();

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastNotification;
}

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('✅ Toast Notification System initialized');
    });
} else {
    console.log('✅ Toast Notification System initialized');
}
