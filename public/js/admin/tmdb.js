/* ======================================== */
/* ADMIN TMDB JAVASCRIPT */
/* ======================================== */
/* Extracted from admin/tmdb/index.blade.php and new-index.blade.php for better code organization */

// Global variables
let currentPage = 1;
let currentQuery = '';
let currentType = 'search';
let selectedMovies = new Set();

// Initialize TMDB admin functionality
function initializeTMDBAdmin(config) {
    // Store config globally for access in other functions
    window.tmdbConfig = config;

    console.log('TMDB Admin initialized with config:', config);

    // Get DOM elements
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const popularBtn = document.getElementById('popularBtn');
    const trendingBtn = document.getElementById('trendingBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultsSection = document.getElementById('resultsSection');
    const noResults = document.getElementById('noResults');
    const movieGrid = document.getElementById('movieGrid');
    const pagination = document.getElementById('pagination');

    // Modal elements
    const importModal = document.getElementById('importModal');
    const bulkImportModal = document.getElementById('bulkImportModal');
    const importForm = document.getElementById('importForm');
    const bulkImportForm = document.getElementById('bulkImportForm');

    // Search functionality
    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            const query = searchInput.value.trim();
            if (query) {
                searchMovies(query);
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });
    }

    if (popularBtn) {
        popularBtn.addEventListener('click', () => {
            loadPopularMovies();
        });
    }

    if (trendingBtn) {
        trendingBtn.addEventListener('click', () => {
            loadTrendingMovies();
        });
    }

    // Bulk actions
    const selectAllBtn = document.getElementById('selectAllBtn');
    const bulkImportBtn = document.getElementById('bulkImportBtn');

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', toggleSelectAll);
    }

    if (bulkImportBtn) {
        bulkImportBtn.addEventListener('click', openBulkImportModal);
    }

    // Modal close handlers
    const closeModal = document.getElementById('closeModal');
    const closeBulkModal = document.getElementById('closeBulkModal');
    const cancelImport = document.getElementById('cancelImport');
    const cancelBulkImport = document.getElementById('cancelBulkImport');

    if (closeModal) closeModal.addEventListener('click', closeImportModal);
    if (closeBulkModal) closeBulkModal.addEventListener('click', closeBulkImportModal);
    if (cancelImport) cancelImport.addEventListener('click', closeImportModal);
    if (cancelBulkImport) cancelBulkImport.addEventListener('click', closeBulkImportModal);

    // Form submissions
    if (importForm) {
        importForm.addEventListener('submit', handleImportSubmit);
    }

    if (bulkImportForm) {
        bulkImportForm.addEventListener('submit', handleBulkImportSubmit);
    }
}

// Search movies
async function searchMovies(query, page = 1) {
    currentQuery = query;
    currentType = 'search';
    currentPage = page;

    showLoading();

    try {
        const response = await fetch(`${window.tmdbConfig.searchUrl}?query=${encodeURIComponent(query)}&page=${page}`);
        const data = await response.json();

        if (data.error) {
            showError(data.error);
            return;
        }

        displayMovies(data);
    } catch (error) {
        showError('Failed to search movies. Please try again.');
    }
}

// Load popular movies
async function loadPopularMovies(page = 1) {
    currentType = 'popular';
    currentPage = page;

    showLoading();

    try {
        const response = await fetch(`${window.tmdbConfig.popularUrl}?page=${page}`);
        const data = await response.json();

        if (data.error) {
            showError(data.error);
            return;
        }

        displayMovies(data);
    } catch (error) {
        showError('Failed to load popular movies. Please try again.');
    }
}

// Load trending movies
async function loadTrendingMovies() {
    currentType = 'trending';
    currentPage = 1;

    showLoading();

    try {
        const response = await fetch(`${window.tmdbConfig.trendingUrl}?time_window=week`);
        const data = await response.json();

        if (data.error) {
            showError(data.error);
            return;
        }

        displayMovies(data);
    } catch (error) {
        showError('Failed to load trending movies. Please try again.');
    }
}

// Display movies
function displayMovies(data) {
    hideLoading();

    if (!data.results || data.results.length === 0) {
        showNoResults();
        return;
    }

    const movieGrid = document.getElementById('movieGrid');
    movieGrid.innerHTML = '';
    selectedMovies.clear();

    data.results.forEach(movie => {
        const movieCard = createMovieCard(movie);
        movieGrid.appendChild(movieCard);
    });

    createPagination(data);
    showResults();
}

