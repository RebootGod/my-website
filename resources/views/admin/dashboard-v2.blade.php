@extends('layouts.admin')

@section('title', 'Dashboard - Admin')

@section('content')
<div class="dashboard-modern">
    {{-- Header Section --}}
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <h1 class="dashboard-title">Dashboard Overview</h1>
                <p class="dashboard-subtitle">Welcome back, {{ auth()->user()->username }}</p>
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
                <button class="btn-refresh" onclick="window.location.reload()" title="Refresh Dashboard">
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
        @php
            $stats = app(App\Services\AdminStatsService::class)->getDashboardStats();
        @endphp

        {{-- Total Movies --}}
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Total Movies</h3>
                <p class="stat-value">{{ number_format($stats['total_movies']) }}</p>
                @if($stats['movies_this_month'] > 0)
                    <span class="stat-change stat-change-up">+{{ $stats['movies_this_month'] }} this month</span>
                @endif
            </div>
        </div>

        {{-- Total Series --}}
        <div class="stat-card">
            <div class="stat-icon stat-icon-purple">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Total Series</h3>
                <p class="stat-value">{{ number_format($stats['total_series']) }}</p>
                @if($stats['series_this_month'] > 0)
                    <span class="stat-change stat-change-up">+{{ $stats['series_this_month'] }} this month</span>
                @endif
            </div>
        </div>

        {{-- Total Users --}}
        <div class="stat-card">
            <div class="stat-icon stat-icon-green">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Total Users</h3>
                <p class="stat-value">{{ number_format($stats['total_users']) }}</p>
                @if($stats['users_this_month'] > 0)
                    <span class="stat-change stat-change-up">+{{ $stats['users_this_month'] }} new users</span>
                @endif
            </div>
        </div>

        {{-- Storage Usage --}}
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Storage Used</h3>
                <p class="stat-value" id="storage-value">Calculating...</p>
                <span class="stat-change stat-change-neutral" id="storage-percent">Loading...</span>
            </div>
        </div>
    </div>

    {{-- Charts Grid --}}
    <div class="charts-grid">
        {{-- Content Growth Chart --}}
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Content Growth</h3>
                <p class="chart-subtitle">Last 30 days</p>
            </div>
            <div class="chart-body">
                <canvas id="contentGrowthChart"></canvas>
            </div>
        </div>

        {{-- User Activity Chart --}}
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">User Activity</h3>
                <p class="chart-subtitle">Active users breakdown</p>
            </div>
            <div class="chart-body">
                <canvas id="userActivityChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Bottom Grid --}}
    <div class="bottom-grid">
        {{-- Top Content --}}
        <div class="content-card">
            <div class="content-header">
                <h3 class="content-title">Top Performing Content</h3>
                <a href="{{ route('admin.reports.index') }}" class="btn-link">View All</a>
            </div>
            <div class="content-body">
                @php
                    $topContent = app(App\Services\AdminStatsService::class)->getTopPerformingContent(5);
                @endphp
                
                @if(count($topContent) > 0)
                    <div class="content-list">
                        @foreach($topContent as $item)
                            <div class="content-item">
                                <div class="content-item-left">
                                    <div class="content-item-icon">
                                        @if($item['type'] === 'Movie')
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="content-item-info">
                                        <h4 class="content-item-title">{{ Str::limit($item['title'], 40) }}</h4>
                                        <p class="content-item-meta">{{ $item['type'] }} • {{ number_format($item['views']) }} views</p>
                                    </div>
                                </div>
                                <div class="content-item-right">
                                    <span class="content-item-rating">⭐ {{ number_format($item['rating'], 1) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <p>No content data available</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="content-card">
            <div class="content-header">
                <h3 class="content-title">Recent Activity</h3>
                <a href="{{ route('admin.user-activity.index') }}" class="btn-link">View All</a>
            </div>
            <div class="content-body">
                @php
                    $activities = app(App\Services\AdminStatsService::class)->getRecentActivity(8);
                @endphp
                
                @if($activities && $activities->count() > 0)
                    <div class="activity-list">
                        @foreach($activities as $activity)
                            <div class="activity-item">
                                <div class="activity-icon">
                                    @if($activity['action'] === 'created')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    @elseif($activity['action'] === 'registered')
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="activity-content">
                                    <p class="activity-text">
                                        @if($activity['action'] === 'created')
                                            <strong>{{ $activity['subject_type'] }}</strong> "{{ Str::limit($activity['subject_title'], 30) }}" was added
                                        @elseif($activity['action'] === 'registered')
                                            <strong>{{ $activity['subject_title'] }}</strong> registered
                                        @endif
                                    </p>
                                    <span class="activity-time">{{ $activity['created_at']->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <p>No recent activity</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="content-card">
            <div class="content-header">
                <h3 class="content-title">Quick Actions</h3>
            </div>
            <div class="content-body">
                <div class="quick-actions">
                    <a href="{{ route('admin.movies.create') }}" class="quick-action-btn quick-action-blue">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Movie</span>
                    </a>
                    <a href="{{ route('admin.series.create') }}" class="quick-action-btn quick-action-purple">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Series</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="quick-action-btn quick-action-green">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Manage Users</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="quick-action-btn quick-action-orange">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span>View Reports</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pass data to JavaScript --}}
<script>
    window.dashboardData = {
        contentGrowth: @json(app(App\Services\AdminStatsService::class)->getContentGrowthStats(30)),
        userActivity: @json(app(App\Services\AdminStatsService::class)->getUserActivityStats()),
        storage: {
            used: {{ $stats['storage_used'] ?? 0 }},
            total: {{ $stats['storage_total'] ?? 0 }}
        }
    };
</script>

{{-- Include Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

{{-- Include Dashboard Scripts --}}
<link rel="stylesheet" href="{{ asset('css/admin/dashboard-v2.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/dashboard-v2_2.css') }}">
<script src="{{ asset('js/admin/theme-switcher.js') }}"></script>
<script src="{{ asset('js/admin/dashboard-v2.js') }}"></script>
@endsection
