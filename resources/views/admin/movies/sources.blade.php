{{-- ======================================== --}}
{{-- SIMPLE ADMIN MOVIE SOURCES MANAGEMENT --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/movies/sources.blade.php --}}

@extends('layouts.admin')

@section('title', 'Manage Sources - ' . $movie->title)

@section('content')
<div class="container mx-auto">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Manage Sources</h1>
            <p class="text-gray-400">{{ $movie->title }}</p>
        </div>
        <a href="{{ route('admin.movies.edit', $movie) }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            ← Back to Movie
        </a>
    </div>

    {{-- Add Source Form --}}
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Add New Source</h3>
        <form action="{{ route('admin.movies.sources.store', $movie) }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="text" name="source_name" placeholder="Source Name" required
                       class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                
                <select name="quality" required class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                    <option value="">Select Quality</option>
                    <option value="CAM">CAM</option>
                    <option value="TS">TS</option>
                    <option value="HD" selected>HD</option>
                    <option value="FHD">FHD</option>
                    <option value="4K">4K</option>
                </select>
                
                <input type="number" name="priority" placeholder="Priority (0-999)" value="0"
                       class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                    + Add Source
                </button>
            </div>
            
            <div class="mt-4">
                <input type="url" name="embed_url" placeholder="Embed URL (https://...)" required
                       class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg">
            </div>
        </form>
    </div>

    {{-- Sources Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Priority</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Source Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Quality</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Reports</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($sources as $source)
                <tr class="hover:bg-gray-700 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono">{{ $source->priority }}</span>
                    </td>
                    <td class="px-4 py-3">
                        {{ $source->source_name }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded bg-gray-600">
                            {{ $source->quality }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($source->report_count > 0)
                            <span class="text-yellow-400">{{ $source->report_count }}</span>
                        @else
                            <span class="text-gray-500">0</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($source->is_active)
                            <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-red-500 text-white">Inactive</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex space-x-2">
                            <a href="{{ $source->embed_url }}" target="_blank" 
                               class="text-blue-400 hover:text-blue-300" title="Test">
                                🔗
                            </a>
                            
                            <form action="{{ route('admin.movies.sources.toggle', [$movie, $source]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="text-orange-400 hover:text-orange-300"
                                        title="{{ $source->is_active ? 'Deactivate' : 'Activate' }}">
                                    {{ $source->is_active ? '🔒' : '🔓' }}
                                </button>
                            </form>
                            
                            @if($source->report_count > 0)
                            <form action="{{ route('admin.movies.sources.reset-reports', [$movie, $source]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="text-green-400 hover:text-green-300"
                                        title="Reset Reports">
                                    🔄
                                </button>
                            </form>
                            @endif
                            
                            <form action="{{ route('admin.movies.sources.destroy', [$movie, $source]) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="text-red-400 hover:text-red-300"
                                        title="Delete"
                                        onclick="return confirm('Delete this source?')">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                        No sources added yet. Add one above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Quick Import from Main Embed --}}
    @if($movie->embed_url)
    <div class="bg-gray-800 rounded-lg p-4 mt-6">
        <form action="{{ route('admin.movies.sources.migrate', $movie) }}" method="POST" class="flex items-center justify-between">
            @csrf
            <span class="text-gray-400">Import main embed URL as a source?</span>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                Import Main Embed
            </button>
        </form>
    </div>
    @endif
</div>
@endsection