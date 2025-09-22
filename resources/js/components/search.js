// Search & Filter Component
class SearchComponent {
    constructor() {
        this.searchInput = null;
        this.filterSelects = [];
        this.searchTimeout = null;
        this.isLoading = false;

        this.init();
    }

    init() {
        this.bindElements();
        this.attachEventListeners();
        this.initializeUrlState();
    }

    bindElements() {
        this.searchInput = document.querySelector('.modern-select[name="search"]');
        this.filterSelects = document.querySelectorAll('.modern-select');
        this.filterForm = document.querySelector('.filter-sidebar form');
    }

    attachEventListeners() {
        // Search input with debouncing
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.handleSearchInput(e);
            });

            this.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.submitSearch();
                }
            });
        }

        // Filter selects with smooth transitions
        this.filterSelects.forEach(select => {
            select.addEventListener('change', (e) => {
                this.handleFilterChange(e);
            });

            select.addEventListener('focus', (e) => {
                this.addFocusAnimation(e.target);
            });

            select.addEventListener('blur', (e) => {
                this.removeFocusAnimation(e.target);
            });
        });

        // Form submission
        if (this.filterForm) {
            this.filterForm.addEventListener('submit', (e) => {
                this.handleFormSubmit(e);
            });
        }
    }

    handleSearchInput(event) {
        const query = event.target.value;

        clearTimeout(this.searchTimeout);

        // Add visual feedback
        this.addLoadingState(event.target);

        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.removeLoadingState(event.target);

            // Future: implement real-time search
            this.onSearchInput(query);
        }, 300);
    }

    handleFilterChange(event) {
        const select = event.target;

        // Add smooth loading state
        this.addLoadingState(select);

        // Add visual feedback
        select.style.transform = 'translateY(-1px)';

        // Submit form with delay for animation
        setTimeout(() => {
            if (this.filterForm) {
                this.filterForm.submit();
            }
        }, 150);
    }

    handleFormSubmit(event) {
        // Add loading state to entire form
        this.addFormLoadingState();

        // Smooth page transition
        document.body.style.opacity = '0.8';
        document.body.style.transform = 'scale(0.98)';
    }

    addLoadingState(element) {
        if (this.isLoading) return;

        element.classList.add('filter-loading');
        element.style.position = 'relative';

        // Add loading indicator
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        loadingIndicator.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            pointer-events: none;
            z-index: 10;
        `;

        element.parentNode.style.position = 'relative';
        element.parentNode.appendChild(loadingIndicator);

        this.isLoading = true;
    }

    removeLoadingState(element) {
        element.classList.remove('filter-loading');

        const loadingIndicator = element.parentNode.querySelector('.loading-indicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }

        this.isLoading = false;
    }

    addFormLoadingState() {
        const filterSidebar = document.querySelector('.filter-sidebar');
        if (filterSidebar) {
            filterSidebar.style.opacity = '0.6';
            filterSidebar.style.pointerEvents = 'none';
        }
    }

    addFocusAnimation(element) {
        element.style.transform = 'translateY(-2px)';
        element.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.2)';
    }

    removeFocusAnimation(element) {
        element.style.transform = '';
        element.style.boxShadow = '';
    }

    submitSearch() {
        if (this.filterForm) {
            this.addFormLoadingState();
            this.filterForm.submit();
        }
    }

    onSearchInput(query) {
        // Future: implement real-time search suggestions
        console.log('Search query:', query);

        // Could implement:
        // - Live search results
        // - Search suggestions
        // - Recent searches
        // - Popular searches
    }

    initializeUrlState() {
        // Highlight active filters based on URL parameters
        const urlParams = new URLSearchParams(window.location.search);

        this.filterSelects.forEach(select => {
            const paramValue = urlParams.get(select.name);
            if (paramValue && paramValue !== select.value) {
                // Animate filter application
                setTimeout(() => {
                    select.style.borderColor = '#667eea';
                    select.style.background = 'rgba(102, 126, 234, 0.1)';
                }, 100);
            }
        });
    }

    // Public methods for external access
    clearFilters() {
        this.filterSelects.forEach(select => {
            if (select.name !== 'search') {
                select.value = '';
            }
        });

        if (this.searchInput) {
            this.searchInput.value = '';
        }

        this.submitSearch();
    }

    setSearchQuery(query) {
        if (this.searchInput) {
            this.searchInput.value = query;
            this.searchInput.focus();
        }
    }
}

// Enhanced filter animations
function initializeFilterAnimations() {
    const filterSidebar = document.querySelector('.filter-sidebar');
    if (!filterSidebar) return;

    // Add entrance animation
    filterSidebar.style.opacity = '0';
    filterSidebar.style.transform = 'translateX(-20px)';

    setTimeout(() => {
        filterSidebar.classList.add('slide-in-left');
        filterSidebar.style.opacity = '1';
        filterSidebar.style.transform = 'translateX(0)';
    }, 200);

    // Animate filter groups
    const filterGroups = document.querySelectorAll('.filter-group');
    filterGroups.forEach((group, index) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(10px)';

        setTimeout(() => {
            group.classList.add('fade-in-up');
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        }, 300 + (index * 100));
    });
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Search component loading...');

    // Initialize search component
    window.searchComponent = new SearchComponent();

    // Initialize filter animations
    initializeFilterAnimations();

    console.log('🔍 Search component loaded');
});

// Export for use in other components
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SearchComponent;
}