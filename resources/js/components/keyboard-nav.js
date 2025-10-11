/* ======================================== */
/* KEYBOARD NAVIGATION COMPONENT */
/* ======================================== */
/* Phase 6.4: Accessibility Audit */
/* Enhanced keyboard navigation and shortcuts */

class KeyboardNav {
    constructor() {
        this.focusTrap = null;
        this.lastFocusedElement = null;
        this.shortcuts = new Map();
        
        this.init();
    }

    init() {
        console.log('⌨️ Keyboard Navigation: Initializing...');
        
        this.setupGlobalHandlers();
        this.setupArrowNavigation();
        this.setupModalHandlers();
        this.registerDefaultShortcuts();
        
        console.log('✅ Keyboard Navigation: Ready');
    }

    /**
     * Setup global keyboard handlers
     */
    setupGlobalHandlers() {
        document.addEventListener('keydown', (e) => {
            // Check for registered shortcuts
            const key = this.getShortcutKey(e);
            if (this.shortcuts.has(key)) {
                e.preventDefault();
                this.shortcuts.get(key)();
                return;
            }

            // Tab trap for modals
            if (e.key === 'Tab' && this.focusTrap) {
                this.handleTabTrap(e);
            }

            // Escape to close
            if (e.key === 'Escape') {
                this.handleEscape();
            }
        });
    }

    /**
     * Get shortcut key string
     */
    getShortcutKey(e) {
        const parts = [];
        if (e.ctrlKey) parts.push('ctrl');
        if (e.shiftKey) parts.push('shift');
        if (e.altKey) parts.push('alt');
        parts.push(e.key.toLowerCase());
        return parts.join('+');
    }

    /**
     * Register keyboard shortcut
     */
    registerShortcut(keys, callback, description = '') {
        this.shortcuts.set(keys, callback);
        console.log(`⌨️ Registered shortcut: ${keys} - ${description}`);
    }

    /**
     * Register default shortcuts
     */
    registerDefaultShortcuts() {
        // Search focus
        this.registerShortcut('ctrl+k', () => {
            const searchInput = document.querySelector('input[type="search"], #search');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }, 'Focus search');

        // Home
        this.registerShortcut('ctrl+h', () => {
            window.location.href = '/';
        }, 'Go home');

        // Watchlist
        this.registerShortcut('ctrl+w', () => {
            const watchlistLink = document.querySelector('a[href*="watchlist"]');
            if (watchlistLink) watchlistLink.click();
        }, 'Go to watchlist');
    }

    /**
     * Setup arrow key navigation for grids
     */
    setupArrowNavigation() {
        document.addEventListener('keydown', (e) => {
            const focused = document.activeElement;
            
            // Only handle arrows on focusable items
            if (!focused.matches('[data-arrow-nav], .movie-card, .series-card')) {
                return;
            }

            const grid = focused.closest('[data-grid-nav]');
            if (!grid) return;

            const items = Array.from(grid.querySelectorAll('[data-arrow-nav], .movie-card, .series-card'));
            const currentIndex = items.indexOf(focused);
            
            if (currentIndex === -1) return;

            let newIndex = currentIndex;
            const cols = parseInt(grid.dataset.gridCols || 4);

            switch (e.key) {
                case 'ArrowRight':
                    e.preventDefault();
                    newIndex = Math.min(currentIndex + 1, items.length - 1);
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    newIndex = Math.max(currentIndex - 1, 0);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    newIndex = Math.min(currentIndex + cols, items.length - 1);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    newIndex = Math.max(currentIndex - cols, 0);
                    break;
                case 'Home':
                    e.preventDefault();
                    newIndex = 0;
                    break;
                case 'End':
                    e.preventDefault();
                    newIndex = items.length - 1;
                    break;
            }

            if (newIndex !== currentIndex) {
                items[newIndex].focus();
                items[newIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });
    }

    /**
     * Setup modal keyboard handlers
     */
    setupModalHandlers() {
        // Observe for modal open/close
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.matches('[role="dialog"], .modal')) {
                        this.trapFocus(node);
                    }
                });

                mutation.removedNodes.forEach((node) => {
                    if (node.nodeType === 1 && node.matches('[role="dialog"], .modal')) {
                        this.releaseFocus();
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    /**
     * Trap focus within element
     */
    trapFocus(element) {
        this.lastFocusedElement = document.activeElement;
        this.focusTrap = element;

        // Find all focusable elements
        const focusableElements = this.getFocusableElements(element);
        
        if (focusableElements.length > 0) {
            // Focus first element
            setTimeout(() => focusableElements[0].focus(), 100);
        }

        console.log('🔒 Focus trapped in modal');
    }

    /**
     * Release focus trap
     */
    releaseFocus() {
        if (this.lastFocusedElement) {
            this.lastFocusedElement.focus();
        }
        this.focusTrap = null;
        this.lastFocusedElement = null;
        
        console.log('🔓 Focus released from modal');
    }

    /**
     * Handle tab trap
     */
    handleTabTrap(e) {
        const focusableElements = this.getFocusableElements(this.focusTrap);
        
        if (focusableElements.length === 0) return;

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.shiftKey) {
            // Shift + Tab
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    /**
     * Get focusable elements
     */
    getFocusableElements(container) {
        const selector = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
        return Array.from(container.querySelectorAll(selector))
            .filter(el => !el.hasAttribute('hidden'));
    }

    /**
     * Handle escape key
     */
    handleEscape() {
        // Close modal
        const modal = document.querySelector('[role="dialog"]:not([hidden]), .modal:not([hidden])');
        if (modal) {
            const closeBtn = modal.querySelector('[data-close], .close, .modal-close');
            if (closeBtn) {
                closeBtn.click();
            } else {
                modal.remove();
            }
            return;
        }

        // Close dropdown
        const dropdown = document.querySelector('.dropdown.open, [aria-expanded="true"]');
        if (dropdown) {
            dropdown.classList.remove('open');
            dropdown.setAttribute('aria-expanded', 'false');
            return;
        }
    }

    /**
     * Focus element
     */
    focus(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.focus();
            element.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    /**
     * Make element focusable
     */
    makeFocusable(selector) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            if (!el.hasAttribute('tabindex')) {
                el.setAttribute('tabindex', '0');
            }
        });
    }
}

// Initialize
const keyboardNav = new KeyboardNav();

// Make globally available
window.keyboardNav = keyboardNav;

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KeyboardNav;
}
