/* ======================================== */
/* KEYBOARD SHORTCUTS SYSTEM */
/* ======================================== */
/* File: resources/js/admin/keyboard-shortcuts.js */

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
            <div class="keyboard-modal-backdrop"></div>
            <div class="keyboard-modal-content">
                <div class="keyboard-search-header">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="keyboardSearchInput" 
                           placeholder="Search movies, series, users..."
                           autocomplete="off">
                    <button class="keyboard-modal-close">
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
            <div class="keyboard-modal-backdrop"></div>
            <div class="keyboard-modal-content">
                <div class="keyboard-help-header">
                    <h3><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h3>
                    <button class="keyboard-modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="keyboard-help-body">
                    ${this.generateHelpContent()}
                </div>
            </div>
        `;
        document.body.appendChild(helpModal);

        // Attach event listeners for Search Modal
        const searchBackdrop = searchModal.querySelector('.keyboard-modal-backdrop');
        const searchCloseBtn = searchModal.querySelector('.keyboard-modal-close');
        if (searchBackdrop) {
            searchBackdrop.addEventListener('click', () => this.closeSearchModal());
        }
        if (searchCloseBtn) {
            searchCloseBtn.addEventListener('click', () => this.closeSearchModal());
        }

        // Attach event listeners for Help Modal
        const helpBackdrop = helpModal.querySelector('.keyboard-modal-backdrop');
        const helpCloseBtn = helpModal.querySelector('.keyboard-modal-close');
        if (helpBackdrop) {
            helpBackdrop.addEventListener('click', () => this.closeHelpModal());
        }
        if (helpCloseBtn) {
            helpCloseBtn.addEventListener('click', () => this.closeHelpModal());
        }

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

    /**
     * Handle search input
     */
    async handleSearchInput(e) {
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            this.clearSearchResults();
            return;
        }

        // Perform search
        await this.performSearch(query);
    }

    /**
     * Perform global search
     */
    async performSearch(query) {
        try {
            // Build search URL
            const searchUrl = `/admin/search?q=${encodeURIComponent(query)}`;
            
            // Show loading
            this.showSearchLoading();
            
            // Fetch results (placeholder - implement actual search endpoint)
            const response = await fetch(searchUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.displaySearchResults(data);
            } else {
                // Fallback to client-side search
                this.performClientSideSearch(query);
            }
        } catch (error) {
            console.error('Search error:', error);
            this.performClientSideSearch(query);
        }
    }

    /**
     * Perform client-side search (fallback)
     */
    performClientSideSearch(query) {
        const results = [];
        const lowerQuery = query.toLowerCase();
        
        // Search in navigation menu
        const navItems = document.querySelectorAll('.admin-nav-item');
        navItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(lowerQuery)) {
                results.push({
                    type: 'navigation',
                    title: item.textContent.trim(),
                    url: item.getAttribute('href'),
                    icon: item.querySelector('i')?.className || 'fas fa-link'
                });
            }
        });
        
        this.displaySearchResults({ results });
    }

    /**
     * Display search results
     */
    displaySearchResults(data) {
        const resultsContainer = document.getElementById('keyboardSearchResults');
        
        if (!resultsContainer) return;
        
        const results = data.results || [];
        this.searchResults = results;
        this.selectedIndex = -1;
        
        if (results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="keyboard-search-empty">
                    <i class="fas fa-search"></i>
                    <span>No results found</span>
                </div>
            `;
            return;
        }
        
        const html = results.map((result, index) => `
            <a href="${this.escapeHtml(result.url)}" 
               class="keyboard-search-result" 
               data-index="${index}">
                <div class="keyboard-search-result-icon">
                    <i class="${this.escapeHtml(result.icon)}"></i>
                </div>
                <div class="keyboard-search-result-content">
                    <div class="keyboard-search-result-title">${this.escapeHtml(result.title)}</div>
                    <div class="keyboard-search-result-type">${this.escapeHtml(result.type)}</div>
                </div>
            </a>
        `).join('');
        
        resultsContainer.innerHTML = html;
    }

    /**
     * Show search loading state
     */
    showSearchLoading() {
        const resultsContainer = document.getElementById('keyboardSearchResults');
        
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="keyboard-search-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Searching...</span>
                </div>
            `;
        }
    }

    /**
     * Clear search results
     */
    clearSearchResults() {
        const resultsContainer = document.getElementById('keyboardSearchResults');
        
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="keyboard-search-hint">
                    <i class="fas fa-keyboard"></i>
                    <span>Type to search across all sections</span>
                </div>
            `;
        }
    }

    /**
     * Handle search navigation (arrow keys)
     */
    handleSearchNavigation(e) {
        const results = document.querySelectorAll('.keyboard-search-result');
        
        if (results.length === 0) return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.selectedIndex = Math.min(this.selectedIndex + 1, results.length - 1);
            this.updateSelectedResult(results);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
            this.updateSelectedResult(results);
        }
    }

    /**
     * Update selected search result
     */
    updateSelectedResult(results) {
        results.forEach((result, index) => {
            if (index === this.selectedIndex) {
                result.classList.add('selected');
                result.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                result.classList.remove('selected');
            }
        });
    }

    /**
     * Select current result
     */
    selectResult() {
        const results = document.querySelectorAll('.keyboard-search-result');
        
        if (this.selectedIndex >= 0 && this.selectedIndex < results.length) {
            results[this.selectedIndex].click();
        }
    }

    /**
     * XSS protection - escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize as singleton
document.addEventListener('DOMContentLoaded', () => {
    window.KeyboardShortcuts = {
        instance: new KeyboardShortcuts()
    };
});
