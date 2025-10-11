@extends('layouts.app')

@section('title', $movie->title . ' - Noobz Cinema')

@push('styles')
@vite([
    'resources/css/pages/movie-detail-v3.css',
    'resources/css/components/share-modal.css'
])
@endpush

@section('content')
<div class="movie-detail-page">
    {{-- Hero Section with Cinematic Backdrop --}}
    <section class="hero-section">
        {{-- Backdrop Image --}}
        @if($movie->backdrop_url)
            <img src="{{ $movie->backdrop_url }}" 
                 alt="{{ $movie->title }} backdrop" 
                 class="hero-backdrop-image">
        @else
            <img src="{{ $movie->poster_url }}" 
                 alt="{{ $movie->title }}" 
                 class="hero-backdrop-image"
                 style="filter: blur(40px) brightness(0.4);">
        @endif

        {{-- Gradient Overlays --}}
        <div class="hero-gradient-overlay"></div>
        <div class="hero-side-gradients"></div>

        {{-- Hero Content --}}
        <div class="container hero-content-wrapper">
            <div class="row g-4 align-items-end">
                {{-- Poster Column --}}
                <div class="col-lg-3 col-md-4 poster-column">
                    <div class="poster-floating-card">
                        <div class="poster-image-wrapper">
                            <img src="{{ $movie->poster_url }}" 
                                 alt="{{ $movie->title }}" 
                                 class="poster-main-image">
                            
                            {{-- Quality Badge --}}
                            <div class="poster-quality-badge">
                                <i class="fas fa-hd me-1"></i>{{ $movie->quality }}
                            </div>

                            {{-- Play Overlay --}}
                            @auth
                            <div class="poster-play-overlay">
                                <a href="{{ route('movies.play', $movie->slug) }}" class="poster-play-button">
                                    <i class="fas fa-play"></i>
                                </a>
                            </div>
                            @endauth
                        </div>
                    </div>
                </div>

                {{-- Movie Info Column --}}
                <div class="col-lg-9 col-md-8 movie-info-column">
                    {{-- Title --}}
                    <h1 class="movie-main-title">{{ $movie->title }}</h1>
                    
                    @if($movie->original_title && $movie->original_title !== $movie->title)
                        <p class="movie-original-title">{{ $movie->original_title }}</p>
                    @endif

                    {{-- Meta Badges --}}
                    <div class="meta-badges-row">
                        <div class="meta-pill rating-pill">
                            <i class="fas fa-star"></i>
                            <span>{{ number_format($movie->rating, 1) }}/10</span>
                        </div>
                        <div class="meta-pill">
                            <i class="fas fa-calendar"></i>
                            <span>{{ $movie->year }}</span>
                        </div>
                        <div class="meta-pill">
                            <i class="fas fa-clock"></i>
                            <span>{{ $movie->getFormattedDuration() }}</span>
                        </div>
                        <div class="meta-pill">
                            <i class="fas fa-eye"></i>
                            <span>{{ number_format($movie->view_count) }} views</span>
                        </div>
                    </div>

                    {{-- Genres --}}
                    @if($movie->genres->count() > 0)
                    <div class="genres-section">
                        <div class="section-label">
                            <i class="fas fa-tags"></i>
                            <span>Genres</span>
                        </div>
                        <div class="genres-pills-wrapper">
                            @foreach($movie->genres as $genre)
                                <a href="{{ route('movies.genre', $genre->slug) }}" class="genre-pill">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="action-buttons-row">
                        @auth
                            {{-- Watch Now Button --}}
                            <a href="{{ route('movies.play', $movie->slug) }}" class="action-btn-modern primary">
                                <i class="fas fa-play"></i>
                                <span>Watch Now</span>
                            </a>

                            {{-- Watchlist Button --}}
                            @php
                                $inWatchlist = \App\Models\Watchlist::where('user_id', auth()->id())
                                    ->where('movie_id', $movie->id)
                                    ->exists();
                            @endphp

                            @if($inWatchlist)
                                <button class="action-btn-modern secondary" disabled>
                                    <i class="fas fa-check"></i>
                                    <span>In Watchlist</span>
                                </button>
                            @else
                                <button onclick="addToWatchlist('{{ $movie->slug }}')"
                                        id="watchlist-btn-{{ $movie->slug }}"
                                        class="action-btn-modern secondary">
                                    <i class="fas fa-plus"></i>
                                    <span>Add to Watchlist</span>
                                </button>
                            @endif

                            {{-- Share Button --}}
                            <button class="action-btn-modern share" 
                                    data-share-btn
                                    data-share-title="{{ $movie->title }}"
                                    data-share-url="{{ route('movies.show', $movie->slug) }}"
                                    data-share-text="Check out {{ $movie->title }} on Noobz Cinema">
                                <i class="fas fa-share-alt"></i>
                                <span>Share</span>
                            </button>
                        @else
                            {{-- Guest Buttons --}}
                            <a href="{{ route('login') }}" class="action-btn-modern primary">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login to Watch</span>
                            </a>
                            <a href="{{ route('register') }}" class="action-btn-modern secondary">
                                <i class="fas fa-user-plus"></i>
                                <span>Register</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Content Section --}}
    <div class="container content-section-wrapper">
        <div class="row g-4">
            <div class="col-12">
                {{-- Synopsis Card --}}
                <div class="glass-info-card">
                    <div class="glass-card-header">
                        <h2 class="glass-card-title">
                            <i class="fas fa-info-circle"></i>
                            <span>Synopsis</span>
                        </h2>
                    </div>
                    <div class="glass-card-body">
                        <p class="synopsis-text">
                            {{ $movie->description ?: 'No description available for this movie.' }}
                        </p>
                    </div>
                </div>

                {{-- Download Section (if available) --}}
                @if($movie->download_url)
                <div class="glass-info-card download-section-card">
                    <div class="glass-card-header">
                        <h2 class="glass-card-title">
                            <i class="fas fa-download"></i>
                            <span>Download</span>
                        </h2>
                    </div>
                    <div class="glass-card-body">
                        <a href="{{ $movie->download_url }}" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="download-link-button">
                            <i class="fas fa-cloud-download-alt"></i>
                            <span>Download {{ $movie->title }}</span>
                            <i class="fas fa-external-link-alt ms-auto"></i>
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Related Movies Section --}}
        @if(isset($relatedMovies) && $relatedMovies->count() > 0)
        <div class="row mt-5">
            <div class="col-12">
                <div class="glass-info-card">
                    <div class="glass-card-header">
                        <h2 class="glass-card-title">
                            <i class="fas fa-film"></i>
                            <span>Similar Movies</span>
                        </h2>
                    </div>
                    <div class="glass-card-body">
                        <div class="row g-4">
                            @foreach($relatedMovies as $related)
                            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                                <div class="related-movie-card">
                                    <a href="{{ route('movies.show', $related->slug) }}" class="related-movie-link">
                                        <div class="related-movie-poster">
                                            <img src="{{ $related->poster_url }}" 
                                                 alt="{{ $related->title }}" 
                                                 loading="lazy">
                                            <div class="related-movie-overlay">
                                                <i class="fas fa-play"></i>
                                            </div>
                                        </div>
                                        <div class="related-movie-info">
                                            <h6 class="related-movie-title">{{ Str::limit($related->title, 30) }}</h6>
                                            <div class="related-movie-meta">
                                                <span class="related-movie-year">{{ $related->year }}</span>
                                                <span class="related-movie-rating">
                                                    <i class="fas fa-star"></i>
                                                    {{ number_format($related->rating, 1) }}
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
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@vite([
    'resources/js/pages/detail-share.js',
    'resources/js/layouts/app.js'
])
@endpush
