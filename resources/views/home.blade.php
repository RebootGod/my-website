@extends('layouts.app')

@section('title', 'Home - Cinema')

@push('styles')
@vite([
    'resources/css/pages/home.css',
    'resources/css/components/movie-cards.css',
    'resources/css/components/skeleton-loading.css',
    'resources/css/components/mobile-filters.css',
    'resources/css/components/loading.css',
    'resources/css/components/animations.css'
])
@endpush

@section('content')

{{-- Mobile Filter Toggle Button (Floating) --}}
<button class="filter-toggle-btn" id="filterToggleBtn" aria-label="Open Filters">
    <i class="fas fa-filter"></i>
</button>

{{-- Mobile Filter Bottom Sheet Overlay --}}
<div class="filter-bottom-sheet-overlay" id="filterOverlay"></div>

<div class="container">
    <div class="row">
        {{-- Desktop Filter Sidebar --}}
        <div class="col-lg-3 col-md-4 mb-4 d-none d-md-block">
            <div class="filter-sidebar">
                <h3 class="filter-title">
                    <i class="fas fa-filter me-2"></i>Filters
                </h3>

                <form method="GET" action="{{ route('home') }}" id="filterForm">
                    {{-- Search Field --}}
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <div class="position-relative">
                            <input type="text"
                                   class="modern-select"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search movies, actors, directors..."
                                   autocomplete="off">
                        </div>
                    </div>

                    {{-- Genre Filter --}}
                    <div class="filter-group">
                        <label class="filter-label">Genre</label>
                        <select name="genre" class="modern-select">
                            <option value="">All Genres</option>
                            @foreach($genres as $genre)
                                <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                                    {{ $genre->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Year Filter --}}
                    <div class="filter-group">
                        <label class="filter-label">Release Year</label>
                        <select name="year" class="modern-select">
                            <option value="">All Years</option>
                            @for($y = 2024; $y >= 1990; $y--)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Rating Filter --}}
                    <div class="filter-group">
                        <label class="filter-label">Minimum Rating</label>
                        <select name="rating" class="modern-select">
                            <option value="">Any Rating</option>
                            <option value="9" {{ request('rating') == '9' ? 'selected' : '' }}>9+ ⭐⭐⭐⭐⭐</option>
                            <option value="8" {{ request('rating') == '8' ? 'selected' : '' }}>8+ ⭐⭐⭐⭐</option>
                            <option value="7" {{ request('rating') == '7' ? 'selected' : '' }}>7+ ⭐⭐⭐</option>
                            <option value="6" {{ request('rating') == '6' ? 'selected' : '' }}>6+ ⭐⭐</option>
                            <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5+ ⭐</option>
                        </select>
                    </div>

                    {{-- Sort Filter --}}
                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select name="sort" class="modern-select">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest Added</option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                            <option value="rating_high" {{ request('sort') == 'rating_high' ? 'selected' : '' }}>Highest Rated</option>
                            <option value="rating_low" {{ request('sort') == 'rating_low' ? 'selected' : '' }}>Lowest Rated</option>
                            <option value="alphabetical" {{ request('sort') == 'alphabetical' ? 'selected' : '' }}>A-Z</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-modern">
                            <i class="fas fa-search me-2"></i>Apply
                        </button>
                        <a href="{{ route('home') }}" class="btn btn-outline-modern">
                            <i class="fas fa-refresh me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Mobile Filter Bottom Sheet --}}
        <div class="filter-bottom-sheet" id="filterBottomSheet">
            <div class="filter-bottom-sheet-handle"></div>
            <div class="filter-bottom-sheet-header">
                <h3 class="filter-title">
                    <i class="fas fa-filter me-2"></i>Filters
                </h3>
                <button class="filter-close-btn" id="filterCloseBtn" aria-label="Close Filters">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="GET" action="{{ route('home') }}" id="mobileFilterForm">
                {{-- Search Field --}}
                <div class="filter-group">
                    <label class="filter-label">Search</label>
                    <div class="position-relative">
                        <input type="text"
                               class="modern-select"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search movies, actors, directors..."
                               autocomplete="off">
                    </div>
                </div>

                {{-- Genre Filter --}}
                <div class="filter-group">
                    <label class="filter-label">Genre</label>
                    <select name="genre" class="modern-select">
                        <option value="">All Genres</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                                {{ $genre->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Year Filter --}}
                <div class="filter-group">
                    <label class="filter-label">Release Year</label>
                    <select name="year" class="modern-select">
                        <option value="">All Years</option>
                        @for($y = 2024; $y >= 1990; $y--)
                            <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Rating Filter --}}
                <div class="filter-group">
                    <label class="filter-label">Minimum Rating</label>
                    <select name="rating" class="modern-select">
                        <option value="">Any Rating</option>
                        <option value="9" {{ request('rating') == '9' ? 'selected' : '' }}>9+ ⭐⭐⭐⭐⭐</option>
                        <option value="8" {{ request('rating') == '8' ? 'selected' : '' }}>8+ ⭐⭐⭐⭐</option>
                        <option value="7" {{ request('rating') == '7' ? 'selected' : '' }}>7+ ⭐⭐⭐</option>
                        <option value="6" {{ request('rating') == '6' ? 'selected' : '' }}>6+ ⭐⭐</option>
                        <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>5+ ⭐</option>
                    </select>
                </div>

                {{-- Sort Filter --}}
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" class="modern-select">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest Added</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="rating_high" {{ request('sort') == 'rating_high' ? 'selected' : '' }}>Highest Rated</option>
                        <option value="rating_low" {{ request('sort') == 'rating_low' ? 'selected' : '' }}>Lowest Rated</option>
                        <option value="alphabetical" {{ request('sort') == 'alphabetical' ? 'selected' : '' }}>A-Z</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-modern w-100 mb-2">
                        <i class="fas fa-search me-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('home') }}" class="btn btn-outline-modern w-100">
                        <i class="fas fa-refresh me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
        </div>

        {{-- Main Content --}}
        <div class="col-lg-9 col-md-8">
            {{-- All Movies Section --}}
            <div class="content-section">
                <h2 class="section-title">
                    @if(request('search'))
                        Search Results for "{{ request('search') }}"
                    @else
                        All Movies & TV Series
                    @endif
                </h2>

                @if($contents->count() > 0)
                    <div class="movie-grid">
                        {{-- Display All Contents (Movies & Series) --}}
                        @foreach($contents as $item)
                            @php
                                $detailUrl = $item instanceof \App\Models\Series 
                                    ? route('series.show', $item) 
                                    : route('movies.show', $item->slug);
                            @endphp
                            <a href="{{ $detailUrl }}" class="movie-card-modern">
                                <div class="movie-poster">
                                    @php
                                        $posterUrl = $item->poster_url ?? 'https://placehold.co/500x750/1a1f3a/8b5cf6?text=' . urlencode($item->title);
                                    @endphp
                                    <img src="{{ $posterUrl }}"
                                         alt="{{ $item->title }}"
                                         loading="lazy"
                                         onerror="this.src='https://placehold.co/500x750/1a1f3a/8b5cf6?text=No+Poster'">
                                    <div class="movie-overlay"></div>
                                    @if($item instanceof \App\Models\Series)
                                        <div class="content-type-badge series-badge">
                                            <i class="fas fa-tv me-1"></i>TV Series
                                        </div>
                                    @else
                                        <div class="content-type-badge movie-badge">
                                            <i class="fas fa-film me-1"></i>Movie
                                        </div>
                                    @endif
                                </div>
                                <div class="movie-info">
                                    <h3 class="movie-title">{{ $item->title }}</h3>
                                    <div class="movie-meta">
                                        <span>{{ number_format($item->rating, 1) }}</span>
                                        <span>{{ $item->year }}</span>
                                        @if($item instanceof \App\Models\Series)
                                            <span>{{ $item->seasons->count() }}S</span>
                                        @else
                                            <span>{{ $item->duration }}m</span>
                                        @endif
                                    </div>
                                    <p class="movie-description">{{ Str::limit($item->description, 120) }}</p>
                                    <div class="movie-actions">
                                        @if($item instanceof \App\Models\Series)
                                            <a href="{{ route('series.show', $item) }}" class="btn-watch">
                                                <i class="fas fa-play me-2"></i>Watch Now
                                            </a>
                                            <button class="btn-bookmark" onclick="toggleWatchlist('{{ $item->slug }}')">
                                                <i class="fas fa-bookmark"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('movies.show', $item->slug) }}" class="btn-watch">
                                                <i class="fas fa-play me-2"></i>Watch Now
                                            </a>
                                            <button class="btn-bookmark" onclick="toggleWatchlist('{{ $item->slug }}')">
                                                <i class="fas fa-bookmark"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Modern Pagination --}}
                    <div class="d-flex justify-content-center mt-5">
                        {{ $contents->withQueryString()->links('components.pagination', ['class' => 'pagination-modern']) }}
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-film"></i>
                        <h3>No content found</h3>
                        <p>Try adjusting your filters or search terms</p>
                        <a href="{{ route('home') }}" class="btn btn-modern mt-3">
                            <i class="fas fa-refresh me-2"></i>Reset Filters
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite([
    'resources/js/pages/home.js',
    'resources/js/components/search.js',
    'resources/js/components/watchlist.js'
])
@endpush