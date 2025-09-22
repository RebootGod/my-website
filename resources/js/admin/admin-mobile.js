/**
 * ========================================
 * ADMIN MOBILE FUNCTIONALITY
 * Mobile-specific interactions for admin interface
 * ========================================
 */

// Extend Admin namespace
window.Admin = window.Admin || {};

/**
 * Initialize mobile functionality
 */
Admin.Mobile = {
    isInitialized: false,
    isMobile: false,
    sidebarOpen: false,

    init: function() {
        if (this.isInitialized) return;

        console.log('📱 Admin Mobile: Initializing...');

        this.detectMobile();
        this.initSidebar();
        this.initTouchEvents();
        this.initResizeHandler();

        this.isInitialized = true;
        console.log('✅ Admin Mobile: Initialized successfully');
    },

    /**
     * Detect if device is mobile/tablet
     */
    detectMobile: function() {
        this.isMobile = window.innerWidth <= 1024;
        document.body.classList.toggle('admin-mobile', this.isMobile);
    },

    /**
     * Initialize sidebar functionality
     */
    initSidebar: function() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = this.createOverlay();
        const toggleBtn = this.createToggleButton();
        const closeBtn = this.createCloseButton();

        if (!sidebar) return;

        // Add overlay and close button to sidebar
        if (closeBtn) {
            sidebar.appendChild(closeBtn);
        }

        // Toggle button click
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleSidebar();
            });
        }

        // Close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeSidebar();
            });
        }

        // Overlay click
        if (overlay) {
            overlay.addEventListener('click', () => {
                this.closeSidebar();
            });
        }

        // Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.sidebarOpen) {
                this.closeSidebar();
            }
        });
    },

    /**
     * Create mobile overlay
     */
    createOverlay: function() {
        let overlay = document.querySelector('.admin-mobile-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'admin-mobile-overlay';
            document.body.appendChild(overlay);
        }
        return overlay;
    },

    /**
     * Create toggle button
     */
    createToggleButton: function() {
        let toggleBtn = document.querySelector('.admin-mobile-toggle');
        if (!toggleBtn) {
            toggleBtn = document.createElement('button');
            toggleBtn.className = 'admin-mobile-toggle';
            toggleBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
            `;
            toggleBtn.setAttribute('aria-label', 'Toggle navigation');
            document.body.appendChild(toggleBtn);
        }
        return toggleBtn;
    },

    /**
     * Create close button for sidebar
     */
    createCloseButton: function() {
        const sidebar = document.querySelector('.admin-sidebar');
        if (!sidebar) return null;

        let closeBtn = sidebar.querySelector('.admin-sidebar-close');
        if (!closeBtn) {
            closeBtn = document.createElement('button');
            closeBtn.className = 'admin-sidebar-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.setAttribute('aria-label', 'Close navigation');
        }
        return closeBtn;
    },

    /**
     * Toggle sidebar visibility
     */
    toggleSidebar: function() {
        if (this.sidebarOpen) {
            this.closeSidebar();
        } else {
            this.openSidebar();
        }
    },

    /**
     * Open sidebar
     */
    openSidebar: function() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.admin-mobile-overlay');

        if (sidebar) {
            sidebar.classList.add('active');
            this.sidebarOpen = true;
            document.body.style.overflow = 'hidden';
        }

        if (overlay) {
            overlay.classList.add('active');
        }

        // Focus management for accessibility
        const firstNavLink = sidebar?.querySelector('.admin-nav-item');
        if (firstNavLink) {
            firstNavLink.focus();
        }
    },

    /**
     * Close sidebar
     */
    closeSidebar: function() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.admin-mobile-overlay');

        if (sidebar) {
            sidebar.classList.remove('active');
            this.sidebarOpen = false;
            document.body.style.overflow = '';
        }

        if (overlay) {
            overlay.classList.remove('active');
        }

        // Return focus to toggle button
        const toggleBtn = document.querySelector('.admin-mobile-toggle');
        if (toggleBtn) {
            toggleBtn.focus();
        }
    },

    /**
     * Initialize touch events for better mobile experience
     */
    initTouchEvents: function() {
        let touchStartX = 0;
        let touchEndX = 0;

        // Swipe to open/close sidebar
        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        });
    },

    /**
     * Handle swipe gestures
     */
    handleSwipe: function() {
        const swipeThreshold = 50;
        const swipeDistance = touchEndX - touchStartX;

        // Swipe right to open sidebar (from left edge)
        if (swipeDistance > swipeThreshold && touchStartX < 50 && !this.sidebarOpen) {
            this.openSidebar();
        }

        // Swipe left to close sidebar
        if (swipeDistance < -swipeThreshold && this.sidebarOpen) {
            this.closeSidebar();
        }
    },

    /**
     * Handle window resize
     */
    initResizeHandler: function() {
        const resizeHandler = Admin.debounce(() => {
            const wasMobile = this.isMobile;
            this.detectMobile();

            // If switching from mobile to desktop, close sidebar
            if (wasMobile && !this.isMobile && this.sidebarOpen) {
                this.closeSidebar();
            }

            // Update main content margin
            this.updateMainContentMargin();
        }, 150);

        window.addEventListener('resize', resizeHandler);
    },

    /**
     * Update main content margin based on screen size
     */
    updateMainContentMargin: function() {
        const main = document.querySelector('.admin-main');
        if (!main) return;

        if (this.isMobile) {
            main.classList.add('sidebar-collapsed');
        } else {
            main.classList.remove('sidebar-collapsed');
        }
    },

    /**
     * Add loading state for mobile interactions
     */
    addTouchFeedback: function(element) {
        if (!element) return;

        element.addEventListener('touchstart', function() {
            this.style.opacity = '0.7';
        });

        element.addEventListener('touchend', function() {
            setTimeout(() => {
                this.style.opacity = '';
            }, 150);
        });
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Admin.Mobile.init();
});

// Re-initialize on navigation (for SPAs)
window.addEventListener('popstate', function() {
    Admin.Mobile.init();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Admin.Mobile;
}