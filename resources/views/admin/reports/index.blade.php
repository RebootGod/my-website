{{-- ======================================== --}}
{{-- SIMPLE ADMIN REPORTS VIEW --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/reports/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Broken Link Reports - Admin')

@section('content')
<div class="container mx-auto">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Broken Link Reports</h1>
        <a href="{{ route('admin.dashboard') }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            ← Back to Dashboard
        </a>
    </div>

    {{-- Reports Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Content</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Issue</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($reports as $report)
                <tr class="hover:bg-gray-700 transition">
                    <td class="px-4 py-3">
                        @if($report->movie)
                            <a href="{{ route('admin.movies.show', $report->movie) }}"
                               class="text-blue-400 hover:text-blue-300">
                                🎬 {{ $report->movie->title }}
                            </a>
                        @elseif($report->series)
                            <div class="text-purple-400">
                                📺 {{ $report->series->title }}
                                @if($report->episode)
                                    <br><small class="text-gray-400">Episode {{ $report->episode->episode_number }}: {{ $report->episode->name }}</small>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-500">Unknown Content</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-medium">{{ $report->getIssueTypeLabel() }}</p>
                            @if($report->description)
                            <p class="text-xs text-gray-400 mt-1">{{ Str::limit($report->description, 50) }}</p>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        {{ $report->user->username }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        {{ $report->created_at->format('M d, Y H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-{{ $report->getStatusColor() }}-500 text-white">
                            {{ ucfirst($report->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex space-x-2">
                            @if($report->status === 'pending')
                            <form action="{{ route('admin.reports.update', $report) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="fixed">
                                <button type="submit" class="text-green-400 hover:text-green-300" title="Mark Fixed">
                                    ✅
                                </button>
                            </form>
                            <form action="{{ route('admin.reports.update', $report) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="dismissed">
                                <button type="submit" class="text-red-400 hover:text-red-300" title="Dismiss">
                                    ❌
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('admin.movies.sources.index', $report->movie) }}" 
                               class="text-yellow-400 hover:text-yellow-300" title="Manage Sources">
                                🎬
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                        No reports yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($reports->hasPages())
    <div class="mt-6">
        {{ $reports->links() }}
    </div>
    @endif
</div>
@endsection