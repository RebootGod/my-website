@extends('layouts.admin')

@section('title', 'Admin Logs')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Admin Activity Logs</h1>
        <div class="flex space-x-3">
            <a href="{{ route('admin.logs.export', request()->query()) }}" 
               class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition">
                <i class="fas fa-download mr-2"></i>Export CSV
            </a>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
        <form method="GET" action="{{ route('admin.logs.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Admin User Filter --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Filter by Admin</label>
                    <select name="admin_id" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        <option value="">All Admins</option>
                        @foreach($adminUsers as $admin)
                            <option value="{{ $admin->id }}" {{ request('admin_id') == $admin->id ? 'selected' : '' }}>
                                {{ $admin->username }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action Filter --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Filter by Action</label>
                    <select name="action" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        <option value="">All Actions</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $action)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Target Type Filter --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Filter by Target</label>
                    <select name="target_type" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                        <option value="">All Targets</option>
                        @foreach($targetTypes as $type)
                            <option value="{{ $type }}" {{ request('target_type') == $type ? 'selected' : '' }}>
                                {{ ucwords($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Date To --}}
                <div>
                    <label class="block text-sm font-medium mb-2">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2">
                </div>

                {{-- Filter Actions --}}
                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="{{ route('admin.logs.index') }}" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Logs Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date/Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Admin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $log->created_at->format('M d, Y') }}</span>
                                    <span class="text-gray-400 text-xs">{{ $log->created_at->format('H:i:s') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center">
                                    <span class="bg-green-600 text-white text-xs px-2 py-1 rounded mr-2">
                                        <i class="fas fa-user-shield"></i>
                                    </span>
                                    <span class="font-medium">{{ $log->admin ? $log->admin->username : 'Unknown' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded">
                                    {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($log->target_type && $log->target_id)
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ ucwords($log->target_type) }}</span>
                                        <span class="text-gray-400 text-xs">ID: {{ $log->target_id }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.logs.show', $log) }}" 
                                   class="bg-gray-600 hover:bg-gray-500 px-3 py-1 rounded text-xs transition">
                                    <i class="fas fa-eye mr-1"></i>Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-search text-4xl mb-4"></i>
                                    <h3 class="text-lg font-medium mb-2">No logs found</h3>
                                    <p>Try adjusting your filters or check back later for new activity.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="bg-gray-700 px-6 py-3">
                {{ $logs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- Statistics --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-medium mb-2">Total Logs</h3>
            <p class="text-3xl font-bold text-blue-400">{{ $logs->total() }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-medium mb-2">This Page</h3>
            <p class="text-3xl font-bold text-green-400">{{ $logs->count() }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-medium mb-2">Date Range</h3>
            <p class="text-sm text-gray-400">
                @if(request('date_from') || request('date_to'))
                    {{ request('date_from', 'All') }} - {{ request('date_to', 'All') }}
                @else
                    All Time
                @endif
            </p>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Custom pagination styles for dark theme */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .pagination .page-link {
        background-color: #374151;
        border: 1px solid #4B5563;
        color: #D1D5DB;
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .pagination .page-link:hover {
        background-color: #4B5563;
        color: white;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #10B981;
        border-color: #10B981;
        color: white;
    }
    
    .pagination .page-item.disabled .page-link {
        background-color: #1F2937;
        border-color: #374151;
        color: #6B7280;
    }
</style>
@endpush