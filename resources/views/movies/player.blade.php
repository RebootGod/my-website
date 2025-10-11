{{-- Modern Movie Player Page - New Layout --}}
@extends('layouts.app')

@section('title', 'Watching: ' . $movie->title . ' - Cinema')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/movie-player.css') }}">
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
                    @if(isset($currentSource) && $currentSource && $currentSource->embed_url)
                        @php
                            $sourceEmbedUrl = null;
                            try {
                                $sourceEmbedUrl = decrypt($currentSource->embed_url);
                            } catch (Exception $e) {
                                $sourceEmbedUrl = $currentSource->embed_url;
                            }
                        @endphp
                        @if($sourceEmbedUrl)
                            <iframe
                                id="moviePlayer"
                                src="{{ $sourceEmbedUrl }}"
                                allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="no-referrer">
                            </iframe>
                        @else
                            <div class="video-placeholder">
                                <div class="placeholder-icon">🚫</div>
                                <h3>Invalid Source URL</h3>
                                <p>The source URL appears to be corrupted. Please try another source or report this issue.</p>
                                <button onclick="reportIssue()" class="btn btn-danger">📢 Report Issue</button>
                            </div>
                        @endif
                    @elseif(isset($movie->embed_url) && $movie->embed_url)
                        @php
                            $embedUrl = null;
                            try {
                                $embedUrl = decrypt($movie->embed_url);
                            } catch (Exception $e) {
                                $embedUrl = $movie->embed_url;
                            }
                        @endphp
                        @if($embedUrl)
                            <iframe
                                id="moviePlayer"
                                src="{{ $embedUrl }}"
                                allowfullscreen
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="no-referrer">
                            </iframe>
                        @else
                            <div class="video-placeholder">
                                <div class="placeholder-icon">🚫</div>
                                <h3>Invalid Video Source</h3>
                                <p>The video URL appears to be corrupted. Please try another source or report this issue.</p>
                                <button onclick="reportIssue()" class="btn btn-danger">📢 Report Issue</button>
                            </div>
                        @endif
                    @else
                        <div class="video-placeholder">
                            <div class="placeholder-icon">🎭</div>
                            <h3>No Video Available</h3>
                            <p>This movie doesn't have any playable sources yet. Check back later or request a source.</p>
                            <div class="d-flex gap-3 justify-content-center flex-wrap">
                                <button onclick="reportIssue()" class="btn">📢 Request Source</button>
                                <a href="{{ route('movies.show', $movie->slug) }}" class="btn btn-secondary">← Back to Movie</a>
                            </div>
                        </div>
                    @endif
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
                        <a href="{{ route('movies.show', $movie->slug) }}" class="btn btn-outline-light">
                            ← Movie Details
                        </a>
                        @if($movie->download_url)
                        <a href="{{ $movie->download_url }}" target="_blank" class="btn btn-success" download>
                            ⬇️ Download Movie
                        </a>
                        @endif
                        <button onclick="addToWatchlist()" class="btn">
                            ❤️ Add to Watchlist
                        </button>
                        <button onclick="reloadPlayer()" class="btn">
                            🔄 Reload Player
                        </button>
                        <button onclick="shareMovie()" class="btn">
                            📤 Share Movie
                        </button>
                        <button onclick="reportIssue()" class="btn btn-danger">
                            🚨 Report Issue
                        </button>
                    </div>
                </div>

                {{-- Source Selection --}}
                @if(isset($allSources) && $allSources->count() > 0)
                    <div class="info-card mt-3">
                        <h3 class="card-title">
                            <span>🔗</span>
                            Available Sources
                        </h3>
                        <div class="d-flex flex-column gap-2">
                            @foreach($allSources as $source)
                                <a href="{{ route('movies.player', ['movie' => $movie->slug, 'source' => $source->id]) }}"
                                   class="btn {{ $currentSource && $currentSource->id == $source->id ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm">
                                    {{ $source->name }}
                                    @if($source->quality)
                                        <span class="badge bg-success ms-1">{{ $source->quality }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Bottom Row: Movie Information --}}
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="info-card">
                    <h3 class="card-title">
                        <span>ℹ️</span>
                        Movie Information
                    </h3>

                    <div class="row g-4">
                        {{-- Movie Poster --}}
                        <div class="col-lg-2 col-md-3">
                            @if($movie->poster_url)
                                <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="w-100 rounded shadow">
                            @endif
                        </div>

                        {{-- Movie Details --}}
                        <div class="col-lg-10 col-md-9">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    {{-- Movie Title --}}
                                    <div class="mb-3">
                                        <h4 class="text-white fw-bold">{{ $movie->title }}</h4>
                                    </div>

                                    <div class="movie-info">
                                        <div class="info-row">
                                            <span class="info-label">Year</span>
                                            <span class="info-value">{{ $movie->year ?? '2024' }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Rating</span>
                                            <span class="info-value">⭐ {{ number_format($movie->rating ?? 8.2, 1) }}/10</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Views</span>
                                            <span class="info-value">{{ number_format($movie->view_count ?? 15420) }}</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Duration</span>
                                            <span class="info-value">{{ $movie->getFormattedDuration() ?? '2h 15m' }}</span>
                                        </div>
                                    </div>

                                    @if(isset($movie->genres) && $movie->genres->count() > 0)
                                        <div class="genres mt-3">
                                            @foreach($movie->genres as $genre)
                                                <a href="{{ route('movies.genre', $genre->slug) }}" class="genre-tag">
                                                    {{ $genre->name }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    @if($movie->description)
                                        <div>
                                            <h4 class="card-title">📖 Synopsis</h4>
                                            <p style="color: #d1d5db; font-size: 0.875rem; line-height: 1.6;">
                                                {{ $movie->description }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- You Might Also Like Section - Expanded --}}
        @if(isset($relatedMovies) && $relatedMovies->count() > 0)
            <div class="row g-4">
                <div class="col-12">
                    <div class="info-card">
                        <h3 class="card-title">
                            <span>🔥</span>
                            You Might Also Like
                        </h3>
                        <div class="row g-4">
                            @foreach($relatedMovies as $related)
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                    <a href="{{ route('movies.show', $related->slug) }}" class="card bg-dark border-secondary h-100 text-decoration-none hover-card">
                                        {{-- EXACT same structure as movie details page - single img element --}}
                                        <img src="{{ $related->poster_url }}"
                                             alt="{{ $related->title }}"
                                             class="card-img-top"
                                             style="height: 280px; object-fit: cover;"
                                             loading="lazy">
                                        <div class="card-body p-3">
                                            <h6 class="card-title text-white mb-2" style="font-size: 0.9rem; line-height: 1.3;">{{ $related->title }}</h6>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">{{ $related->year ?? '2024' }}</small>
                                                <small class="text-warning">⭐ {{ number_format($related->rating ?? 7.5, 1) }}</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Report Modal --}}
    <div id="reportModal" style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px); z-index: 50; display: none; align-items: center; justify-content: center; padding: 1rem;">
        <div class="info-card" style="max-width: 500px; width: 100%; transform: scale(0.95); opacity: 0; transition: all 0.3s ease;" id="reportModalContent">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="card-title mb-0">🚨 Report Issue</h3>
                <button onclick="closeReportModal()" style="background: none; border: none; color: #9ca3af; font-size: 1.5rem; cursor: pointer; padding: 0.5rem;">×</button>
            </div>

            <form id="reportForm" onsubmit="submitReport(event)">
                <input type="hidden" name="movie_id" value="{{ $movie->id }}">
                <input type="hidden" id="sourceId" name="source_id" value="{{ isset($currentSource) && $currentSource ? $currentSource->id : '' }}">

                <div class="mb-3">
                    <label style="display: block; color: #d1d5db; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Issue Type</label>
                    <select id="issueType" name="issue_type" style="width: 100%; background: rgba(55, 65, 81, 0.8); border: 1px solid rgba(75, 85, 99, 0.5); border-radius: 12px; padding: 0.75rem; color: white;">
                        <option value="not_loading">🚫 Video won't load</option>
                        <option value="poor_quality">📹 Poor video quality</option>
                        <option value="no_audio">🔊 Audio problems</option>
                        <option value="no_subtitle">📝 Subtitle issues</option>
                        <option value="buffering">⏳ Buffering issues</option>
                        <option value="wrong_movie">🎬 Wrong movie/content</option>
                        <option value="other">❓ Other issue</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label style="display: block; color: #d1d5db; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Description (Optional)</label>
                    <textarea id="issueDescription" name="description" rows="4"
                              style="width: 100%; background: rgba(55, 65, 81, 0.8); border: 1px solid rgba(75, 85, 99, 0.5); border-radius: 12px; padding: 0.75rem; color: white; resize: none;"
                              placeholder="Please describe the issue in detail..."></textarea>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn flex-fill">
                        📤 Submit Report
                    </button>
                    <button type="button" onclick="closeReportModal()" class="btn btn-secondary flex-fill">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/movie-player.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeMoviePlayer({
            movieId: {{ $movie->id }},
            movieSlug: '{{ $movie->slug }}',
            movieTitle: '{{ addslashes($movie->title) }}',
            csrfToken: '{{ csrf_token() }}',
            currentSourceId: {{ isset($currentSource) && $currentSource ? $currentSource->id : 'null' }}
        });
    });
</script>
@endpush