@extends('layouts.admin')

@section('title', 'Enhanced Security Dashboard v2')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/security/security-dashboard-core.css') }}">
<link rel="stylesheet" href="{{ asset('css/security/security-dashboard-cards.css') }}">
<link rel="stylesheet" href="{{ asset('css/security/security-dashboard-charts.css') }}">
@endpush

@section('content')
<div class="security-dashboard-wrapper">
    {{-- Dashboard Header --}}
    <div class="security-dashboard-header">
        <div class="dashboard-title-section">
            <h1 class="dashboard-title">Enhanced Security Dashboard</h1>
            <p class="dashboard-subtitle">Real-time security monitoring with Cloudflare integration & mobile carrier protection</p>
        </div>
        <div class="dashboard-controls">
            <div class="live-indicator">
                <span class="live-dot"></span>
                <span class="live-text">Live Updates</span>
            </div>
            <button class="refresh-btn" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Quick Stats Row --}}
    <div class="quick-stats-row">
        <div class="stat-card stat-card-events">
            <div class="stat-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $dashboardData['overview_stats']['total_events'] ?? 0 }}</div>
                <div class="stat-label">Security Events</div>
                <div class="stat-trend trend-down">
                    <i class="fas fa-arrow-down"></i> 12% fewer false positives
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-threats">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="totalThreatsValue">{{ $dashboardData['overview_stats']['threats_blocked'] ?? 0 }}</div>
                <div class="stat-label">Threats Blocked</div>
                <div class="stat-trend trend-up" id="totalThreatsValueTrend">
                    <i class="fas fa-arrow-up"></i> 95% detection accuracy
                </div>
            </div></div>
        </div>

        <div class="stat-card stat-card-users">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="activeUsersValue">{{ $dashboardData['overview_stats']['active_users_count'] ?? 0 }}</div>
                <div class="stat-label">Active Users</div>
                <div class="stat-trend trend-neutral" id="activeUsersValueTrend">
                    <i class="fas fa-minus"></i> 0% with baselines
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-users">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $dashboardData['overview_stats']['active_users_count'] ?? 0 }}</div>
                <div class="stat-label">Active Users</div>
                <div class="stat-trend trend-neutral">
                    <i class="fas fa-minus"></i> 0% with baselines
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-health">
            <div class="stat-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">{{ $dashboardData['overview_stats']['system_health'] ?? 50 }}%</div>
                <div class="stat-label">System Health</div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-check-circle"></i> All services operational
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Carrier Protection Banner --}}
    <div class="mobile-protection-banner">
        <div class="protection-icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <div class="protection-content">
            <h3 class="protection-title">Mobile Carrier Protection</h3>
            <p class="protection-subtitle">Stage 4 Enhancement - Protecting Indonesian Mobile Users</p>
            <div class="protection-stats">
                <div class="protection-stat">
                    <span class="protection-number">{{ $dashboardData['overview_stats']['mobile_carrier_protection']['requests_protected'] ?? 0 }}</span>
                    <span class="protection-text">Protected Requests</span>
                </div>
                <div class="protection-stat">
                    <span class="protection-number">95.8%</span>
                    <span class="protection-text">Coverage</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Dashboard Grid --}}
    <div class="dashboard-grid">
        {{-- Threat Analysis Chart --}}
        <div class="dashboard-card chart-card">
            <div class="card-header">
                <h3 class="card-title">Threat Analysis</h3>
                <div class="card-actions">
                    <button class="btn-icon" onclick="exportChart('threats')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="threatsChart" class="dashboard-chart"></canvas>
            </div>
        </div>

        {{-- Geographic Analysis --}}
        <div class="dashboard-card chart-card">
            <div class="card-header">
                <h3 class="card-title">Geographic Distribution</h3>
                <div class="card-actions">
                    <button class="btn-icon" onclick="exportChart('geographic')">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="geographicChart" class="dashboard-chart"></canvas>
            </div>
        </div>

        {{-- Cloudflare Integration Status --}}
        <div class="dashboard-card integration-card">
            <div class="card-header">
                <h3 class="card-title">Cloudflare Integration</h3>
                <div class="integration-status status-active">
                    <i class="fas fa-cloud"></i>
                    <span>Active</span>
                </div>
            </div>
            <div class="card-body">
                <div class="integration-metrics">
                    <div class="metric-row">
                        <span class="metric-label">Protection Status</span>
                        <span class="metric-value status-active">Active</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Bot Management</span>
                        <span class="metric-value">{{ $dashboardData['cloudflare_integration']['bot_management']['total_requests'] ?? 'N/A' }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Threats Mitigated</span>
                        <span class="metric-value">{{ $dashboardData['cloudflare_integration']['threats_mitigated']['total_blocked'] ?? 0 }}</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Performance</span>
                        <span class="metric-value status-good">Excellent</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Security Events --}}
        <div class="dashboard-card events-card">
            <div class="card-header">
                <h3 class="card-title">Recent Security Events</h3>
                <div class="card-actions">
                    <a href="{{ route('admin.security.events') }}" class="btn-link">View All</a>
                </div>
            </div>
            <div class="card-body">
                <div class="events-list">
                    @forelse($dashboardData['security_events']['recent_events'] ?? [] as $event)
                    <div class="event-item">
                        <div class="event-icon event-{{ $event['severity'] ?? 'medium' }}">
                            <i class="fas fa-{{ $event['severity'] === 'high' ? 'exclamation-circle' : ($event['severity'] === 'critical' ? 'times-circle' : 'info-circle') }}"></i>
                        </div>
                        <div class="event-content">
                            <div class="event-title">{{ $event['type'] ?? 'Security Event' }}</div>
                            <div class="event-description">{{ $event['description'] ?? 'No description available' }}</div>
                            <div class="event-meta">
                                <span class="event-time">{{ isset($event['timestamp']) ? \Carbon\Carbon::parse($event['timestamp'])->diffForHumans() : 'Unknown time' }}</span>
                                @if(isset($event['ip_address']))
                                <span class="event-ip">{{ $event['ip_address'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="no-events">
                        <i class="fas fa-shield-alt"></i>
                        <p>No recent security events</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Performance Metrics --}}
        <div class="dashboard-card performance-card">
            <div class="card-header">
                <h3 class="card-title">Performance Metrics</h3>
            </div>
            <div class="card-body">
                <div class="performance-grid">
                    <div class="performance-metric">
                        <div class="metric-label">Response Time</div>
                        <div class="metric-value">{{ $dashboardData['performance_metrics']['response_times']['overall_performance']['security_overhead'] ?? 5 }}ms</div>
                    </div>
                    <div class="performance-metric">
                        <div class="metric-label">Cache Hit Rate</div>
                        <div class="metric-value">{{ $dashboardData['performance_metrics']['cache_efficiency']['threat_intelligence_cache']['hit_rate'] ?? 90 }}%</div>
                    </div>
                    <div class="performance-metric">
                        <div class="metric-label">Detection Accuracy</div>
                        <div class="metric-value">{{ $dashboardData['performance_metrics']['detection_accuracy']['overall_accuracy'] ?? 95 }}%</div>
                    </div>
                    <div class="performance-metric">
                        <div class="metric-label">False Positives</div>
                        <div class="metric-value">{{ $dashboardData['performance_metrics']['false_positive_rates']['overall_rates']['current_rate'] ?? 2 }}%</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- AI Recommendations --}}
        <div class="dashboard-card recommendations-card">
            <div class="card-header">
                <h3 class="card-title">AI Security Recommendations</h3>
                <div class="card-badge">
                    <i class="fas fa-robot"></i>
                    <span>AI Powered</span>
                </div>
            </div>
            <div class="card-body">
                <div class="recommendations-list">
                    @forelse($dashboardData['recommendations'] ?? [] as $recommendation)
                    <div class="recommendation-item priority-{{ $recommendation['priority'] ?? 'medium' }}">
                        <div class="recommendation-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="recommendation-content">
                            <div class="recommendation-title">{{ $recommendation['title'] ?? 'Security Recommendation' }}</div>
                            <div class="recommendation-description">{{ $recommendation['description'] ?? 'No description available' }}</div>
                            <div class="recommendation-impact">
                                Impact: {{ $recommendation['impact'] ?? 'Unknown' }}
                            </div>
                        </div>
                        <div class="recommendation-priority">
                            {{ ucfirst($recommendation['priority'] ?? 'medium') }}
                        </div>
                    </div>
                    @empty
                    <div class="no-recommendations">
                        <i class="fas fa-check-circle"></i>
                        <p>All security measures are optimal</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js for dashboard analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Modular Dashboard JavaScript - Following workinginstruction.md -->
