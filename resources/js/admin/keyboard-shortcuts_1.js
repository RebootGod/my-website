/* ======================================== */
/* KEYBOARD SHORTCUTS SYSTEM - PART 1 */
/* ======================================== */
/* File: resources/js/admin/keyboard-shortcuts_1.js */
/* Continued in keyboard-shortcuts_2.js */

/**
 * KeyboardShortcuts - Global keyboard navigation system
 * 
 * Features:
 * - Ctrl/Cmd + K: Open global search modal
 * - Ctrl/Cmd + ?: Show shortcuts help modal
 * - Escape: Close modals
 * - Browser shortcut conflict prevention
 * 
 * Security: XSS prevention via escapeHtml()
 */

class KeyboardShortcuts {
    constructor() {
        this.shortcuts = new Map();
        this.isSearchModalOpen = false;
        this.isHelpModalOpen = false;
        this.searchResults = [];
        this.selectedIndex = -1;
        
        this.init();
    }

    /**
     * Initialize keyboard shortcuts system
     */
    init() {
        this.registerShortcuts();
        this.attachEventListeners();
        this.createModals();
        console.log('Keyboard Shortcuts initialized');
    }

    /**
     * Register all keyboard shortcuts
     */
    registerShortcuts() {
        // Ctrl/Cmd + K: Global Search
        this.shortcuts.set('k+ctrl', {
            keys: ['k', 'control'],
            description: 'Open Global Search',
            action: () => this.openSearchModal()
        });

        this.shortcuts.set('k+meta', {
            keys: ['k', 'meta'],
            description: 'Open Global Search',
            action: () => this.openSearchModal()
        });

        // Ctrl/Cmd + ?: Show Help
        this.shortcuts.set('?+shift', {
            keys: ['?', 'shift'],
            description: 'Show Keyboard Shortcuts',
            action: () => this.openHelpModal()
        });

        // Escape: Close modals
        this.shortcuts.set('escape', {
            keys: ['escape'],
            description: 'Close Modal',
            action: () => this.closeModals()
        });
    }

    /**
     * Attach global keyboard event listeners
     */
    attachEventListeners() {
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
    }

    /**
     * Handle keydown events
     */
    handleKeyDown(e) {
        // Ignore if user is typing in input/textarea
        const activeElement = document.activeElement;
        const isTyping = activeElement.tagName === 'INPUT' || 
                        activeElement.tagName === 'TEXTAREA' ||
                        activeElement.isContentEditable;

        // Allow Escape even when typing
        if (e.key === 'Escape') {
            this.closeModals();
            return;
        }

        // Ignore other shortcuts when typing (except in search modal)
        if (isTyping && !this.isSearchModalOpen) {
            return;
        }

        // Handle Ctrl/Cmd + K
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            this.openSearchModal();
            return;
        }

        // Handle ? (Shift + /)
        if (e.shiftKey && e.key === '?') {
            e.preventDefault();
            this.openHelpModal();
            return;
        }