// Create movie card
function createMovieCard(movie) {
    const card = document.createElement('div');
    card.className = 'tmdb-movie-card';

    const posterUrl = movie.poster_path
        ? `https://image.tmdb.org/t/p/w300${movie.poster_path}`
        : '/images/no-poster.jpg';

    const releaseYear = movie.release_date ? new Date(movie.release_date).getFullYear() : 'N/A';
    const rating = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';

    card.innerHTML = `
        <div style="position: relative;">
            <img src="${posterUrl}" alt="${movie.title}" class="tmdb-movie-poster">
            <div style="position: absolute; top: 0.5rem; right: 0.5rem;">
                <input type="checkbox"
                       class="movie-checkbox tmdb-movie-checkbox"
                       data-movie='${JSON.stringify(movie)}'
                       ${movie.exists_in_db ? 'disabled' : ''}>
            </div>
            ${movie.exists_in_db ? '<div class="tmdb-imported-badge">Imported</div>' : ''}
        </div>
        <div class="tmdb-movie-content">
            <h3 class="tmdb-movie-title">${movie.title}</h3>
            <div class="tmdb-movie-meta">
                <span>${releaseYear}</span> • <span>★ ${rating}</span>
            </div>
            <div class="tmdb-movie-actions">
                <button class="view-details-btn tmdb-btn tmdb-btn-primary" style="flex: 1; font-size: 0.75rem;"
                        data-tmdb-id="${movie.tmdb_id}">
                    View Details
                </button>
                ${!movie.exists_in_db ? `
                    <button class="import-btn tmdb-btn tmdb-btn-success" style="font-size: 0.75rem;"
                            data-movie='${JSON.stringify(movie)}'>
                        Import
                    </button>
                ` : ''}
            </div>
        </div>
    `;

    // Add event listeners
    const checkbox = card.querySelector('.movie-checkbox');
    const importBtn = card.querySelector('.import-btn');
    const detailsBtn = card.querySelector('.view-details-btn');

    if (checkbox && !movie.exists_in_db) {
        checkbox.addEventListener('change', (e) => {
            if (e.target.checked) {
                selectedMovies.add(movie);
            } else {
                selectedMovies.delete(movie);
            }
            updateBulkImportButton();
        });
    }

    if (importBtn) {
        importBtn.addEventListener('click', () => {
            openImportModal(movie);
        });
    }

    if (detailsBtn) {
        detailsBtn.addEventListener('click', () => {
            viewMovieDetails(movie.tmdb_id);
        });
    }

    return card;
}

// Helper functions
function showLoading() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultsSection = document.getElementById('resultsSection');
    const noResults = document.getElementById('noResults');

    if (loadingIndicator) loadingIndicator.classList.remove('hidden');
    if (resultsSection) resultsSection.classList.add('hidden');
    if (noResults) noResults.classList.add('hidden');
}

function hideLoading() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    if (loadingIndicator) loadingIndicator.classList.add('hidden');
}

function showResults() {
    const resultsSection = document.getElementById('resultsSection');
    const noResults = document.getElementById('noResults');

    if (resultsSection) resultsSection.classList.remove('hidden');
    if (noResults) noResults.classList.add('hidden');
}

function showNoResults() {
    const resultsSection = document.getElementById('resultsSection');
    const noResults = document.getElementById('noResults');

    if (resultsSection) resultsSection.classList.add('hidden');
    if (noResults) noResults.classList.remove('hidden');
}

function showError(message) {
    hideLoading();

    // Show error message
    let errorDiv = document.getElementById('errorMessage');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'errorMessage';
        errorDiv.className = 'tmdb-error';
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(errorDiv, container.firstChild);
        }
    }

    errorDiv.textContent = message;
    errorDiv.style.display = 'block';

    // Hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

// Modal functions
function openImportModal(movie) {
    const tmdbIdInput = document.getElementById('tmdbId');
    const importModal = document.getElementById('importModal');

    if (tmdbIdInput) tmdbIdInput.value = movie.tmdb_id;
    if (importModal) importModal.classList.remove('hidden');
}

function closeImportModal() {
    const importModal = document.getElementById('importModal');
    const importForm = document.getElementById('importForm');

    if (importModal) importModal.classList.add('hidden');
    if (importForm) importForm.reset();
}

function openBulkImportModal() {
    if (selectedMovies.size === 0) {
        alert('Please select at least one movie to import.');
        return;
    }

    const selectedCount = document.getElementById('selectedCount');
    const bulkEmbedUrls = document.getElementById('bulkEmbedUrls');
    const bulkImportModal = document.getElementById('bulkImportModal');

    if (selectedCount) selectedCount.textContent = selectedMovies.size;

    if (bulkEmbedUrls) {
        bulkEmbedUrls.innerHTML = '';
        selectedMovies.forEach((movie, index) => {
            const inputDiv = document.createElement('div');
            inputDiv.innerHTML = `
                <label class="tmdb-form-label">${movie.title} (${movie.release_date ? new Date(movie.release_date).getFullYear() : 'N/A'})</label>
                <input type="url"
                       class="tmdb-bulk-embed-input"
                       name="embed_urls[]"
                       placeholder="https://example.com/player/movie${movie.tmdb_id}"
                       required>
                <input type="hidden" name="tmdb_ids[]" value="${movie.tmdb_id}">
            `;
            bulkEmbedUrls.appendChild(inputDiv);
        });
    }

    if (bulkImportModal) bulkImportModal.classList.remove('hidden');
}

function closeBulkImportModal() {
    const bulkImportModal = document.getElementById('bulkImportModal');
    const bulkImportForm = document.getElementById('bulkImportForm');

    if (bulkImportModal) bulkImportModal.classList.add('hidden');
    if (bulkImportForm) bulkImportForm.reset();
}

