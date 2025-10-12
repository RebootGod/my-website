/* ======================================== *//* ======================================== */

/* KEYBOARD SHORTCUTS SYSTEM - PART 2 *//* KEYBOARD SHORTCUTS SYSTEM - PART 2 */

/* ======================================== *//* ======================================== */

/* File: resources/js/admin/keyboard-shortcuts_2.js *//* File: resources/js/admin/keyboard-shortcuts_2.js */

/* Continuation from keyboard-shortcuts_1.js *//* Continuation from keyboard-shortcuts_1.js */



// Extending KeyboardShortcuts class with remaining methods// Extending KeyboardShortcuts class with remaining methods

KeyboardShortcuts.prototype.handleSearchInput = async function(e) {

/**        const query = e.target.value.trim();

 * Handle search input        

 */        if (query.length < 2) {

KeyboardShortcuts.prototype.handleSearchInput = async function(e) {            this.clearSearchResults();

    const query = e.target.value.trim();            return;

            }

    if (query.length < 2) {

        this.clearSearchResults();        // Perform search

        return;        await this.performSearch(query);

    }    }



    // Perform search    /**

    await this.performSearch(query);     * Perform global search

};     */

    async performSearch(query) {

/**        try {

 * Perform global search            // Build search URL

 */            const searchUrl = `/admin/search?q=${encodeURIComponent(query)}`;

KeyboardShortcuts.prototype.performSearch = async function(query) {            

    try {            // Show loading

        // Build search URL            this.showSearchLoading();

        const searchUrl = `/admin/search?q=${encodeURIComponent(query)}`;            

                    // Fetch results (placeholder - implement actual search endpoint)

        // Show loading            const response = await fetch(searchUrl, {

        this.showSearchLoading();                headers: {

                            'X-Requested-With': 'XMLHttpRequest',

        // Fetch results (placeholder - implement actual search endpoint)                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''

        const response = await fetch(searchUrl, {                }

            headers: {            });

                'X-Requested-With': 'XMLHttpRequest',            

                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''            if (response.ok) {

            }                const data = await response.json();

        });                this.displaySearchResults(data);

                    } else {

        if (response.ok) {                // Fallback to client-side search

            const data = await response.json();                this.performClientSideSearch(query);

            this.displaySearchResults(data);            }

        } else {        } catch (error) {

            // Fallback to client-side search            console.error('Search error:', error);

            this.performClientSideSearch(query);            this.performClientSideSearch(query);

        }        }

    } catch (error) {    }

        console.error('Search error:', error);

        this.performClientSideSearch(query);    /**

    }     * Perform client-side search (fallback)

};     */

    performClientSideSearch(query) {

/**        const results = [];

 * Perform client-side search (fallback)        const lowerQuery = query.toLowerCase();

 */        

KeyboardShortcuts.prototype.performClientSideSearch = function(query) {        // Search in navigation menu

    const results = [];        const navItems = document.querySelectorAll('.admin-nav-item');

    const lowerQuery = query.toLowerCase();        navItems.forEach(item => {

                const text = item.textContent.toLowerCase();

    // Search in navigation menu            if (text.includes(lowerQuery)) {

    const navItems = document.querySelectorAll('.admin-nav-item');                results.push({

    navItems.forEach(item => {                    type: 'navigation',

        const text = item.textContent.toLowerCase();                    title: item.textContent.trim(),

        if (text.includes(lowerQuery)) {                    url: item.getAttribute('href'),

            results.push({                    icon: item.querySelector('i')?.className || 'fas fa-link'

                type: 'navigation',                });

                title: item.textContent.trim(),            }

                url: item.getAttribute('href'),        });

                icon: item.querySelector('i')?.className || 'fas fa-link'        

            });        this.displaySearchResults({ results });

        }    }

    });

        /**

    this.displaySearchResults({ results });     * Display search results

};     */

    displaySearchResults(data) {

/**        const resultsContainer = document.getElementById('keyboardSearchResults');

 * Display search results        

 */        if (!resultsContainer) return;

KeyboardShortcuts.prototype.displaySearchResults = function(data) {        

    const resultsContainer = document.getElementById('keyboardSearchResults');        const results = data.results || [];

            this.searchResults = results;

    if (!resultsContainer) return;        this.selectedIndex = -1;

            

    const results = data.results || [];        if (results.length === 0) {

    this.searchResults = results;            resultsContainer.innerHTML = `

    this.selectedIndex = -1;                <div class="keyboard-search-empty">

                        <i class="fas fa-search"></i>

    if (results.length === 0) {                    <span>No results found</span>

        resultsContainer.innerHTML = `                </div>

            <div class="keyboard-search-empty">            `;

                <i class="fas fa-search"></i>            return;

                <span>No results found</span>        }

            </div>        

        `;        const html = results.map((result, index) => `

        return;            <a href="${this.escapeHtml(result.url)}" 

    }               class="keyboard-search-result" 

                   data-index="${index}">

    const html = results.map((result, index) => `                <div class="keyboard-search-result-icon">

        <a href="${this.escapeHtml(result.url)}"                     <i class="${this.escapeHtml(result.icon)}"></i>

           class="keyboard-search-result"                 </div>

           data-index="${index}">                <div class="keyboard-search-result-content">

            <div class="keyboard-search-result-icon">                    <div class="keyboard-search-result-title">${this.escapeHtml(result.title)}</div>

                <i class="${this.escapeHtml(result.icon)}"></i>                    <div class="keyboard-search-result-type">${this.escapeHtml(result.type)}</div>

            </div>                </div>

            <div class="keyboard-search-result-content">            </a>

                <div class="keyboard-search-result-title">${this.escapeHtml(result.title)}</div>        `).join('');

                <div class="keyboard-search-result-type">${this.escapeHtml(result.type)}</div>        

            </div>        resultsContainer.innerHTML = html;

        </a>    }

    `).join('');

        /**

    resultsContainer.innerHTML = html;     * Show search loading state

};     */

    showSearchLoading() {

/**        const resultsContainer = document.getElementById('keyboardSearchResults');

 * Show search loading state        

 */        if (resultsContainer) {

KeyboardShortcuts.prototype.showSearchLoading = function() {            resultsContainer.innerHTML = `

    const resultsContainer = document.getElementById('keyboardSearchResults');                <div class="keyboard-search-loading">

                        <i class="fas fa-spinner fa-spin"></i>

    if (resultsContainer) {                    <span>Searching...</span>

        resultsContainer.innerHTML = `                </div>

            <div class="keyboard-search-loading">            `;

                <i class="fas fa-spinner fa-spin"></i>        }

                <span>Searching...</span>    }

            </div>

        `;    /**

    }     * Clear search results

};     */

    clearSearchResults() {

/**        const resultsContainer = document.getElementById('keyboardSearchResults');

 * Clear search results        

 */        if (resultsContainer) {

KeyboardShortcuts.prototype.clearSearchResults = function() {            resultsContainer.innerHTML = `

    const resultsContainer = document.getElementById('keyboardSearchResults');                <div class="keyboard-search-hint">

                        <i class="fas fa-keyboard"></i>

    if (resultsContainer) {                    <span>Type to search across all sections</span>

        resultsContainer.innerHTML = `                </div>

            <div class="keyboard-search-hint">            `;

                <i class="fas fa-keyboard"></i>        }

                <span>Type to search across all sections</span>    }

            </div>

        `;    /**

    }     * Handle search navigation (arrow keys)

};     */

    handleSearchNavigation(e) {

/**        const results = document.querySelectorAll('.keyboard-search-result');

 * Handle search navigation (arrow keys)        

 */        if (results.length === 0) return;

KeyboardShortcuts.prototype.handleSearchNavigation = function(e) {        

    const results = document.querySelectorAll('.keyboard-search-result');        if (e.key === 'ArrowDown') {

                e.preventDefault();

    if (results.length === 0) return;            this.selectedIndex = Math.min(this.selectedIndex + 1, results.length - 1);

                this.updateSelectedResult(results);

    if (e.key === 'ArrowDown') {        } else if (e.key === 'ArrowUp') {

        e.preventDefault();            e.preventDefault();

        this.selectedIndex = Math.min(this.selectedIndex + 1, results.length - 1);            this.selectedIndex = Math.max(this.selectedIndex - 1, 0);

        this.updateSelectedResult(results);            this.updateSelectedResult(results);

    } else if (e.key === 'ArrowUp') {        }

        e.preventDefault();    }

        this.selectedIndex = Math.max(this.selectedIndex - 1, 0);

        this.updateSelectedResult(results);    /**

    }     * Update selected search result

};     */

    updateSelectedResult(results) {

/**        results.forEach((result, index) => {

 * Update selected search result            if (index === this.selectedIndex) {

 */                result.classList.add('selected');

KeyboardShortcuts.prototype.updateSelectedResult = function(results) {                result.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

    results.forEach((result, index) => {            } else {

        if (index === this.selectedIndex) {                result.classList.remove('selected');

            result.classList.add('selected');            }

            result.scrollIntoView({ block: 'nearest', behavior: 'smooth' });        });

        } else {    }

            result.classList.remove('selected');

        }    /**

    });     * Select current result

};     */

    selectResult() {

/**        const results = document.querySelectorAll('.keyboard-search-result');

 * Select current result        

 */        if (this.selectedIndex >= 0 && this.selectedIndex < results.length) {

KeyboardShortcuts.prototype.selectResult = function() {            results[this.selectedIndex].click();

    const results = document.querySelectorAll('.keyboard-search-result');        }

        }

    if (this.selectedIndex >= 0 && this.selectedIndex < results.length) {

        results[this.selectedIndex].click();    /**

    }     * XSS protection - escape HTML

};     */

    escapeHtml(text) {

/**        const div = document.createElement('div');

 * XSS protection - escape HTML        div.textContent = text;

 */        return div.innerHTML;

KeyboardShortcuts.prototype.escapeHtml = function(text) {    }

    const div = document.createElement('div');}

    div.textContent = text;

    return div.innerHTML;// Initialize as singleton

};document.addEventListener('DOMContentLoaded', () => {

    window.KeyboardShortcuts = {

// Initialize as singleton        instance: new KeyboardShortcuts()

document.addEventListener('DOMContentLoaded', () => {    };

    window.KeyboardShortcuts = {});

        instance: new KeyboardShortcuts()
    };
});
