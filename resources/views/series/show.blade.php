@extends('layouts.app')

@section('title', $series->title . ' - Noobz Cinema')

@push('styles')
@vite([
    'resources/css/pages/series-detail.css',
    'resources/css/components/animations.css',
    'resources/css/components/mobile.css'
])
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Series Hero Section --}}
            <div class="series-hero" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)), url('{{ $series->backdrop_path ? (str_starts_with($series->backdrop_path, 'http') ? $series->backdrop_path : 'https://image.tmdb.org/t/p/w1280' . $series->backdrop_path) : ($series->backdrop_url ?: 'https://placehold.co/1920x1080?text=No+Backdrop') }}')">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-4">
                            <img src="{{ $series->poster_path ? (str_starts_with($series->poster_path, 'http') ? $series->poster_path : 'https://image.tmdb.org/t/p/w500' . $series->poster_path) : ($series->poster_url ?: 'https://placehold.co/300x450?text=No+Poster') }}"
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
                        <div class="seasons-container">
                            @foreach($series->seasons as $season)
                                <div class="season-card mb-4">
                                    <div class="season-header">
                                        <h3 class="season-title">Season {{ $season->season_number }}</h3>
                                        <span class="episode-count">{{ $season->episodes->count() }} episodes</span>
                                    </div>

                                    @if($season->name)
                                        <p class="season-name">{{ $season->name }}</p>
                                    @endif

                                    @if($season->overview)
                                        <p class="season-overview">{{ $season->overview }}</p>
                                    @endif

                                    {{-- Episodes --}}
                                    @if($season->episodes->count() > 0)
                                        <div class="episodes-grid">
                                            @foreach($season->episodes as $episode)
                                                <div class="episode-card"
                                                     data-episode-id="{{ $episode->id }}"
                                                     data-episode-poster="{{ $episode->still_path }}">
                                                    <div class="episode-header">
                                                        <div class="episode-number">{{ $episode->episode_number }}</div>
                                                        <div class="episode-info">
                                                            <h4 class="episode-title">{{ $episode->name }}</h4>
                                                            @if($episode->overview)
                                                                <p class="episode-overview">{{ Str::limit($episode->overview, 120) }}</p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if($episode->runtime || $episode->air_date)
                                                        <div class="episode-meta">
                                                            @if($episode->runtime)
                                                                <span class="runtime">
                                                                    <i class="fas fa-clock"></i>
                                                                    {{ $episode->runtime }}m
                                                                </span>
                                                            @endif
                                                            @if($episode->air_date)
                                                                <span class="air-date">
                                                                    <i class="fas fa-calendar"></i>
                                                                    {{ $episode->air_date ? \Carbon\Carbon::parse($episode->air_date)->format('M d, Y') : 'TBA' }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center">
                                            <p class="text-muted">No episodes available for this season.</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center">
                            <p class="text-muted">No seasons available for this series.</p>
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