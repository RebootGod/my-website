@extends('layouts.admin')

@section('title', 'Edit Episode - ' . $episode->name)

@push('styles')
{{-- Following workinginstruction.md - separate CSS file for easy debugging --}}
<link rel="stylesheet" href="{{ safe_asset_version('css/admin/episode-edit-modern.css') }}">
<link rel="stylesheet" href="{{ safe_asset_version('css/admin/episode-draft-manager.css') }}">
@endpush

@section('content')
<div class="container mx-auto px-6 py-8 max-w-4xl">
        
    {{-- Header matching other admin forms --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-white">Edit Episode: {{ $episode->name }}</h1>
        <a href="{{ route('admin.series.show', $series) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            Back to Series
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    {{-- Episode Info --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-400">Series:</span>
                <span class="text-white ml-2">{{ $series->title }}</span>
            </div>
            <div>
                <span class="text-gray-400">Season:</span>
                <span class="text-white ml-2">{{ $episode->season->season_number }}</span>
            </div>
            <div>
                <span class="text-gray-400">Episode:</span>
                <span class="text-white ml-2">{{ $episode->episode_number }}</span>
            </div>
            <div>
                <span class="text-gray-400">Status:</span>
                <span class="ml-2 {{ $episode->is_active ? 'text-green-400' : 'text-gray-400' }}">
                    <i class="fas fa-{{ $episode->is_active ? 'check-circle' : 'times-circle' }}"></i>
                    {{ $episode->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>

    <form id="episode-edit-form" 
          action="{{ route('admin.series.episodes.update', [$series, $episode]) }}" 
          method="POST" 
          class="bg-gray-800 rounded-lg p-6"
          data-episode-id="{{ $episode->id }}"
          data-redirect-url="{{ route('admin.series.show', $series) }}">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="series_id" value="{{ $series->id }}">

            <form id="episode-edit-form" 
                  action="{{ route('admin.series.episodes.update', [$series, $episode]) }}" 
                  method="POST" 
                  data-episode-id="{{ $episode->id }}"
                  data-redirect-url="{{ route('admin.series.show', $series) }}">
                @csrf
                @method('PUT')
                
                <input type="hidden" name="series_id" value="{{ $series->id }}">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Season Selection --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Season *</label>
                <select name="season_id" id="season_id" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="">Choose a season...</option>
                    @foreach($series->seasons->sortBy('season_number') as $season)
                        <option value="{{ $season->id }}" {{ $episode->season_id == $season->id ? 'selected' : '' }}>
                            Season {{ $season->season_number }}{{ $season->name ? ' - ' . $season->name : '' }}
                        </option>
                    @endforeach
                </select>
                @error('season_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Episode Number --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Episode Number *</label>
                <input type="number" 
                       name="episode_number" 
                       id="episode_number" 
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                       value="{{ old('episode_number', $episode->episode_number) }}" 
                       placeholder="Enter episode number (e.g. 1, 2, 3...)" 
                       min="1" 
                       required>
                @error('episode_number')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Episode Name --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Episode Name *</label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                       value="{{ old('name', $episode->name) }}" 
                       placeholder="Enter episode title (e.g. 'The Beginning', 'Final Battle')" 
                       maxlength="255" 
                       required>
                @error('name')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Episode Overview --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Episode Overview *</label>
                <textarea name="overview" 
                          id="overview" 
                          rows="4"
                          class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                          placeholder="Write an engaging description of this episode... What exciting things happen?" 
                          maxlength="1000" 
                          required>{{ old('overview', $episode->overview) }}</textarea>
                @error('overview')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Runtime --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Runtime (minutes) *</label>
                <input type="number" 
                       name="runtime" 
                       id="runtime" 
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                       value="{{ old('runtime', $episode->runtime) }}" 
                       placeholder="e.g. 45, 60, 90" 
                       min="1" 
                       max="600" 
                       required>
                @error('runtime')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Availability Status</label>
                <div class="flex items-center space-x-3 p-3 bg-gray-700 rounded-lg">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" 
                           name="is_active" 
                           id="is_active" 
                           class="w-4 h-4 text-green-600 bg-gray-600 border-gray-500 rounded focus:ring-green-500" 
                           value="1" 
                           {{ old('is_active', $episode->is_active) ? 'checked' : '' }}>
                    <div>
                        <label for="is_active" class="text-sm font-medium text-white">
                            Active Episode (Visible to Users)
                        </label>
                        <p class="text-xs text-gray-400">
                            Inactive episodes won't be visible to users until activated
                        </p>
                    </div>
                </div>
            </div>

            {{-- Embed URL --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Video Embed URL *</label>
                <input type="url"
                       name="embed_url"
                       id="embed_url"
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                       value="{{ old('embed_url', $episode->embed_url) }}"
                       placeholder="https://example.com/embed/video123 or https://player.vimeo.com/video/123456"
                       required>
                @error('embed_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Download URL --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Download URL</label>
                <input type="url"
                       name="download_url"
                       id="download_url"
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                       value="{{ old('download_url', $episode->download_url) }}"
                       placeholder="https://example.com/download/episode.mp4">
                @error('download_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Thumbnail URL --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Episode Thumbnail URL</label>
                <input type="url" 
                       name="still_path" 
                       id="still_path" 
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" 
                       value="{{ old('still_path', $episode->still_path) }}" 
                       placeholder="https://example.com/images/episode-thumbnail.jpg">
                @error('still_path')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="bg-red-600 text-white px-6 py-3 rounded-lg mb-6">
                <div class="flex items-center mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Please fix the following errors:</strong>
                </div>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Actions --}}
        <div class="flex flex-col md:flex-row justify-between items-center mt-8 pt-6 border-t border-gray-700 gap-4">
            <div class="order-2 md:order-1">
                {{-- Progress indicator will be added here by JavaScript --}}
            </div>
            
            <div class="flex items-center gap-3 order-1 md:order-2">
                <button type="button" id="cancel-btn" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </button>
                
                <button type="submit" id="submit-btn" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-save mr-2"></i>
                    Update Episode
                </button>
                
                @can('delete', $episode)
                <button type="button" 
                        id="delete-btn" 
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition"
                        data-delete-url="{{ route('admin.series.episodes.destroy', [$series, $episode]) }}">
                    <i class="fas fa-trash mr-2"></i>
                    Delete Episode
                </button>
                @endcan
            </div>
        </div>
    </form>
@endsection

@push('scripts')
{{-- Following workinginstruction.md - separate JS files for easy debugging --}}
<script src="{{ safe_asset_version('js/admin/episode-edit-modern.js') }}"></script>
<script src="{{ safe_asset_version('js/admin/episode-draft-manager.js') }}"></script>
@endpush