        // Handle Arrow keys in search modal
        if (this.isSearchModalOpen) {
            this.handleSearchNavigation(e);
        }
    }

    /**
     * Create modal HTML elements
     */
    createModals() {
        // Search Modal
        const searchModal = document.createElement('div');
        searchModal.id = 'keyboardSearchModal';
        searchModal.className = 'keyboard-modal keyboard-search-modal';
        searchModal.innerHTML = `
            <div class="keyboard-modal-backdrop" onclick="KeyboardShortcuts.instance.closeSearchModal()"></div>
            <div class="keyboard-modal-content">
                <div class="keyboard-search-header">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="keyboardSearchInput" 
                           placeholder="Search movies, series, users..."
                           autocomplete="off">
                    <button class="keyboard-modal-close" onclick="KeyboardShortcuts.instance.closeSearchModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="keyboard-search-results" id="keyboardSearchResults">
                    <div class="keyboard-search-hint">
                        <i class="fas fa-keyboard"></i>
                        <span>Type to search across all sections</span>
                    </div>
                </div>
                <div class="keyboard-search-footer">
                    <div class="keyboard-search-shortcuts">
                        <span><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
                        <span><kbd>Enter</kbd> Select</span>
                        <span><kbd>Esc</kbd> Close</span>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(searchModal);

        // Help Modal
        const helpModal = document.createElement('div');
        helpModal.id = 'keyboardHelpModal';
        helpModal.className = 'keyboard-modal keyboard-help-modal';
        helpModal.innerHTML = `
            <div class="keyboard-modal-backdrop" onclick="KeyboardShortcuts.instance.closeHelpModal()"></div>
            <div class="keyboard-modal-content">
                <div class="keyboard-help-header">
                    <h3><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h3>
                    <button class="keyboard-modal-close" onclick="KeyboardShortcuts.instance.closeHelpModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="keyboard-help-body">
                    ${this.generateHelpContent()}
                </div>
            </div>
        `;
        document.body.appendChild(helpModal);

        // Attach search input listener
        const searchInput = document.getElementById('keyboardSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearchInput(e));
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.selectResult();
                }
            });
        }
    }

    /**
     * Generate help modal content
     */
    generateHelpContent() {
        return `
            <div class="keyboard-shortcuts-grid">
                <div class="keyboard-shortcut-section">
                    <h4>Navigation</h4>
                    <div class="keyboard-shortcut-item">
                        <div class="keyboard-shortcut-keys">
                            <kbd>Ctrl</kbd> + <kbd>K</kbd>
                        </div>
                        <div class="keyboard-shortcut-desc">Global Search</div>
                    </div>
                    <div class="keyboard-shortcut-item">
                        <div class="keyboard-shortcut-keys">
                            <kbd>Esc</kbd>
                        </div>
                        <div class="keyboard-shortcut-desc">Close Modal</div>
                    </div>
                </div>
                
                <div class="keyboard-shortcut-section">
                    <h4>Search</h4>
                    <div class="keyboard-shortcut-item">
                        <div class="keyboard-shortcut-keys">
                            <kbd>↑</kbd> <kbd>↓</kbd>
                        </div>
                        <div class="keyboard-shortcut-desc">Navigate Results</div>
                    </div>
                    <div class="keyboard-shortcut-item">
                        <div class="keyboard-shortcut-keys">
                            <kbd>Enter</kbd>
                        </div>
                        <div class="keyboard-shortcut-desc">Select Result</div>
                    </div>
                </div>
                
                <div class="keyboard-shortcut-section">
                    <h4>Help</h4>
                    <div class="keyboard-shortcut-item">
                        <div class="keyboard-shortcut-keys">
                            <kbd>?</kbd>
                        </div>
                        <div class="keyboard-shortcut-desc">Show This Help</div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Open search modal
     */
    openSearchModal() {
        const modal = document.getElementById('keyboardSearchModal');
        const input = document.getElementById('keyboardSearchInput');
        
        if (modal && input) {
            modal.classList.add('active');
            this.isSearchModalOpen = true;
            
            // Focus input after animation
            setTimeout(() => {
                input.focus();
            }, 100);
        }
    }

    /**
     * Close search modal
     */
    closeSearchModal() {
        const modal = document.getElementById('keyboardSearchModal');
        const input = document.getElementById('keyboardSearchInput');
        
        if (modal) {
            modal.classList.remove('active');
            this.isSearchModalOpen = false;
            this.selectedIndex = -1;
            
            if (input) {
                input.value = '';
            }
            
            this.clearSearchResults();
        }
    }

    /**
     * Open help modal
     */
    openHelpModal() {
        const modal = document.getElementById('keyboardHelpModal');
        
        if (modal) {
            modal.classList.add('active');
            this.isHelpModalOpen = true;
        }
    }

    /**
     * Close help modal
     */
    closeHelpModal() {
        const modal = document.getElementById('keyboardHelpModal');
        
        if (modal) {
            modal.classList.remove('active');
            this.isHelpModalOpen = false;
        }
    }

    /**
     * Close all modals
     */
    closeModals() {
        this.closeSearchModal();
        this.closeHelpModal();
    }

