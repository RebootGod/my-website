{{-- ======================================== --}}
{{-- ENHANCED MOVIES INDEX WITH FILTERS --}}
{{-- ======================================== --}}
{{-- File: resources/views/movies/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Browse Movies - Noobz Cinema')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Sidebar Filters --}}
        <div class="lg:w-64 flex-shrink-0">
            <div class="bg-gray-800 rounded-lg p-4 sticky top-4">
                {{-- Filter Header --}}
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Filters</h3>
                    @if($activeFiltersCount > 0)
                    <a href="{{ route('movies.index') }}" 
                       class="text-sm text-red-400 hover:text-red-300">
                        Clear All ({{ $activeFiltersCount }})
                    </a>
                    @endif
                </div>

                <form id="filterForm" method="GET" action="{{ route('movies.index') }}">
                    {{-- Search Input --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Search</label>
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   id="searchInput"
                                   value="{{ request('search') }}"
                                   placeholder="Movie title..."
                                   class="w-full bg-gray-700 text-white px-3 py-2 rounded-lg pr-8">
                            <button type="submit" class="absolute right-2 top-2.5 text-gray-400 hover:text-white">
                                🔍
                            </button>
                        </div>
                        {{-- Search Suggestions --}}
                        <div id="searchSuggestions" class="hidden absolute z-50 w-full bg-gray-700 rounded-lg mt-1 shadow-lg">
                        </div>
                    </div>

                    {{-- Genre Filter --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Genres</label>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($genres as $genre)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="genres[]" 
                                       value="{{ $genre->id }}"
                                       {{ in_array($genre->id, (array)request('genres')) ? 'checked' : '' }}
                                       class="mr-2 rounded bg-gray-700">
                                <span class="text-sm">{{ $genre->name }}</span>
                                <span class="text-xs text-gray-500 ml-auto">({{ $genre->movies_count }})</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Year Range Filter --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Year</label>
                        <div class="flex gap-2">
                            <input type="number" 
                                   name="year_from" 
                                   value="{{ request('year_from') }}"
                                   placeholder="From"
                                   min="1900"
                                   max="{{ date('Y') }}"
                                   class="w-1/2 bg-gray-700 text-white px-2 py-1 rounded text-sm">
                            <input type="number" 
                                   name="year_to" 
                                   value="{{ request('year_to') }}"
                                   placeholder="To"
                                   min="1900"
                                   max="{{ date('Y') }}"
                                   class="w-1/2 bg-gray-700 text-white px-2 py-1 rounded text-sm">
                        </div>
                    </div>

                    {{-- Rating Filter --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-400 mb-2">
                            Minimum Rating
                            <span id="ratingValue" class="text-yellow-400 ml-2">
                                {{ request('rating_min', '0') }}+
                            </span>
                        </label>
                        <input type="range" 
                               name="rating_min" 
                               id="ratingRange"
                               value="{{ request('rating_min', 0) }}"
                               min="0" 
                               max="9" 
                               step="1"
                               class="w-full">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>0</span>
                            <span>5</span>
                            <span>9+</span>
                        </div>
                    </div>

                    {{-- Quality Filter --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Quality</label>
                        <div class="space-y-2">
                            @foreach(['CAM', 'TS', 'HD', 'FHD', '4K'] as $quality)
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="quality[]" 
                                       value="{{ $quality }}"
                                       {{ in_array($quality, (array)request('quality')) ? 'checked' : '' }}
                                       class="mr-2 rounded bg-gray-700">
                                <span class="text-sm">{{ $quality }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Sort Options --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Sort By</label>
                        <select name="sort" class="w-full bg-gray-700 text-white px-3 py-2 rounded-lg">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest Added</option>
                            <option value="views" {{ request('sort') == 'views' ? 'selected' : '' }}>Most Viewed</option>
                            <option value="rating_desc" {{ request('sort') == 'rating_desc' ? 'selected' : '' }}>Highest Rated</option>
                            <option value="rating_asc" {{ request('sort') == 'rating_asc' ? 'selected' : '' }}>Lowest Rated</option>
                            <option value="year_desc" {{ request('sort') == 'year_desc' ? 'selected' : '' }}>Newest Movies</option>
                            <option value="year_asc" {{ request('sort') == 'year_asc' ? 'selected' : '' }}>Oldest Movies</option>
                            <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>Title (A-Z)</option>
                            <option value="title_desc" {{ request('sort') == 'title_desc' ? 'selected' : '' }}>Title (Z-A)</option>
                        </select>
                    </div>

                    {{-- Apply Button --}}
                    <button type="submit" 
                            class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg transition">
                        Apply Filters
                    </button>
                </form>

                {{-- Popular Searches --}}
                <div class="mt-6 pt-6 border-t border-gray-700">
                    <h4 class="text-sm font-medium text-gray-400 mb-2">Popular Searches</h4>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('movies.index', ['search' => 'Action']) }}" 
                           class="text-xs bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded transition">
                            Action
                        </a>
                        <a href="{{ route('movies.index', ['search' => 'Comedy']) }}" 
                           class="text-xs bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded transition">
                            Comedy
                        </a>
                        <a href="{{ route('movies.index', ['sort' => 'rating_desc']) }}" 
                           class="text-xs bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded transition">
                            Top Rated
                        </a>
                        <a href="{{ route('movies.index', ['year_from' => date('Y')]) }}" 
                           class="text-xs bg-gray-700 hover:bg-gray-600 px-2 py-1 rounded transition">
                            {{ date('Y') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="flex-1">
            {{-- Results Header --}}
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold">Browse Movies</h1>
                    <p class="text-gray-400 text-sm mt-1">
                        Found {{ $movies->total() }} movies
                        @if(request()->hasAny(['search', 'genres', 'year_from', 'year_to', 'quality', 'rating_min']))
                            matching your filters
                        @endif
                    </p>
                </div>

                {{-- View Mode Toggle --}}
                <div class="flex gap-2">
                    <button onclick="setViewMode('grid')" 
                            id="gridViewBtn"
                            class="p-2 bg-gray-700 rounded hover:bg-gray-600 transition">
                        ▦
                    </button>
                    <button onclick="setViewMode('list')" 
                            id="listViewBtn"
                            class="p-2 bg-gray-800 rounded hover:bg-gray-600 transition">
                        ☰
                    </button>
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if(request()->hasAny(['search', 'genres', 'year_from', 'year_to', 'quality', 'rating_min']))
            <div class="mb-4 flex flex-wrap gap-2">
                @if(request('search'))
                <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-sm">
                    Search: {{ request('search') }}
                    <a href="{{ request()->fullUrlWithoutQuery('search') }}" class="ml-2">✕</a>
                </span>
                @endif
                
                @if(request('genres'))
                    @foreach($genres->whereIn('id', (array)request('genres')) as $genre)
                    <span class="bg-purple-500/20 text-purple-400 px-3 py-1 rounded-full text-sm">
                        {{ $genre->name }}
                    </span>
                    @endforeach
                @endif
                
                @if(request('year_from') || request('year_to'))
                <span class="bg-green-500/20 text-green-400 px-3 py-1 rounded-full text-sm">
                    Year: {{ request('year_from', '...') }} - {{ request('year_to', '...') }}
                </span>
                @endif
                
                @if(request('rating_min'))
                <span class="bg-yellow-500/20 text-yellow-400 px-3 py-1 rounded-full text-sm">
                    Rating: {{ request('rating_min') }}+
                </span>
                @endif
            </div>
            @endif

            {{-- Movies Grid/List --}}
            <div id="moviesContainer" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @forelse($movies as $movie)
                <div class="movie-card bg-gray-800 rounded-lg overflow-hidden hover:ring-2 hover:ring-green-400 transition group">
                    <a href="{{ route('movies.show', $movie->slug) }}" class="block relative">
                        {{-- Watchlist Button --}}
                        @auth
                            @php
                                $inWatchlist = \App\Models\Watchlist::where('user_id', auth()->id())
                                    ->where('movie_id', $movie->id)
                                    ->exists();
                            @endphp
                            <div class="absolute top-2 right-2 z-10">
                                @if($inWatchlist)
                                    <span class="bg-green-500 text-white p-1.5 rounded-full text-xs">✓</span>
                                @else
                                    <button onclick="event.preventDefault(); event.stopPropagation(); addToWatchlist({{ $movie->id }})" 
                                            class="bg-black/50 hover:bg-green-500 text-white p-1.5 rounded-full text-xs transition opacity-0 group-hover:opacity-100">
                                        +
                                    </button>
                                @endif
                            </div>
                        @endauth
                        
                        {{-- Quality Badge --}}
                        <span class="absolute top-2 left-2 bg-black/70 text-white px-2 py-1 rounded text-xs z-10">
                            {{ $movie->quality }}
                        </span>
                        
                        {{-- Poster --}}
                        <div class="aspect-[2/3] bg-gray-700">
                            @php
                                $posterUrl = '';
                                if ($movie->poster_path) {
                                    // If poster_path starts with http, use as is, otherwise prepend TMDB base URL
                                    $posterUrl = str_starts_with($movie->poster_path, 'http') 
                                        ? $movie->poster_path 
                                        : 'https://image.tmdb.org/t/p/w500' . $movie->poster_path;
                                } elseif ($movie->poster_url) {
                                    // If poster_url starts with http, use as is, otherwise prepend TMDB base URL  
                                    $posterUrl = str_starts_with($movie->poster_url, 'http') 
                                        ? $movie->poster_url 
                                        : 'https://image.tmdb.org/t/p/w500' . $movie->poster_url;
                                } else {
                                    $posterUrl = 'https://via.placeholder.com/400x600/343a40/ffffff?text=' . urlencode($movie->title);
                                }
                            @endphp
                            <img src="{{ $posterUrl }}" 
                                 alt="{{ $movie->title }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        </div>
                        
                        {{-- Info Overlay --}}
                        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/90 to-transparent p-3 opacity-0 group-hover:opacity-100 transition">
                            <p class="text-white font-semibold text-sm">{{ $movie->title }}</p>
                            <div class="flex items-center gap-2 text-xs text-gray-300 mt-1">
                                <span>{{ $movie->year }}</span>
                                <span>•</span>
                                <span>⭐ {{ number_format($movie->rating, 1) }}</span>
                            </div>
                        </div>
                    </a>
                    
                    {{-- Card Footer --}}
                    <div class="p-3">
                        <h3 class="font-medium text-sm line-clamp-1">{{ $movie->title }}</h3>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-gray-400">{{ $movie->year }}</span>
                            <span class="text-xs text-yellow-400">⭐ {{ number_format($movie->rating, 1) }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-16">
                    <p class="text-gray-400 text-lg">No movies found matching your criteria.</p>
                    <a href="{{ route('movies.index') }}" 
                       class="text-green-400 hover:text-green-300 mt-2 inline-block">
                        Clear filters and try again
                    </a>
                </div>
                @endforelse
            </div>

            {{-- Debug Info --}}
            <div class="mt-8 text-center text-white">
                <p>Total Movies: {{ $movies->total() }}</p>
                <p>Per Page: {{ $movies->perPage() }}</p>
                <p>Current Page: {{ $movies->currentPage() }}</p>
                <p>Last Page: {{ $movies->lastPage() }}</p>
                <p>Has Pages: {{ $movies->hasPages() ? 'Yes' : 'No' }}</p>
            </div>

            {{-- Pagination --}}
            @if($movies->hasPages())
            <div class="mt-12 bg-red-500 p-4 rounded">
                <p class="text-white mb-4">PAGINATION SHOULD BE HERE</p>
                <nav class="flex items-center justify-center space-x-1">
                    {{-- Previous Page --}}
                    @if ($movies->onFirstPage())
                        <span class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 rounded-l border cursor-not-allowed">
                            ← Previous
                        </span>
                    @else
                        <a href="{{ $movies->appends(request()->query())->previousPageUrl() }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-l border hover:bg-blue-700">
                            ← Previous
                        </a>
                    @endif

                    {{-- Current/Total Pages --}}
                    <span class="px-4 py-2 text-sm font-medium text-white bg-gray-800 border">
                        Page {{ $movies->currentPage() }} of {{ $movies->lastPage() }}
                    </span>

                    {{-- Next Page --}}
                    @if ($movies->hasMorePages())
                        <a href="{{ $movies->appends(request()->query())->nextPageUrl() }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-r border hover:bg-blue-700">
                            Next →
                        </a>
                    @else
                        <span class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-700 rounded-r border cursor-not-allowed">
                            Next →
                        </span>
                    @endif
                </nav>
                
                <div class="mt-4 text-center">
                    <p class="text-sm text-white">
                        Showing {{ $movies->firstItem() }} to {{ $movies->lastItem() }} of {{ $movies->total() }} results
                    </p>
                </div>
            </div>
            @else
            <div class="mt-8 text-center">
                <p class="text-red-500 text-lg">NO PAGINATION - NOT ENOUGH PAGES</p>
            </div>
            @endif
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Rating slider update
document.getElementById('ratingRange')?.addEventListener('input', function(e) {
    document.getElementById('ratingValue').textContent = e.target.value + '+';
});

// Search suggestions (requires route implementation)
let searchTimeout;
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value;
    
    if (query.length < 2) {
        document.getElementById('searchSuggestions').classList.add('hidden');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`/movies/suggestions?q=${query}`)
            .then(response => response.json())
            .then(data => {
                const suggestionsDiv = document.getElementById('searchSuggestions');
                if (data.length > 0) {
                    suggestionsDiv.innerHTML = data.map(movie => `
                        <a href="${movie.url}" class="flex items-center p-2 hover:bg-gray-600">
                            <img src="${movie.poster}" class="w-10 h-14 object-cover rounded mr-3">
                            <div>
                                <p class="text-sm font-medium">${movie.title}</p>
                                <p class="text-xs text-gray-400">${movie.year}</p>
                            </div>
                        </a>
                    `).join('');
                    suggestionsDiv.classList.remove('hidden');
                } else {
                    suggestionsDiv.classList.add('hidden');
                }
            });
    }, 300);
});

// View mode toggle
function setViewMode(mode) {
    const container = document.getElementById('moviesContainer');
    if (mode === 'list') {
        container.className = 'space-y-2';
        // You can implement list view layout here
    } else {
        container.className = 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4';
    }
    
    // Update button states
    document.getElementById('gridViewBtn').classList.toggle('bg-gray-700', mode === 'grid');
    document.getElementById('listViewBtn').classList.toggle('bg-gray-700', mode === 'list');
}

// Add to watchlist function
function addToWatchlist(movieId) {
    fetch(`/watchlist/add/${movieId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Simple reload for now
        }
    });
}
</script>
@endpush
@endsection