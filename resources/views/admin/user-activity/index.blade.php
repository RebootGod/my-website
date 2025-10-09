@extends('layouts.admin')

@section('title', 'User Activity - Admin')

@section('content')
<div class="user-activity-dashboard">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-white">User Activity Dashboard</h1>
        <div class="flex gap-3">
            <button onclick="refreshDashboard()" class="btn-modern">
                <i class="fas fa-sync-alt"></i>
                Refresh
            </button>
            <a href="{{ route('admin.user-activity.export', request()->query()) }}" class="btn-modern outline">
                <i class="fas fa-download"></i>
                Export CSV
            </a>
            @if(auth()->user()->hasRole('super_admin'))
            <button onclick="showCleanupModal()" class="btn-modern bg-red-500 hover:bg-red-600">
                <i class="fas fa-trash-alt"></i>
                Cleanup Old Data
            </button>
            @endif
        </div>
    </div>

    {{-- Activity Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Activities</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($stats['total_activities']) }}</p>
                    </div>
                    <div class="activity-icon bg-blue-500">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Today's Activities</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($stats['today_activities']) }}</p>
                    </div>
                    <div class="activity-icon bg-green-500">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">This Week</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($stats['week_activities']) }}</p>
                    </div>
                    <div class="activity-icon bg-yellow-500">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">This Month</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($stats['month_activities']) }}</p>
                    </div>
                    <div class="activity-icon bg-purple-500">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Activity Trend Chart --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Activity Trend ({{ $period }} days)</h3>
                <div class="chart-actions">
                    <button class="chart-toggle {{ $period == 7 ? 'active' : '' }}" data-period="7">7 Days</button>
                    <button class="chart-toggle {{ $period == 30 ? 'active' : '' }}" data-period="30">30 Days</button>
                    <button class="chart-toggle {{ $period == 90 ? 'active' : '' }}" data-period="90">90 Days</button>
                </div>
            </div>
            <div class="admin-card-body">
                <div class="chart-container">
                    <canvas id="activityTrendChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Activity Types Breakdown --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Activity Types</h3>
            </div>
            <div class="admin-card-body">
                <div class="chart-container">
                    <canvas id="activityTypesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters and Activity Feed --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Activity Feed --}}
        <div class="lg:col-span-2">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Recent Activities</h3>
                </div>
                <div class="admin-card-body">
                    {{-- Filters --}}
                    <form method="GET" class="activity-filters mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <select name="activity_type" class="admin-input">
                                    <option value="">All Activity Types</option>
                                    @foreach($activityTypes as $type)
                                        <option value="{{ $type }}" {{ $activityType == $type ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <select name="user_id" class="admin-input">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                            {{ $user->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn-modern w-full">
                                    <i class="fas fa-filter"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- Activity List --}}
                    <div class="activity-list">
                        @forelse($activities as $activity)
                            <div class="activity-item">
                                <div class="activity-icon activity-{{ $activity->color_class }}">
                                    <i class="{{ $activity->icon }}"></i>
                                </div>

                                <div class="activity-content">
                                    <div class="activity-message">
                                        {{ $activity->description }}
                                    </div>
                                    <div class="activity-meta">
                                        <span class="activity-user">
                                            @if($activity->user)
                                                <a href="{{ route('admin.user-activity.show', $activity->user) }}" class="text-blue-400 hover:text-blue-300">
                                                    {{ $activity->user->username }}
                                                </a>
                                            @else
                                                Unknown User
                                            @endif
                                        </span>
                                        <span class="activity-time">
                                            {{ $activity->activity_at->diffForHumans() }}
                                        </span>
                                        <span class="activity-ip">
                                            {{ $activity->ip_address }}
                                        </span>
                                    </div>
                                </div>

                                <div class="activity-badge">
                                    <span class="badge {{ $activity->color_class }}">
                                        {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="activity-empty">
                                <div class="empty-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <p class="empty-message">No activities found</p>
                                <p class="empty-description">Adjust your filters to see more activities</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($activities->hasPages())
                        <div class="mt-6">
                            {{ $activities->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar Stats --}}
        <div class="space-y-6">
            {{-- Most Active Users --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Most Active Users</h3>
                </div>
                <div class="admin-card-body">
                    <div class="space-y-3">
                        @forelse($stats['most_active_users'] as $activeUser)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    @if($activeUser->user)
                                        <div class="user-avatar">
                                            {{ substr($activeUser->user->username, 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <a href="{{ route('admin.user-activity.show', $activeUser->user) }}" class="text-white hover:text-blue-400">
                                                {{ $activeUser->user->username }}
                                            </a>
                                        </div>
                                    @else
                                        <div class="user-avatar">
                                            ?
                                        </div>
                                        <div class="ml-3">
                                            <span class="text-gray-400">Anonymous/System</span>
                                        </div>
                                    @endif
                                </div>
                                <span class="text-gray-400 text-sm">
                                    {{ $activeUser->activity_count }} activities
                                </span>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm">No user activity data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Popular Content --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Popular Content</h3>
                </div>
                <div class="admin-card-body">
                    <div class="content-tabs">
                        <button class="content-tab active" onclick="showContentTab('movies')">Movies</button>
                        <button class="content-tab" onclick="showContentTab('series')">Series</button>
                    </div>

                    <div id="movies-content" class="content-list active">
                        @forelse($popularContent['movies'] as $movie)
                            <div class="content-item">
                                <div class="content-info">
                                    <div class="content-title">{{ $movie->movie_title }}</div>
                                    <div class="content-meta">{{ $movie->watch_count }} views</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm">No movie data</p>
                        @endforelse
                    </div>

                    <div id="series-content" class="content-list">
                        @forelse($popularContent['series'] as $series)
                            <div class="content-item">
                                <div class="content-info">
                                    <div class="content-title">{{ $series->series_title }}</div>
                                    <div class="content-meta">{{ $series->watch_count }} views</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 text-sm">No series data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Hourly Activity --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Today's Hourly Activity</h3>
                </div>
                <div class="admin-card-body">
                    <div class="chart-container">
                        <canvas id="hourlyActivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cleanup Confirmation Modal --}}
<div id="cleanupModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white">Cleanup Old Activities</h3>
            <button onclick="closeCleanupModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="cleanupModalContent">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                <p class="text-gray-300">Loading data...</p>
            </div>
        </div>
        
        <div id="cleanupModalActions" class="hidden flex justify-end gap-3 mt-6">
            <button onclick="closeCleanupModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                Cancel
            </button>
            <button onclick="performCleanup()" id="confirmCleanupBtn" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition">
                <i class="fas fa-trash-alt mr-2"></i>
                Cleanup Now
            </button>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/user-activity.css') }}">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="{{ asset('js/admin/user-activity.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dashboard dengan data dari backend
        initializeUserActivityDashboard({
            daily_trend: @json($stats['daily_trend'] ?? ['labels' => [], 'data' => []]),
            activity_breakdown: @json($stats['activity_breakdown'] ?? []),
            hourly_pattern: @json($stats['hourly_pattern'] ?? ['labels' => [], 'data' => []])
        });
    });

    // Cleanup Modal Functions
    function showCleanupModal() {
        const modal = document.getElementById('cleanupModal');
        modal.classList.remove('hidden');
        
        // Fetch old activities count
        fetch('{{ route("admin.user-activity.old-count") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const content = document.getElementById('cleanupModalContent');
                    const actions = document.getElementById('cleanupModalActions');
                    
                    if (data.count === 0) {
                        content.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                                <p class="text-gray-300 text-lg mb-2">All Clean!</p>
                                <p class="text-gray-400 text-sm">No old records to cleanup.</p>
                            </div>
                        `;
                        actions.classList.add('hidden');
                    } else {
                        content.innerHTML = `
                            <div class="mb-6">
                                <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 mb-4">
                                    <div class="flex items-start">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
                                        <div>
                                            <p class="text-yellow-300 font-semibold mb-1">Warning</p>
                                            <p class="text-gray-300 text-sm">This action will permanently delete old activity records.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Records to delete:</span>
                                        <span class="text-red-400 font-bold text-lg">${data.count.toLocaleString()}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Cutoff date:</span>
                                        <span class="text-white">${new Date(data.cutoff_date).toLocaleString()}</span>
                                    </div>
                                    ${data.oldest_date ? `
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Oldest record:</span>
                                        <span class="text-white">${new Date(data.oldest_date).toLocaleString()}</span>
                                    </div>
                                    ` : ''}
                                </div>

                                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mt-4">
                                    <div class="flex items-start">
                                        <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                                        <div class="text-sm text-gray-300">
                                            <p class="font-semibold text-blue-300 mb-1">Backup & Delete</p>
                                            <p>Records will be backed up to a JSON file before deletion. You can keep the last 7 days of activities.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        actions.classList.remove('hidden');
                    }
                } else {
                    showError(data.message || 'Failed to load data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to load cleanup data');
            });
    }

    function closeCleanupModal() {
        const modal = document.getElementById('cleanupModal');
        modal.classList.add('hidden');
    }

    function performCleanup() {
        const confirmBtn = document.getElementById('confirmCleanupBtn');
        const originalText = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        fetch('{{ route("admin.user-activity.cleanup-old") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const content = document.getElementById('cleanupModalContent');
                const actions = document.getElementById('cleanupModalActions');
                
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                        <p class="text-white text-lg font-semibold mb-2">Cleanup Successful!</p>
                        <div class="text-left bg-gray-700/50 rounded-lg p-4 mt-4">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Records deleted:</span>
                                    <span class="text-green-400 font-bold">${data.records_deleted.toLocaleString()}</span>
                                </div>
                                ${data.backup_file ? `
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Backup file:</span>
                                    <span class="text-blue-400 text-xs">${data.backup_file}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        <p class="text-gray-400 text-sm mt-4">Page will reload in 3 seconds...</p>
                    </div>
                `;
                actions.classList.add('hidden');
                
                // Reload page after 3 seconds
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                showError(data.message || 'Cleanup failed');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to perform cleanup');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        });
    }

    function showError(message) {
        const content = document.getElementById('cleanupModalContent');
        content.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-circle text-5xl text-red-500 mb-4"></i>
                <p class="text-white text-lg font-semibold mb-2">Error</p>
                <p class="text-gray-400">${message}</p>
            </div>
        `;
        document.getElementById('cleanupModalActions').classList.add('hidden');
    }
</script>
@endpush
@endsection