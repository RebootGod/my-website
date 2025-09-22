{{-- ======================================== --}}
{{-- 2. ADMIN MOVIES CREATE --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/movies/create.blade.php --}}

@extends('layouts.admin')

@section('title', 'Add New Movie - Admin')

@section('content')
<div class="container mx-auto px-6 py-8 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Add New Movie</h1>
        <a href="{{ route('admin.movies.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            Back to List
        </a>
    </div>

    <form action="{{ route('admin.movies.store') }}" method="POST" class="bg-gray-800 rounded-lg p-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Title --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Title *</label>
                <input 
                    type="text" 
                    name="title" 
                    value="{{ old('title') }}"
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
                >{{ old('description') }}</textarea>
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
                    value="{{ old('embed_url') }}"
                    placeholder="https://short.icu/example"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                    required
                >
                @error('embed_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Poster URL --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Poster URL</label>
                <input 
                    type="url" 
                    name="poster_url" 
                    value="{{ old('poster_url') }}"
                    placeholder="https://image.tmdb.org/..."
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('poster_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Backdrop URL --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Backdrop URL</label>
                <input 
                    type="url" 
                    name="backdrop_url" 
                    value="{{ old('backdrop_url') }}"
                    placeholder="https://image.tmdb.org/..."
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('backdrop_url')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Year --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Year</label>
                <input 
                    type="number" 
                    name="year" 
                    value="{{ old('year', date('Y')) }}"
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
                    value="{{ old('duration') }}"
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
                    value="{{ old('rating') }}"
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
                    <option value="CAM" {{ old('quality') == 'CAM' ? 'selected' : '' }}>CAM</option>
                    <option value="HD" {{ old('quality', 'HD') == 'HD' ? 'selected' : '' }}>HD</option>
                    <option value="FHD" {{ old('quality') == 'FHD' ? 'selected' : '' }}>Full HD</option>
                    <option value="4K" {{ old('quality') == '4K' ? 'selected' : '' }}>4K</option>
                </select>
                @error('quality')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Status *</label>
                <select name="status" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>Archived</option>
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
                    value="{{ old('tmdb_id') }}"
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
                            {{ in_array($genre->id, old('genres', [])) ? 'checked' : '' }}
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

        <div class="mt-6 flex justify-end space-x-4">
            <a href="{{ route('admin.movies.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                Cancel
            </a>
            <button type="submit" class="bg-green-400 hover:bg-green-500 text-black px-6 py-2 rounded-lg font-semibold transition">
                Save Movie
            </button>
        </div>
    </form>
</div>
@endsection