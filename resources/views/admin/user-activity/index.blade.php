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
</script>
@endpush
@endsection