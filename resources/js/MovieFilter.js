// resources/js/components/MovieFilter.js

class MovieFilter {
    constructor() {
        this.filterForm = document.getElementById('filterForm');
        this.searchInput = document.getElementById('searchInput');
        this.suggestionsDropdown = document.getElementById('searchSuggestions');
        this.genreCheckboxes = document.querySelectorAll('input[name="genres[]"]');
        this.ratingRange = document.getElementById('ratingRange');
        this.qualitySelect = document.querySelector('select[name="quality"]');
        this.sortSelect = document.querySelector('select[name="sort"]');
        this.yearFromInput = document.querySelector('input[name="year_from"]');
        this.yearToInput = document.querySelector('input[name="year_to"]');
        
        this.searchTimeout = null;
        this.selectedSuggestionIndex = -1;
        this.suggestions = [];
        this.activeFilters = new Set();
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadSavedFilters();
        this.updateActiveFiltersCount();
        this.initializeTooltips();
    }

    setupEventListeners() {
        // Search input with debounce
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => this.handleSearchInput(e));
            this.searchInput.addEventListener('keydown', (e) => this.handleSearchKeydown(e));
            this.searchInput.addEventListener('focus', () => this.showSuggestions());
        }

        // Genre checkboxes
        this.genreCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateActiveFilters());
        });

        // Rating range slider
        if (this.ratingRange) {
            this.ratingRange.addEventListener('input', (e) => this.updateRatingDisplay(e));
            this.ratingRange.addEventListener('change', () => this.saveFilters());
        }

        // Auto-submit on select changes
        [this.qualitySelect, this.sortSelect].forEach(select => {
            if (select) {
                select.addEventListener('change', () => {
                    this.saveFilters();
                    this.filterForm.submit();
                });
            }
        });

        // Year inputs validation
        [this.yearFromInput, this.yearToInput].forEach(input => {
            if (input) {
                input.addEventListener('change', () => this.validateYearRange());
            }
        });

        // Clear filters button
        const clearBtn = document.getElementById('clearFiltersBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearAllFilters();
            });
        }

        // Click outside to close suggestions
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.suggestionsDropdown.contains(e.target)) {
                this.hideSuggestions();
            }
        });

        // Filter form submission
        if (this.filterForm) {
            this.filterForm.addEventListener('submit', (e) => {
                this.saveFilters();
            });
        }
    }

    handleSearchInput(e) {
        clearTimeout(this.searchTimeout);
        const query = e.target.value.trim();

        if (query.length < 2) {
            this.hideSuggestions();
            return;
        }

        // Show loading state
        this.showLoadingState();

        this.searchTimeout = setTimeout(() => {
            this.fetchSuggestions(query);
        }, 300);
    }

    handleSearchKeydown(e) {
        if (!this.suggestions.length) return;

        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.navigateSuggestions(1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.navigateSuggestions(-1);
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedSuggestionIndex >= 0) {
                    this.selectSuggestion(this.suggestions[this.selectedSuggestionIndex]);
                } else {
                    this.filterForm.submit();
                }
                break;
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }

    async fetchSuggestions(query) {
        try {
            const response = await fetch(`/search/suggestions?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();
            this.suggestions = data;
            this.renderSuggestions(data);
        } catch (error) {
            console.error('Search suggestions error:', error);
            this.hideSuggestions();
        }
    }

    renderSuggestions(suggestions) {
        if (!suggestions.length) {
            this.suggestionsDropdown.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No movies found</p>
                </div>
            `;
            this.suggestionsDropdown.classList.add('show');
            return;
        }

        const html = suggestions.map((movie, index) => `
            <div class="search-suggestion-item ${index === this.selectedSuggestionIndex ? 'selected' : ''}" 
                 data-index="${index}">
                <img src="${movie.poster}" alt="${movie.title}" loading="lazy">
                <div class="search-suggestion-info">
                    <div class="search-suggestion-title">${this.highlightMatch(movie.title)}</div>
                    <div class="search-suggestion-meta">
                        <span class="year">${movie.year || 'N/A'}</span>
                        <span class="separator">•</span>
                        <span class="rating">
                            <i class="fas fa-star"></i> ${movie.rating}
                        </span>
                    </div>
                </div>
            </div>
        `).join('');

        this.suggestionsDropdown.innerHTML = html;
        this.suggestionsDropdown.classList.add('show');

        // Add click handlers to suggestions
        this.suggestionsDropdown.querySelectorAll('.search-suggestion-item').forEach((item, index) => {
            item.addEventListener('click', () => this.selectSuggestion(suggestions[index]));
            item.addEventListener('mouseenter', () => {
                this.selectedSuggestionIndex = index;
                this.updateSuggestionSelection();
            });
        });
    }

    highlightMatch(text) {
        const query = this.searchInput.value.trim();
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    navigateSuggestions(direction) {
        this.selectedSuggestionIndex += direction;
        
        if (this.selectedSuggestionIndex < -1) {
            this.selectedSuggestionIndex = this.suggestions.length - 1;
        } else if (this.selectedSuggestionIndex >= this.suggestions.length) {
            this.selectedSuggestionIndex = -1;
        }

        this.updateSuggestionSelection();
    }

    updateSuggestionSelection() {
        const items = this.suggestionsDropdown.querySelectorAll('.search-suggestion-item');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedSuggestionIndex);
        });
    }

    selectSuggestion(suggestion) {
        window.location.href = suggestion.url;
    }

    showSuggestions() {
        if (this.suggestions.length > 0) {
            this.suggestionsDropdown.classList.add('show');
        }
    }

    hideSuggestions() {
        this.suggestionsDropdown.classList.remove('show');
        this.selectedSuggestionIndex = -1;
    }

    showLoadingState() {
        this.suggestionsDropdown.innerHTML = `
            <div class="loading-state">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>Searching...</span>
            </div>
        `;
        this.suggestionsDropdown.classList.add('show');
    }

    updateRatingDisplay(e) {
        const value = e.target.value;
        const display = document.getElementById('ratingValue');
        if (display) {
            display.textContent = value + '+';
        }
    }

    validateYearRange() {
        if (this.yearFromInput && this.yearToInput) {
            const yearFrom = parseInt(this.yearFromInput.value);
            const yearTo = parseInt(this.yearToInput.value);

            if (yearFrom && yearTo && yearFrom > yearTo) {
                this.yearToInput.setCustomValidity('End year must be after start year');
                this.yearToInput.classList.add('is-invalid');
            } else {
                this.yearToInput.setCustomValidity('');
                this.yearToInput.classList.remove('is-invalid');
            }
        }
    }

    updateActiveFilters() {
        this.activeFilters.clear();

        // Check genres
        const checkedGenres = document.querySelectorAll('input[name="genres[]"]:checked');
        if (checkedGenres.length > 0) {
            this.activeFilters.add('genres');
        }

        // Check year range
        if (this.yearFromInput?.value || this.yearToInput?.value) {
            this.activeFilters.add('year');
        }

        // Check rating
        if (this.ratingRange?.value > 0) {
            this.activeFilters.add('rating');
        }

        // Check quality
        if (this.qualitySelect?.value) {
            this.activeFilters.add('quality');
        }

        // Check search
        if (this.searchInput?.value.trim()) {
            this.activeFilters.add('search');
        }

        this.updateActiveFiltersCount();
        this.toggleClearButton();
    }

    updateActiveFiltersCount() {
        const countBadge = document.querySelector('.filter-count-badge');
        const count = this.activeFilters.size;
        
        if (countBadge) {
            if (count > 0) {
                countBadge.textContent = count;
                countBadge.style.display = 'inline-block';
            } else {
                countBadge.style.display = 'none';
            }
        }
    }

    toggleClearButton() {
        const clearBtn = document.getElementById('clearFiltersBtn');
        if (clearBtn) {
            clearBtn.style.display = this.activeFilters.size > 0 ? 'inline-block' : 'none';
        }
    }

    clearAllFilters() {
        // Clear search
        if (this.searchInput) this.searchInput.value = '';
        
        // Clear genres
        this.genreCheckboxes.forEach(checkbox => checkbox.checked = false);
        
        // Clear year range
        if (this.yearFromInput) this.yearFromInput.value = '';
        if (this.yearToInput) this.yearToInput.value = '';
        
        // Clear rating
        if (this.ratingRange) {
            this.ratingRange.value = 0;
            this.updateRatingDisplay({ target: this.ratingRange });
        }
        
        // Clear quality
        if (this.qualitySelect) this.qualitySelect.value = '';
        
        // Clear sort
        if (this.sortSelect) this.sortSelect.value = 'latest';
        
        // Clear localStorage
        localStorage.removeItem('movieFilters');
        
        // Redirect to clean URL
        window.location.href = '/';
    }

    saveFilters() {
        const filters = {
            search: this.searchInput?.value || '',
            genres: Array.from(document.querySelectorAll('input[name="genres[]"]:checked')).map(cb => cb.value),
            yearFrom: this.yearFromInput?.value || '',
            yearTo: this.yearToInput?.value || '',
            rating: this.ratingRange?.value || 0,
            quality: this.qualitySelect?.value || '',
            sort: this.sortSelect?.value || 'latest'
        };
        
        localStorage.setItem('movieFilters', JSON.stringify(filters));
    }

    loadSavedFilters() {
        const saved = localStorage.getItem('movieFilters');
        if (!saved) return;
        
        try {
            const filters = JSON.parse(saved);
            
            // Only load if we're on the home page without query params
            if (window.location.pathname === '/' && !window.location.search) {
                // Apply saved filters if user is returning to the page
                // This is optional - you may want to always start fresh
            }
        } catch (error) {
            console.error('Error loading saved filters:', error);
            localStorage.removeItem('movieFilters');
        }
    }

    initializeTooltips() {
        // Initialize Bootstrap tooltips if available
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }
}

