@extends('layouts.admin')

@section('title', 'Dashboard - Admin')

@section('content')
{{-- Theme & Density Controls --}}
<div class="dashboard-controls">
    <button id="theme-toggle" class="control-btn theme-toggle-btn" title="Toggle theme (Alt+T)">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <span>Dark</span>
    </button>
    
    <button id="density-toggle" class="control-btn density-toggle-btn" title="View density (Alt+D)">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
        <span>Normal</span>
    </button>
    
    <button onclick="resetDashboardLayout()" class="control-btn reset-btn" title="Reset layout">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span>Reset</span>
    </button>
</div>

<div class="dashboard-grid">
    {{-- Breadcrumb Navigation --}}
    @include('admin.components.breadcrumbs', [
        'items' => [
            ['label' => 'Dashboard', 'icon' => 'fas fa-chart-bar']
        ]
    ])

    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
        <div class="header-actions">
            <button onclick="exportDashboardLayout()" class="btn-modern btn-secondary">
                <i class="fas fa-download"></i>
                Export Layout
            </button>
            <button onclick="importDashboardLayout()" class="btn-modern btn-secondary">
                <i class="fas fa-upload"></i>
                Import Layout
            </button>
            <button onclick="refreshDashboard()" class="btn-modern">
                <i class="fas fa-sync-alt"></i>
                Refresh
            </button>
        </div>
    </div>

    {{-- Widget Container (Drag & Drop) --}}
    <div id="widgets-container" class="widgets-container">
        {{-- Statistics Row --}}
        <div class="stats-grid">
            @include('admin.components.widgets.stat-widget', [
                'id' => 'stat-movies',
                'title' => 'Total Movies',
                'value' => number_format($stats['total_movies'] ?? 0),
                'icon' => 'movies',
                'color' => 'primary',
                'trend' => '+12%',
                'trendDirection' => 'up',
                'description' => 'Total movies in database',
                'link' => route('admin.movies.index')
            ])
            
            @include('admin.components.widgets.stat-widget', [
                'id' => 'stat-series',
                'title' => 'Total Series',
                'value' => number_format($stats['total_series'] ?? 0),
                'icon' => 'series',
                'color' => 'warning',
                'trend' => '+8%',
                'trendDirection' => 'up',
                'description' => 'Total series in database',
                'link' => route('admin.series.index')
            ])
            
            @include('admin.components.widgets.stat-widget', [
                'id' => 'stat-users',
                'title' => 'Total Users',
                'value' => number_format($stats['total_users'] ?? 0),
                'icon' => 'users',
                'color' => 'success',
                'trend' => '+15%',
                'trendDirection' => 'up',
                'description' => 'Registered users',
                'link' => route('admin.users.index')
            ])
            
            @include('admin.components.widgets.stat-widget', [
                'id' => 'stat-active-users',
                'title' => 'Active Users',
                'value' => number_format($stats['active_users'] ?? 0),
                'icon' => 'views',
                'color' => 'info',
                'trend' => '+20%',
                'trendDirection' => 'up',
                'description' => 'Active in last 30 days'
            ])
            
            @include('admin.components.widgets.stat-widget', [
                'id' => 'stat-storage',
                'title' => 'Storage Used',
                'value' => $stats['storage_used'] ?? '0 GB',
                'icon' => 'storage',
                'color' => 'warning',
                'description' => 'Total storage usage'
            ])
            
            @include('admin.components.widgets.stat-widget', [
                'id' => 'stat-invite-codes',
                'title' => 'Invite Codes',
                'value' => number_format($stats['total_invite_codes'] ?? 0),
                'icon' => 'chart-bar',
                'color' => 'secondary',
                'link' => route('admin.invite-codes.index')
            ])
        </div>
        
        {{-- Charts Row --}}
        @php
            $contentGrowthData = [
                'labels' => $contentGrowth['labels'] ?? [],
                'datasets' => [
                    [
                        'label' => 'Movies',
                        'data' => $contentGrowth['movies'] ?? [],
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
                    ],
                    [
                        'label' => 'Series',
                        'data' => $contentGrowth['series'] ?? [],
                        'borderColor' => '#fbbf24',
                        'backgroundColor' => 'rgba(251, 191, 36, 0.1)'
                    ]
                ]
            ];
            
            $userActivityData = [
                'labels' => $userActivity['labels'] ?? [],
                'datasets' => [
                    [
                        'label' => 'Active Users',
                        'data' => $userActivity['data'] ?? [],
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)'
                    ]
                ]
            ];
        @endphp
        
        @include('admin.components.widgets.chart-widget', [
            'id' => 'chart-content-growth',
            'title' => 'Content Growth',
            'subtitle' => 'Last 30 days',
            'type' => 'line',
            'data' => $contentGrowthData,
            'height' => '320px'
        ])
        
        @include('admin.components.widgets.chart-widget', [
            'id' => 'chart-user-activity',
            'title' => 'User Activity',
            'subtitle' => 'Last 7 days',
            'type' => 'bar',
            'data' => $userActivityData,
            'height' => '320px'
        ])
        
        {{-- Top Content Widgets --}}
        @include('admin.components.widgets.top-content-widget', [
            'id' => 'top-movies',
            'title' => 'Top Movies',
            'contentType' => 'movies',
            'items' => $topContent['movies'] ?? collect(),
            'limit' => 5,
            'showThumbnails' => true,
            'showViews' => true,
            'showRating' => true
        ])
        
        @include('admin.components.widgets.top-content-widget', [
            'id' => 'top-series',
            'title' => 'Top Series',
            'contentType' => 'series',
            'items' => $topContent['series'] ?? collect(),
            'limit' => 5,
            'showThumbnails' => true,
            'showViews' => true,
            'showRating' => true
        ])
        
        {{-- Activity Feed --}}
        @include('admin.components.widgets.activity-feed-widget', [
            'id' => 'recent-activity',
            'title' => 'Recent Activity',
            'activities' => $recentActivity ?? collect(),
            'limit' => 10,
            'showUserAvatar' => true,
            'showTimestamp' => true
        ])
        
        {{-- Quick Actions --}}
        @include('admin.components.widgets.quick-actions-widget', [
            'id' => 'quick-actions',
            'title' => 'Quick Actions',
            'actions' => [
                [
                    'label' => 'Add Movie',
                    'url' => route('admin.movies.create'),
                    'icon' => 'movie',
                    'color' => 'primary',
                    'shortcut' => 'Alt+M'
                ],
                [
                    'label' => 'Add Series',
                    'url' => route('admin.series.create'),
                    'icon' => 'series',
                    'color' => 'primary',
                    'shortcut' => 'Alt+S'
                ],
                [
                    'label' => 'View Users',
                    'url' => route('admin.users.index'),
                    'icon' => 'users',
                    'color' => 'info'
                ],
                [
                    'label' => 'Invite Codes',
                    'url' => route('admin.invite-codes.create'),
                    'icon' => 'plus',
                    'color' => 'success'
                ],
                [
                    'label' => 'Import TMDB',
                    'url' => route('admin.tmdb-new.index'),
                    'icon' => 'download',
                    'color' => 'warning'
                ],
                [
                    'label' => 'Settings',
                    'url' => '#',
                    'icon' => 'settings',
                    'color' => 'secondary'
                ]
            ]
        ])
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/dashboard-modern.css') }}">
@vite('resources/css/admin/admin-dashboard.css')
<style>
/* Dashboard Controls */
.dashboard-controls {
    position: fixed;
    top: 6rem;
    right: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    z-index: 100;
}

