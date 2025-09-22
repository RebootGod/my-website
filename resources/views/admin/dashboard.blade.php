@extends('layouts.admin')

@section('title', 'Dashboard - Admin')

@section('content')
<div class="dashboard-grid">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
        <button onclick="refreshDashboard()" class="btn-modern">
            <i class="fas fa-sync-alt"></i>
            Refresh
        </button>
    </div>

    {{-- Statistics Cards --}}
    <div class="dashboard-row stats">
        <x-admin.stat-card
            title="Total Movies"
            :value="$stats['total_movies']"
            icon="fas fa-film"
            color="primary"
            :link="route('admin.movies.index')"
        />

        <x-admin.stat-card
            title="Total Users"
            :value="$stats['total_users']"
            icon="fas fa-users"
            color="success"
            :link="route('admin.users.index')"
        />

        <x-admin.stat-card
            title="Active Users"
            :value="$stats['active_users']"
            icon="fas fa-user-check"
            color="info"
        />

        <x-admin.stat-card
            title="Total Series"
            :value="$stats['total_series'] ?? 0"
            icon="fas fa-tv"
            color="warning"
            :link="route('admin.series.index')"
        />

        <x-admin.stat-card
            title="Invite Codes"
            :value="$stats['total_invite_codes']"
            icon="fas fa-ticket-alt"
            color="secondary"
            :link="route('admin.invite-codes.index')"
        />

        <x-admin.stat-card
            title="Pending Reports"
            :value="$stats['pending_reports'] ?? 0"
            icon="fas fa-exclamation-triangle"
            color="error"
        />
    </div>

    {{-- Charts Row --}}
    <div class="dashboard-row charts">
        {{-- Content Growth Chart --}}
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">Content Growth</h3>
                <div class="chart-actions">
                    <button class="chart-toggle active" data-period="7d">7 Days</button>
                    <button class="chart-toggle" data-period="30d">30 Days</button>
                    <button class="chart-toggle" data-period="90d">90 Days</button>
                </div>
            </div>
            <div class="chart-canvas">
                <canvas id="contentGrowthChart"></canvas>
            </div>
        </div>

        {{-- User Activity Chart --}}
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">User Activity</h3>
            </div>
            <div class="chart-canvas">
                <canvas id="userActivityChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Content and Activity Row --}}
    <div class="dashboard-row content">
        {{-- Top Content --}}
        <div class="top-content-container">
            <div class="content-tabs">
                <button class="content-tab active" onclick="showContentTab('movies')">Top Movies</button>
                <button class="content-tab" onclick="showContentTab('series')">Top Series</button>
            </div>

            <div id="movies-content" class="content-list active">
                @foreach(($topContent['movies'] ?? []) as $index => $movie)
                <div class="content-item">
                    <div class="content-rank">{{ $index + 1 }}</div>
                    <div class="content-info">
                        <div class="content-title">{{ $movie->title }}</div>
                        <div class="content-meta">{{ $movie->created_at->format('M j, Y') }}</div>
                    </div>
                    <div class="content-stats">
                        <div class="content-views">{{ number_format($movie->view_count) }}</div>
                        <div class="content-date">views</div>
                    </div>
                </div>
                @endforeach

                @if(empty($topContent['movies']))
                <div class="content-item">
                    <div class="content-rank">—</div>
                    <div class="content-info">
                        <div class="content-title">No movies found</div>
                        <div class="content-meta">Add some movies to see top performers</div>
                    </div>
                    <div class="content-stats">
                        <div class="content-views">0</div>
                        <div class="content-date">views</div>
                    </div>
                </div>
                @endif
            </div>

            <div id="series-content" class="content-list">
                @foreach(($topContent['series'] ?? []) as $index => $series)
                <div class="content-item">
                    <div class="content-rank">{{ $index + 1 }}</div>
                    <div class="content-info">
                        <div class="content-title">{{ $series->title }}</div>
                        <div class="content-meta">{{ $series->created_at->format('M j, Y') }}</div>
                    </div>
                    <div class="content-stats">
                        <div class="content-views">{{ number_format($series->view_count) }}</div>
                        <div class="content-date">views</div>
                    </div>
                </div>
                @endforeach

                @if(empty($topContent['series']))
                <div class="content-item">
                    <div class="content-rank">—</div>
                    <div class="content-info">
                        <div class="content-title">No series found</div>
                        <div class="content-meta">Add some series to see top performers</div>
                    </div>
                    <div class="content-stats">
                        <div class="content-views">0</div>
                        <div class="content-date">views</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Activity Feed --}}
        <x-admin.activity-feed :activities="$recentActivity ?? collect()" />
    </div>

    {{-- Quick Actions --}}
    <div class="quick-actions">
        <h3 class="quick-actions-title">Quick Actions</h3>
        <div class="quick-actions-grid">
            <a href="{{ route('admin.movies.create') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="quick-action-label">Add Movie</div>
            </a>

            <a href="{{ route('admin.series.create') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="quick-action-label">Add Series</div>
            </a>

            <a href="{{ route('admin.users.index') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="quick-action-label">Manage Users</div>
            </a>

            <a href="{{ route('admin.invite-codes.create') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="quick-action-label">Generate Code</div>
            </a>

            <a href="{{ route('admin.tmdb-new.index') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-download"></i>
                </div>
                <div class="quick-action-label">Import TMDB</div>
            </a>

            <a href="{{ route('admin.logs.index') }}" class="quick-action">
                <div class="quick-action-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="quick-action-label">View Logs</div>
            </a>
        </div>
    </div>
</div>

@push('styles')
@vite('resources/css/admin/admin-dashboard.css')
@endpush

@push('scripts')
@vite('resources/js/admin/admin-charts.js')

<script>
// Pass backend data to JavaScript
window.adminDashboardData = {
    contentGrowth: @json($contentGrowth),
    userActivity: @json($userActivity),
    stats: @json($stats)
};
</script>

<script>
function showContentTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.content-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Show/hide content
    document.querySelectorAll('.content-list').forEach(list => {
        list.classList.remove('active');
    });
    document.getElementById(tab + '-content').classList.add('active');
}

function refreshDashboard() {
    // Add loading state
    const refreshBtn = event.target.closest('button');
    const originalContent = refreshBtn.innerHTML;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;

    // Refresh charts
    if (typeof Admin !== 'undefined' && Admin.Charts) {
        Admin.Charts.refreshCharts();
    }

    // Refresh stats (in real app, this would be an AJAX call)
    setTimeout(() => {
        refreshBtn.innerHTML = originalContent;
        refreshBtn.disabled = false;
        Admin.showToast('Dashboard refreshed successfully', 'success');
    }, 2000);
}
</script>
@endpush
@endsection