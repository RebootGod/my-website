@extends('layouts.admin')

@section('title', 'User Activity')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/user-activity-v3.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/user-activity-v3_2.css') }}">
@endsection

@section('content')
<div class="activity-container">
    <!-- Header -->
    <div class="activity-header">
        <div>
            <h1 class="activity-title">User Activity</h1>
            <p class="activity-subtitle">Monitor and analyze user behavior across your platform</p>
        </div>
        <div class="activity-actions">
            <button class="btn-action" id="autoRefreshToggle">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Auto-refresh
            </button>
            <button class="btn-primary" id="exportBtn">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a 3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export CSV
            </button>
            <button class="btn-icon" data-theme-toggle>
                <svg class="icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg class="icon-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon gradient-blue">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Total Activities</h3>
                <div class="stat-value">{{ number_format($stats['total_activities'] ?? 0) }}</div>
                <p class="stat-detail">All time</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon gradient-green">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Today's Activities</h3>
                <div class="stat-value">{{ number_format($stats['today_activities'] ?? 0) }}</div>
                <p class="stat-detail">{{ now()->format('M d, Y') }}</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon gradient-purple">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Active Users</h3>
                <div class="stat-value">{{ number_format(count($stats['most_active_users'] ?? [])) }}</div>
                <p class="stat-detail">Last {{ $period }} days</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon gradient-orange">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-label">Peak Activity</h3>
                <div class="stat-value">
                    @php
                        $dailyTrend = $stats['daily_trend'] ?? [];
                        $peakDay = 0;
                        if (!empty($dailyTrend) && isset($dailyTrend['data']) && is_array($dailyTrend['data'])) {
                            $peakDay = !empty($dailyTrend['data']) ? max($dailyTrend['data']) : 0;
                        }
                    @endphp
                    {{ number_format($peakDay) }}
                </div>
                <p class="stat-detail">Highest in {{ $period }} days</p>
            </div>
        </div>
    </div>

    <!-- Activity Chart -->
    <div class="chart-section">
        <div class="chart-header">
            <div>
                <h2 class="chart-title">Activity Trend</h2>
                <p class="chart-subtitle">Daily user activities over the last {{ $period }} days</p>
            </div>
            <div class="chart-legend">
                <span class="legend-item">
                    <span class="legend-color" style="background: var(--gradient-blue)"></span>
                    Activities
                </span>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Filters Sidebar -->
        <div class="filters-section">
            <div class="filters-header">
                <h3 class="filters-title">Filters</h3>
                <button class="btn-text" onclick="resetFilters()">Reset</button>
            </div>

            <form method="GET" action="{{ route('admin.user-activity.index') }}" id="filterForm">
                <!-- Time Period -->
                <div class="filter-group">
                    <label class="filter-label">Time Period</label>
                    <select name="period" class="filter-input" onchange="this.form.submit()">
                        <option value="1" {{ $period == 1 ? 'selected' : '' }}>Last 24 Hours</option>
                        <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 Days</option>
                        <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last Year</option>
                    </select>
                </div>

                <!-- Activity Type -->
                <div class="filter-group">
                    <label class="filter-label">Activity Type</label>
                    <select name="activity_type" class="filter-input" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        @foreach($activityTypes as $type)
                            <option value="{{ $type }}" {{ $activityType == $type ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- User Filter -->
                <div class="filter-group">
                    <label class="filter-label">User</label>
                    <select name="user_id" class="filter-input" onchange="this.form.submit()">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                {{ $user->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <h4 class="quick-stats-title">Quick Stats</h4>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Avg per Day</span>
                    <span class="quick-stat-value">
                        {{ number_format(($stats['total_activities'] ?? 0) / max($period, 1)) }}
                    </span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Most Active</span>
                    <span class="quick-stat-value">
                        @if(isset($stats['most_active_users'][0]))
                            {{ $stats['most_active_users'][0]->user->username ?? 'N/A' }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
                <div class="quick-stat-item">
                    <span class="quick-stat-label">Top Activity</span>
                    <span class="quick-stat-value">
                        @php
                            $breakdown = $stats['activity_breakdown'] ?? [];
                            $topActivity = !empty($breakdown) ? array_key_first($breakdown) : 'None';
                        @endphp
                        {{ ucwords(str_replace('_', ' ', $topActivity)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="timeline-section">
            <div class="timeline-header">
                <h3 class="timeline-title">Recent Activities</h3>
                <span class="timeline-count">{{ $activities->total() }} total</span>
            </div>

            <div class="timeline-content" id="timelineContent">
                @forelse($activities as $activity)
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <div class="timeline-icon {{ $activity->activity_type }}">
                                @if($activity->activity_type == 'login')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                @elseif($activity->activity_type == 'view_movie')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                @elseif($activity->activity_type == 'view_series')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                @elseif($activity->activity_type == 'search')
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                @else
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                @endif
                            </div>
                        </div>
                        <div class="timeline-details">
                            <div class="timeline-user">
                                @if($activity->user && $activity->user->id)
                                    <a href="{{ route('admin.users.show', $activity->user) }}" class="user-link">
                                        {{ $activity->user->username }}
                                    </a>
                                @else
                                    <span class="user-guest">Guest User</span>
                                @endif
                                <span class="timeline-time">{{ $activity->activity_at->diffForHumans() }}</span>
                            </div>
                            <p class="timeline-description">{{ $activity->description }}</p>
                            @if($activity->ip_address)
                                <span class="timeline-meta">IP: {{ $activity->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="empty-title">No activities found</h3>
                        <p class="empty-text">Try adjusting your filters or check back later</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($activities->hasPages())
                <div class="pagination">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>

        <!-- Popular Content Sidebar -->
        <div class="popular-section">
            <div class="popular-header">
                <h3 class="popular-title">Popular Content</h3>
                <span class="popular-period">Last {{ $period }} days</span>
            </div>

            <!-- Popular Movies -->
            @if(isset($popularContent['movies']) && count($popularContent['movies']) > 0)
                <div class="popular-category">
                    <h4 class="category-title">Movies</h4>
                    <div class="popular-list">
                        @foreach($popularContent['movies']->take(5) as $movie)
                            <div class="popular-item">
                                <span class="popular-rank">{{ $loop->iteration }}</span>
                                <div class="popular-info">
                                    <h5 class="popular-name">{{ Str::limit(trim($movie->movie_title, '"'), 25) }}</h5>
                                    <span class="popular-views">{{ number_format($movie->watch_count) }} views</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Popular Series -->
            @if(isset($popularContent['series']) && count($popularContent['series']) > 0)
                <div class="popular-category">
                    <h4 class="category-title">Series</h4>
                    <div class="popular-list">
                        @foreach($popularContent['series']->take(5) as $series)
                            <div class="popular-item">
                                <span class="popular-rank">{{ $loop->iteration }}</span>
                                <div class="popular-info">
                                    <h5 class="popular-name">{{ Str::limit(trim($series->series_title, '"'), 25) }}</h5>
                                    <span class="popular-views">{{ number_format($series->watch_count) }} views</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(
                (empty($popularContent['movies']) || count($popularContent['movies']) == 0) && 
                (empty($popularContent['series']) || count($popularContent['series']) == 0)
            )
                <div class="empty-state-small">
                    <svg class="empty-icon-small" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                    </svg>
                    <p class="empty-text-small">No popular content in this period</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/admin/theme-switcher.js') }}"></script>
    <script src="{{ asset('js/admin/user-activity-v3.js') }}"></script>
    
    <script>
        // Pass data to JavaScript
        window.activityData = {
            dailyTrend: @json($stats['daily_trend'] ?? []),
            period: {{ $period }},
            currentUrl: "{{ url()->current() }}"
        };
    </script>
@endsection
