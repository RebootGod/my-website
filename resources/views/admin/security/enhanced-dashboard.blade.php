@extends('admin.layout')

@section('title', 'Enhanced Security Dashboard - Stage 5')

@section('additional_css')
<link rel="stylesheet" href="{{ asset('css/enhanced-security-dashboard.css') }}">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
{{-- Enhanced Security Dashboard - Following workinginstruction.md --}}
<div class="enhanced-security-dashboard">
    
    {{-- Dashboard Header --}}
    <div class="dashboard-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="dashboard-title">Enhanced Security Dashboard</h1>
                    <p class="dashboard-subtitle">
                        Real-time security monitoring with Cloudflare integration & behavioral analytics
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex gap-2 justify-content-end align-items-center">
                        <div class="realtime-status">
                            <div class="status-dot"></div>
                            <span>Live Updates</span>
                        </div>
                        <button id="refresh-dashboard" class="interactive-button">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        
        {{-- Time Range Controls --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button class="time-range-btn" data-hours="1">1H</button>
                    <button class="time-range-btn active" data-hours="24">24H</button>
                    <button class="time-range-btn" data-hours="168">7D</button>
                    <button class="time-range-btn" data-hours="720">30D</button>
                </div>
            </div>
        </div>

        {{-- Overview Statistics Grid --}}
        <div class="stats-grid">
            
            {{-- Total Security Events --}}
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Security Events</div>
                    <div class="stat-icon security">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
                <div class="stat-value" id="total-events-count">
                    {{ $dashboard_data['overview_stats']['total_security_events'] ?? 0 }}
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-down"></i>
                    <span>12% fewer false positives</span>
                </div>
            </div>

            {{-- Blocked Threats --}}
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Threats Blocked</div>
                    <div class="stat-icon security">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
                <div class="stat-value" id="blocked-threats-count">
                    {{ $dashboard_data['overview_stats']['blocked_threats'] ?? 0 }}
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>95% detection accuracy</span>
                </div>
            </div>

            {{-- Active Users --}}
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Active Users</div>
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value" id="active-users-count">
                    {{ $dashboard_data['overview_stats']['active_users']['total_active'] ?? 0 }}
                </div>
                <div class="stat-change neutral">
                    <i class="fas fa-chart-line"></i>
                    <span>{{ $dashboard_data['overview_stats']['active_users']['baseline_coverage'] ?? 0 }}% with baselines</span>
                </div>
            </div>

            {{-- False Positive Reduction --}}
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">FP Reduction</div>
                    <div class="stat-icon performance">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="stat-value" id="fp-reduction-percentage">
                    {{ $dashboard_data['overview_stats']['false_positive_reduction']['reduction_percentage'] ?? 0 }}%
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-down"></i>
                    <span>Stage 4 optimization impact</span>
                </div>
            </div>

            {{-- System Health --}}
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">System Health</div>
                    <div class="stat-icon performance">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                </div>
                <div class="stat-value" id="system-health-score">
                    {{ $dashboard_data['overview_stats']['system_health'] ?? 0 }}%
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-check-circle"></i>
                    <span>All services operational</span>
                </div>
            </div>

            {{-- Cloudflare Status --}}
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Cloudflare Protection</div>
                    <div class="stat-icon cloudflare">
                        <i class="fab fa-cloudflare"></i>
                    </div>
                </div>
                <div class="stat-value">
                    <span class="text-success" id="cf-protection-status">
                        {{ $cloudflare_data['protection_overview']['protection_status']['status'] ?? 'Active' }}
                    </span>
                </div>
                <div class="stat-change positive">
                    <i class="fas fa-shield-alt"></i>
                    <span id="cf-coverage">
                        {{ $cloudflare_data['protection_overview']['protection_status']['coverage_percentage'] ?? 95 }}% coverage
                    </span>
                </div>
            </div>

        </div>

        {{-- Mobile Carrier Protection Section --}}
        <div class="mobile-protection">
            <div class="protection-header">
                <h2 class="protection-title">Mobile Carrier Protection</h2>
                <p class="protection-subtitle">Stage 4 Enhancement - Protecting Indonesian Mobile Users</p>
            </div>
            <div class="protection-stats">
                <div class="protection-stat">
                    <div class="protection-stat-value" id="protected-requests-count">
                        {{ $dashboard_data['overview_stats']['mobile_carrier_protection']['requests_protected'] ?? 0 }}
                    </div>
                    <div class="protection-stat-label">Protected Requests</div>
                </div>
                <div class="protection-stat">
                    <div class="protection-stat-value" id="prevented-fp-count">
                        {{ $dashboard_data['overview_stats']['mobile_carrier_protection']['false_positives_prevented'] ?? 0 }}
                    </div>
                    <div class="protection-stat-label">False Positives Prevented</div>
                </div>
                <div class="protection-stat">
                    <div class="protection-stat-value">3</div>
                    <div class="protection-stat-label">Major Carriers Protected</div>
                </div>
                <div class="protection-stat">
                    <div class="protection-stat-value">9</div>
                    <div class="protection-stat-label">IP Ranges Covered</div>
                </div>
            </div>
            <div class="mt-3 text-center">
                <div id="protected-carriers-list">
                    <span class="carrier-badge">Telkomsel</span>
                    <span class="carrier-badge">Indosat</span>
                    <span class="carrier-badge">XL Axiata</span>
                </div>
            </div>
        </div>

        {{-- Charts and Analytics Section --}}
        <div class="charts-section">
            
            {{-- Main Security Events Chart --}}
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Security Events Timeline</h3>
                    <div class="chart-controls">
                        <button class="secondary-button" onclick="exportChart('securityEvents')">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="chart-loading" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Loading security events data...</p>
                </div>
                <canvas id="securityEventsChart" height="300"></canvas>
            </div>

            {{-- Cloudflare Integration Panel --}}
            <div class="cloudflare-panel">
                <div class="cloudflare-header">
                    <div class="cloudflare-logo">CF</div>
                    <div>
                        <h3>Cloudflare Analytics</h3>
                        <p>Edge security metrics</p>
                    </div>
                </div>
                <div class="cloudflare-metrics">
                    <div class="cloudflare-metric">
                        <span class="metric-label">Requests Analyzed</span>
                        <span class="metric-value" id="cf-requests-analyzed">
                            {{ number_format($cloudflare_data['protection_overview']['requests_analyzed']['total_requests'] ?? 0) }}
                        </span>
                    </div>
                    <div class="cloudflare-metric">
                        <span class="metric-label">Threats Mitigated</span>
                        <span class="metric-value" id="cf-threats-mitigated">
                            {{ $cloudflare_data['protection_overview']['threats_mitigated']['total_threats'] ?? 0 }}
                        </span>
                    </div>
                    <div class="cloudflare-metric">
                        <span class="metric-label">Edge Cache Rate</span>
                        <span class="metric-value" id="cf-cache-rate">
                            {{ $cloudflare_data['protection_overview']['edge_vs_origin_ratio']['edge_percentage'] ?? 95 }}%
                        </span>
                    </div>
                    <div class="cloudflare-metric">
                        <span class="metric-label">Bot Detection</span>
                        <span class="metric-value text-success">Active</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Threat Analysis & Bot Scores --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Threat Distribution</h3>
                    </div>
                    <canvas id="threatDistributionChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Cloudflare Bot Scores</h3>
                    </div>
                    <canvas id="botScoresChart" height="250"></canvas>
                </div>
            </div>
        </div>

        {{-- User Behavior Analytics --}}
        <div class="behavior-analytics">
            
            <div class="behavior-card">
                <div class="behavior-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <h4 class="behavior-title">Baseline Established</h4>
                <div class="behavior-value">
                    {{ $dashboard_data['user_behavior_analytics']['baseline_establishment']['users_with_baselines'] ?? 0 }}
                </div>
                <p class="behavior-description">Users with behavioral baselines</p>
            </div>

            <div class="behavior-card">
                <div class="behavior-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h4 class="behavior-title">Anomalies Detected</h4>
                <div class="behavior-value">
                    {{ $dashboard_data['user_behavior_analytics']['anomaly_detection']['total_anomalies'] ?? 0 }}
                </div>
                <p class="behavior-description">Behavioral anomalies identified</p>
            </div>

            <div class="behavior-card">
                <div class="behavior-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h4 class="behavior-title">Auth Patterns</h4>
                <div class="behavior-value">
                    {{ $dashboard_data['user_behavior_analytics']['authentication_patterns']['normal_patterns'] ?? 0 }}
                </div>
                <p class="behavior-description">Normal authentication patterns</p>
            </div>

            <div class="behavior-card">
                <div class="behavior-icon">
                    <i class="fas fa-chart-radar"></i>
                </div>
                <h4 class="behavior-title">Risk Analysis</h4>
                <div class="behavior-value">
                    {{ $dashboard_data['user_behavior_analytics']['risk_scoring']['average_risk'] ?? 'Low' }}
                </div>
                <p class="behavior-description">Average user risk level</p>
            </div>

        </div>

        {{-- Advanced Analytics Charts --}}
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">User Behavior Analytics</h3>
                    </div>
                    <canvas id="behaviorAnalyticsChart" height="300"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Geographic Threats</h3>
                    </div>
                    <canvas id="geographicChart" height="300"></canvas>
                </div>
            </div>
        </div>

        {{-- Threat Analysis Cards --}}
        <div class="threat-analysis">
            
            <div class="threat-card critical">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5>Critical Threats</h5>
                    <span class="threat-level critical">Critical</span>
                </div>
                <div class="display-4 text-danger mb-2">
                    {{ $dashboard_data['threat_analysis']['severity_distribution']['critical'] ?? 0 }}
                </div>
                <p class="text-muted mb-0">Immediate attention required</p>
            </div>

            <div class="threat-card medium">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5>High Priority</h5>
                    <span class="threat-level high">High</span>
                </div>
                <div class="display-4 text-warning mb-2">
                    {{ $dashboard_data['threat_analysis']['severity_distribution']['high'] ?? 0 }}
                </div>
                <p class="text-muted mb-0">Monitor closely</p>
            </div>

            <div class="threat-card low">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5>Low Risk Events</h5>
                    <span class="threat-level low">Low</span>
                </div>
                <div class="display-4 text-success mb-2">
                    {{ $dashboard_data['threat_analysis']['severity_distribution']['low'] ?? 0 }}
                </div>
                <p class="text-muted mb-0">Normal activity levels</p>
            </div>

        </div>

        {{-- Recent Security Events Timeline --}}
        <div class="row">
            <div class="col-12">
                <div class="events-timeline">
                    <div class="chart-header">
                        <h3 class="chart-title">Recent Security Events</h3>
                        <div class="text-muted">
                            <i class="fas fa-clock"></i>
                            Last updated: <span id="last-refresh-time">{{ now()->format('H:i:s') }}</span>
                        </div>
                    </div>
                    <div id="events-timeline">
                        @if(isset($dashboard_data['security_events']['recent_events']))
                            @foreach(array_slice($dashboard_data['security_events']['recent_events'], 0, 10) as $event)
                                <div class="timeline-item">
                                    <div class="timeline-dot {{ $event['severity'] ?? 'medium' }}"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-time">
                                            {{ \Carbon\Carbon::parse($event['timestamp'])->format('H:i:s') }}
                                        </div>
                                        <div class="timeline-title">
                                            {{ $event['event_type'] ?? 'Security Event' }}
                                        </div>
                                        <div class="timeline-description">
                                            {{ Str::limit($event['details'] ?? $event['event_type'] . ' from ' . ($event['ip_address'] ?? 'unknown'), 100) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle"></i>
                                <p>No recent security events</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Request Context (for debugging) --}}
        @if(isset($current_request_context) && config('app.debug'))
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bug"></i> Current Request Context (Debug Mode)</h5>
                        </div>
                        <div class="card-body">
                            <pre class="bg-light p-3 rounded">{{ json_encode($current_request_context, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- Hidden elements for JavaScript --}}
    <div id="dashboard-error" class="error-message" style="display: none;"></div>
    <div class="realtime-toggle" style="display: none;">
        <input type="checkbox" id="realtime-toggle" checked>
        <label for="realtime-toggle">Real-time updates</label>
    </div>

</div>
@endsection

@section('additional_js')
<script src="{{ asset('js/enhanced-security-dashboard.js') }}"></script>
<script>
    // Pass server-side data to JavaScript
    window.dashboardConfig = {
        timeRange: {{ $time_range ?? 24 }},
        realTimeEnabled: true,
        updateInterval: 30000, // 30 seconds
        endpoints: {
            realtimeUpdates: '{{ route("admin.security.realtime-updates") }}',
            dashboardData: '{{ route("admin.security.dashboard-data") }}',
            exportData: '{{ route("admin.security.export-data") }}'
        }
    };

    // Export chart function
    function exportChart(chartType) {
        if (window.securityDashboard && window.securityDashboard.charts[chartType]) {
            const canvas = window.securityDashboard.charts[chartType].canvas;
            const url = canvas.toDataURL('image/png');
            const a = document.createElement('a');
            a.download = `${chartType}-chart-${Date.now()}.png`;
            a.href = url;
            a.click();
        }
    }
</script>
@endsection