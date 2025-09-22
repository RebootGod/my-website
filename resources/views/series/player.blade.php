{{-- Series Episode Player Page --}}
@extends('layouts.app')

@section('title', 'Watching: ' . $series->title . ' - Episode ' . $episode->episode_number . ' - Noobz Cinema')

@push('styles')
@vite('resources/css/pages/player.css')
@endpush

@section('content')
<div class="player-wrapper">
    {{-- Main Content Layout --}}
    <div class="container-fluid px-4 fade-in">
        {{-- Top Row: Player + Quick Actions --}}
        <div class="row g-4 mb-4">
            {{-- Video Player Column --}}
            <div class="col-lg-8">
                <div class="video-container">
                    @if($episode->embed_url)
                        <iframe
                            id="episodePlayer"
                            src="{{ $episode->embed_url }}"
                            allowfullscreen
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="no-referrer"
                            sandbox="allow-scripts allow-same-origin allow-presentation">
                        </iframe>
                    @else
                        <div class="video-placeholder">
                            <div class="placeholder-icon">🎭</div>
                            <h3>No Video Available</h3>
                            <p>This episode doesn't have a playable source yet. Check back later.</p>
                            <div class="d-flex gap-3 justify-content-center flex-wrap">
                                <button onclick="reportIssue()" class="btn">📢 Report Issue</button>
                                <a href="{{ route('series.show', $series) }}" class="btn btn-secondary">← Back to Series</a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Episode Info Below Player --}}
                <div class="info-card mt-4">
                    <h3 class="card-title">
                        <span>📺</span>
                        Episode Info
                    </h3>
                    <div class="episode-details">
                        <h4 class="episode-title">{{ $episode->name }}</h4>
                        <div class="episode-meta">
                            <div class="meta-item">
                                <strong>Series:</strong> {{ $series->title }}
                            </div>
                            <div class="meta-item">
                                <strong>Season:</strong> {{ $currentSeason->season_number }}
                            </div>
                            <div class="meta-item">
                                <strong>Episode:</strong> {{ $episode->episode_number }}
                            </div>
                            @if($episode->runtime)
                            <div class="meta-item">
                                <strong>Duration:</strong> {{ $episode->getFormattedRuntime() }}
                            </div>
                            @endif
                        </div>
                        @if($episode->overview)
                        <p class="episode-overview">{{ $episode->overview }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Quick Actions Sidebar --}}
            <div class="col-lg-4">
                <div class="info-card">
                    <h3 class="card-title">
                        <span>⚡</span>
                        Quick Actions
                    </h3>
                    <div class="d-grid gap-2">
                        <a href="{{ route('series.show', $series) }}" class="btn btn-outline-light">
                            ← Series Details
                        </a>
                        <button onclick="reloadPlayer()" class="btn">
                            🔄 Reload Player
                        </button>
                        <button onclick="shareEpisode()" class="btn btn-secondary">
                            📤 Share Episode
                        </button>
                        <button onclick="reportIssue()" class="btn btn-danger">
                            🚨 Report Issue
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Episodes List --}}
        <div class="row g-4">
            <div class="col-12">
                <div class="info-card">
                    <h3 class="card-title">
                        <span>📑</span>
                        Season {{ $currentSeason->season_number }} Episodes
                    </h3>
                    <div class="episodes-list">
                        @foreach($seasonEpisodes as $ep)
                            <div class="episode-item {{ $ep->id === $episode->id ? 'active' : '' }}">
                                <div class="episode-number">{{ $ep->episode_number }}</div>
                                <div class="episode-info">
                                    <h5 class="episode-name">{{ $ep->name }}</h5>
                                    @if($ep->overview)
                                        <p class="episode-desc">{{ Str::limit($ep->overview, 120) }}</p>
                                    @endif
                                    <div class="episode-meta-small">
                                        @if($ep->runtime)
                                            <span class="runtime">{{ $ep->getFormattedRuntime() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="episode-actions">
                                    @if($ep->embed_url)
                                        @if($ep->id === $episode->id)
                                            <span class="btn btn-success btn-sm disabled">
                                                ▶️ Now Playing
                                            </span>
                                        @else
                                            <a href="{{ route('series.episode.watch', [$series, $ep]) }}" class="btn btn-primary btn-sm">
                                                ▶️ Watch
                                            </a>
                                        @endif
                                    @else
                                        <span class="btn btn-secondary btn-sm disabled">
                                            Not Available
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Related Series --}}
        @if($relatedSeries->count() > 0)
        <div class="row g-4 mt-4">
            <div class="col-12">
                <div class="info-card">
                    <h3 class="card-title">
                        <span>🎭</span>
                        Related Series
                    </h3>
                    <div class="related-grid">
                        @foreach($relatedSeries as $relatedItem)
                            <a href="{{ route('series.show', $relatedItem) }}" class="related-item">
                                <img src="{{ $relatedItem->poster_path ? 'https://image.tmdb.org/t/p/w200' . $relatedItem->poster_path : ($relatedItem->poster_url ?: 'https://via.placeholder.com/150x225?text=No+Poster') }}"
                                     alt="{{ $relatedItem->title }}" class="related-poster">
                                <div class="related-info">
                                    <h6 class="related-title">{{ $relatedItem->title }}</h6>
                                    <div class="related-meta">
                                        <span class="year">{{ $relatedItem->year }}</span>
                                        <span class="rating">⭐ {{ number_format($relatedItem->rating, 1) }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.episode-details {
    color: white;
}

.episode-title {
    color: #3498db;
    font-size: 1.2rem;
    margin-bottom: 1rem;
}

.episode-meta {
    margin-bottom: 1rem;
}

.meta-item {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.episode-overview {
    color: #bdc3c7;
    font-size: 0.9rem;
    line-height: 1.5;
}


.episodes-list {
    max-height: 500px;
    overflow-y: auto;
}

.episode-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    margin-bottom: 0.5rem;
    background: rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.episode-item:hover {
    background: rgba(0,0,0,0.5);
    border-color: rgba(52, 152, 219, 0.5);
}

.episode-item.active {
    background: rgba(52, 152, 219, 0.2);
    border-color: #3498db;
}

.episode-item .episode-number {
    background: linear-gradient(135deg, #3498db, #2ecc71);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 1rem;
    flex-shrink: 0;
}

.episode-item.active .episode-number {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.episode-item .episode-info {
    flex: 1;
    min-width: 0;
}

.episode-name {
    color: white;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.episode-desc {
    color: #bdc3c7;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.episode-meta-small {
    font-size: 0.8rem;
    color: #95a5a6;
}

.episode-actions {
    flex-shrink: 0;
    margin-left: 1rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}

.related-item {
    text-decoration: none;
    color: inherit;
    transition: transform 0.3s ease;
}

.related-item:hover {
    transform: translateY(-5px);
    color: inherit;
    text-decoration: none;
}

.related-poster {
    width: 100%;
    height: 225px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.related-info {
    text-align: center;
}

.related-title {
    color: white;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.related-meta {
    display: flex;
    justify-content: center;
    gap: 10px;
    font-size: 0.8rem;
    color: #bdc3c7;
}

@media (max-width: 768px) {
    .episode-item {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }

    .episode-item .episode-number {
        margin-right: 0;
    }

    .episode-actions {
        margin-left: 0;
    }

    .related-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }

    /* Stack episode info below player on mobile */
    .col-lg-8 .info-card {
        margin-top: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
function reloadPlayer() {
    const iframe = document.getElementById('episodePlayer');
    if (iframe) {
        iframe.src = iframe.src;
    }
}

function shareEpisode() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $series->title }} - Episode {{ $episode->episode_number }}',
            text: 'Watch {{ $series->title }} Episode {{ $episode->episode_number }}: {{ $episode->name }}',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Episode link copied to clipboard!');
    }
}

function reportIssue() {
    // You can implement your reporting system here
    alert('Thank you for reporting! We will investigate this issue.');
}

// You can add additional player functionality here
</script>
@endpush