// View Mode Manager
class ViewModeManager {
    constructor() {
        this.gridBtn = document.getElementById('gridViewBtn');
        this.listBtn = document.getElementById('listViewBtn');
        this.container = document.getElementById('moviesContainer');
        this.currentMode = localStorage.getItem('viewMode') || 'grid';
        
        this.init();
    }

    init() {
        this.applyMode(this.currentMode);
        this.setupEventListeners();
    }

    setupEventListeners() {
        if (this.gridBtn) {
            this.gridBtn.addEventListener('click', () => this.setMode('grid'));
        }
        
        if (this.listBtn) {
            this.listBtn.addEventListener('click', () => this.setMode('list'));
        }
    }

    setMode(mode) {
        this.currentMode = mode;
        localStorage.setItem('viewMode', mode);
        this.applyMode(mode);
    }

    applyMode(mode) {
        if (!this.container) return;
        
        if (mode === 'grid') {
            this.container.classList.remove('movies-list');
            this.container.classList.add('movies-grid');
            this.gridBtn?.classList.add('active');
            this.listBtn?.classList.remove('active');
        } else {
            this.container.classList.remove('movies-grid');
            this.container.classList.add('movies-list');
            this.listBtn?.classList.add('active');
            this.gridBtn?.classList.remove('active');
        }
        
        // Trigger animation
        this.animateTransition();
    }

