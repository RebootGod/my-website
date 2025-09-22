@extends('layouts.admin')

@section('title', 'Import from TMDB - Admin')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Import Movies from TMDB</h1>
        <a href="{{ route('admin.movies.index') }}" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Movies
        </a>
    </div>

    <!-- Search Section -->
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Search Movies</h2>
        
        <div class="flex gap-4 mb-4">
            <div class="flex-1">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Search for movies..." 
                       class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400">
            </div>
            <button id="searchBtn" 
                    class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-lg transition">
                <i class="fas fa-search mr-2"></i>Search
            </button>
        </div>

        <div class="flex gap-4">
            <button id="popularBtn" 
                    class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded-lg transition">
                <i class="fas fa-fire mr-2"></i>Popular Movies
            </button>
            <button id="trendingBtn" 
                    class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg transition">
                <i class="fas fa-trending-up mr-2"></i>Trending Movies
            </button>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="hidden text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
        <p class="mt-2 text-gray-400">Loading movies...</p>
    </div>

    <!-- Results Section -->
    <div id="resultsSection" class="hidden">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Search Results</h2>
            <div class="flex gap-2">
                <button id="selectAllBtn" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded text-sm transition">
                    Select All
                </button>
                <button id="bulkImportBtn" class="bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded text-sm transition">
                    <i class="fas fa-download mr-2"></i>Bulk Import Selected
                </button>
            </div>
        </div>

        <div id="movieGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Movies will be populated here -->
        </div>

        <!-- Pagination -->
        <div id="pagination" class="mt-6 flex justify-center">
            <!-- Pagination will be populated here -->
        </div>
    </div>

    <!-- No Results -->
    <div id="noResults" class="hidden text-center py-12">
        <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-400">No movies found. Try a different search term.</p>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Import Movie</h3>
                <button id="closeModal" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="importForm">
                <input type="hidden" id="tmdbId" name="tmdb_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Embed URL *</label>
                    <input type="url" 
                           id="embedUrl" 
                           name="embed_url" 
                           required
                           placeholder="https://example.com/player/movie123"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Quality *</label>
                    <select id="quality" name="quality" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        <option value="CAM">CAM</option>
                        <option value="HD" selected>HD</option>
                        <option value="FHD">FHD</option>
                        <option value="4K">4K</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Status *</label>
                    <select id="status" name="status" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        <option value="draft">Draft</option>
                        <option value="published" selected>Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" 
                            id="cancelImport" 
                            class="flex-1 bg-gray-600 hover:bg-gray-700 py-2 px-4 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            id="confirmImport" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 py-2 px-4 rounded transition">
                        Import Movie
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Import Modal -->
<div id="bulkImportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg p-6 w-full max-w-lg">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Bulk Import Movies</h3>
                <button id="closeBulkModal" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="bulkImportForm">
                <div class="mb-4">
                    <p class="text-sm text-gray-400 mb-2">
                        Selected <span id="selectedCount">0</span> movies for import
                    </p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Default Quality *</label>
                    <select id="bulkQuality" name="default_quality" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        <option value="CAM">CAM</option>
                        <option value="HD" selected>HD</option>
                        <option value="FHD">FHD</option>
                        <option value="4K">4K</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Default Status *</label>
                    <select id="bulkStatus" name="default_status" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        <option value="draft" selected>Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <h4 class="text-sm font-medium mb-2">Embed URLs for Selected Movies:</h4>
                    <div id="bulkEmbedUrls" class="max-h-60 overflow-y-auto space-y-2">
                        <!-- URLs will be populated here -->
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <button type="button" 
                            id="cancelBulkImport" 
                            class="flex-1 bg-gray-600 hover:bg-gray-700 py-2 px-4 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            id="confirmBulkImport" 
                            class="flex-1 bg-orange-600 hover:bg-orange-700 py-2 px-4 rounded transition">
                        Import All
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    let currentPage = 1;
    let currentQuery = '';
    let currentType = 'search';
    let selectedMovies = new Set();

    // Search functionality
    searchBtn.addEventListener('click', () => {
        const query = searchInput.value.trim();
        if (query) {
            searchMovies(query);
        }
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });

    popularBtn.addEventListener('click', () => {
        loadPopularMovies();
    });

    trendingBtn.addEventListener('click', () => {
        loadTrendingMovies();
    });

    // Search movies
    async function searchMovies(query, page = 1) {
        currentQuery = query;
        currentType = 'search';
        currentPage = page;
        
        showLoading();
        
        try {
            const response = await fetch(`{{ route('admin.tmdb.search') }}?query=${encodeURIComponent(query)}&page=${page}`);
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
            const response = await fetch(`{{ route('admin.tmdb.popular') }}?page=${page}`);
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
            const response = await fetch(`{{ route('admin.tmdb.trending') }}?time_window=week`);
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
        card.className = 'bg-gray-700 rounded-lg overflow-hidden hover:bg-gray-600 transition';
        
        const posterUrl = movie.poster_path 
            ? `https://image.tmdb.org/t/p/w300${movie.poster_path}`
            : '/images/no-poster.jpg';
        
        const releaseYear = movie.release_date ? new Date(movie.release_date).getFullYear() : 'N/A';
        const rating = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';
        
        card.innerHTML = `
            <div class="relative">
                <img src="${posterUrl}" alt="${movie.title}" class="w-full h-64 object-cover">
                <div class="absolute top-2 right-2">
                    <input type="checkbox" 
                           class="movie-checkbox" 
                           data-movie='${JSON.stringify(movie)}'
                           ${movie.exists_in_db ? 'disabled' : ''}>
                </div>
                ${movie.exists_in_db ? '<div class="absolute top-2 left-2 bg-green-600 text-xs px-2 py-1 rounded">Imported</div>' : ''}
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-sm mb-2 line-clamp-2">${movie.title}</h3>
                <div class="text-xs text-gray-400 mb-2">
                    <span>${releaseYear}</span> • <span>★ ${rating}</span>
                </div>
                <div class="flex gap-2">
                    <button class="view-details-btn flex-1 bg-blue-600 hover:bg-blue-700 px-3 py-1 rounded text-xs transition"
                            data-tmdb-id="${movie.tmdb_id}">
                        View Details
                    </button>
                    ${!movie.exists_in_db ? `
                        <button class="import-btn bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs transition"
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
        loadingIndicator.classList.remove('hidden');
        resultsSection.classList.add('hidden');
        noResults.classList.add('hidden');
    }

    function hideLoading() {
        loadingIndicator.classList.add('hidden');
    }

    function showResults() {
        resultsSection.classList.remove('hidden');
        noResults.classList.add('hidden');
    }

    function showNoResults() {
        resultsSection.classList.add('hidden');
        noResults.classList.remove('hidden');
    }

    function showError(message) {
        hideLoading();
        alert(message); // You can replace this with a better notification system
    }

    // Modal functions
    function openImportModal(movie) {
        document.getElementById('tmdbId').value = movie.tmdb_id;
        importModal.classList.remove('hidden');
    }

    function closeImportModal() {
        importModal.classList.add('hidden');
        importForm.reset();
    }

    // More JavaScript functions would continue here...
    // This is a simplified version for demonstration
});
</script>
@endsection