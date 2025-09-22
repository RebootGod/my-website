{{-- ======================================== --}}
{{-- ENHANCED TMDB SEARCH VIEW --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/movies/tmdb-search.blade.php --}}

@extends('layouts.admin')

@section('title', 'TMDB Movie Search - Admin')

@section('content')
<div class="container mx-auto">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Import Movies from TMDB</h1>
        <a href="{{ route('admin.movies.index') }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            ← Back to Movies
        </a>
    </div>

    {{-- Search Box --}}
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-xl font-semibold mb-4">Search TMDB Database</h2>
            
            <div class="space-y-4">
                {{-- Search Input --}}
                <div class="flex gap-3">
                    <input type="text" 
                           id="searchQuery" 
                           placeholder="Enter movie title, TMDB ID, or IMDB ID (tt1234567)..." 
                           class="flex-1 bg-gray-700 text-white px-4 py-3 rounded-lg focus:ring-2 focus:ring-green-400"
                           onkeypress="if(event.key === 'Enter') searchMovies()">
                    
                    <button onclick="searchMovies()" 
                            class="bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-lg transition font-medium">
                        🔍 Search
                    </button>
                </div>

                {{-- Quick Search Options --}}
                <div class="flex flex-wrap gap-2">
                    <span class="text-gray-400 text-sm">Quick Search:</span>
                    <button onclick="searchPopular()" 
                            class="text-blue-400 hover:text-blue-300 text-sm underline">
                        Popular Movies
                    </button>
                    <button onclick="searchTrending()" 
                            class="text-purple-400 hover:text-purple-300 text-sm underline">
                        Trending This Week
                    </button>
                    <button onclick="searchById('872585')" 
                            class="text-yellow-400 hover:text-yellow-300 text-sm underline">
                        Example: Oppenheimer (ID: 872585)
                    </button>
                </div>

                {{-- Search Tips --}}
                <div class="bg-gray-700 rounded-lg p-3 text-sm text-gray-300">
                    <strong>💡 Search Tips:</strong>
                    <ul class="mt-1 space-y-1 ml-4">
                        <li>• Enter movie title for text search (e.g., "Avatar")</li>
                        <li>• Enter TMDB ID for direct lookup (e.g., "19995")</li>
                        <li>• Enter IMDB ID with prefix (e.g., "tt0499549")</li>
                        <li>• Search results will show if movie already exists in database</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading Indicator --}}
    <div id="loadingIndicator" class="hidden text-center py-8">
        <div class="inline-flex items-center space-x-2">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-green-400"></div>
            <span class="text-gray-400">Searching TMDB database...</span>
        </div>
    </div>

    {{-- Search Results --}}
    <div id="searchResults" class="hidden">
        <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">
                    Search Results 
                    <span id="resultCount" class="text-gray-400 text-sm ml-2"></span>
                </h2>
                <button onclick="clearResults()" 
                        class="text-gray-400 hover:text-white text-sm">
                    ✕ Clear Results
                </button>
            </div>
            
            <div id="resultsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Results will be inserted here via JavaScript --}}
            </div>
        </div>
    </div>

    {{-- Movie Details Modal --}}
    <div id="movieModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-gray-800 border-b border-gray-700 p-4 flex justify-between items-center">
                    <h3 class="text-xl font-semibold">Movie Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-white text-2xl">
                        ✕
                    </button>
                </div>
                <div id="modalContent" class="p-6">
                    {{-- Movie details will be inserted here --}}
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentResults = [];