function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.movie-checkbox:not([disabled])');
    const selectAllBtn = document.getElementById('selectAllBtn');

    const allSelected = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(checkbox => {
        checkbox.checked = !allSelected;
        const movieData = JSON.parse(checkbox.dataset.movie);

        if (checkbox.checked) {
            selectedMovies.add(movieData);
        } else {
            selectedMovies.delete(movieData);
        }
    });

    if (selectAllBtn) {
        selectAllBtn.textContent = allSelected ? 'Select All' : 'Deselect All';
    }

    updateBulkImportButton();
}

function updateBulkImportButton() {
    const bulkImportBtn = document.getElementById('bulkImportBtn');
    if (bulkImportBtn) {
        bulkImportBtn.disabled = selectedMovies.size === 0;
        bulkImportBtn.innerHTML = `<i class="fas fa-download mr-2"></i>Bulk Import Selected (${selectedMovies.size})`;
    }
}

// Form submissions
async function handleImportSubmit(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const submitBtn = event.target.querySelector('button[type="submit"]');

    // Show loading state
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
        submitBtn.disabled = true;
    }

    try {
        const response = await fetch(window.tmdbConfig.importUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.tmdbConfig.csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert('Movie imported successfully!');
            closeImportModal();
            // Refresh current view
            if (currentType === 'search') {
                searchMovies(currentQuery, currentPage);
            } else if (currentType === 'popular') {
                loadPopularMovies(currentPage);
            } else if (currentType === 'trending') {
                loadTrendingMovies();
            }
        } else {
            alert(data.message || 'Failed to import movie.');
        }
    } catch (error) {
        alert('Failed to import movie. Please try again.');
    } finally {
        // Restore button state
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Import Movie';
            submitBtn.disabled = false;
        }
    }
}

async function handleBulkImportSubmit(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const submitBtn = event.target.querySelector('button[type="submit"]');

    // Show loading state
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';
        submitBtn.disabled = true;
    }

    try {
        const response = await fetch(window.tmdbConfig.bulkImportUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.tmdbConfig.csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(`Successfully imported ${data.imported_count} movies!`);
            closeBulkImportModal();
            selectedMovies.clear();
            // Refresh current view
            if (currentType === 'search') {
                searchMovies(currentQuery, currentPage);
            } else if (currentType === 'popular') {
                loadPopularMovies(currentPage);
            } else if (currentType === 'trending') {
                loadTrendingMovies();
            }
        } else {
            alert(data.message || 'Failed to import movies.');
        }
    } catch (error) {
        alert('Failed to import movies. Please try again.');
    } finally {
        // Restore button state
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-download mr-2"></i>Import All';
            submitBtn.disabled = false;
        }
    }
}

// Pagination
function createPagination(data) {
    const pagination = document.getElementById('pagination');
    if (!pagination) return;

    pagination.innerHTML = '';

    const totalPages = data.total_pages || 1;
    const currentPageNum = data.page || 1;

    // Previous button
    if (currentPageNum > 1) {
        const prevBtn = document.createElement('button');
        prevBtn.className = 'tmdb-pagination-btn';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.addEventListener('click', () => {
            if (currentType === 'search') {
                searchMovies(currentQuery, currentPageNum - 1);
            } else if (currentType === 'popular') {
                loadPopularMovies(currentPageNum - 1);
            }
        });
        pagination.appendChild(prevBtn);
    }

    // Page numbers (show max 5 pages)
    const startPage = Math.max(1, currentPageNum - 2);
    const endPage = Math.min(totalPages, startPage + 4);

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'tmdb-pagination-btn';
        if (i === currentPageNum) pageBtn.classList.add('active');
        pageBtn.textContent = i;
        pageBtn.addEventListener('click', () => {
            if (currentType === 'search') {
                searchMovies(currentQuery, i);
            } else if (currentType === 'popular') {
                loadPopularMovies(i);
            }
        });
        pagination.appendChild(pageBtn);
    }

    // Next button
    if (currentPageNum < totalPages) {
        const nextBtn = document.createElement('button');
        nextBtn.className = 'tmdb-pagination-btn';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.addEventListener('click', () => {
            if (currentType === 'search') {
                searchMovies(currentQuery, currentPageNum + 1);
            } else if (currentType === 'popular') {
                loadPopularMovies(currentPageNum + 1);
            }
        });
        pagination.appendChild(nextBtn);
    }
}

function viewMovieDetails(tmdbId) {
    // Open TMDB movie details in new tab
    window.open(`https://www.themoviedb.org/movie/${tmdbId}`, '_blank');
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize basic functionality even without explicit config
    if (typeof window.tmdbConfig === 'undefined') {
        console.log('TMDB Config not found, initializing with defaults...');
        // Set default config if not provided
        window.tmdbConfig = {
            searchUrl: '/admin/tmdb/search',
            popularUrl: '/admin/tmdb/popular',
            trendingUrl: '/admin/tmdb/trending',
            importUrl: '/admin/tmdb/import',
            bulkImportUrl: '/admin/tmdb/bulk-import',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        };
    }
});