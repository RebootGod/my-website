@extends('layouts.admin')

@section('title', 'New TMDB Import - Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/tmdb.css') }}?v={{ filemtime(public_path('css/admin/tmdb.css')) }}">
@endpush

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">New TMDB Movie Import</h1>
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
                       placeholder="Search by title or TMDB ID (e.g., 1107979)..." 
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

    <!-- Results -->
    <div id="resultsContainer">
        <div id="loadingIndicator" class="hidden text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-400">Loading movies...</p>
        </div>

        <div id="errorMessage" class="hidden bg-red-600 text-white p-4 rounded-lg mb-4">
            <span id="errorText"></span>
        </div>

        <div id="moviesGrid" class="hidden">
            <h3 class="text-xl font-semibold mb-4">Results</h3>
            <div id="moviesList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Movies will be populated here -->
            </div>
        </div>

        <div id="noResults" class="hidden text-center py-12">
            <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-400">No movies found. Try a different search term.</p>
        </div>
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
                        <option value="">Select Quality</option>
                        <option value="CAM">CAM</option>
                        <option value="HD">HD</option>
                        <option value="FHD">Full HD</option>
                        <option value="4K">4K</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium mb-2">Status *</label>
                    <select id="status" name="status" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        <option value="">Select Status</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" id="cancelImport" class="flex-1 bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" id="submitImport" class="flex-1 bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
                        <span id="importBtnText">Import Movie</span>
                        <span id="importBtnLoading" class="hidden">Importing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/admin/tmdb.js') }}?v={{ filemtime(public_path('js/admin/tmdb.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeTMDBAdmin({
        searchUrl: '{{ route("admin.tmdb.new-search") }}',
        popularUrl: '{{ route("admin.tmdb.new-popular") }}',
        trendingUrl: '{{ route("admin.tmdb.new-trending") }}',
        importUrl: '{{ route("admin.tmdb.new-import") }}',
        bulkImportUrl: '{{ route("admin.tmdb.bulk-import") }}',
        csrfToken: '{{ csrf_token() }}'
    });
    // Elements
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const popularBtn = document.getElementById('popularBtn');
    const trendingBtn = document.getElementById('trendingBtn');
    
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const moviesGrid = document.getElementById('moviesGrid');
    const moviesList = document.getElementById('moviesList');
    const noResults = document.getElementById('noResults');
    
    const importModal = document.getElementById('importModal');
    const importForm = document.getElementById('importForm');
    const closeModal = document.getElementById('closeModal');
    const cancelImport = document.getElementById('cancelImport');

    // Event listeners
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

    closeModal.addEventListener('click', hideImportModal);
    cancelImport.addEventListener('click', hideImportModal);

    importForm.addEventListener('submit', handleImport);

    // Functions
    function searchMovies(query) {
        console.log('Starting search for:', query);
        showLoading();
        
        const url = `{{ route('admin.tmdb-new.search') }}?query=${encodeURIComponent(query)}`;
        console.log('Fetching URL:', url);
        
        fetch(url)
            .then(response => {
                console.log('Response received:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                console.log('Results array:', data.results);
                console.log('Results type:', typeof data.results);
                console.log('Results length:', data.results ? data.results.length : 'undefined');
                hideLoading();
                if (data.error) {
                    console.error('API Error:', data.error);
                    showError(data.error);
                } else {
                    console.log('Success! Results:', data.results?.length || 0);
                    displayMovies(data.results || []);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                hideLoading();
                showError('Failed to search movies. Please try again. Error: ' + error.message);
            });
    }

    function loadPopularMovies() {
        console.log('Loading popular movies');
        showLoading();
        
        const url = `{{ route('admin.tmdb-new.popular') }}`;
        console.log('Fetching URL:', url);
        
        fetch(url)
            .then(response => {
                console.log('Popular response:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('Popular data:', data);
                hideLoading();
                if (data.error) {
                    console.error('Popular API Error:', data.error);
                    showError(data.error);
                } else {
                    console.log('Popular success! Results:', data.results?.length || 0);
                    displayMovies(data.results || []);
                }
            })
            .catch(error => {
                console.error('Popular fetch error:', error);
                hideLoading();
                showError('Failed to load popular movies. Please try again. Error: ' + error.message);
            });
    }

    function loadTrendingMovies() {
        showLoading();
        
        fetch(`{{ route('admin.tmdb-new.trending') }}`)
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.error) {
                    showError(data.error);
                } else {
                    displayMovies(data.results || []);
                }
            })
            .catch(error => {
                hideLoading();
                showError('Failed to load trending movies. Please try again.');
                console.error('Trending error:', error);
            });
    }

    function displayMovies(movies) {
        hideError();
        
        if (!movies || movies.length === 0) {
            showNoResults();
            return;
        }

        moviesList.innerHTML = '';
        
        movies.forEach(movie => {
            const movieCard = createMovieCard(movie);
            moviesList.appendChild(movieCard);
        });
        
        showMoviesGrid();
    }

    function createMovieCard(movie) {
        const card = document.createElement('div');
        card.className = 'bg-gray-700 rounded-lg overflow-hidden hover:bg-gray-600 transition';
        
        const posterUrl = movie.poster_path 
            ? `https://image.tmdb.org/t/p/w300${movie.poster_path}`
            : '/images/no-poster.jpg';
        
        const year = movie.year || 'N/A';
        const rating = movie.rating ? movie.rating.toFixed(1) : 'N/A';
        
        card.innerHTML = `
            <div class="relative">
                <img src="${posterUrl}" alt="${movie.title}" class="w-full h-64 object-cover" onerror="this.src='/images/no-poster.jpg'">
                ${movie.exists_in_db ? '<div class="absolute top-2 left-2 bg-green-600 text-xs px-2 py-1 rounded">Already Imported</div>' : ''}
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-sm mb-2 line-clamp-2">${movie.title}</h3>
                <p class="text-xs text-gray-400 mb-2 line-clamp-2">${movie.description || 'No description available'}</p>
                <div class="text-xs text-gray-400 mb-3">
                    <span>${year}</span> • <span>★ ${rating}</span> • <span>ID: ${movie.tmdb_id}</span>
                </div>
                <div class="flex gap-2">
                    ${!movie.exists_in_db ? `
                        <button class="import-btn flex-1 bg-green-600 hover:bg-green-700 px-3 py-1 rounded text-xs transition"
                                data-tmdb-id="${movie.tmdb_id}">
                            Import
                        </button>
                    ` : `
                        <button class="flex-1 bg-gray-500 px-3 py-1 rounded text-xs cursor-not-allowed" disabled>
                            Already Imported
                        </button>
                    `}
                </div>
            </div>
        `;
        
        // Add import event listener
        const importBtn = card.querySelector('.import-btn');
        if (importBtn) {
            importBtn.addEventListener('click', () => {
                showImportModal(movie.tmdb_id);
            });
        }
        
        return card;
    }

    function showImportModal(tmdbId) {
        document.getElementById('tmdbId').value = tmdbId;
        importModal.classList.remove('hidden');
    }

    function hideImportModal() {
        importModal.classList.add('hidden');
        importForm.reset();
    }

    function handleImport(e) {
        e.preventDefault();
        
        const formData = new FormData(importForm);
        const submitBtn = document.getElementById('submitImport');
        const btnText = document.getElementById('importBtnText');
        const btnLoading = document.getElementById('importBtnLoading');
        
        // Show loading state
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        
        fetch(`{{ route('admin.tmdb-new.import') }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Movie imported successfully!');
                hideImportModal();
                // Refresh the current search to update the UI
                if (searchInput.value.trim()) {
                    searchMovies(searchInput.value.trim());
                }
            } else {
                alert(data.error || 'Failed to import movie');
            }
        })
        .catch(error => {
            console.error('Import error:', error);
            alert('Failed to import movie. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        });
    }

    // UI state functions
    function showLoading() {
        loadingIndicator.classList.remove('hidden');
        moviesGrid.classList.add('hidden');
        noResults.classList.add('hidden');
        hideError();
    }

    function hideLoading() {
        loadingIndicator.classList.add('hidden');
    }

    function showMoviesGrid() {
        loadingIndicator.classList.add('hidden');
        moviesGrid.classList.remove('hidden');
        noResults.classList.add('hidden');
        hideError();
    }

    function showNoResults() {
        loadingIndicator.classList.add('hidden');
        moviesGrid.classList.add('hidden');
        noResults.classList.remove('hidden');
        hideError();
    }

    function showError(message) {
        loadingIndicator.classList.add('hidden');
        moviesGrid.classList.add('hidden');
        noResults.classList.add('hidden');
        errorText.textContent = message;
        errorMessage.classList.remove('hidden');
    }

});
</script>
@endpush
@endsection