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
                        <span class="count-number">{{ $watchlist->total() }}</span> 
                        {{ $watchlist->total() === 1 ? 'item' : 'items' }} saved
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
    
    {{-- Movie & Series Grid --}}
    @if($watchlist->count() > 0)
        <div class="watchlist-grid">
            @foreach($watchlist as $item)
                @if($item->movie)
                    {{-- Movie Card --}}
                    <div class="watchlist-movie-card">
                        {{-- Poster with Watch Overlay --}}
                        <a href="{{ route('movies.show', $item->movie->id) }}" class="watchlist-poster-container">
                            <img src="{{ $item->movie->poster_url }}" 
                                 class="watchlist-poster-img" 
                                 alt="{{ $item->movie->title }}"
                                 onerror="this.src='https://via.placeholder.com/400x600/2d3748/ffffff?text=No+Image'">
                            
                            <div class="watch-overlay">
                                <div class="watch-icon">▶</div>
                                <div class="watch-text">Watch Now</div>
                            </div>
                        </a>
                        
                        {{-- Remove Button --}}
                        <form action="{{ route('watchlist.remove', $item->movie->id) }}" 
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
                            <h6 class="watchlist-movie-title">{{ $item->movie->title }}</h6>
                            @if($item->movie->overview)
                                <p class="watchlist-movie-overview">
                                    {{ Str::limit($item->movie->overview, 120) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @elseif($item->series)
                    {{-- Series Card --}}
                    <div class="watchlist-movie-card">
                        {{-- Poster with Watch Overlay --}}
                        <a href="{{ route('series.show', $item->series->id) }}" class="watchlist-poster-container">
                            <img src="{{ $item->series->poster_url }}" 
                                 class="watchlist-poster-img" 
                                 alt="{{ $item->series->title }}"
                                 onerror="this.src='https://via.placeholder.com/400x600/2d3748/ffffff?text=No+Image'">
                            
                            <div class="watch-overlay">
                                <div class="watch-icon">▶</div>
                                <div class="watch-text">Watch Now</div>
                            </div>
                            
                            {{-- Series Badge --}}
                            <div class="series-badge">SERIES</div>
                        </a>
                        
                        {{-- Remove Button --}}
                        <form action="{{ route('watchlist.series.remove', $item->series->id) }}" 
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
                            <h6 class="watchlist-movie-title">{{ $item->series->title }}</h6>
                            @if($item->series->overview)
                                <p class="watchlist-movie-overview">
                                    {{ Str::limit($item->series->overview, 120) }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        
        {{-- Pagination --}}
        <div class="pagination-modern">
            {{ $watchlist->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="watchlist-empty">
            <div class="empty-icon">📽️</div>
            <h3 class="empty-title">Your Watchlist is Empty</h3>
            <p class="empty-description">
                Start adding movies and series you want to watch later!<br>
                Discover amazing content and build your collection.
            </p>
            <a href="{{ route('home') }}" class="explore-btn">
                <span>🎬</span>
                <span>Explore Content</span>
            </a>
        </div>
    @endif
</div>
@endsection
