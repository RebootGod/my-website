@extends('layouts.app')

@section('title', $movie->title . ' - Cinema')

@push('styles')
@vite([
    'resources/css/pages/movie-detail-v2.css',
    'resources/css/components/share-modal.css',
    'resources/css/components/animations.css'
])
@endpush

@push('scripts')
@vite([
    'resources/js/pages/detail-share.js'
])
@endpush


@section('content')
<div class="movie-detail-page">
    {{-- Hero Section with Backdrop --}}
    @if($movie->backdrop_url)
    <div class="hero-backdrop">
        <img
            src="{{ $movie->backdrop_url }}"
            alt="{{ $movie->title }} backdrop"
            class="hero-backdrop-img"
        >
        <div class="container hero-content">
            <div class="row align-items-end w-100">
                <div class="col-md-3">
                    <div class="poster-container">
                        <img
                            src="{{ $movie->poster_url }}"
                            alt="{{ $movie->title }}"
                            class="poster-img"
                        >
                        <div class="poster-overlay">
                            @auth
                                <a href="{{ route('movies.play', $movie->slug) }}" class="play-btn">
                                    <i class="fas fa-play"></i>
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="movie-title">{{ $movie->title }}</h1>
                    @if($movie->original_title && $movie->original_title !== $movie->title)
                        <p class="movie-subtitle">{{ $movie->original_title }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="container pt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="poster-container">
                    <img
                        src="{{ $movie->poster_url }}"
                        alt="{{ $movie->title }}"
                        class="poster-img"
                    >
                    <div class="poster-overlay">
                        @auth
                            <a href="{{ route('movies.play', $movie->slug) }}" class="play-btn">
                                <i class="fas fa-play"></i>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <h1 class="movie-title">{{ $movie->title }}</h1>
                @if($movie->original_title && $movie->original_title !== $movie->title)
                    <p class="movie-subtitle">{{ $movie->original_title }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Movie Details Content --}}
    <div class="container py-5">
        <div class="row g-4">
            @if(!$movie->backdrop_path)
            {{-- Show poster on mobile/small screens when no backdrop --}}
            <div class="col-12 d-md-none mb-4">
                <div class="poster-container mx-auto" style="max-width: 250px;">
                    <img
                        src="{{ $movie->poster_url }}"
                        alt="{{ $movie->title }}"
                        class="poster-img"
                    >
                    <div class="poster-overlay">
                        @auth
                            <a href="{{ route('movies.play', $movie->slug) }}" class="play-btn">
                                <i class="fas fa-play"></i>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
            @endif

            {{-- Movie Info --}}
            <div class="col-12">
                @if(!$movie->backdrop_url)
                <div class="row">
                    <div class="col-md-3 d-none d-md-block">
                        <div class="poster-container sticky-top" style="top: 2rem;">
                            <img
                                src="{{ $movie->poster_url }}"
                                alt="{{ $movie->title }}"
                                class="poster-img"
                            >
                            <div class="poster-overlay">
                                @auth
                                    <a href="{{ route('movies.play', $movie->slug) }}" class="play-btn">
                                        <i class="fas fa-play"></i>
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h1 class="movie-title">{{ $movie->title }}</h1>
                        @if($movie->original_title && $movie->original_title !== $movie->title)
                            <p class="movie-subtitle">{{ $movie->original_title }}</p>
                        @endif
                @endif

                {{-- Movie Meta Info --}}
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <span class="meta-badge rating">
                        <i class="fas fa-star me-1"></i>{{ number_format($movie->rating, 1) ?: 'N/A' }}
                    </span>
                    <span class="meta-badge">
                        <i class="fas fa-calendar me-1"></i>{{ $movie->year }}
                    </span>
                    <span class="meta-badge">
                        <i class="fas fa-clock me-1"></i>{{ $movie->getFormattedDuration() }}
                    </span>
                    <span class="meta-badge">
                        <i class="fas fa-video me-1"></i>{{ $movie->quality }}
                    </span>
                </div>

                {{-- Genres --}}
                @if($movie->genres->count() > 0)
                <div class="mb-4">
                    <h6 class="text-light mb-3 d-flex align-items-center">
                        <i class="fas fa-tags me-2"></i>Genres
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($movie->genres as $genre)
                        <a href="{{ route('movies.genre', $genre->slug) }}" class="genre-tag">
                            {{ $genre->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Description --}}
                <div class="info-card mb-4">
                    <div class="info-card-header">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>Sinopsis
                        </h5>
                    </div>
                    <div class="p-4">
                        <p class="mb-0 lh-lg text-secondary">
                            {{ $movie->description ?: 'Deskripsi film tidak tersedia.' }}
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex flex-wrap gap-3 mb-5">
                    @auth
                        {{-- Watch Now Button --}}
                        <a href="{{ route('movies.play', $movie->slug) }}" class="action-btn primary">
                            <i class="fas fa-play me-2"></i>Watch Now
                        </a>

                        {{-- Watchlist Button --}}
                        @php
                            $inWatchlist = \App\Models\Watchlist::where('user_id', auth()->id())
                                ->where('movie_id', $movie->id)
                                ->exists();
                        @endphp

                        @if($inWatchlist)
                            <button class="action-btn outline" disabled>
                                <i class="fas fa-check me-2"></i>In Watchlist
                            </button>
                        @else
                            <button onclick="addToWatchlist('{{ $movie->slug }}')"
                                    id="watchlist-btn-{{ $movie->slug }}"
                                    class="action-btn outline">
                                <i class="fas fa-plus me-2"></i>Add to Watchlist
                            </button>
                        @endif

                        {{-- Share Button --}}
                        <button class="action-btn share" 
                                data-share-btn
                                data-share-title="{{ $movie->title }}"
                                data-share-url="{{ route('movies.show', $movie->slug) }}"
                                data-share-text="Check out {{ $movie->title }} on Noobz Cinema">
                            <i class="fas fa-share-alt me-2"></i>Share
                        </button>
                    @else
                        {{-- Guest Buttons --}}
                        <a href="{{ route('login') }}" class="action-btn primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login to Watch
                        </a>
                        <a href="{{ route('register') }}" class="action-btn outline">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </a>
                    @endauth
                </div>

                {{-- Movie Stats --}}
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="stat-value">{{ number_format($movie->view_count) }}</div>
                            <div class="stat-label">Views</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-value">{{ number_format($movie->rating, 1) }}/10</div>
                            <div class="stat-label">Rating</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-value">{{ $movie->year }}</div>
                            <div class="stat-label">Release Year</div>
                        </div>
                    </div>
                </div>

                @if(!$movie->backdrop_path)
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Related Movies --}}
        @if($relatedMovies->count() > 0)
        <div class="related-movies">
            <div class="related-header">
                <h3 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-film me-2"></i>Film Serupa
                </h3>
            </div>
            <div class="p-4">
                <div class="row g-4">
                    @foreach($relatedMovies as $related)
                    <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                        <div class="movie-card position-relative">
                            {{-- Watchlist Button on Card --}}
                            @auth
                                @php
                                    $relatedInWatchlist = \App\Models\Watchlist::where('user_id', auth()->id())
                                        ->where('movie_id', $related->id)
                                        ->exists();
                                @endphp
                                @if($relatedInWatchlist)
                                    <div class="watchlist-btn watchlist-added">
                                        <i class="fas fa-check"></i>
                                    </div>
                                @else
                                    <button onclick="event.preventDefault(); event.stopPropagation(); addToWatchlist('{{ $related->slug }}')"
                                            id="watchlist-btn-{{ $related->slug }}"
                                            class="watchlist-btn">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                @endif
                            @endauth

                            <a href="{{ route('movies.show', $related->slug) }}" class="text-decoration-none">
                                <img
                                    src="{{ $related->poster_url }}"
                                    alt="{{ $related->title }}"
                                    class="movie-card-img w-100"
                                    loading="lazy"
                                >
                                <div class="movie-card-body">
                                    <h6 class="movie-card-title mb-2 text-truncate">{{ $related->title }}</h6>
                                    <div class="d-flex justify-content-between align-items-center movie-card-meta">
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>{{ $related->year }}
                                        </small>
                                        <span class="rating-badge">
                                            <i class="fas fa-star me-1"></i>{{ number_format($related->rating, 1) }}
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>


@push('scripts')
@vite([
    'resources/js/pages/movie-detail.js',
    'resources/js/components/watchlist.js'
])
@endpush
@endsection