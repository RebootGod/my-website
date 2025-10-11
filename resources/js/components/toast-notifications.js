/* ======================================== */
/* TOAST NOTIFICATIONS COMPONENT */
/* ======================================== */
/* Phase 6.2: Modern toast notification system */
/* Success, error, warning, info messages with animations */

class ToastNotification {
    constructor() {
        this.container = null;
        this.toasts = new Map();
        this.init();
    }

    init() {
        console.log('🍞 Toast Notifications: Initializing...');
        this.createContainer();
        console.log('✅ Toast Notifications: Ready');
    }

    /**
     * Create toast container
     */
    createContainer() {
        if (this.container) return;

        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }

    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - success|error|warning|info
     * @param {number} duration - Auto-dismiss duration (ms)
     * @param {object} options - Additional options
     */
    show(message, type = 'info', duration = 3000, options = {}) {
        const toast = this.createToast(message, type, duration, options);
        this.container.appendChild(toast);

        // Trigger entrance animation
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => this.dismiss(toast), duration);
        }

        return toast;
    }

    /**
     * Create toast element
     */
    createToast(message, type, duration, options) {
        const toast = document.createElement('div');
        const id = `toast-${Date.now()}-${Math.random()}`;
        toast.id = id;
        toast.className = `toast toast-${type}`;
        
        // Icon based on type
        const icon = this.getIcon(type);
        
        // Progress bar for timed toasts
        const progressBar = duration > 0 ? `
            <div class="toast-progress">
                <div class="toast-progress-bar" style="animation-duration: ${duration}ms"></div>
            </div>
        ` : '';

        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">
                ${options.title ? `<div class="toast-title">${options.title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            ${progressBar}
        `;

        // Close button handler
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => this.dismiss(toast));

        // Store reference
        this.toasts.set(id, toast);

        return toast;
    }

    /**
     * Get icon for toast type
     */
    getIcon(type) {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-exclamation-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        return icons[type] || icons.info;
    }

    /**
     * Dismiss toast
     */
    dismiss(toast) {
        if (typeof toast === 'string') {
            toast = this.toasts.get(toast) || document.getElementById(toast);
        }

        if (!toast) return;

        toast.classList.remove('show');
        toast.classList.add('hide');

        setTimeout(() => {
            toast.remove();
            this.toasts.delete(toast.id);
        }, 300);
    }

    /**
     * Shorthand methods
     */
    success(message, duration = 3000, options = {}) {
        return this.show(message, 'success', duration, options);
    }

    error(message, duration = 4000, options = {}) {
        return this.show(message, 'error', duration, options);
    }

    warning(message, duration = 3500, options = {}) {
        return this.show(message, 'warning', duration, options);
    }

    info(message, duration = 3000, options = {}) {
        return this.show(message, 'info', duration, options);
    }

    /**
     * Show loading toast (doesn't auto-dismiss)
     */
    loading(message = 'Loading...', options = {}) {
        const toast = this.show(message, 'info', 0, {
            ...options,
            title: options.title || ''
        });

        // Add spinner
        const icon = toast.querySelector('.toast-icon');
        icon.innerHTML = '<div class="loading-spinner-sm"></div>';

        return toast;
    }

    /**
     * Update existing toast
     */
    update(toast, message, type = null) {
        if (typeof toast === 'string') {
            toast = this.toasts.get(toast) || document.getElementById(toast);
        }

        if (!toast) return;

        const messageEl = toast.querySelector('.toast-message');
        if (messageEl) {
            messageEl.textContent = message;
        }

        if (type) {
            // Update type class
            toast.className = `toast toast-${type} show`;

            // Update icon
            const iconEl = toast.querySelector('.toast-icon');
            if (iconEl) {
                iconEl.innerHTML = this.getIcon(type);
            }
        }
    }

    /**
     * Dismiss all toasts
     */
    dismissAll() {
        this.toasts.forEach(toast => this.dismiss(toast));
    }

    /**
     * Show confirmation toast with actions
     */
    confirm(message, onConfirm, onCancel = null, options = {}) {
        const toast = document.createElement('div');
        toast.className = 'toast toast-confirm show';
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="toast-content">
                ${options.title ? `<div class="toast-title">${options.title}</div>` : ''}
                <div class="toast-message">${message}</div>
                <div class="toast-actions">
                    <button class="toast-btn toast-btn-cancel">Cancel</button>
                    <button class="toast-btn toast-btn-confirm">Confirm</button>
                </div>
            </div>
        `;

        this.container.appendChild(toast);

        // Button handlers
        const cancelBtn = toast.querySelector('.toast-btn-cancel');
        const confirmBtn = toast.querySelector('.toast-btn-confirm');

        cancelBtn.addEventListener('click', () => {
            this.dismiss(toast);
            if (onCancel) onCancel();
        });

        confirmBtn.addEventListener('click', () => {
            this.dismiss(toast);
            if (onConfirm) onConfirm();
        });

        return toast;
    }
}

// Inject toast styles
const toastStyles = document.createElement('style');
toastStyles.textContent = `
.toast-container {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 10000;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
}

.toast {
    background: rgba(20, 24, 38, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 16px;
    min-width: 320px;
    max-width: 400px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transform: translateX(400px);
    opacity: 0;
    transition: transform 0.3s ease, opacity 0.3s ease;
    pointer-events: auto;
    position: relative;
    overflow: hidden;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast.hide {
    transform: translateX(400px);
    opacity: 0;
}

.toast-icon {
    flex-shrink: 0;
    font-size: 20px;
}

.toast-success { border-left: 4px solid #10b981; }
.toast-success .toast-icon { color: #10b981; }

.toast-error { border-left: 4px solid #ef4444; }
.toast-error .toast-icon { color: #ef4444; }

.toast-warning { border-left: 4px solid #f59e0b; }
.toast-warning .toast-icon { color: #f59e0b; }

.toast-info { border-left: 4px solid #3b82f6; }
.toast-info .toast-icon { color: #3b82f6; }

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    margin-bottom: 4px;
    color: #fff;
}

.toast-message {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.4;
}

.toast-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background 0.2s, color 0.2s;
}

.toast-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(255, 255, 255, 0.1);
    overflow: hidden;
}

.toast-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    animation: toast-progress linear forwards;
}

@keyframes toast-progress {
    from { width: 100%; }
    to { width: 0%; }
}

.toast-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.toast-btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.toast-btn-cancel {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.toast-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.2);
}

.toast-btn-confirm {
    background: #667eea;
    color: #fff;
}

.toast-btn-confirm:hover {
    background: #5568d3;
}

@media (max-width: 480px) {
    .toast-container {
        top: 70px;
        right: 12px;
        left: 12px;
    }

    .toast {
        min-width: auto;
        max-width: none;
    }
}
`;

document.head.appendChild(toastStyles);

// Initialize and export
const toast = new ToastNotification();

// Make available globally
window.toast = toast;

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastNotification;
}
