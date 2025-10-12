{{-- ======================================== --}}
{{-- ADMIN MOVIES EDIT --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/movies/edit.blade.php --}}

@extends('layouts.admin')

@section('title', 'Edit Movie - Admin')

@section('content')
<div class="container mx-auto px-6 py-8 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Edit Movie: {{ $movie->title }}</h1>
        <a href="{{ route('admin.movies.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            Back to List
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.movies.update', $movie) }}" method="POST" class="bg-gray-800 rounded-lg p-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Title --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Title *</label>
                <input 
                    type="text" 
                    name="title" 
                    value="{{ old('title', $movie->title) }}"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                    required
                >
                @error('title')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                <textarea 
                    name="description" 
                    rows="4"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >{{ old('description', $movie->description) }}</textarea>
                @error('description')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Embed URL --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Embed URL *</label>
                <input
                    type="url"
                    name="embed_url"
                    value="{{ old('embed_url', $movie->embed_url ? (function() use ($movie) {
                        try {
                            return decrypt($movie->embed_url);
                        } catch (\Exception $e) {
                            return $movie->embed_url; // Return as-is if decryption fails
                        }
                    })() : (isset($primarySource) ? $primarySource->embed_url : '')) }}"
                    placeholder="https://short.icu/example"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                    required
                >
                @error('embed_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Download URL --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Download URL</label>
                <input
                    type="url"
                    name="download_url"
                    value="{{ old('download_url', $movie->download_url) }}"
                    placeholder="https://example.com/download/movie.mp4"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('download_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Current Poster --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Current Poster</label>
                <div class="flex items-center gap-4">
                    @if($movie->poster_url)
                        <img src="{{ $movie->poster_url }}" alt="Current Poster" class="w-24 h-32 object-cover rounded-lg">
                        <div class="text-sm">
                            <p class="text-gray-400">Source: 
                                @if($movie->local_poster_path)
                                    <span class="text-green-400">Local (Downloaded from TMDB)</span>
                                @elseif($movie->getRawOriginal('poster_url'))
                                    <span class="text-blue-400">Custom Upload</span>
                                @else
                                    <span class="text-gray-500">Placeholder</span>
                                @endif
                            </p>
                        </div>
                    @else
                        <p class="text-gray-500">No poster available</p>
                    @endif
                </div>
            </div>

            {{-- Upload New Poster --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">
                    Upload New Poster (Optional)
                    <span class="text-xs text-gray-500">- Max 5MB, JPG/PNG/WebP</span>
                </label>
                <input 
                    type="file" 
                    name="poster" 
                    accept="image/jpeg,image/png,image/webp"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-500 file:text-black hover:file:bg-green-600"
                >
                <p class="text-xs text-gray-500 mt-1">Leave empty to keep current poster</p>
                @error('poster')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Current Backdrop --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Current Backdrop</label>
                <div class="flex items-center gap-4">
                    @if($movie->backdrop_url)
                        <img src="{{ $movie->backdrop_url }}" alt="Current Backdrop" class="w-32 h-18 object-cover rounded-lg">
                        <div class="text-sm">
                            <p class="text-gray-400">Source: 
                                @if($movie->local_backdrop_path)
                                    <span class="text-green-400">Local (Downloaded from TMDB)</span>
                                @elseif($movie->getRawOriginal('backdrop_url'))
                                    <span class="text-blue-400">Custom Upload</span>
                                @else
                                    <span class="text-gray-500">Placeholder</span>
                                @endif
                            </p>
                        </div>
                    @else
                        <p class="text-gray-500">No backdrop available</p>
                    @endif
                </div>
            </div>

            {{-- Upload New Backdrop --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">
                    Upload New Backdrop (Optional)
                    <span class="text-xs text-gray-500">- Max 5MB, JPG/PNG/WebP</span>
                </label>
                <input 
                    type="file" 
                    name="backdrop" 
                    accept="image/jpeg,image/png,image/webp"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-500 file:text-black hover:file:bg-green-600"
                >
                <p class="text-xs text-gray-500 mt-1">Leave empty to keep current backdrop</p>
                @error('backdrop')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Year --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Year</label>
                <input 
                    type="number" 
                    name="year" 
                    value="{{ old('year', $movie->year) }}"
                    min="1900" 
                    max="{{ date('Y') + 5 }}"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('year')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Duration --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Duration (minutes)</label>
                <input 
                    type="number" 
                    name="duration" 
                    value="{{ old('duration', $movie->duration) }}"
                    min="1"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('duration')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Rating --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Rating (0-10)</label>
                <input 
                    type="number" 
                    name="rating" 
                    value="{{ old('rating', $movie->rating) }}"
                    min="0" 
                    max="10" 
                    step="0.1"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('rating')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quality --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Quality *</label>
                <select name="quality" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="CAM" {{ old('quality', $movie->quality) == 'CAM' ? 'selected' : '' }}>CAM</option>
                    <option value="HD" {{ old('quality', $movie->quality) == 'HD' ? 'selected' : '' }}>HD</option>
                    <option value="FHD" {{ old('quality', $movie->quality) == 'FHD' ? 'selected' : '' }}>Full HD</option>
                    <option value="4K" {{ old('quality', $movie->quality) == '4K' ? 'selected' : '' }}>4K</option>
                </select>
                @error('quality')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Status *</label>
                <select name="status" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="draft" {{ old('status', $movie->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $movie->status) == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ old('status', $movie->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
                @error('status')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- TMDB ID --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">TMDB ID</label>
                <input 
                    type="number" 
                    name="tmdb_id" 
                    value="{{ old('tmdb_id', $movie->tmdb_id) }}"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('tmdb_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Genres --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Genres</label>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
                    @foreach($genres as $genre)
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="genres[]" 
                            value="{{ $genre->id }}"
                            {{ in_array($genre->id, old('genres', $movie->genres->pluck('id')->toArray())) ? 'checked' : '' }}
                            class="mr-2"
                        >
                        <span class="text-sm">{{ $genre->name }}</span>
                    </label>
                    @endforeach
                </div>
                @error('genres')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 flex justify-between">
            <div>
                <a href="{{ route('movies.show', $movie->slug) }}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    View on Site
                </a>
                <a href="{{ route('admin.movies.sources.index', $movie) }}" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        📺 Manage Sources
                </a>
            </div>
            <div class="flex space-x-4">
                <a href="{{ route('admin.movies.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="bg-green-400 hover:bg-green-500 text-black px-6 py-2 rounded-lg font-semibold transition">
                    Update Movie
                </button>
            </div>
        </div>
    </form>

    {{-- Quick Actions --}}
    <div class="mt-6 bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold mb-3">Quick Actions</h3>
        <div class="flex space-x-4">
            <form action="{{ route('admin.movies.toggle-status', $movie) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition">
                    {{ $movie->status == 'published' ? 'Unpublish' : 'Publish' }} Movie
                </button>
            </form>
            <form action="{{ route('admin.movies.destroy', $movie) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this movie?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                    Delete Movie
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Form Auto-save for movie edit form
    new FormAutoSave('form[action*="movies"]', {
        saveInterval: 30000, // 30 seconds
        storageKey: 'autosave_movie_{{ $movie->id }}',
        showNotifications: true
    });
});
</script>
@endpush
