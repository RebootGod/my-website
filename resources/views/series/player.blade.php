{{-- Series Episode Player Page --}}
@extends('layouts.app')

@section('title', 'Watching: ' . $series->title . ' - Episode ' . $episode->episode_number . ' - Noobz Cinema')

@push('styles')
@vite([
    'resources/css/pages/series-player.css',
    'resources/css/components/player-controls-v2.css',
    'resources/css/components/player-mobile.css'
])
@endpush

@push('scripts')
@vite([
    'resources/js/components/player-gestures.js'
])
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

                {{-- Episode Info Below Player - Modern Design --}}
                <div class="episode-info-card mt-4">
                    <div class="episode-info-header">
                        <div class="icon">📺</div>
                        <h3>Episode Info</h3>
                    </div>
                    
                    <h4 class="episode-main-title">{{ $episode->name }}</h4>
                    
                    <div class="episode-meta-grid">
                        <div class="meta-pill">
                            <div class="meta-pill-label">Series</div>
                            <div class="meta-pill-value">{{ $series->title }}</div>
                        </div>
                        <div class="meta-pill">
                            <div class="meta-pill-label">Season</div>
                            <div class="meta-pill-value">{{ $currentSeason->season_number }}</div>
                        </div>
                        <div class="meta-pill">
                            <div class="meta-pill-label">Episode</div>
                            <div class="meta-pill-value">{{ $episode->episode_number }}</div>
                        </div>
                        @if($episode->runtime)
                        <div class="meta-pill">
                            <div class="meta-pill-label">Duration</div>
                            <div class="meta-pill-value">{{ $episode->getFormattedRuntime() }}</div>
                        </div>
                        @endif
                    </div>
                    
                    @if($episode->overview)
                    <div class="episode-overview-section">
                        <p class="episode-overview-text">{{ $episode->overview }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions Sidebar - Modern Design --}}
            <div class="col-lg-4">
                <div class="quick-actions-card">
                    <div class="quick-actions-header">
                        <div class="icon">⚡</div>
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="actions-stack">
                        <a href="{{ route('series.show', $series) }}" class="action-btn-modern action-btn-outline">
                            <span>←</span>
                            <span>Series Details</span>
                        </a>
                        @if($episode->download_url)
                        <a href="{{ $episode->download_url }}" target="_blank" class="action-btn-modern action-btn-success" download>
                            <span>⬇️</span>
                            <span>Download Episode</span>
                        </a>
                        @endif
                        <button onclick="reloadPlayer()" class="action-btn-modern action-btn-primary">
                            <span>🔄</span>
                            <span>Reload Player</span>
                        </button>
                        <button onclick="shareEpisode()" class="action-btn-modern action-btn-outline">
                            <span>📤</span>
                            <span>Share Episode</span>
                        </button>
                        <button onclick="reportIssue()" class="action-btn-modern action-btn-danger">
                            <span>🚨</span>
                            <span>Report Issue</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Episodes List - Modern Design --}}
        <div class="row g-4">
            <div class="col-12">
                <div class="season-episodes-card">
                    <div class="season-episodes-header">
                        <div class="icon">📑</div>
                        <h3>Season {{ $currentSeason->season_number }} Episodes</h3>
                    </div>
                    <div class="episodes-grid-modern">
                        @foreach($seasonEpisodes as $ep)
                            <a href="{{ route('series.episode.watch', [$series, $ep]) }}" 
                               class="episode-card-modern {{ $ep->id === $episode->id ? 'active' : '' }}">
                                <div class="episode-thumbnail-container">
                                    <img src="{{ $ep->still_url }}"
                                         alt="Episode {{ $ep->episode_number }}"
                                         class="episode-thumbnail-img"
                                         loading="lazy"
                                         onerror="this.src='https://via.placeholder.com/300x169?text=Episode+{{ $ep->episode_number }}'">
                                    <div class="episode-number-badge">{{ $ep->episode_number }}</div>
                                    @if($ep->embed_url && $ep->id !== $episode->id)
                                    <div class="episode-play-overlay">
                                        <div class="play-icon">▶</div>
                                    </div>
                                    @endif
                                </div>
                                <div class="episode-content-modern">
                                    <h5 class="episode-name-modern">{{ $ep->name }}</h5>
                                    @if($ep->overview)
                                        <p class="episode-desc-modern">{{ Str::limit($ep->overview, 100) }}</p>
                                    @endif
                                    <div class="episode-meta-row">
                                        @if($ep->runtime)
                                            <span class="episode-runtime">{{ $ep->getFormattedRuntime() }}</span>
                                        @endif
                                        @if($ep->id === $episode->id)
                                            <span class="episode-status-badge status-playing">Now Playing</span>
                                        @elseif($ep->embed_url)
                                            <span class="episode-status-badge status-available">Available</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
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