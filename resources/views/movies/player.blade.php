@extends('layouts.app')

@section('title', 'Watching: ' . $movie->title . ' - Noobz Cinema')

@push('styles')
@vite(['resources/css/pages/player-v3.css'])
@endpush

@section('content')
<div class="player-wrapper">
    <div class="container">
        {{-- Video Player Section --}}
        <div class="video-player-section">
            <div class="video-container-modern">
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
                        <div class="video-placeholder-modern">
                            <div class="placeholder-icon-modern">🚫</div>
                            <h3>Invalid Source URL</h3>
                            <p>The source URL appears to be corrupted. Please try another source or report this issue.</p>
                            <button onclick="reportIssue()" class="action-pill danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Report Issue</span>
                            </button>
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
                        <div class="video-placeholder-modern">
                            <div class="placeholder-icon-modern">🚫</div>
                            <h3>Invalid Video Source</h3>
                            <p>The video URL appears to be corrupted. Please try another source or report this issue.</p>
                            <button onclick="reportIssue()" class="action-pill danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Report Issue</span>
                            </button>
                        </div>
                    @endif
                @else
                    <div class="video-placeholder-modern">
                        <div class="placeholder-icon-modern">🎭</div>
                        <h3>No Video Available</h3>
                        <p>This movie doesn't have any playable sources yet. Check back later or request a source.</p>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
                            <button onclick="reportIssue()" class="action-pill primary">
                                <i class="fas fa-plus"></i>
                                <span>Request Source</span>
                            </button>
                            <a href="{{ route('movies.show', $movie->slug) }}" class="action-pill">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back to Movie</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions Bar --}}
        <div class="quick-actions-bar">
            <a href="{{ route('movies.show', $movie->slug) }}" class="action-pill">
                <i class="fas fa-arrow-left"></i>
                <span>Movie Details</span>
            </a>

            @auth
                @php
                    $inWatchlist = \App\Models\Watchlist::where('user_id', auth()->id())
                        ->where('movie_id', $movie->id)
                        ->exists();
                @endphp
                @if($inWatchlist)
                    <button class="action-pill" disabled style="opacity: 0.5; cursor: not-allowed;">
                        <i class="fas fa-check"></i>
                        <span>In Watchlist</span>
                    </button>
                @else
                    <button onclick="addToWatchlist('{{ $movie->slug }}')" class="action-pill">
                        <i class="fas fa-heart"></i>
                        <span>Add to Watchlist</span>
                    </button>
                @endif
            @endauth

            @if($movie->download_url)
                <a href="{{ $movie->download_url }}" target="_blank" class="action-pill success">
                    <i class="fas fa-download"></i>
                    <span>Download</span>
                </a>
            @endif

            <button onclick="reloadPlayer()" class="action-pill">
                <i class="fas fa-redo"></i>
                <span>Reload</span>
            </button>

            <button onclick="shareMovie()" class="action-pill">
                <i class="fas fa-share-alt"></i>
                <span>Share</span>
            </button>

            <button onclick="reportIssue()" class="action-pill danger">
                <i class="fas fa-flag"></i>
                <span>Report</span>
            </button>
        </div>

        {{-- Source Selector --}}
        @if(isset($allSources) && $allSources->count() > 0)
            <div class="sources-section">
                <h2 class="section-header-modern">
                    <i class="fas fa-server"></i>
                    <span>Available Sources</span>
                </h2>
                <div class="sources-tabs">
                    @foreach($allSources as $index => $source)
                        <a href="{{ route('movies.player', ['movie' => $movie->slug, 'source' => $source->id]) }}"
                           class="source-tab {{ $currentSource && $currentSource->id == $source->id ? 'active' : '' }}">
                            <i class="fas fa-play-circle"></i>
                            <span>Server {{ $index + 1 }}</span>
                            @if($source->quality)
                                <span class="quality-badge-inline">{{ $source->quality }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Movie Information - Compact Card --}}
        <div class="movie-info-compact">
            <div class="movie-info-header">
                @if($movie->poster_url)
                    <div class="movie-poster-compact">
                        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}">
                    </div>
                @endif

                <div class="movie-details-compact">
                    <h1 class="movie-title-compact">{{ $movie->title }}</h1>

                    <div class="movie-meta-row">
                        <div class="meta-item-inline rating">
                            <i class="fas fa-star"></i>
                            <span>{{ number_format($movie->rating, 1) }}/10</span>
                        </div>
                        <div class="meta-item-inline">
                            <i class="fas fa-calendar"></i>
                            <span>{{ $movie->year }}</span>
                        </div>
                        <div class="meta-item-inline">
                            <i class="fas fa-clock"></i>
                            <span>{{ $movie->getFormattedDuration() }}</span>
                        </div>
                        <div class="meta-item-inline">
                            <i class="fas fa-eye"></i>
                            <span>{{ number_format($movie->view_count) }} views</span>
                        </div>
                    </div>

                    @if($movie->genres && $movie->genres->count() > 0)
                        <div class="genres-inline">
                            @foreach($movie->genres as $genre)
                                <a href="{{ route('movies.genre', $genre->slug) }}" class="genre-tag-inline">
                                    {{ $genre->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if($movie->description)
                <div class="synopsis-section">
                    <h3 class="synopsis-title">
                        <i class="fas fa-align-left"></i>
                        <span>Synopsis</span>
                    </h3>
                    <p class="synopsis-text">{{ $movie->description }}</p>
                </div>
            @endif
        </div>

        {{-- Related Movies Section --}}
        @if(isset($relatedMovies) && $relatedMovies->count() > 0)
            <div class="related-section">
                <h2 class="section-header-modern">
                    <i class="fas fa-fire"></i>
                    <span>You Might Also Like</span>
                </h2>
                <div class="related-movies-grid">
                    @foreach($relatedMovies as $related)
                        <a href="{{ route('movies.show', $related->slug) }}" class="related-movie-card-modern" style="text-decoration: none;">
                            <div class="related-movie-poster">
                                <img src="{{ $related->poster_url }}" 
                                     alt="{{ $related->title }}" 
                                     loading="lazy">
                                <div class="related-movie-overlay">
                                    <div class="play-icon-overlay">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="related-movie-info">
                                <h4 class="related-movie-title">{{ $related->title }}</h4>
                                <div class="related-movie-meta">
                                    <span class="related-movie-year">{{ $related->year }}</span>
                                    <span class="related-movie-rating">
                                        <i class="fas fa-star"></i>
                                        {{ number_format($related->rating, 1) }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Report Modal --}}
<div id="reportModal" class="report-modal-overlay">
    <div class="report-modal-content">
        <div class="report-modal-header">
            <h3 class="report-modal-title">
                <i class="fas fa-flag"></i>
                <span>Report Issue</span>
            </h3>
            <button onclick="closeReportModal()" class="close-modal-btn">×</button>
        </div>

        <form id="reportForm" onsubmit="submitReport(event)">
            <input type="hidden" name="movie_id" value="{{ $movie->id }}">
            <input type="hidden" id="sourceId" name="source_id" value="{{ isset($currentSource) && $currentSource ? $currentSource->id : '' }}">

            <div class="form-group-modern">
                <label class="form-label-modern">Issue Type</label>
                <select id="issueType" name="issue_type" class="form-select-modern">
                    <option value="not_loading">🚫 Video won't load</option>
                    <option value="poor_quality">📹 Poor video quality</option>
                    <option value="no_audio">🔊 Audio problems</option>
                    <option value="no_subtitle">📝 Subtitle issues</option>
                    <option value="buffering">⏳ Buffering issues</option>
                    <option value="wrong_movie">🎬 Wrong movie/content</option>
                    <option value="other">❓ Other issue</option>
                </select>
            </div>

            <div class="form-group-modern">
                <label class="form-label-modern">Description (Optional)</label>
                <textarea id="issueDescription" 
                          name="description" 
                          rows="4"
                          class="form-textarea-modern"
                          placeholder="Please describe the issue in detail..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="action-pill primary" style="flex: 1;">
                    <i class="fas fa-paper-plane"></i>
                    <span>Submit Report</span>
                </button>
                <button type="button" onclick="closeReportModal()" class="action-pill" style="flex: 1;">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/layouts/app.js'])
<script>
    // Report Modal Functions
    function reportIssue() {
        const modal = document.getElementById('reportModal');
        modal.classList.add('show');
    }

    function closeReportModal() {
        const modal = document.getElementById('reportModal');
        modal.classList.remove('show');
    }

    function submitReport(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        fetch('{{ route("movies.report", $movie->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Report submitted successfully! Thank you for helping improve our service.');
                closeReportModal();
                event.target.reset();
            } else {
                alert('❌ Failed to submit report. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ An error occurred. Please try again later.');
        });
    }

    // Reload Player
    function reloadPlayer() {
        const iframe = document.getElementById('moviePlayer');
        if (iframe) {
            iframe.src = iframe.src;
        }
    }

    // Share Movie
    function shareMovie() {
        const title = '{{ $movie->title }}';
        const url = window.location.href;
        
        if (navigator.share) {
            navigator.share({
                title: title,
                text: `Watch ${title} on Noobz Cinema`,
                url: url
            });
        } else {
            // Fallback: Copy to clipboard
            navigator.clipboard.writeText(url).then(() => {
                alert('🔗 Link copied to clipboard!');
            });
        }
    }

    // Close modal on outside click
    document.getElementById('reportModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeReportModal();
        }
    });

    // Track view
    document.addEventListener('DOMContentLoaded', function() {
        fetch('{{ route("movies.track-view", $movie->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
    });
</script>
@endpush
