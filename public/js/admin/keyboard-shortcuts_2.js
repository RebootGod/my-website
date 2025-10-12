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
