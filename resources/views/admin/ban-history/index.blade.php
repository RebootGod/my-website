{{-- ======================================== --}}
{{-- BAN HISTORY TIMELINE VIEW --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/ban-history/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Ban History - Admin')

@section('content')
<div class="container mx-auto">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold">Ban & Suspension History</h1>
            <p class="text-gray-400 mt-1">Complete timeline of all administrative actions</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('admin.ban-history.export', request()->query()) }}" 
               class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition flex items-center">
                📥 Export CSV
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Total Events</h3>
            <p class="text-2xl font-bold text-white">{{ number_format($stats['total_events']) }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Bans</h3>
            <p class="text-2xl font-bold text-red-400">{{ number_format($stats['bans_count']) }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Suspensions</h3>
            <p class="text-2xl font-bold text-yellow-400">{{ number_format($stats['suspensions_count']) }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Activations</h3>
            <p class="text-2xl font-bold text-green-400">{{ number_format($stats['unbans_count'] + $stats['activations_count']) }}</p>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <div class="flex justify-between items-center">
            <div class="flex space-x-6 text-sm">
                <div>
                    <span class="text-gray-400">Today:</span>
                    <span class="text-white font-semibold">{{ $stats['today_events'] }}</span>
                </div>
                <div>
                    <span class="text-gray-400">This Week:</span>
                    <span class="text-white font-semibold">{{ $stats['week_events'] }}</span>
                </div>
                <div>
                    <span class="text-gray-400">This Month:</span>
                    <span class="text-white font-semibold">{{ $stats['month_events'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.ban-history.index') }}" class="flex flex-wrap gap-3">
            {{-- Action Type Filter --}}
            <select name="action_type" class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                <option value="">All Actions</option>
                <option value="ban" {{ request('action_type') == 'ban' ? 'selected' : '' }}>🔴 Bans</option>
                <option value="unban" {{ request('action_type') == 'unban' ? 'selected' : '' }}>🟢 Unbans</option>
                <option value="suspend" {{ request('action_type') == 'suspend' ? 'selected' : '' }}>🟡 Suspensions</option>
                <option value="activate" {{ request('action_type') == 'activate' ? 'selected' : '' }}>🔵 Activations</option>
            </select>
            
            {{-- Search by Username/Email --}}
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Search username or email..." 
                   class="bg-gray-700 text-white px-4 py-2 rounded-lg flex-1 min-w-[200px]">
            
            {{-- Date From --}}
            <input type="date" 
                   name="date_from" 
                   value="{{ request('date_from') }}"
                   placeholder="From Date"
                   class="bg-gray-700 text-white px-4 py-2 rounded-lg">
            
            {{-- Date To --}}
            <input type="date" 
                   name="date_to" 
                   value="{{ request('date_to') }}"
                   placeholder="To Date"
                   class="bg-gray-700 text-white px-4 py-2 rounded-lg">
            
            {{-- Apply Filters --}}
            <button type="submit" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">
                🔍 Filter
            </button>
            
            {{-- Clear Filters --}}
            @if(request()->hasAny(['action_type', 'search', 'date_from', 'date_to']))
            <a href="{{ route('admin.ban-history.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                ✖️ Clear
            </a>
            @endif
        </form>
    </div>

    {{-- Timeline --}}
    <div class="bg-gray-800 rounded-lg p-6">
        @forelse($histories as $history)
        <div class="border-l-4 {{ $history->badge_color == 'red' ? 'border-red-500' : ($history->badge_color == 'yellow' ? 'border-yellow-500' : ($history->badge_color == 'green' ? 'border-green-500' : 'border-blue-500')) }} pl-6 pb-8 relative">
            {{-- Timeline Dot --}}
            <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full {{ $history->badge_color == 'red' ? 'bg-red-500' : ($history->badge_color == 'yellow' ? 'bg-yellow-500' : ($history->badge_color == 'green' ? 'bg-green-500' : 'bg-blue-500')) }}"></div>
            
            {{-- Event Content --}}
            <div class="bg-gray-700 rounded-lg p-4 hover:bg-gray-600 transition">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center space-x-3">
                        {{-- Action Badge --}}
                        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $history->badge_color == 'red' ? 'bg-red-500' : ($history->badge_color == 'yellow' ? 'bg-yellow-500' : ($history->badge_color == 'green' ? 'bg-green-500' : 'bg-blue-500')) }} text-white">
                            {{ $history->action_label }}
                        </span>
                        
                        {{-- User Info --}}
                        <div>
                            <a href="{{ route('admin.users.show', $history->user_id) }}" 
                               class="text-white font-semibold hover:underline">
                                {{ $history->user->username ?? 'Deleted User' }}
                            </a>
                            <span class="text-gray-400 text-sm">
                                ({{ $history->user->email ?? 'N/A' }})
                            </span>
                        </div>
                    </div>
                    
                    {{-- Timestamp --}}
                    <span class="text-gray-400 text-sm">
                        {{ $history->created_at->diffForHumans() }}
                    </span>
                </div>
                
                {{-- Details Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                    {{-- Reason --}}
                    <div>
                        <span class="text-gray-400 text-xs">Reason:</span>
                        <p class="text-white text-sm">{{ $history->reason }}</p>
                    </div>
                    
                    {{-- Duration --}}
                    @if($history->duration)
                    <div>
                        <span class="text-gray-400 text-xs">Duration:</span>
                        <p class="text-white text-sm">{{ $history->duration_text }}</p>
                    </div>
                    @endif
                    
                    {{-- Performed By --}}
                    <div>
                        <span class="text-gray-400 text-xs">Performed By:</span>
                        <p class="text-white text-sm">
                            {{ $history->admin->username ?? 'System' }}
                            @if($history->admin_ip)
                            <span class="text-gray-500 text-xs">({{ $history->admin_ip }})</span>
                            @endif
                        </p>
                    </div>
                </div>
                
                {{-- Metadata (if exists) --}}
                @if($history->metadata && is_array($history->metadata))
                <div class="mt-3 pt-3 border-t border-gray-600">
                    <span class="text-gray-400 text-xs">Additional Info:</span>
                    <div class="text-gray-300 text-xs mt-1">
                        @foreach($history->metadata as $key => $value)
                        <span class="mr-3">
                            <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-12">
            <div class="text-gray-400 text-5xl mb-4">📋</div>
            <p class="text-gray-400 text-lg">No ban history found</p>
            <p class="text-gray-500 text-sm mt-2">Try adjusting your filters</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($histories->hasPages())
    <div class="mt-6">
        {{ $histories->links('vendor.pagination.tailwind') }}
    </div>
    @endif
</div>

@section('scripts')
<script>
// Auto-refresh every 60 seconds if on first page
@if(request()->input('page', 1) == 1)
setTimeout(function() {
    location.reload();
}, 60000);
@endif
</script>
@endsection
@endsection
