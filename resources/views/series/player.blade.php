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

{{-- Report Modal --}}
<div id="reportModal" class="report-modal" style="display: none;">
    <div class="modal-backdrop"></div>
    <div class="modal-content" id="reportModalContent">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                Report Issue
            </h3>
            <button type="button" class="btn-close" onclick="closeReportModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="reportForm" onsubmit="submitReport(event)">
            @csrf
            <input type="hidden" name="series_id" value="{{ $series->id }}">
            <input type="hidden" name="episode_id" value="{{ $episode->id }}">

            <div class="modal-body">
                <div class="mb-4">
                    <h5 class="text-light mb-2">What issue are you experiencing?</h5>
                    <div class="issue-type-grid">
                        <label class="issue-type-option">
                            <input type="radio" name="issue_type" value="not_loading" required>
                            <span class="issue-content">
                                <i class="fas fa-spinner"></i>
                                Video Not Loading
                            </span>
                        </label>

                        <label class="issue-type-option">
                            <input type="radio" name="issue_type" value="wrong_episode" required>
                            <span class="issue-content">
                                <i class="fas fa-exclamation"></i>
                                Wrong Episode
                            </span>
                        </label>

                        <label class="issue-type-option">
                            <input type="radio" name="issue_type" value="poor_quality" required>
                            <span class="issue-content">
                                <i class="fas fa-video"></i>
                                Poor Quality
                            </span>
                        </label>

                        <label class="issue-type-option">
                            <input type="radio" name="issue_type" value="no_audio" required>
                            <span class="issue-content">
                                <i class="fas fa-volume-mute"></i>
                                No Audio
                            </span>
                        </label>

                        <label class="issue-type-option">
                            <input type="radio" name="issue_type" value="no_subtitle" required>
                            <span class="issue-content">
                                <i class="fas fa-closed-captioning"></i>
                                No Subtitle
                            </span>
                        </label>

                        <label class="issue-type-option">
                            <input type="radio" name="issue_type" value="buffering" required>
                            <span class="issue-content">
                                <i class="fas fa-clock"></i>
                                Constant Buffering
                            </span>
                        </label>

                        <label class="issue-type-option">
                            <input type="radio" name="issue_type" value="other" required>
                            <span class="issue-content">
                                <i class="fas fa-question-circle"></i>
                                Other Issue
                            </span>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light">Additional Details (Optional)</label>
                    <textarea
                        name="description"
                        class="form-control bg-dark text-light border-secondary"
                        rows="3"
                        placeholder="Please describe the issue in detail..."
                        style="resize: vertical; min-height: 80px;"
                    ></textarea>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Your report will help us improve the viewing experience. Thank you for taking the time to let us know about this issue.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReportModal()">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i> Submit Report
                </button>
            </div>
        </form>
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

/* Report Modal Styles */
.report-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
}

.modal-content {
    position: relative;
    background: linear-gradient(145deg, #2c3e50, #34495e);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    max-width: 600px;
    width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    color: #fff;
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.btn-close {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.5rem;
    padding: 0;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.btn-close:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
    transform: rotate(90deg);
}

.modal-body {
    padding: 1.5rem;
}

.issue-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.issue-type-option {
    cursor: pointer;
    position: relative;
    display: block;
}

.issue-type-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.issue-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(52, 73, 94, 0.8);
    border: 2px solid transparent;
    border-radius: 12px;
    color: #ecf0f1;
    font-weight: 500;
    transition: all 0.3s ease;
    min-height: 60px;
}

.issue-type-option input[type="radio"]:checked + .issue-content {
    background: rgba(231, 76, 60, 0.2);
    border-color: #e74c3c;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(231, 76, 60, 0.3);
}

.issue-content i {
    font-size: 1.25rem;
    width: 20px;
    text-align: center;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

@media (max-width: 768px) {
    .issue-type-grid {
        grid-template-columns: 1fr;
    }

    .modal-content {
        width: 95%;
        margin: 1rem;
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

// Report Modal Functions
function reportIssue() {
    openReportModal();
}

function openReportModal() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportModalContent');

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
    }, 10);
}

function closeReportModal() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportModalContent');

    content.style.transform = 'scale(0.95)';
    content.style.opacity = '0';

    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('reportForm').reset();
    }, 300);
}

function submitReport(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);

    // Convert FormData to JSON
    const data = {
        series_id: formData.get('series_id'),
        episode_id: formData.get('episode_id'),
        issue_type: formData.get('issue_type'),
        description: formData.get('description')
    };

    fetch(`/series/{{ $series->slug }}/episodes/{{ $episode->id }}/report`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        showNotification(data.message || 'Report submitted successfully!', 'success');
        closeReportModal();
    })
    .catch((error) => {
        console.error('Error submitting report:', error);
        showNotification('Thank you for your report! We will investigate the issue.', 'success');
        closeReportModal();
    });
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    }[type] || '#3b82f6';

    const icon = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    }[type] || 'ℹ️';

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        color: white;
        font-weight: 500;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: ${bgColor};
        backdrop-filter: blur(10px);
    `;

    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 1.25rem;">${icon}</span>
            <span style="flex: 1;">${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: rgba(255,255,255,0.7); font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>
    `;

    document.body.appendChild(notification);

    setTimeout(() => notification.style.transform = 'translateX(0)', 100);
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// You can add additional player functionality here
</script>
@endpush