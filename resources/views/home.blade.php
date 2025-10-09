@extends('layouts.app')

@section('title', 'Home - Cinema')

@push('styles')
@vite([
    'resources/css/pages/home.css',
    'resources/css/components/movie-cards.css',
    'resources/css/components/loading.css',
    'resources/css/components/animations.css',
    'resources/css/components/mobile.css'
])
@endpush

@section('content')

<div class="container">
    <div class="row">
        {{-- Filter Sidebar --}}
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="filter-sidebar">
                <h3 class="filter-title">
                    <i class="fas fa-filter me-2"></i>Filters
                </h3>

                <form method="GET" action="{{ route('home') }}">
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
                            <div class="movie-card-modern">
                                <div class="movie-poster">
                                    <img src="{{ $item->poster_url ?: 'https://placehold.co/300x450/2c3e50/ecf0f1?text=No+Image' }}"
                                         alt="{{ $item->title }}"
                                         loading="lazy">
                                    <div class="movie-overlay"></div>
                                    <div class="movie-rating">
                                        <i class="fas fa-star me-1"></i>{{ number_format($item->rating, 1) }}
                                    </div>
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
                                        <span>{{ $item->year }}</span>
                                        @if($item instanceof \App\Models\Series)
                                            <span class="me-2">{{ $item->seasons->count() }} Season{{ $item->seasons->count() != 1 ? 's' : '' }}</span>
                                        @else
                                            <span>{{ $item->duration }} min</span>
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
                            </div>
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