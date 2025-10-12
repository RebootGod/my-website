@extends('layouts.admin')

@section('title', 'User Activity - Admin')

@section('content')
<div class="user-activity-modern">
    {{-- Header Section --}}
    <div class="activity-header">
        <div class="header-content">
            <div class="header-left">
                <h1 class="activity-title">User Activity</h1>
                <p class="activity-subtitle">Monitor and analyze user behavior</p>
            </div>
            <div class="header-right">
                <button class="btn-icon" id="theme-toggle" data-theme-toggle title="Toggle Theme (Alt+T)">
                    <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <button class="btn-export" onclick="window.location.href='{{ route('admin.user-activity.export', request()->query()) }}'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </button>
                <button class="btn-refresh" onclick="window.location.reload()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Total Activities</h3>
                <p class="stat-value">{{ number_format($stats['total_activities'] ?? 0) }}</p>
                <span class="stat-change stat-change-neutral">All time</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-green">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Today's Activities</h3>
                <p class="stat-value">{{ number_format($stats['today_activities'] ?? 0) }}</p>
                <span class="stat-change stat-change-up">{{ now()->format('M d, Y') }}</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-purple">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Active Users</h3>
                <p class="stat-value">{{ number_format($stats['active_users'] ?? 0) }}</p>
                <span class="stat-change stat-change-up">Last 7 days</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-icon-orange">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Peak Activity</h3>
                <p class="stat-value">{{ number_format($stats['peak_hour_count'] ?? 0) }}</p>
                <span class="stat-change stat-change-neutral">{{ $stats['peak_hour'] ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    {{-- Filters & Activity List --}}
    <div class="content-grid">
        {{-- Filters Section --}}
        <div class="filter-card">
            <div class="filter-header">
                <h3 class="filter-title">Filters</h3>
                <button onclick="resetFilters()" class="btn-text">Reset</button>
            </div>
            <div class="filter-body">
                <form method="GET" action="{{ route('admin.user-activity.index') }}" id="filterForm">
                    {{-- Period Filter --}}
                    <div class="filter-group">
                        <label class="filter-label">Time Period</label>
                        <select name="period" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="1" {{ $period == 1 ? 'selected' : '' }}>Last 24 Hours</option>
                            <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last Year</option>
                        </select>
                    </div>

                    {{-- Activity Type Filter --}}
                    <div class="filter-group">
                        <label class="filter-label">Activity Type</label>
                        <select name="activity_type" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Types</option>
                            @foreach($activityTypes as $type)
                                <option value="{{ $type }}" {{ $activityType == $type ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- User Filter --}}
                    <div class="filter-group">
                        <label class="filter-label">User</label>
                        <select name="user_id" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                    {{ $user->username }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                {{-- Quick Stats --}}
                <div class="quick-stats">
                    <h4 class="quick-stats-title">Quick Stats</h4>
                    <div class="quick-stat-item">
                        <span class="quick-stat-label">Avg per Day</span>
                        <span class="quick-stat-value">{{ number_format($stats['avg_per_day'] ?? 0) }}</span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="quick-stat-label">Most Active User</span>
                        <span class="quick-stat-value">{{ $stats['most_active_user'] ?? 'N/A' }}</span>
                    </div>
                    <div class="quick-stat-item">
                        <span class="quick-stat-label">Top Activity</span>
                        <span class="quick-stat-value">{{ ucfirst(str_replace('_', ' ', $stats['top_activity_type'] ?? 'None')) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity Timeline --}}
        <div class="activity-card">
            <div class="activity-card-header">
                <h3 class="activity-card-title">Recent Activities</h3>
                <span class="activity-count">{{ $activities->total() }} total</span>
            </div>
            <div class="activity-card-body">
                @if($activities->count() > 0)
                    <div class="activity-timeline">
                        @foreach($activities as $activity)
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    @if($activity->activity_type === 'login')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                        </svg>
                                    @elseif($activity->activity_type === 'view_movie')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    @elseif($activity->activity_type === 'search')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h4 class="timeline-title">
                                            @if($activity->user && $activity->user->id)
                                                <a href="{{ route('admin.users.show', $activity->user) }}" class="user-link">
                                                    {{ $activity->user->username ?? 'Unknown User' }}
                                                </a>
                                            @else
                                                <span class="user-link-disabled">Unknown User</span>
                                            @endif
                                        </h4>
                                        <span class="timeline-time">{{ $activity->activity_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="timeline-description">
                                        {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                                        @if($activity->description)
                                            - {{ Str::limit($activity->description, 60) }}
                                        @endif
                                    </p>
                                    @if($activity->ip_address)
                                        <span class="timeline-meta">IP: {{ $activity->ip_address }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="pagination-wrapper">
                        {{ $activities->links() }}
                    </div>
                @else
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p>No activities found for the selected filters</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Popular Content --}}
        <div class="popular-card">
            <div class="popular-header">
                <h3 class="popular-title">Popular Content</h3>
                <span class="popular-subtitle">Most viewed this period</span>
            </div>
            <div class="popular-body">
                @if(isset($popularContent) && count($popularContent) > 0)
                    <div class="popular-list">
                        @foreach($popularContent as $index => $content)
                            <div class="popular-item">
                                <div class="popular-rank">{{ $index + 1 }}</div>
                                <div class="popular-info">
                                    <h4 class="popular-item-title">{{ Str::limit($content['title'] ?? 'Unknown', 30) }}</h4>
                                    <p class="popular-item-meta">{{ number_format($content['views'] ?? 0) }} views</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state-small">
                        <p>No popular content data</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Pass data to JavaScript --}}
<script>
    window.activityData = {
        period: {{ $period }},
        activityType: '{{ $activityType ?? '' }}',
        userId: '{{ $userId ?? '' }}'
    };

    function resetFilters() {
        window.location.href = '{{ route('admin.user-activity.index') }}';
    }
</script>

{{-- Include Styles & Scripts --}}
<link rel="stylesheet" href="{{ asset('css/admin/user-activity-v2.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/dashboard-v2_2.css') }}">
<script src="{{ asset('js/admin/theme-switcher.js') }}"></script>
<script src="{{ asset('js/admin/user-activity-v2.js') }}"></script>
@endsection
