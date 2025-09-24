{{-- Modern Movie Player Page - New Layout --}}
@extends('layouts.app')

@section('title', 'Watching: ' . $movie->title . ' - Cinema')

@push('styles')
<style>
:root {
    --primary-bg: #0a0e1a;
    --secondary-bg: #1a1a2e;
    --accent-bg: #16213e;
    --card-bg: #0f172a;
    --text-primary: #ffffff;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --accent-color: #00ff88;
    --accent-secondary: #66ff99;
    --warning-color: #ffd700;
    --danger-color: #ff6b6b;
    --success-color: #4ade80;
    --border-color: #334155;
    --shadow-primary: 0 4px 20px rgba(0, 255, 136, 0.1);
    --shadow-card: 0 8px 32px rgba(0, 0, 0, 0.3);
    --gradient-primary: linear-gradient(135deg, var(--accent-color), var(--accent-secondary));
    --gradient-dark: linear-gradient(135deg, var(--primary-bg), var(--secondary-bg));
}

* {
    box-sizing: border-box;
}

body {
    background: var(--gradient-dark);
    min-height: 100vh;
    color: var(--text-primary);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.player-wrapper {
    background: var(--primary-bg);
    min-height: 100vh;
    padding: 2rem 0;
}

.video-container {
    position: relative;
    background: #000;
    border-radius: 16px;
    overflow: hidden;
    aspect-ratio: 16/9;
    box-shadow: var(--shadow-card);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid var(--border-color);
}

.video-container:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 255, 136, 0.1), var(--shadow-card);
    border-color: var(--accent-color);
}

.video-container iframe {
    width: 100%;
    height: 100%;
    border: none;
    border-radius: 16px;
}

.video-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: var(--gradient-dark);
    color: var(--text-muted);
    flex-direction: column;
    gap: 1.5rem;
    padding: 3rem;
    text-align: center;
    border-radius: 16px;
}

.placeholder-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.info-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-card);
}

.info-card:hover {
    border-color: var(--accent-color);
    box-shadow: 0 8px 32px rgba(0, 255, 136, 0.15);
    transform: translateY(-2px);
}

.card-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-primary);
}

.card-title span {
    font-size: 1.1rem;
}

.btn {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn:hover {
    background: var(--accent-bg);
    border-color: var(--accent-color);
    color: var(--text-primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-primary);
}

.btn-outline-light {
    background: transparent;
    border-color: var(--accent-color);
    color: var(--accent-color);
}

.btn-outline-light:hover {
    background: var(--accent-color);
    color: var(--primary-bg);
}

.btn-primary {
    background: var(--gradient-primary);
    border-color: var(--accent-color);
    color: var(--primary-bg);
}

.btn-primary:hover {
    background: var(--gradient-primary);
    color: var(--primary-bg);
    box-shadow: 0 8px 25px rgba(0, 255, 136, 0.3);
}

.btn-outline-secondary {
    background: transparent;
    border-color: var(--border-color);
    color: var(--text-secondary);
}

.btn-outline-secondary:hover {
    background: var(--accent-bg);
    border-color: var(--accent-color);
    color: var(--text-primary);
}

.btn-secondary {
    background: var(--accent-bg);
    border-color: var(--border-color);
    color: var(--text-secondary);
}

.btn-secondary:hover {
    background: var(--secondary-bg);
    color: var(--text-primary);
}

.btn-danger {
    background: var(--danger-color);
    border-color: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background: #ff5252;
    border-color: #ff5252;
    color: white;
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-muted);
    font-size: 0.875rem;
    font-weight: 500;
}

.info-value {
    color: var(--text-primary);
    font-weight: 600;
}

.genre-tag {
    display: inline-block;
    background: var(--accent-bg);
    color: var(--text-primary);
    padding: 0.3rem 0.8rem;
    border-radius: 16px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    margin: 0.2rem 0.2rem 0.2rem 0;
    transition: all 0.3s ease;
    border: 1px solid var(--border-color);
}

.genre-tag:hover {
    background: var(--gradient-primary);
    color: var(--primary-bg);
    transform: translateY(-1px);
    border-color: var(--accent-color);
}

.hover-card {
    transition: all 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 255, 136, 0.15);
}

.badge {
    background: var(--warning-color);
    color: var(--primary-bg);
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
}

.fade-in {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Report Modal Styling */
#reportModal {
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
}

#reportModal .info-card {
    background: var(--card-bg);
    border: 1px solid var(--accent-color);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
}

#reportModal input,
#reportModal select,
#reportModal textarea {
    background: var(--accent-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0.75rem;
    color: var(--text-primary);
    font-size: 0.9rem;
}