.control-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--dashboard-card-bg, #1f2937);
    border: 1px solid var(--dashboard-border, rgba(255, 255, 255, 0.1));
    border-radius: 0.5rem;
    color: var(--dashboard-text-primary, #fff);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.control-btn:hover {
    background: var(--dashboard-card-hover-bg, #374151);
    border-color: var(--dashboard-primary, #3b82f6);
    transform: translateX(-2px);
}

.control-btn svg {
    width: 1.25rem;
    height: 1.25rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-secondary {
    background: var(--dashboard-card-bg, #1f2937);
    border: 1px solid var(--dashboard-border, rgba(255, 255, 255, 0.1));
}

.btn-secondary:hover {
    background: var(--dashboard-card-hover-bg, #374151);
}

.widgets-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .dashboard-controls {
        top: 5rem;
        right: 1rem;
    }
    
    .control-btn span {
        display: none;
    }
    
    .control-btn {
        padding: 0.625rem;
        width: 2.5rem;
        height: 2.5rem;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .dashboard-controls {
        flex-direction: row;
        top: auto;
        bottom: 1rem;
        right: 1rem;
        left: 1rem;
        justify-content: center;
    }
    
    .header-actions {
        flex-wrap: wrap;
    }
    
    .widgets-container {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin/theme-switcher.js') }}"></script>
<script src="{{ asset('js/admin/density-switcher.js') }}"></script>
<script src="{{ asset('js/admin/dashboard-widgets.js') }}"></script>
@vite('resources/js/admin/admin-charts.js')

<script>
// Initialize widget system
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard widgets
    window.dashboardWidgets = new DashboardWidgets();
    
    console.log('✅ Dashboard initialized with widget system');
});

// Dashboard actions
function refreshDashboard() {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    btn.disabled = true;

    // Simulate refresh (in production, this would be an AJAX call)
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function resetDashboardLayout() {
    if (confirm('Reset dashboard layout to default? This will clear your customizations.')) {
        if (window.dashboardWidgets) {
            window.dashboardWidgets.resetLayout();
        }
        
        if (window.showToast) {
            window.showToast('Dashboard layout reset to default', 'success');
        }
    }
}

function exportDashboardLayout() {
    if (window.dashboardWidgets) {
        window.dashboardWidgets.exportLayout();
        
        if (window.showToast) {
            window.showToast('Dashboard layout exported', 'success');
        }
    }
}

function importDashboardLayout() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    
    input.onchange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const json = event.target.result;
                if (window.dashboardWidgets) {
                    window.dashboardWidgets.importLayout(json);
                    
                    if (window.showToast) {
                        window.showToast('Dashboard layout imported successfully', 'success');
                    }
                }
            } catch (error) {
                console.error('Failed to import layout:', error);
                if (window.showToast) {
                    window.showToast('Failed to import layout', 'error');
                }
            }
        };
        reader.readAsText(file);
    };
    
    input.click();
}

// Pass backend data to JavaScript
window.adminDashboardData = {
    contentGrowth: @json($contentGrowth),
    userActivity: @json($userActivity),
    stats: @json($stats)
};
</script>
@endpush
@endsection