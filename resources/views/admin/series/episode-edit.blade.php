@extends('layouts.admin')

@section('title', 'Edit Episode - ' . $episode->name)

@push('styles')
@if(file_exists(public_path('css/admin/episode-edit.css')))
<link rel="stylesheet" href="{{ asset('css/admin/episode-edit.css') }}?v={{ filemtime(public_path('css/admin/episode-edit.css')) }}">
@else
<link rel="stylesheet" href="{{ asset('css/admin/episode-edit.css') }}?v={{ time() }}">
@endif
@endpush

@section('content')
<div class="episode-edit-container">
    <!-- Header Section -->
    <div class="episode-edit-header">
        <div class="breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a> /
            <a href="{{ route('admin.series.index') }}">Series</a> /
            <a href="{{ route('admin.series.show', $series) }}">{{ $series->title }}</a> /
            Edit Episode
        </div>
        <h1>
            <i class="fas fa-edit me-2"></i>
            Edit Episode: {{ $episode->name }}
        </h1>
    </div>

    <!-- Main Form -->
    <div class="episode-edit-form">
        <div class="episode-form-content">
            <!-- Episode Information Card -->
            <div class="episode-info-card">
                <div class="episode-info-title">
                    <i class="fas fa-info-circle me-1"></i>
                    Current Episode Information
                </div>
                <div class="episode-info-details">
                    <div><strong>Series:</strong> {{ $series->title }}</div>
                    <div><strong>Season:</strong> {{ $episode->season->season_number }}</div>
                    <div><strong>Episode:</strong> {{ $episode->episode_number }}</div>
                    <div><strong>Status:</strong> 
                        <span class="badge {{ $episode->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $episode->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            <form id="episode-edit-form" action="{{ route('admin.series.episodes.update', [$series, $episode]) }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Hidden Fields -->
                <input type="hidden" name="series_id" value="{{ $series->id }}">

                <!-- Basic Information Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-play-circle me-2"></i>
                        Episode Details
                    </div>
                    
                    <div class="form-grid">
                        <!-- Season Selection -->
                        <div class="form-group">
                            <label for="season_id" class="form-label required">Season</label>
                            <select name="season_id" id="season_id" class="form-input form-select" required>
                                <option value="">Choose a season...</option>
                                @foreach($series->seasons->sortBy('season_number') as $season)
                                    <option value="{{ $season->id }}" {{ $episode->season_id == $season->id ? 'selected' : '' }}>
                                        🎪 Season {{ $season->season_number }}{{ $season->name ? ' - ' . $season->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="field-help">📝 Select which season this episode belongs to</div>
                        </div>

                        <!-- Episode Number -->
                        <div class="form-group">
                            <label for="episode_number" class="form-label required">Episode Number</label>
                            <input type="number" name="episode_number" id="episode_number" 
                                   class="form-input" value="{{ old('episode_number', $episode->episode_number) }}" 
                                   placeholder="Enter episode number (e.g. 1, 2, 3...)" 
                                   min="1" required>
                            <div class="field-help">Episode number within the season</div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <!-- Episode Name -->
                        <div class="form-group form-grid-full">
                            <label for="name" class="form-label required">Episode Name</label>
                            <input type="text" name="name" id="name" 
                                   class="form-input" value="{{ old('name', $episode->name) }}" 
                                   placeholder="Enter episode title (e.g. 'The Beginning', 'Final Battle')" 
                                   maxlength="255" required>
                            <div class="field-help">The catchy title of this episode</div>
                        </div>
                    </div>

                    <!-- Episode Overview -->
                    <div class="form-group">
                        <label for="overview" class="form-label required">Episode Overview</label>
                        <textarea name="overview" id="overview" 
                                  class="form-input form-textarea" 
                                  placeholder="Write an engaging description of this episode... What exciting things happen? What conflicts arise? Keep viewers interested!" 
                                  required>{{ old('overview', $episode->overview) }}</textarea>
                        <div class="field-help">Brief but compelling description of what happens in this episode</div>
                    </div>
                </div>

                <!-- Technical Information Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-cog me-2"></i>
                        Technical Details
                    </div>
                    
                    <div class="form-grid">
                        <!-- Runtime -->
                        <div class="form-group">
                            <label for="runtime" class="form-label required">Runtime (minutes)</label>
                            <input type="number" name="runtime" id="runtime" 
                                   class="form-input" value="{{ old('runtime', $episode->runtime) }}" 
                                   placeholder="e.g. 45, 60, 90" 
                                   min="1" required>
                            <div class="field-help">Duration in minutes (typical TV episodes: 22-60 min)</div>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <div class="form-checkbox-group">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="is_active" 
                                       class="form-checkbox" value="1" 
                                       {{ old('is_active', $episode->is_active) ? 'checked' : '' }}>
                                <label for="is_active" class="checkbox-label">Active Episode (visible to users)</label>
                            </div>
                            <div class="field-help">Inactive episodes won't be visible to users until activated</div>
                        </div>
                    </div>
                </div>

                <!-- Media Sources Section -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-video me-2"></i>
                        Media Sources
                    </div>
                    
                    <!-- Embed URL -->
                    <div class="form-group">
                        <label for="embed_url" class="form-label required">Embed URL</label>
                        <div class="input-group">
                            <input type="url" name="embed_url" id="embed_url" 
                                   class="form-input" value="{{ old('embed_url', $episode->embed_url) }}" 
                                   placeholder="https://example.com/embed/video123 or https://player.vimeo.com/video/123456" 
                                   required>
                            <button type="button" class="btn btn-secondary" onclick="previewUrl(document.getElementById('embed_url').value, 'embed')">
                                <i class="fas fa-play"></i> Preview
                            </button>
                        </div>
                        <div class="field-help">Direct link to the video player embed (YouTube, Vimeo, or custom player)</div>
                    </div>

                    <!-- Still Path -->
                    <div class="form-group">
                        <label for="still_path" class="form-label">Episode Thumbnail URL</label>
                        <div class="input-group">
                            <input type="url" name="still_path" id="still_path" 
                                   class="form-input" value="{{ old('still_path', $episode->still_path) }}" 
                                   placeholder="https://example.com/images/episode-thumbnail.jpg">
                            <button type="button" class="btn btn-secondary" onclick="previewUrl(document.getElementById('still_path').value, 'image')">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </div>
                        <div class="field-help">Optional: Eye-catching thumbnail image for this episode (JPG, PNG, WebP)</div>
                    </div>
                </div>

                @if($errors->any())
                    <div class="error-message">
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </form>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <div></div> <!-- Empty div for spacing -->
            
            <div class="btn-group">
                <button type="button" id="cancel-btn" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
                
                <button type="submit" form="episode-edit-form" id="submit-btn" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Episode
                </button>
                
                @can('delete', $episode)
                <button type="button" id="delete-btn" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                    Delete Episode
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Delete -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this episode?</p>
                <div class="alert alert-danger">
                    <strong>Episode:</strong> {{ $episode->episode_number }} - {{ $episode->name }}<br>
                    <strong>Season:</strong> {{ $episode->season->season_number }}<br>
                    <strong>Series:</strong> {{ $series->title }}
                </div>
                <p class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    This action cannot be undone. All episode data will be permanently deleted.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <form method="POST" action="{{ route('admin.series.episodes.destroy', [$series, $episode]) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete Episode
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(file_exists(public_path('js/admin/episode-edit.js')))
<script src="{{ asset('js/admin/episode-edit.js') }}?v={{ filemtime(public_path('js/admin/episode-edit.js')) }}"></script>
@else
<script src="{{ asset('js/admin/episode-edit.js') }}?v={{ time() }}"></script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Runtime formatter
    const runtimeInput = document.getElementById('runtime');
    if (runtimeInput) {
        runtimeInput.addEventListener('blur', function() {
            const minutes = parseInt(this.value);
            if (minutes > 0) {
                const formatted = formatRuntime(minutes);
                const helpDiv = this.parentNode.querySelector('.field-help');
                if (helpDiv) {
                    helpDiv.textContent = `Duration: ${formatted}`;
                }
            }
        });
        
        // Trigger initial format
        if (runtimeInput.value) {
            runtimeInput.dispatchEvent(new Event('blur'));
        }
    }

    // URL validation indicators
    const urlInputs = document.querySelectorAll('input[type="url"]');
    urlInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const previewBtn = this.parentNode.querySelector('button');
            if (previewBtn) {
                previewBtn.disabled = !this.value || !this.checkValidity();
            }
        });
    });

    // Auto-save draft functionality (optional)
    let draftTimer;
    const formInputs = document.querySelectorAll('#episode-edit-form input, #episode-edit-form textarea, #episode-edit-form select');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(draftTimer);
            draftTimer = setTimeout(() => {
                saveDraft();
            }, 2000); // Save draft after 2 seconds of inactivity
        });
    });

    function saveDraft() {
        const formData = new FormData(document.getElementById('episode-edit-form'));
        const draft = {};
        
        for (let [key, value] of formData.entries()) {
            draft[key] = value;
        }
        
        localStorage.setItem('episode_edit_draft_{{ $episode->id }}', JSON.stringify(draft));
        
        // Show subtle indication that draft was saved
        const indicator = document.createElement('span');
        indicator.textContent = '✓ Draft saved';
        indicator.className = 'text-success small ms-2';
        indicator.style.opacity = '0';
        indicator.style.transition = 'opacity 0.3s';
        
        const title = document.querySelector('.episode-edit-header h1');
        title.appendChild(indicator);
        
        setTimeout(() => indicator.style.opacity = '1', 100);
        setTimeout(() => {
            indicator.style.opacity = '0';
            setTimeout(() => indicator.remove(), 300);
        }, 2000);
    }

    // Load draft on page load if available
    const savedDraft = localStorage.getItem('episode_edit_draft_{{ $episode->id }}');
    if (savedDraft) {
        try {
            const draft = JSON.parse(savedDraft);
            let hasChanges = false;
            
            Object.keys(draft).forEach(key => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input && input.value !== draft[key]) {
                    if (confirm('A draft of your changes was found. Would you like to restore it?')) {
                        input.value = draft[key];
                        hasChanges = true;
                    }
                }
            });
            
            if (hasChanges) {
                // Clear the draft since we restored it
                localStorage.removeItem('episode_edit_draft_{{ $episode->id }}');
            }
        } catch (e) {
            console.warn('Failed to load draft:', e);
        }
    }
});
</script>
@endpush