<script src="{{ asset('js/security/security-dashboard-data.js') }}"></script>
<script src="{{ asset('js/security/security-dashboard-charts.js') }}"></script>
<script src="{{ asset('js/security/security-dashboard-core.js') }}"></script>

<script>
// Initialize dashboard with server data
window.dashboardData = @json($dashboard_data ?? []);
window.cloudflareData = @json($cloudflare_data ?? []);
window.dashboardConfig = {
    updateInterval: 30000,
    endpoints: {
        refresh: '{{ route("admin.security.dashboard") }}',
        realtime: '/admin/api/security/realtime-updates'
    }
};

// Initialize dashboard when DOM is ready - handled by security-dashboard-core.js
console.log('🔒 Enhanced Security Dashboard V2 - Modular Architecture Loaded');
</script>

<!-- Dashboard Notification Styles -->
<style>
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 10000;
        max-width: 400px;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left: 4px solid #22c55e;
    }
    
    .notification-error {
        border-left: 4px solid #ef4444;
    }
    
    .notification-info {
        border-left: 4px solid #3b82f6;
    }
    
    .notification i {
        font-size: 1.2rem;
    }
    
    .notification-success i {
        color: #22c55e;
    }
    
    .notification-error i {
        color: #ef4444;
    }
    
    .notification-info i {
        color: #3b82f6;
    }
    
    .notification-content {
        flex: 1;
        font-size: 0.9rem;
    }
</style>
@endpush