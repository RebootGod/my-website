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
                                    <div class="user-avatar">
                                        {{ substr($activeUser->user->username, 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <a href="{{ route('admin.user-activity.show', $activeUser->user) }}" class="text-white hover:text-blue-400">
                                            {{ $activeUser->user->username }}
                                        </a>
                                    </div>
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
<style>
.user-activity-dashboard {
    padding: 1rem;
}

.activity-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.chart-actions {
    display: flex;
    gap: 0.5rem;
}

.chart-toggle {
    padding: 0.375rem 0.75rem;
    border: 1px solid #374151;
    background: transparent;
    color: #9ca3af;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    transition: all 0.2s;
    cursor: pointer;
}

.chart-toggle.active {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
}

.chart-toggle:hover {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
    border-color: #3b82f6;
}

.chart-container {
    position: relative;
    height: 300px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 600px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--admin-bg-light);
    border: 1px solid var(--admin-border);
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.activity-item:hover {
    background: var(--admin-bg-dark);
    border-color: var(--admin-primary);
    transform: translateY(-1px);
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-message {
    font-size: 0.875rem;
    color: var(--admin-text-white);
    line-height: 1.4;
    margin-bottom: 0.25rem;
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: var(--admin-text-muted);
    flex-wrap: wrap;
}

.activity-badge {
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.activity-empty {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: var(--admin-bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--admin-text-muted);
}

.empty-message {
    font-size: 1rem;
    font-weight: 500;
    color: var(--admin-text-white);
    margin-bottom: 0.5rem;
}

.empty-description {
    font-size: 0.875rem;
    color: var(--admin-text-muted);
}

.user-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: var(--admin-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.content-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.content-tab {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--admin-border);
    background: transparent;
    color: var(--admin-text-muted);
    border-radius: 0.375rem;
    font-size: 0.75rem;
    transition: all 0.2s;
}

.content-tab.active,
.content-tab:hover {
    background: var(--admin-primary);
    color: white;
    border-color: var(--admin-primary);
}

.content-list {
    display: none;
}

.content-list.active {
    display: block;
}

.content-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--admin-border);
}

.content-item:last-child {
    border-bottom: none;
}

.content-title {
    font-size: 0.875rem;
    color: var(--admin-text-white);
    margin-bottom: 0.25rem;
}

.content-meta {
    font-size: 0.75rem;
    color: var(--admin-text-muted);
}

.activity-filters .admin-input {
    background: var(--admin-bg-light);
    border: 1px solid var(--admin-border);
    color: var(--admin-text-white);
}

/* Activity type colors */
.activity-success { background: var(--admin-success); }
.activity-warning { background: var(--admin-warning); }
.activity-primary { background: var(--admin-primary); }
.activity-info { background: var(--admin-info); }
.activity-secondary { background: #6b7280; }
.activity-default { background: var(--admin-primary); }

.badge.success { background: var(--admin-success); }
.badge.warning { background: var(--admin-warning); }
.badge.primary { background: var(--admin-primary); }
.badge.info { background: var(--admin-info); }
.badge.secondary { background: #6b7280; }
.badge.default { background: var(--admin-primary); }

@media (max-width: 768px) {
    .user-activity-dashboard {
        padding: 0.5rem;
    }

    .activity-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .chart-container {
        height: 250px;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
// Chart data from backend
const chartData = {
    daily_trend: @json($stats['daily_trend'] ?? ['labels' => [], 'data' => []]),
    activity_breakdown: @json($stats['activity_breakdown'] ?? []),
    hourly_pattern: @json($stats['hourly_pattern'] ?? ['labels' => [], 'data' => []])
};

// User Activity Charts - Chart.js Integration

// Prevent double initialization
let chartsInitialized = false;

// Initialize User Activity Charts
function initializeCharts() {
    if (chartsInitialized) return;

    // Wait for Chart.js to be loaded
    if (typeof Chart === 'undefined') {
        setTimeout(initializeCharts, 500);
        return;
    }

    // Validate data
    if (!chartData || !chartData.daily_trend || !chartData.activity_breakdown || !chartData.hourly_pattern) {
        console.error('Chart data is incomplete');
        return;
    }

    chartsInitialized = true;

    // Activity Trend Chart
    const trendCtx = document.getElementById('activityTrendChart');
    if (trendCtx) {
        try {
            new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: chartData.daily_trend.labels,
                datasets: [{
                    label: 'Activities',
                    data: chartData.daily_trend.data,
                    borderColor: '#ffffff',
                    backgroundColor: 'rgba(255, 255, 255, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#ffffff',
                    pointHoverBackgroundColor: '#10b981',
                    pointHoverBorderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: '#9ca3af' },
                        grid: { color: '#374151' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#9ca3af' },
                        grid: { color: '#374151' }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error creating trend chart:', error);
        }
    }

    // Activity Types Chart
    const typesCtx = document.getElementById('activityTypesChart');
    if (typesCtx) {
        try {
            const typeLabels = Object.keys(chartData.activity_breakdown);
            const typeData = Object.values(chartData.activity_breakdown);

            new Chart(typesCtx, {
            type: 'doughnut',
            data: {
                labels: typeLabels.map(label => label.replace('_', ' ').toUpperCase()),
                datasets: [{
                    data: typeData,
                    backgroundColor: [
                        '#10b981', '#3b82f6', '#f59e0b', '#ef4444',
                        '#8b5cf6', '#06b6d4', '#f97316', '#84cc16'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#d1d5db' }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error creating types chart:', error);
        }
    }

    // Hourly Activity Chart
    const hourlyCtx = document.getElementById('hourlyActivityChart');
    if (hourlyCtx) {
        try {
            new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: chartData.hourly_pattern.labels,
                datasets: [{
                    label: 'Activities',
                    data: chartData.hourly_pattern.data,
                    backgroundColor: 'rgba(255, 255, 255, 0.8)',
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: '#9ca3af' },
                        grid: { color: '#374151' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#9ca3af' },
                        grid: { color: '#374151' }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Error creating hourly chart:', error);
        }
    }

    // Period toggle
    document.querySelectorAll('.chart-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();

            // Update active state
            document.querySelectorAll('.chart-toggle').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Get period and redirect
            const period = this.dataset.period;
            const url = new URL(window.location);
            url.searchParams.set('period', period);
            window.location.href = url.toString();
        });
    });
}

// Initialize charts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

// Also try to initialize after a delay to ensure Chart.js is loaded
setTimeout(initializeCharts, 1000);

// Functions
function showContentTab(tab) {
    document.querySelectorAll('.content-tab').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.content-list').forEach(list => list.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById(tab + '-content').classList.add('active');
}

function refreshDashboard() {
    const refreshBtn = event.target.closest('button');
    const originalContent = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;

    window.location.reload();
}
</script>
@endpush
@endsection