#reportModal input:focus,
#reportModal select:focus,
#reportModal textarea:focus {
    border-color: var(--accent-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .player-wrapper {
        padding: 1rem 0;
    }

    .container-fluid {
        padding: 0 1rem;
    }

    .video-container {
        margin-bottom: 1rem;
    }

    .btn {
        font-size: 0.8rem;
        padding: 0.6rem 1rem;
    }

    .card-title {
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .info-card {
        padding: 1rem;
    }

    .btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.8rem;
    }

    .placeholder-icon {
        font-size: 3rem;
    }
}
</style>
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
                                referrerpolicy="no-referrer"
                                sandbox="allow-scripts allow-same-origin allow-presentation">
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
                                        @if($related->poster_url)
                                            <img src="{{ $related->poster_url }}" alt="{{ $related->title }}" class="card-img-top" style="height: 280px; object-fit: cover;">
                                        @else
                                            <div class="card-img-top d-flex align-items-center justify-content-center bg-secondary" style="height: 280px;">
                                                <span style="color: #9ca3af; font-size: 2rem;">🎬</span>
                                            </div>
                                        @endif
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

<style>
.hover-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(75, 85, 99, 0.3);
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #9ca3af;
    font-size: 0.875rem;
}

.info-value {
    color: #f3f4f6;
    font-weight: 500;
}

.genre-tag {
    display: inline-block;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.75rem;
    margin: 0.25rem 0.25rem 0.25rem 0;
    transition: transform 0.2s ease;
}

.genre-tag:hover {
    transform: scale(1.05);
    color: white;
}
</style>
@endsection

@push('scripts')
@vite('resources/js/pages/player.js')
<script>
    const movieId = {{ $movie->id }};
    const movieSlug = '{{ $movie->slug }}';
    const movieTitle = '{{ addslashes($movie->title) }}';
    const csrfToken = '{{ csrf_token() }}';
    const currentSourceId = {{ isset($currentSource) && $currentSource ? $currentSource->id : 'null' }};
    
    // Direct function definitions (fallback if module doesn't work)
    function addToWatchlist() {
        console.log('🚀 addToWatchlist called');
        if (!movieId) {
            alert('Error: Movie ID not available');
            return;
        }
        
        if (!csrfToken) {
            alert('Please login to add movies to watchlist');
            return;
        }
        
        fetch(`/watchlist/add/${movieId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    alert('Please login to add movies to watchlist');
                    return;
                }
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data) {
                alert(data.message || 'Added to watchlist successfully!');
            }
        })
        .catch((error) => {
            console.error('Error adding to watchlist:', error);
            alert('Error adding movie to watchlist. Please try again.');
        });
    }
    
    function reloadPlayer() {
        console.log('� reloadPlayer called');
        const player = document.getElementById('moviePlayer');
        if (player) {
            const src = player.src;
            player.src = '';
            setTimeout(() => player.src = src, 100);
            alert('Player reloaded successfully!');
        }
    }
    
    function shareMovie() {
        console.log('🚀 shareMovie called');
        if (navigator.share) {
            navigator.share({
                title: movieTitle,
                text: `Watch ${movieTitle} on Noobz Cinema`,
                url: window.location.href
            }).catch(() => {
                copyToClipboard();
            });
        } else {
            copyToClipboard();
        }
    }
    
    function copyToClipboard() {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Movie link copied to clipboard!');
            }).catch(() => {
                fallbackCopy();
            });
        } else {
            fallbackCopy();
        }
    }
    
    function fallbackCopy() {
        const textArea = document.createElement('textarea');
        textArea.value = window.location.href;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Movie link copied to clipboard!');
        } catch (err) {
            alert('Unable to copy link. Please copy manually: ' + window.location.href);
        }
        document.body.removeChild(textArea);
    }
    
    function reportIssue() {
        console.log('🚀 reportIssue called');
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
        
        if (currentSourceId) {
            document.getElementById('sourceId').value = currentSourceId;
        }
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
        
        const data = {
            movie_id: formData.get('movie_id'),
            source_id: formData.get('source_id'),
            issue_type: formData.get('issue_type'),
            description: formData.get('description')
        };
        
        fetch(`/movie/${movieSlug}/report`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
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
            alert(data.message || 'Report submitted successfully!');
            closeReportModal();
        })
        .catch((error) => {
            console.error('Error submitting report:', error);
            alert('Thank you for your report! We will investigate the issue.');
            closeReportModal();
        });
    }
    
    // Ensure functions are available globally (fallback)
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 DOM loaded, checking functions...');
        console.log('addToWatchlist exists:', typeof addToWatchlist);
        console.log('reloadPlayer exists:', typeof reloadPlayer);
        console.log('shareMovie exists:', typeof shareMovie);
        console.log('reportIssue exists:', typeof reportIssue);
    });
</script>
@endpush