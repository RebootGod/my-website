{{-- ======================================== --}}
{{-- ADMIN SERIES EDIT --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/series/edit.blade.php --}}

@extends('layouts.admin')

@section('title', 'Edit Series - Admin')

@section('content')
<div class="container mx-auto px-6 py-8 max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Edit Series: {{ $series->title }}</h1>
        <a href="{{ route('admin.series.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            Back to List
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.series.update', $series) }}" method="POST" class="bg-gray-800 rounded-lg p-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Title --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Title *</label>
                <input 
                    type="text" 
                    name="title" 
                    value="{{ old('title', $series->title) }}"
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
                >{{ old('description', $series->description) }}</textarea>
                @error('description')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Current Poster --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Current Poster</label>
                <div class="flex items-center gap-4">
                    @if($series->poster_url)
                        <img src="{{ $series->poster_url }}" alt="Current Poster" class="w-24 h-32 object-cover rounded-lg">
                        <div class="text-sm">
                            <p class="text-gray-400">Source: 
                                @if($series->local_poster_path)
                                    <span class="text-green-400">Local (Downloaded from TMDB)</span>
                                @elseif($series->getRawOriginal('poster_url'))
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
                    @if($series->backdrop_url)
                        <img src="{{ $series->backdrop_url }}" alt="Current Backdrop" class="w-32 h-18 object-cover rounded-lg">
                        <div class="text-sm">
                            <p class="text-gray-400">Source: 
                                @if($series->local_backdrop_path)
                                    <span class="text-green-400">Local (Downloaded from TMDB)</span>
                                @elseif($series->getRawOriginal('backdrop_url'))
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
                    value="{{ old('year', $series->year) }}"
                    min="1900" 
                    max="{{ date('Y') + 10 }}"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('year')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Duration (minutes) --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Duration (minutes)</label>
                <input 
                    type="number" 
                    name="duration" 
                    value="{{ old('duration', $series->duration) }}"
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
                    value="{{ old('rating', $series->rating) }}"
                    min="0" 
                    max="10" 
                    step="0.1"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('rating')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-2">Status *</label>
                <select 
                    name="status" 
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                    required
                >
                    <option value="">Select Status</option>
                    <option value="published" {{ old('status', $series->status) == 'published' ? 'selected' : '' }}>Published</option>
                    <option value="draft" {{ old('status', $series->status) == 'draft' ? 'selected' : '' }}>Draft</option>
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
                    value="{{ old('tmdb_id', $series->tmdb_id) }}"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                    placeholder="Optional"
                >
                @error('tmdb_id')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Genres --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Genres</label>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($genres as $genre)
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="genre_ids[]" 
                                value="{{ $genre->id }}"
                                {{ in_array($genre->id, old('genre_ids', $series->genres->pluck('id')->toArray())) ? 'checked' : '' }}
                                class="mr-2 rounded text-green-400 focus:ring-green-400"
                            >
                            <span class="text-gray-300">{{ $genre->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('genre_ids')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Poster Upload --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-400 mb-2">Upload New Poster</label>
                <input 
                    type="file" 
                    name="poster" 
                    accept="image/*"
                    class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
                >
                @error('poster')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
                @if($series->poster_path)
                    <p class="text-gray-400 text-sm mt-2">Current poster: {{ $series->poster_path }}</p>
                @endif
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end space-x-4 mt-8">
            <a href="{{ route('admin.series.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                Cancel
            </a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">
                Update Series
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    console.log('=== SERIES FORM AUTO-SAVE INITIALIZATION ===');
    console.log('Admin object exists:', typeof Admin !== 'undefined');
    console.log('Admin.showToast exists:', typeof Admin !== 'undefined' && typeof Admin.showToast === 'function');
    console.log('FormAutoSave exists:', typeof FormAutoSave !== 'undefined');
    
    // Initialize Form Auto-save for series edit form
    const seriesFormAutoSave = new FormAutoSave('form[action*="series"]', {
        saveInterval: 10000, // 10 seconds for testing
        storageKey: 'autosave_series_{{ $series->id }}',
        showNotifications: true,
        onSave: (data) => {
            console.log('✓ Series onSave callback triggered');
        }
    });
    
    console.log('Series FormAutoSave instance created:', seriesFormAutoSave);
    console.log('=== END SERIES INITIALIZATION ===');
});
</script>
@endpush