// Search movies
async function searchMovies() {
    const query = document.getElementById('searchQuery').value.trim();
    
    if (!query) {
        alert('Please enter a search term');
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch(`{{ route('admin.movies.tmdb.search') }}?query=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.success) {
            displayResults(data.results, data.search_type);
        } else {
            alert(data.message || 'Search failed');
        }
    } catch (error) {
        console.error('Search error:', error);
        alert('An error occurred while searching');
    } finally {
        hideLoading();
    }
}

// Search by specific ID
function searchById(tmdbId) {
    document.getElementById('searchQuery').value = tmdbId;
    searchMovies();
}

// Search popular movies
async function searchPopular() {
    showLoading();
    
    try {
        const response = await fetch(`{{ route('admin.movies.tmdb.search') }}?type=popular`);
        const data = await response.json();
        
        if (data.success) {
            displayResults(data.results, 'popular');
        }
    } catch (error) {
        console.error('Error fetching popular movies:', error);
    } finally {
        hideLoading();
    }
}

// Search trending movies
async function searchTrending() {
    showLoading();
    
    try {
        const response = await fetch(`{{ route('admin.movies.tmdb.search') }}?type=trending`);
        const data = await response.json();
        
        if (data.success) {
            displayResults(data.results, 'trending');
        }
    } catch (error) {
        console.error('Error fetching trending movies:', error);
    } finally {
        hideLoading();
    }
}

// Display search results
function displayResults(results, searchType) {
    currentResults = results;
    const resultsGrid = document.getElementById('resultsGrid');
    const resultCount = document.getElementById('resultCount');
    
    // Update result count
    if (searchType === 'id') {
        resultCount.textContent = '(Direct ID lookup)';
    } else if (searchType === 'popular') {
        resultCount.textContent = '(Popular movies)';
    } else if (searchType === 'trending') {
        resultCount.textContent = '(Trending this week)';
    } else {
        resultCount.textContent = `(${results.length} results found)`;
    }
    
    // Clear previous results
    resultsGrid.innerHTML = '';
    
    if (results.length === 0) {
        resultsGrid.innerHTML = '<div class="col-span-full text-center text-gray-400 py-8">No results found</div>';
    } else {
        results.forEach(movie => {
            resultsGrid.innerHTML += createMovieCard(movie);
        });
    }
    
    document.getElementById('searchResults').classList.remove('hidden');
}

// Create movie card HTML
function createMovieCard(movie) {
    const posterUrl = movie.poster_path || '/images/no-poster.jpg';
    const year = movie.year || 'N/A';
    const rating = movie.rating ? movie.rating.toFixed(1) : 'N/A';
    
    return `
        <div class="bg-gray-700 rounded-lg overflow-hidden hover:bg-gray-600 transition">
            <div class="flex">
                <img src="${posterUrl}" 
                     alt="${movie.title}" 
                     class="w-24 h-36 object-cover"
                     onerror="this.src='/images/no-poster.jpg'">
                <div class="flex-1 p-4">
                    <h3 class="font-semibold text-white mb-1">${movie.title}</h3>
                    <p class="text-sm text-gray-300 mb-2">
                        ${year} • Rating: ${rating}/10
                    </p>
                    <p class="text-xs text-gray-400 line-clamp-2 mb-3">
                        ${movie.description || 'No description available'}
                    </p>
                    <div class="flex gap-2">
                        <button onclick="viewDetails(${movie.tmdb_id})" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition">
                            View Details
                        </button>
                        <button onclick="importMovie(${movie.tmdb_id})" 
                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition">
                            Import
                        </button>
                    </div>
                    <div class="mt-2">
                        <span class="text-xs text-gray-500">TMDB ID: ${movie.tmdb_id}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// View movie details
async function viewDetails(tmdbId) {
    const modal = document.getElementById('movieModal');
    const modalContent = document.getElementById('modalContent');
    
    modalContent.innerHTML = '<div class="text-center py-8">Loading details...</div>';
    modal.classList.remove('hidden');
    
    try {
        const response = await fetch(`{{ route('admin.movies.tmdb.details') }}?tmdb_id=${tmdbId}`);
        const data = await response.json();
        
        if (data.success) {
            displayMovieDetails(data.data);
        } else {
            modalContent.innerHTML = '<div class="text-center text-red-400 py-8">Failed to load movie details</div>';
        }
    } catch (error) {
        console.error('Error fetching details:', error);
        modalContent.innerHTML = '<div class="text-center text-red-400 py-8">An error occurred</div>';
    }
}

// Display movie details in modal
function displayMovieDetails(movie) {
    const modalContent = document.getElementById('modalContent');
    
    modalContent.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <img src="${movie.poster_path || '/images/no-poster.jpg'}" 
                     alt="${movie.title}" 
                     class="w-full rounded-lg shadow-lg">
            </div>
            <div class="md:col-span-2 space-y-4">
                <div>
                    <h2 class="text-2xl font-bold text-white">${movie.title}</h2>
                    ${movie.tagline ? `<p class="text-gray-400 italic">"${movie.tagline}"</p>` : ''}
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <span class="bg-gray-700 px-3 py-1 rounded-full text-sm">
                        📅 ${movie.year || 'N/A'}
                    </span>
                    <span class="bg-gray-700 px-3 py-1 rounded-full text-sm">
                        ⏱️ ${movie.duration ? movie.duration + ' min' : 'N/A'}
                    </span>
                    <span class="bg-gray-700 px-3 py-1 rounded-full text-sm">
                        ⭐ ${movie.rating}/10
                    </span>
                    <span class="bg-gray-700 px-3 py-1 rounded-full text-sm">
                        👥 ${movie.vote_count} votes
                    </span>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-2">Overview</h3>
                    <p class="text-gray-300">${movie.description || 'No description available'}</p>
                </div>
                
                ${movie.genres && movie.genres.length > 0 ? `
                <div>
                    <h3 class="font-semibold mb-2">Genres</h3>
                    <div class="flex flex-wrap gap-2">
                        ${movie.genres.map(genre => `
                            <span class="bg-purple-500/20 text-purple-400 px-3 py-1 rounded-full text-sm">
                                ${genre}
                            </span>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                ${movie.director ? `
                <div>
                    <h3 class="font-semibold mb-2">Director</h3>
                    <p class="text-gray-300">${movie.director}</p>
                </div>
                ` : ''}
                
                ${movie.cast && movie.cast.length > 0 ? `
                <div>
                    <h3 class="font-semibold mb-2">Main Cast</h3>
                    <div class="text-gray-300 text-sm">
                        ${movie.cast.slice(0, 5).map(actor => 
                            `${actor.name} as ${actor.character}`
                        ).join(', ')}
                    </div>
                </div>
                ` : ''}
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400">TMDB ID:</span>
                        <span class="text-white ml-2">${movie.tmdb_id}</span>
                    </div>
                    ${movie.imdb_id ? `
                    <div>
                        <span class="text-gray-400">IMDB ID:</span>
                        <span class="text-white ml-2">${movie.imdb_id}</span>
                    </div>
                    ` : ''}
                </div>
                
                ${movie.trailer ? `
                <div>
                    <a href="${movie.trailer}" target="_blank" 
                       class="inline-flex items-center bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                        ▶️ Watch Trailer
                    </a>
                </div>
                ` : ''}
                
                <div class="pt-4 border-t border-gray-700">
                    <form action="{{ route('admin.movies.tmdb.import') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="tmdb_id" value="${movie.tmdb_id}">
                        <button type="submit" 
                                class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition font-medium">
                            📥 Import This Movie
                        </button>
                    </form>
                </div>
            </div>
        </div>
    `;
}

// Import movie
async function importMovie(tmdbId) {
    if (!confirm('Import this movie to your database?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.movies.tmdb.import") }}';
    form.innerHTML = `
        @csrf
        <input type="hidden" name="tmdb_id" value="${tmdbId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Close modal
function closeModal() {
    document.getElementById('movieModal').classList.add('hidden');
}

// Clear results
function clearResults() {
    document.getElementById('searchResults').classList.add('hidden');
    document.getElementById('resultsGrid').innerHTML = '';
    document.getElementById('searchQuery').value = '';
}

// Show/hide loading
function showLoading() {
    document.getElementById('loadingIndicator').classList.remove('hidden');
    document.getElementById('searchResults').classList.add('hidden');
}

function hideLoading() {
    document.getElementById('loadingIndicator').classList.add('hidden');
}

// Handle Enter key
document.getElementById('searchQuery').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchMovies();
    }
});

// Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Close modal on background click
document.getElementById('movieModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endpush
@endsection