{{-- ======================================== --}}
{{-- 1. ADMIN SERIES INDEX --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/series/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Manage Series - Admin')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin/bulk-operations.css') }}?v={{ filemtime(public_path('css/admin/bulk-operations.css')) }}">
@endsection

@section('content')
<div class="container mx-auto px-6 py-8" data-content-type="series">
    {{-- Breadcrumb Navigation --}}
    @include('admin.components.breadcrumbs', [
        'items' => [
            ['label' => 'Series', 'icon' => 'fas fa-tv']
        ]
    ])

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Series</h1>
        <div class="flex space-x-4">
            <button id="refresh-all-tmdb-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold transition" title="Refresh TMDB data for ALL series">
                <i class="fas fa-sync-alt"></i> Refresh All TMDB
            </button>
            <a href="{{ route('admin.series.tmdb-new.index') }}" class="bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-lg font-semibold transition">
                Import Series from TMDB
            </a>
            <a href="{{ route('admin.series.create') }}" class="bg-green-400 hover:bg-green-500 text-black px-6 py-2 rounded-lg font-semibold transition">
                + Add New Series
            </a>
        </div>
    </div>

    {{-- Advanced Filters Component (Same as Movies) --}}
    @include('admin.components.advanced-filters', [
        'action' => route('admin.series.index'),
        'contentType' => 'series',
        'genres' => $genres
    ])

    {{-- Quick Search Bar (Optional - kept for convenience) --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <form action="{{ route('admin.series.index') }}" method="GET" class="flex gap-4">
            <input 
                type="text" 
                name="search" 
                placeholder="Quick search series..."
                value="{{ request('search') }}"
                class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
            >
            <select name="status" class="px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="">All Status</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
            </select>
            <select name="genre" class="px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="">All Genres</option>
                @foreach($genres as $genre)
                    <option value="{{ $genre->id }}" {{ request('genre') == $genre->id ? 'selected' : '' }}>
                        {{ $genre->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">Search</button>
        </form>
    </div>

    {{-- Series Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-900">
                <tr>
                    <th class="bulk-checkbox-column">
                        <input type="checkbox" id="bulk-select-all" title="Select All">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Series</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Seasons</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Views</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
@forelse($series as $item)
                <tr class="hover:bg-gray-700">
                    <td class="bulk-checkbox-column">
                        <input type="checkbox" class="bulk-checkbox" value="{{ $item->id }}" title="Select series">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="{{ $item->poster_url }}" alt="{{ $item->title }}" class="w-12 h-16 rounded mr-4">
                            <div>
                                <div class="text-white font-medium">{{ $item->title }}</div>
                                <div class="text-gray-400 text-sm">{{ Str::limit($item->description, 50) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-300">{{ $item->year ?? 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-300">{{ $item->seasons_count ?? 0 }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $item->status == 'published' ? 'bg-green-600' : ($item->status == 'draft' ? 'bg-yellow-600' : 'bg-gray-600') }} text-white">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">{{ number_format($item->view_count) }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            @if($item && $item->id)
                                <a href="{{ route('admin.series.show', $item->id) }}" class="text-green-400 hover:text-green-300">
                                    Manage
                                </a>
                                <a href="{{ route('admin.series.edit', $item->id) }}" class="text-blue-400 hover:text-blue-300">
                                    Edit
                                </a>
                                <form action="{{ route('admin.series.toggle-status', $item->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-400 hover:text-yellow-300">
                                        {{ $item->status === 'published' ? 'Unpublish' : 'Publish' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.series.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this series?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300">Delete</button>
                                </form>
                            @else
                                <span class="text-gray-500 text-sm">Invalid series</span>
                            @endif
                        </div>
                    </td>
                </tr>
@empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-400">No series found</td>
                </tr>
@endforelse
            </tbody>
        </table>
    </div>

    {{-- Bulk Action Bar --}}
    <div id="bulk-action-bar" class="hidden">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="bulk-selected-badge">
                <i class="fas fa-check-circle"></i>
                <span id="bulk-selected-count">0</span> selected
            </div>
            
            <div class="bulk-actions">
                <button id="bulk-publish" class="bulk-action-btn primary">
                    <i class="fas fa-check"></i> Publish
                </button>
                <button id="bulk-draft" class="bulk-action-btn warning">
                    <i class="fas fa-file"></i> Draft
                </button>
                <button id="bulk-refresh-tmdb" class="bulk-action-btn secondary">
                    <i class="fas fa-sync"></i> Refresh TMDB
                </button>
                <button id="bulk-delete" class="bulk-action-btn danger">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button id="bulk-clear" class="bulk-action-btn ghost">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if($series->hasPages())
    <div class="mt-6">
        {{ $series->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/admin/bulk-operations.js') }}?v={{ filemtime(public_path('js/admin/bulk-operations.js')) }}" defer></script>
<script src="{{ asset('js/admin/bulk-progress-tracker.js') }}?v={{ filemtime(public_path('js/admin/bulk-progress-tracker.js')) }}" defer></script>
<script src="{{ asset('js/admin/refresh_all_tmdb_series_admin_panel.js') }}?v={{ filemtime(public_path('js/admin/refresh_all_tmdb_series_admin_panel.js')) }}" defer></script>
@endsection