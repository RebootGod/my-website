@extends('layouts.admin')

@section('title', 'Edit Episode - ' . $episode->name)

@push('styles')
{{-- Following workinginstruction.md - separate CSS file for easy debugging --}}
@if(file_exists(public_path('css/admin/episode-edit-modern.css')))
<link rel="stylesheet" href="{{ asset('css/admin/episode-edit-modern.css') }}?v={{ filemtime(public_path('css/admin/episode-edit-modern.css')) }}">
@else
<link rel="stylesheet" href="{{ asset('css/admin/episode-edit-modern.css') }}?v={{ time() }}">
@endif
@if(file_exists(public_path('css/admin/episode-draft-manager.css')))
<link rel="stylesheet" href="{{ asset('css/admin/episode-draft-manager.css') }}?v={{ filemtime(public_path('css/admin/episode-draft-manager.css')) }}">
@else
<link rel="stylesheet" href="{{ asset('css/admin/episode-draft-manager.css') }}?v={{ time() }}">
@endif
@endpush

@section('content')
<div class="episode-edit-wrapper">
    <div class="episode-edit-container">
        
        {{-- Header Section --}}
        <div class="episode-header">
            <div class="episode-header-top">
                <div class="episode-header-content">
                    <nav class="breadcrumb-nav">
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a> / 
                        <a href="{{ route('admin.series.index') }}">Series</a> / 
                        <a href="{{ route('admin.series.show', $series) }}">{{ $series->title }}</a> / 
                        Edit Episode
                    </nav>
                    
                    <h1 class="episode-title">
                        <div class="title-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        Edit Episode: {{ $episode->name }}
                    </h1>
                </div>
            </div>
            
            <div class="episode-info-section">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Series</div>
                        <div class="info-value">{{ $series->title }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Season</div>
                        <div class="info-value">{{ $episode->season->season_number }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Episode</div>
                        <div class="info-value">{{ $episode->episode_number }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge {{ $episode->is_active ? 'active' : 'inactive' }}">
                                <i class="fas fa-{{ $episode->is_active ? 'check-circle' : 'times-circle' }}"></i>
                                {{ $episode->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Form --}}
        <div class="episode-form-card">
            <div class="form-header">
                <h2 class="form-title">
                    <i class="fas fa-cog"></i>
                    Episode Configuration
                </h2>
            </div>

            <form id="episode-edit-form" 
                  action="{{ route('admin.series.episodes.update', [$series, $episode]) }}" 
                  method="POST" 
                  data-episode-id="{{ $episode->id }}"
                  data-redirect-url="{{ route('admin.series.show', $series) }}">
                @csrf
                @method('PUT')
                
                <input type="hidden" name="series_id" value="{{ $series->id }}">

                <div class="form-content">
                    
                    {{-- Episode Details Section --}}
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Episode Details</h3>
                                <p class="section-description">Basic information about this episode</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            {{-- Season Selection --}}
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label for="season_id" class="form-label required">
                                        <i class="fas fa-layer-group label-icon"></i>
                                        Season
                                    </label>
                                    <select name="season_id" id="season_id" class="form-input form-select" required>
                                        <option value="">Choose a season...</option>
                                        @foreach($series->seasons->sortBy('season_number') as $season)
                                            <option value="{{ $season->id }}" {{ $episode->season_id == $season->id ? 'selected' : '' }}>
                                                Season {{ $season->season_number }}{{ $season->name ? ' - ' . $season->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="field-help">
                                        <i class="fas fa-info-circle icon"></i>
                                        Select which season this episode belongs to
                                    </div>
                                </div>
                            </div>

                            {{-- Episode Number --}}
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label for="episode_number" class="form-label required">
                                        <i class="fas fa-hashtag label-icon"></i>
                                        Episode Number
                                    </label>
                                    <input type="number" 
                                           name="episode_number" 
                                           id="episode_number" 
                                           class="form-input" 
                                           value="{{ old('episode_number', $episode->episode_number) }}" 
                                           placeholder="Enter episode number (e.g. 1, 2, 3...)" 
                                           min="1" 
                                           required>
                                    <div class="field-help">
                                        <i class="fas fa-sort-numeric-up icon"></i>
                                        Episode number within the season
                                    </div>
                                </div>
                            </div>

                            {{-- Episode Name --}}
                            <div class="form-col-12">
                                <div class="form-group">
                                    <label for="name" class="form-label required">
                                        <i class="fas fa-heading label-icon"></i>
                                        Episode Name
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           id="name" 
                                           class="form-input" 
                                           value="{{ old('name', $episode->name) }}" 
                                           placeholder="Enter episode title (e.g. 'The Beginning', 'Final Battle')" 
                                           maxlength="255" 
                                           required>
                                    <div class="field-help">
                                        <i class="fas fa-tag icon"></i>
                                        The catchy title of this episode
                                    </div>
                                </div>
                            </div>

                            {{-- Episode Overview --}}
                            <div class="form-col-12">
                                <div class="form-group">
                                    <label for="overview" class="form-label required">
                                        <i class="fas fa-align-left label-icon"></i>
                                        Episode Overview
                                    </label>
                                    <textarea name="overview" 
                                              id="overview" 
                                              class="form-input form-textarea" 
                                              placeholder="Write an engaging description of this episode... What exciting things happen? What conflicts arise? Keep viewers interested!" 
                                              maxlength="1000" 
                                              required>{{ old('overview', $episode->overview) }}</textarea>
                                    <div class="field-help">
                                        <i class="fas fa-pen icon"></i>
                                        Brief but compelling description of what happens in this episode
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Technical Details Section --}}
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Technical Details</h3>
                                <p class="section-description">Runtime and availability settings</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            {{-- Runtime --}}
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label for="runtime" class="form-label required">
                                        <i class="fas fa-clock label-icon"></i>
                                        Runtime (minutes)
                                    </label>
                                    <input type="number" 
                                           name="runtime" 
                                           id="runtime" 
                                           class="form-input" 
                                           value="{{ old('runtime', $episode->runtime) }}" 
                                           placeholder="e.g. 45, 60, 90" 
                                           min="1" 
                                           max="600" 
                                           required>
                                    <div class="field-help">
                                        <i class="fas fa-stopwatch icon"></i>
                                        Duration in minutes (typical TV episodes: 22-60 min)
                                    </div>
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="form-col-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-toggle-on label-icon"></i>
                                        Availability Status
                                    </label>
                                    <div class="checkbox-group">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" 
                                               name="is_active" 
                                               id="is_active" 
                                               class="form-checkbox" 
                                               value="1" 
                                               {{ old('is_active', $episode->is_active) ? 'checked' : '' }}>
                                        <div>
                                            <label for="is_active" class="checkbox-label">
                                                Active Episode (Visible to Users)
                                            </label>
                                            <div class="checkbox-description">
                                                Inactive episodes won't be visible to users until activated
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Media Sources Section --}}
                    <div class="form-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <div>
                                <h3 class="section-title">Media Sources</h3>
                                <p class="section-description">Video player and thumbnail configuration</p>
                            </div>
                        </div>

                        <div class="form-grid">
                            {{-- Embed URL --}}
                            <div class="form-col-12">
                                <div class="form-group">
                                    <label for="embed_url" class="form-label required">
                                        <i class="fas fa-play label-icon"></i>
                                        Video Embed URL
                                    </label>
                                    <div class="input-group">
                                        <input type="url" 
                                               name="embed_url" 
                                               id="embed_url" 
                                               class="form-input" 
                                               value="{{ old('embed_url', $episode->embed_url) }}" 
                                               placeholder="https://example.com/embed/video123 or https://player.vimeo.com/video/123456" 
                                               required>
                                        <button type="button" 
                                                class="btn btn-secondary preview-btn" 
                                                data-preview-type="video">
                                            <i class="fas fa-external-link-alt"></i>
                                            Preview
                                        </button>
                                    </div>
                                    <div class="field-help">
                                        <i class="fas fa-link icon"></i>
                                        Direct link to the video player embed (YouTube, Vimeo, or custom player)
                                    </div>
                                </div>
                            </div>

                            {{-- Thumbnail URL --}}
                            <div class="form-col-12">
                                <div class="form-group">
                                    <label for="still_path" class="form-label">
                                        <i class="fas fa-image label-icon"></i>
                                        Episode Thumbnail URL
                                    </label>
                                    <div class="input-group">
                                        <input type="url" 
                                               name="still_path" 
                                               id="still_path" 
                                               class="form-input" 
                                               value="{{ old('still_path', $episode->still_path) }}" 
                                               placeholder="https://example.com/images/episode-thumbnail.jpg">
                                        <button type="button" 
                                                class="btn btn-secondary preview-btn" 
                                                data-preview-type="image">
                                            <i class="fas fa-eye"></i>
                                            Preview
                                        </button>
                                    </div>
                                    <div class="field-help">
                                        <i class="fas fa-photo-video icon"></i>
                                        Optional: Eye-catching thumbnail image for this episode (JPG, PNG, WebP)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Validation Errors --}}
                    @if($errors->any())
                        <div class="form-section">
                            <div class="alert alert-danger">
                                <div class="alert-header">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Please fix the following errors:</strong>
                                </div>
                                <ul class="error-list">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Form Actions --}}
                <div class="form-actions">
                    <div class="form-actions-left">
                        {{-- Progress indicator will be added here by JavaScript --}}
                    </div>
                    
                    <div class="form-actions-right">
                        <button type="button" id="cancel-btn" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        
                        <button type="submit" id="submit-btn" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Episode
                        </button>
                        
                        @can('delete', $episode)
                        <button type="button" 
                                id="delete-btn" 
                                class="btn btn-danger"
                                data-delete-url="{{ route('admin.series.episodes.destroy', [$series, $episode]) }}">
                            <i class="fas fa-trash"></i>
                            Delete Episode
                        </button>
                        @endcan
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Following workinginstruction.md - separate JS files for easy debugging --}}
@if(file_exists(public_path('js/admin/episode-edit-modern.js')))
<script src="{{ asset('js/admin/episode-edit-modern.js') }}?v={{ filemtime(public_path('js/admin/episode-edit-modern.js')) }}"></script>
@else
<script src="{{ asset('js/admin/episode-edit-modern.js') }}?v={{ time() }}"></script>
@endif
@if(file_exists(public_path('js/admin/episode-draft-manager.js')))
<script src="{{ asset('js/admin/episode-draft-manager.js') }}?v={{ filemtime(public_path('js/admin/episode-draft-manager.js')) }}"></script>
@else
<script src="{{ asset('js/admin/episode-draft-manager.js') }}?v={{ time() }}"></script>
@endif
@endpush