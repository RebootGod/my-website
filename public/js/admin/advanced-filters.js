/**
 * Advanced Filters Module
 * 
 * Handles advanced filtering UI and interactions
 * Max 350 lines per workinginstruction.md
 * 
 * Features:
 * - Filter panel toggle
 * - Range sliders
 * - Multi-select genres
 * - Real-time result preview
 * - URL state management
 */

class AdvancedFilters {
    constructor(contentType) {
        this.contentType = contentType; // 'movie' or 'series'
        this.filterPanel = document.getElementById('advanced-filter-panel');
        this.filterForm = document.getElementById('advanced-filter-form');
        this.toggleBtn = document.getElementById('toggle-advanced-filters');
        this.clearBtn = document.getElementById('clear-all-filters');
        this.applyBtn = document.getElementById('apply-filters');
        this.resultCount = document.getElementById('filter-result-count');
        
        this.init();
    }

    init() {
        if (!this.filterPanel) return;

        this.setupEventListeners();
        this.initializeRangeSliders();
        this.loadFiltersFromURL();
        this.updateResultCount();
    }

    setupEventListeners() {
        // Toggle panel
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.togglePanel());
        }

        // Clear filters
        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', () => this.clearAllFilters());
        }

        // Apply filters
        if (this.applyBtn) {
            this.applyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }

        // Real-time updates on input change
        const inputs = this.filterForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                this.updateResultCount();
            });
        });
    }

    initializeRangeSliders() {
        // Year range slider
        this.initSlider('year', {
            min: 1900,
            max: new Date().getFullYear(),
            step: 1
        });

        // Rating range slider
        this.initSlider('rating', {
            min: 0,
            max: 10,
            step: 0.1
        });

        // Views range slider
        this.initSlider('views', {
            min: 0,
            max: 100000,
            step: 100
        });
    }

    initSlider(name, config) {
        const fromInput = document.getElementById(`${name}_from`);
        const toInput = document.getElementById(`${name}_to`);
        const fromValue = document.getElementById(`${name}_from_value`);
        const toValue = document.getElementById(`${name}_to_value`);

        if (!fromInput || !toInput) return;

        // Set min/max
        fromInput.min = config.min;
        fromInput.max = config.max;
        fromInput.step = config.step;
        toInput.min = config.min;
        toInput.max = config.max;
        toInput.step = config.step;

        // Set default values if empty
        if (!fromInput.value) fromInput.value = config.min;
        if (!toInput.value) toInput.value = config.max;

        // Update value displays
        const updateDisplay = () => {
            if (fromValue) fromValue.textContent = fromInput.value;
            if (toValue) toValue.textContent = toInput.value;

            // Ensure from <= to
            if (parseFloat(fromInput.value) > parseFloat(toInput.value)) {
                fromInput.value = toInput.value;
            }
        };

        fromInput.addEventListener('input', updateDisplay);
        toInput.addEventListener('input', updateDisplay);

        updateDisplay();
    }

    togglePanel() {
        const isHidden = this.filterPanel.classList.contains('hidden');
        
        if (isHidden) {
            this.filterPanel.classList.remove('hidden');
            this.toggleBtn.innerHTML = '<i class="fas fa-chevron-up mr-2"></i>Hide Advanced Filters';
        } else {
            this.filterPanel.classList.add('hidden');
            this.toggleBtn.innerHTML = '<i class="fas fa-filter mr-2"></i>Show Advanced Filters';
        }

        // Save state
        localStorage.setItem(`advanced_filters_${this.contentType}_visible`, isHidden ? '1' : '0');
    }

    getFilterValues() {
        const formData = new FormData(this.filterForm);
        const filters = {};

        // Get all form values
        for (let [key, value] of formData.entries()) {
            if (value !== '' && value !== null) {
                // Handle multi-select (genre_ids[])
                if (key.endsWith('[]')) {
                    const cleanKey = key.replace('[]', '');
                    if (!filters[cleanKey]) filters[cleanKey] = [];
                    filters[cleanKey].push(value);
                } else {
                    filters[key] = value;
                }
            }
        }

        return filters;
    }

    applyFilters() {
        const filters = this.getFilterValues();
        
        // Build query string
        const params = new URLSearchParams();
        
        Object.entries(filters).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach(v => params.append(`${key}[]`, v));
            } else {
                params.append(key, value);
            }
        });

        // Reload page with filters
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    }

    clearAllFilters() {
        // Reset form
        this.filterForm.reset();

        // Reset range sliders to defaults
        const yearFrom = document.getElementById('year_from');
        const yearTo = document.getElementById('year_to');
        const ratingFrom = document.getElementById('rating_from');
        const ratingTo = document.getElementById('rating_to');
        const viewsFrom = document.getElementById('views_from');
        const viewsTo = document.getElementById('views_to');

        if (yearFrom) yearFrom.value = 1900;
        if (yearTo) yearTo.value = new Date().getFullYear();
        if (ratingFrom) ratingFrom.value = 0;
        if (ratingTo) ratingTo.value = 10;
        if (viewsFrom) viewsFrom.value = 0;
        if (viewsTo) viewsTo.value = 100000;

        // Update displays
        this.initializeRangeSliders();

        // Redirect without filters
        window.location.href = window.location.pathname;
    }

    loadFiltersFromURL() {
        const params = new URLSearchParams(window.location.search);

        // Load each filter value
        params.forEach((value, key) => {
            // Handle array parameters (genre_ids[])
            if (key.endsWith('[]')) {
                const cleanKey = key.replace('[]', '');
                const checkboxes = this.filterForm.querySelectorAll(`input[name="${key}"]`);
                checkboxes.forEach(checkbox => {
                    if (checkbox.value === value) {
                        checkbox.checked = true;
                    }
                });
            } else {
                const input = this.filterForm.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = value === '1' || value === 'true';
                    } else {
                        input.value = value;
                    }
                }
            }
        });

        // Update range slider displays
        this.initializeRangeSliders();
    }

    async updateResultCount() {
        if (!this.resultCount) return;

        const filters = this.getFilterValues();
        
        try {
            const params = new URLSearchParams(filters);
            params.append('count_only', '1');

            const response = await fetch(`/admin/${this.contentType}s?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                console.error('Failed to fetch result count, status:', response.status);
                this.resultCount.textContent = '- results';
                return;
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Response is not JSON, got:', contentType);
                this.resultCount.textContent = '- results';
                return;
            }

            const data = await response.json();
            this.resultCount.textContent = `${data.count || 0} results`;
        } catch (error) {
            console.error('Failed to update result count:', error);
            // Set fallback text instead of leaving empty
            if (this.resultCount) {
                this.resultCount.textContent = '- results';
            }
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Detect content type from URL
    const path = window.location.pathname;
    let contentType = null;

    if (path.includes('/admin/movies')) {
        contentType = 'movie';
    } else if (path.includes('/admin/series')) {
        contentType = 'series';
    }

    if (contentType) {
        window.advancedFilters = new AdvancedFilters(contentType);
        
        // Restore panel visibility
        const savedVisibility = localStorage.getItem(`advanced_filters_${contentType}_visible`);
        if (savedVisibility === '1') {
            const panel = document.getElementById('advanced-filter-panel');
            const toggleBtn = document.getElementById('toggle-advanced-filters');
            if (panel && toggleBtn) {
                panel.classList.remove('hidden');
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up mr-2"></i>Hide Advanced Filters';
            }
        }
    }
});
