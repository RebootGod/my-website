{{-- Series Episode Player Page --}}
@extends('layouts.app')

@section('title', 'Watching: ' . $series->title . ' - Episode ' . $episode->episode_number . ' - Noobz Cinema')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/series-player.css') }}?v={{ filemtime(public_path('css/series-player.css')) }}">
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
                            referrerpolicy="no-referrer">
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
                        @if($episode->download_url)
                        <a href="{{ $episode->download_url }}" target="_blank" class="btn btn-success" download>
                            ⬇️ Download Episode
                        </a>
                        @endif
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
                                <div class="episode-poster">
                                    <img src="{{ $ep->still_url }}"
                                         alt="Episode {{ $ep->episode_number }}"
                                         class="episode-thumbnail"
                                         loading="lazy"
                                         onerror="this.src='https://via.placeholder.com/300x169?text=Episode+{{ $ep->episode_number }}'">
                                </div>
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


    </div>
</div>

{{-- Report Modal --}}
<div id="reportModal" style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px); z-index: 50; display: none; align-items: center; justify-content: center; padding: 1rem;">
    <div class="info-card" style="max-width: 500px; width: 100%; transform: scale(0.95); opacity: 0; transition: all 0.3s ease;" id="reportModalContent">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="card-title mb-0">🚨 Report Issue</h3>
            <button onclick="closeReportModal()" style="background: none; border: none; color: #9ca3af; font-size: 1.5rem; cursor: pointer; padding: 0.5rem;">×</button>
        </div>

        <form id="reportForm" onsubmit="submitReport(event)">
            <input type="hidden" name="series_id" value="{{ $series->id }}">
            <input type="hidden" name="episode_id" value="{{ $episode->id }}">

            <div class="mb-3">
                <label style="display: block; color: #d1d5db; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem;">Issue Type</label>
                <select id="issueType" name="issue_type" style="width: 100%; background: rgba(55, 65, 81, 0.8); border: 1px solid rgba(75, 85, 99, 0.5); border-radius: 12px; padding: 0.75rem; color: white;">
                    <option value="not_loading">🚫 Video won't load</option>
                    <option value="poor_quality">📹 Poor video quality</option>
                    <option value="no_audio">🔊 Audio problems</option>
                    <option value="no_subtitle">📝 Subtitle issues</option>
                    <option value="buffering">⏳ Buffering issues</option>
                    <option value="wrong_episode">🎬 Wrong episode/content</option>
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

@endsection

@push('scripts')
<script src="{{ asset('js/series-player.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeSeriesPlayer({
            csrfToken: '{{ csrf_token() }}',
            shareTitle: '{{ $series->title }} - Episode {{ $episode->episode_number }}',
            shareText: 'Watch {{ $series->title }} Episode {{ $episode->episode_number }}: {{ addslashes($episode->name) }}',
            reportUrl: '/series/{{ $series->slug }}/episodes/{{ $episode->id }}/report'
        });
    });
</script>
@endpush