    animateTransition() {
        const items = this.container.querySelectorAll('.movie-item');
        items.forEach((item, index) => {
            item.style.animation = 'none';
            setTimeout(() => {
                item.style.animation = `fadeIn 0.3s ease-in-out ${index * 0.05}s`;
            }, 10);
        });
    }
}

// Infinite Scroll Manager
class InfiniteScrollManager {
    constructor() {
        this.container = document.getElementById('moviesContainer');
        this.loadMoreBtn = document.getElementById('loadMoreBtn');
        this.currentPage = 1;
        this.isLoading = false;
        this.hasMore = true;
        
        this.init();
    }

    init() {
        if (this.loadMoreBtn) {
            this.loadMoreBtn.addEventListener('click', () => this.loadMore());
        }
        
        // Intersection Observer for auto-load
        this.setupIntersectionObserver();
    }

    setupIntersectionObserver() {
        const options = {
            root: null,
            rootMargin: '100px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.isLoading && this.hasMore) {
                    this.loadMore();
                }
            });
        }, options);
        
        // Observe the load more button or a sentinel element
        const sentinel = document.getElementById('scrollSentinel');
        if (sentinel) {
            observer.observe(sentinel);
        }
    }

    async loadMore() {
        if (this.isLoading || !this.hasMore) return;
        
        this.isLoading = true;
        this.showLoadingState();
        
        try {
            const params = new URLSearchParams(window.location.search);
            params.set('page', this.currentPage + 1);
            
            const response = await fetch(`/?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) throw new Error('Failed to load more movies');
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const newMovies = doc.querySelectorAll('.movie-item');
            
            if (newMovies.length > 0) {
                this.appendMovies(newMovies);
                this.currentPage++;
            } else {
                this.hasMore = false;
                this.hideLoadMoreButton();
            }
        } catch (error) {
            console.error('Error loading more movies:', error);
            this.showErrorState();
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    appendMovies(movies) {
        const container = this.container.querySelector('.row');
        movies.forEach((movie, index) => {
            movie.style.animation = `fadeIn 0.3s ease-in-out ${index * 0.05}s`;
            container.appendChild(movie);
        });
    }

    showLoadingState() {
        if (this.loadMoreBtn) {
            this.loadMoreBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            this.loadMoreBtn.disabled = true;
        }
    }

    hideLoadingState() {
        if (this.loadMoreBtn) {
            this.loadMoreBtn.innerHTML = 'Load More';
            this.loadMoreBtn.disabled = false;
        }
    }

    showErrorState() {
        if (this.loadMoreBtn) {
            this.loadMoreBtn.innerHTML = 'Error - Click to retry';
            this.loadMoreBtn.classList.add('btn-danger');
        }
    }

    hideLoadMoreButton() {
        if (this.loadMoreBtn) {
            this.loadMoreBtn.style.display = 'none';
        }
    }
}

// Quick Actions Manager
class QuickActionsManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupWatchlistButtons();
        this.setupQuickPlay();
        this.setupShareButtons();
    }

    setupWatchlistButtons() {
        document.addEventListener('click', async (e) => {
            if (e.target.matches('.add-to-watchlist, .add-to-watchlist *')) {
                e.preventDefault();
                const btn = e.target.closest('.add-to-watchlist');
                const movieId = btn.dataset.movieId;
                await this.toggleWatchlist(movieId, btn);
            }
        });
    }

    async toggleWatchlist(movieId, button) {
        try {
            const isAdded = button.classList.contains('added');
            const url = isAdded ? `/watchlist/remove/${movieId}` : `/watchlist/add/${movieId}`;
            const method = isAdded ? 'DELETE' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                button.classList.toggle('added');
                button.querySelector('i').classList.toggle('fas');
                button.querySelector('i').classList.toggle('far');
                
                // Show toast notification
                this.showToast(isAdded ? 'Removed from watchlist' : 'Added to watchlist');
            }
        } catch (error) {
            console.error('Watchlist error:', error);
            this.showToast('Error updating watchlist', 'error');
        }
    }

    setupQuickPlay() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.quick-play, .quick-play *')) {
                const btn = e.target.closest('.quick-play');
                const movieSlug = btn.dataset.movieSlug;
                
                // Open in modal or new tab based on user preference
                if (localStorage.getItem('playInModal') === 'true') {
                    this.openPlayerModal(movieSlug);
                } else {
                    window.location.href = `/movie/${movieSlug}/play`;
                }
            }
        });
    }

    openPlayerModal(movieSlug) {
        // Implementation for modal player
        const modal = new bootstrap.Modal(document.getElementById('playerModal'));
        const iframe = document.getElementById('playerFrame');
        iframe.src = `/movie/${movieSlug}/play?modal=1`;
        modal.show();
    }

    setupShareButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.share-movie, .share-movie *')) {
                e.preventDefault();
                const btn = e.target.closest('.share-movie');
                const url = btn.dataset.url;
                const title = btn.dataset.title;
                
                if (navigator.share) {
                    navigator.share({
                        title: title,
                        url: url
                    });
                } else {
                    // Fallback to copy link
                    this.copyToClipboard(url);
                }
            }
        });
    }

    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showToast('Link copied to clipboard');
        });
    }

    showToast(message, type = 'success') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : 'success'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        // Add to container
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove after hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}

// Initialize all components when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize main components
    const movieFilter = new MovieFilter();
    const viewModeManager = new ViewModeManager();
    const infiniteScroll = new InfiniteScrollManager();
    const quickActions = new QuickActionsManager();
    
    // Export to window for debugging
    window.NoobzCinema = {
        filter: movieFilter,
        viewMode: viewModeManager,
        scroll: infiniteScroll,
        actions: quickActions
    };
});

// CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .movie-item {
        animation: fadeIn 0.3s ease-in-out;
    }
`;
document.head.appendChild(style);