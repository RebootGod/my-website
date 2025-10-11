@extends('layouts.app')

@section('title', $series->title . ' - Noobz Cinema')

@push('styles')
@vite([
    'resources/css/pages/series-detail-v2.css',
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
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Series Hero Section --}}
            <div class="series-hero" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)), url('{{ $series->backdrop_url }}')">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4">
                            <img src="{{ $series->poster_url }}"
                                 alt="{{ $series->title }}"
                                 class="series-poster">
                        </div>
                        <div class="col-lg-8">
                            <div class="series-info">
                                <div class="series-badges mb-3">
                                    <span class="badge series-type-badge">SERIES</span>
                                    <span class="badge status-badge status-{{ strtolower($series->status) }}">{{ ucfirst($series->status) }}</span>
                                </div>

                                <h1 class="series-title">{{ $series->title }}</h1>

                                <div class="series-meta mb-4">
                                    <div class="meta-item">
                                        <i class="fas fa-star text-warning"></i>
                                        <span>{{ $series->getFormattedRating() }}</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>{{ $series->year ?? 'N/A' }}</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-tv"></i>
                                        <span>{{ $series->seasons->count() }} Season{{ $series->seasons->count() != 1 ? 's' : '' }}</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-eye"></i>
                                        <span>{{ number_format($series->view_count) }} views</span>
                                    </div>
                                </div>

                                {{-- Genres --}}
                                @if($series->genres->count() > 0)
                                <div class="series-genres mb-4">
                                    @foreach($series->genres as $genre)
                                        <span class="genre-badge">{{ $genre->name }}</span>
                                    @endforeach
                                </div>
                                @endif

                                {{-- Description --}}
                                <p class="series-description">{{ $series->description ?: 'No description available.' }}</p>

                                {{-- Share Button --}}
                                <div class="d-flex flex-wrap gap-3 mt-4">
                                    <button class="action-btn share" 
                                            data-share-btn
                                            data-share-title="{{ $series->title }}"
                                            data-share-url="{{ route('series.show', $series->slug) }}"
                                            data-share-text="Check out {{ $series->title }} on Noobz Cinema">
                                        <i class="fas fa-share-alt me-2"></i>Share
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Seasons and Episodes --}}
            <div class="container mt-5">
                <div class="seasons-section">
                    <h2 class="section-title mb-4">
                        <i class="fas fa-list me-2"></i>Seasons & Episodes
                    </h2>

                    @if($series->seasons->count() > 0)
                        {{-- Season Navigation (only show if more than 1 season) --}}
                        @if($series->seasons->count() > 1)
                            <div class="seasons-nav" id="seasons-nav">
                                <div class="container">
                                    <div class="seasons-nav-list">
                                        @foreach($series->seasons as $index => $season)
                                            <a href="#season-{{ $season->season_number }}"
                                               class="season-nav-item {{ $index === 0 ? 'active' : '' }}"
                                               data-season="{{ $season->season_number }}">
                                                Season {{ $season->season_number }}
                                                <span class="badge ms-2">{{ $season->episodes->count() }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="seasons-container">
                            @foreach($series->seasons as $season)
                                <div class="season-card mb-4" id="season-{{ $season->season_number }}">
                                    <div class="season-header">
                                        <div class="season-title-wrapper">
                                            <h3 class="season-title">Season {{ $season->season_number }}</h3>
                                            @if($season->name && $season->name !== "Season {$season->season_number}")
                                                <p class="season-name">{{ $season->name }}</p>
                                            @endif
                                        </div>
                                        <div class="season-meta">
                                            <span class="episode-count">{{ $season->episodes->count() }} episodes</span>
                                            @if($season->air_date)
                                                <span class="season-air-date">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    {{ \Carbon\Carbon::parse($season->air_date)->format('Y') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($season->overview)
                                        <div class="season-overview-wrapper">
                                            <p class="season-overview">{{ $season->overview }}</p>
                                        </div>
                                    @endif

                                    {{-- Episodes --}}
                                    @if($season->episodes->count() > 0)
                                        <div class="episodes-grid">
                                            @foreach($season->episodes as $episode)
                                                <div class="episode-card"
                                                     data-episode-id="{{ $episode->id }}"
                                                     data-season="{{ $season->season_number }}"
                                                     data-episode="{{ $episode->episode_number }}">

                                                    {{-- Episode Thumbnail --}}
                                                    @if($episode->still_path)
                                                        <div class="episode-thumbnail-wrapper">
                                                            <img src="{{ $episode->still_url }}"
                                                                 alt="Episode {{ $episode->episode_number }}"
                                                                 class="episode-thumbnail"
                                                                 loading="lazy">
                                                            <div class="episode-play-overlay">
                                                                <i class="fas fa-play"></i>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="episode-thumbnail-placeholder">
                                                            <i class="fas fa-film"></i>
                                                        </div>
                                                    @endif

                                                    <div class="episode-content">
                                                        <div class="episode-header">
                                                            <div class="episode-number">{{ $episode->episode_number }}</div>
                                                            <div class="episode-info">
                                                                <h4 class="episode-title">
                                                                    Episode {{ $episode->episode_number }}
                                                                    @if($episode->name && $episode->name !== "Episode {$episode->episode_number}")
                                                                        : {{ $episode->name }}
                                                                    @endif
                                                                </h4>
                                                                @if($episode->overview)
                                                                    <p class="episode-overview">{{ Str::limit($episode->overview, 150) }}</p>
                                                                @else
                                                                    <p class="episode-overview text-muted">There is no Description on TMDB</p>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="episode-meta">
                                                            <div class="episode-meta-left">
                                                                @if($episode->runtime)
                                                                    <span class="runtime">
                                                                        <i class="fas fa-clock"></i>
                                                                        {{ $episode->getFormattedRuntime() }}
                                                                    </span>
                                                                @endif
                                                                @if($episode->air_date)
                                                                    <span class="air-date">
                                                                        <i class="fas fa-calendar"></i>
                                                                        {{ \Carbon\Carbon::parse($episode->air_date)->format('M d, Y') }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="episode-meta-right">
                                                                @if($episode->vote_average && $episode->vote_average > 0)
                                                                    <span class="episode-rating">
                                                                        <i class="fas fa-star"></i>
                                                                        {{ number_format($episode->vote_average, 1) }}
                                                                    </span>
                                                                @endif
                                                                @if($episode->embed_url)
                                                                    <span class="watch-available">
                                                                        <i class="fas fa-play-circle text-success"></i>
                                                                        Available
                                                                    </span>
                                                                @else
                                                                    <span class="watch-unavailable">
                                                                        <i class="fas fa-clock text-warning"></i>
                                                                        Coming Soon
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- Play Button --}}
                                                        @if($episode->embed_url)
                                                            <div class="episode-actions">
                                                                <a href="{{ route('series.episode.watch', ['series' => $series, 'episode' => $episode->id]) }}"
                                                                   class="btn btn-play btn-primary">
                                                                    <i class="fas fa-play me-2"></i>
                                                                    Watch Episode
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-5">
                                            <i class="fas fa-film text-muted mb-3" style="font-size: 3rem;"></i>
                                            <p class="text-muted">No episodes available for this season.</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tv text-muted mb-3" style="font-size: 4rem;"></i>
                            <p class="text-muted mb-0">No seasons available for this series.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite([
    'resources/js/pages/series-detail.js',
    'resources/js/components/watchlist.js'
])
@endpush