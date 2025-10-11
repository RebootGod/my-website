@extends('layouts.app')

@section('title', 'Watchlist Saya')

@push('styles')
@vite(['resources/css/pages/watchlist.css'])
@endpush

@section('content')
<div class="container mt-4">
    {{-- Page Header --}}
    <div class="watchlist-header">
        <div class="watchlist-title-row">
            <div class="watchlist-title-section">
                <div class="watchlist-icon">💖</div>
                <div class="watchlist-title-text">
                    <h1>My Watchlist</h1>
                    <p class="watchlist-count">
                        <span class="count-number">{{ $movies->total() }}</span> 
                        {{ $movies->total() === 1 ? 'movie' : 'movies' }} saved
                    </p>
                </div>
            </div>
            <a href="{{ route('home') }}" class="explore-btn">
                <span>🎬</span>
                <span>Explore Movies</span>
            </a>
        </div>
    </div>
    
    {{-- Success Alert --}}
    @if(session('success'))
        <div class="alert-modern alert-success-modern">
            <div class="alert-icon">✓</div>
            <div class="alert-content">{{ session('success') }}</div>
            <button type="button" class="alert-close-btn" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif
    
    {{-- Error Alert --}}
    @if(session('error'))
        <div class="alert-modern alert-danger-modern">
            <div class="alert-icon">⚠</div>
            <div class="alert-content">{{ session('error') }}</div>
            <button type="button" class="alert-close-btn" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif
    
    {{-- Movie Grid --}}
    @if($movies->count() > 0)
        <div class="watchlist-grid">
            @foreach($movies as $movie)
                <div class="watchlist-movie-card">
                    {{-- Poster with Watch Overlay --}}
                    <a href="{{ route('movies.show', $movie->id) }}" class="watchlist-poster-container">
                        <img src="{{ $movie->poster_url }}" 
                             class="watchlist-poster-img" 
                             alt="{{ $movie->title }}"
                             onerror="this.src='https://via.placeholder.com/400x600/2d3748/ffffff?text=No+Image'">
                        
                        <div class="watch-overlay">
                            <div class="watch-icon">▶</div>
                            <div class="watch-text">Watch Now</div>
                        </div>
                    </a>
                    
                    {{-- Remove Button --}}
                    <form action="{{ route('watchlist.remove', $movie->id) }}" 
                          method="POST" 
                          class="remove-watchlist-btn-form"
                          onsubmit="return confirm('Remove from watchlist?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="remove-watchlist-btn" title="Remove from watchlist">
                            ×
                        </button>
                    </form>
                    
                    {{-- Card Info --}}
                    <div class="watchlist-card-info">
                        <h6 class="watchlist-movie-title">{{ $movie->title }}</h6>
                        @if($movie->overview)
                            <p class="watchlist-movie-overview">
                                {{ Str::limit($movie->overview, 120) }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
        {{-- Pagination --}}
        <div class="pagination-modern">
            {{ $movies->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="watchlist-empty">
            <div class="empty-icon">📽️</div>
            <h3 class="empty-title">Your Watchlist is Empty</h3>
            <p class="empty-description">
                Start adding movies you want to watch later!<br>
                Discover amazing content and build your collection.
            </p>
            <a href="{{ route('home') }}" class="explore-btn">
                <span>🎬</span>
                <span>Explore Movies</span>
            </a>
        </div>
    @endif
</div>
@endsection
