{{-- ======================================== --}}
{{-- 1. ADMIN MOVIES INDEX --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/movies/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Manage Movies - Admin')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Movies</h1>
        <div class="flex space-x-4">
            <a href="{{ route('admin.tmdb-new.index') }}" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-lg font-semibold transition">
                Import Movies from TMDB
            </a>
            <a href="{{ route('admin.movies.create') }}" class="bg-green-400 hover:bg-green-500 text-black px-6 py-2 rounded-lg font-semibold transition">
                + Add New Movie
            </a>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <form action="{{ route('admin.movies.index') }}" method="GET" class="flex gap-4">
            <input 
                type="text" 
                name="search" 
                placeholder="Search movies..."
                value="{{ request('search') }}"
                class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
            >
            <select name="status" class="px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="">All Status</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">
                Search
            </button>
        </form>
    </div>

    {{-- Movies Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Movie</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Quality</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Views</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($movies as $movie)
                <tr class="hover:bg-gray-700">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="w-12 h-16 rounded mr-4">
                            <div>
                                <div class="text-white font-medium">{{ $movie->title }}</div>
                                <div class="text-gray-400 text-sm">{{ Str::limit($movie->description, 50) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-300">{{ $movie->year ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-600 text-white">{{ $movie->quality }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $movie->status == 'published' ? 'bg-green-600' : ($movie->status == 'draft' ? 'bg-yellow-600' : 'bg-gray-600') }} text-white">
                            {{ ucfirst($movie->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">{{ number_format($movie->view_count) }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('admin.movies.edit', $movie) }}" class="text-blue-400 hover:text-blue-300">
                                Edit
                            </a>
                            <form action="{{ route('admin.movies.toggle-status', $movie) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $movie->status == 'published' ? 'draft' : 'published' }}">
                                <button type="submit" class="text-yellow-400 hover:text-yellow-300">
                                    {{ $movie->status == 'published' ? 'Unpublish' : 'Publish' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.movies.destroy', $movie) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-400">No movies found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($movies->hasPages())
    <div class="mt-6">
        {{ $movies->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection