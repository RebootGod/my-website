/**
 * ========================================
 * ENHANCED SECURITY DASHBOARD JAVASCRIPT
 * Real-time dashboard updates and interactive features
 * Following workinginstruction.md: Separate JavaScript file for enhanced dashboard
 * ========================================
 */

class EnhancedSecurityDashboard {
    constructor() {
        this.updateInterval = null;
        this.charts = {};
        this.realTimeEnabled = true;
        this.currentTimeRange = 24; // hours
        
        this.init();
    }
    
    /**
     * Initialize dashboard
     */
    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.loadInitialData();
        this.startRealTimeUpdates();
        
        console.log('Enhanced Security Dashboard initialized');
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Time range buttons
        document.querySelectorAll('.time-range-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.changeTimeRange(parseInt(e.target.dataset.hours));
            });
        });
        
        // Real-time toggle
        const realtimeToggle = document.getElementById('realtime-toggle');
        if (realtimeToggle) {
            realtimeToggle.addEventListener('change', (e) => {
                this.toggleRealTime(e.target.checked);
            });
        }
        
        // Refresh button
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshAllData();
            });
        }
        
        // Export buttons
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.exportData(e.target.dataset.format);
            });
        });
    }
    
    /**
     * Initialize charts using Chart.js
     */
    initializeCharts() {
        try {
            // Security Events Timeline Chart
            this.initSecurityEventsChart();
            
            // Threat Distribution Chart
            this.initThreatDistributionChart();
            
            // Cloudflare Bot Scores Chart
            this.initBotScoresChart();
            
            // User Behavior Analytics Chart
            this.initBehaviorAnalyticsChart();
            
            // Geographic Threats Chart
            this.initGeographicChart();
            
            console.log('Charts initialized successfully');
        } catch (error) {
            console.error('Error initializing charts:', error);
        }
    }
    
    /**
     * Initialize security events timeline chart
     */
    initSecurityEventsChart() {
        const ctx = document.getElementById('securityEventsChart');
        if (!ctx) return;
        
        this.charts.securityEvents = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Security Events',
                    data: [],
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Blocked Threats',
                    data: [],
                    borderColor: 'rgba(34, 197, 94, 1)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Initialize threat distribution chart
     */
    initThreatDistributionChart() {
        const ctx = document.getElementById('threatDistributionChart');
        if (!ctx) return;
        
        this.charts.threatDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Critical', 'High', 'Medium', 'Low'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    /**
     * Initialize Cloudflare bot scores chart
     */
    initBotScoresChart() {
        const ctx = document.getElementById('botScoresChart');
        if (!ctx) return;
        
        this.charts.botScores = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['0-10', '11-30', '31-70', '71-100'],
                datasets: [{
                    label: 'Bot Score Distribution',
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    /**
     * Initialize user behavior analytics chart
     */
    initBehaviorAnalyticsChart() {
        const ctx = document.getElementById('behaviorAnalyticsChart');
        if (!ctx) return;
        
        this.charts.behaviorAnalytics = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Authentication', 'Navigation', 'Data Access', 'Session', 'Privileges'],
                datasets: [{
                    label: 'Normal Behavior',
                    data: [80, 85, 75, 90, 70],
                    borderColor: 'rgba(34, 197, 94, 1)',
                    backgroundColor: 'rgba(34, 197, 94, 0.2)'
                }, {
                    label: 'Anomalous Behavior',
                    data: [40, 30, 60, 20, 80],
                    borderColor: 'rgba(239, 68, 68, 1)',
                    backgroundColor: 'rgba(239, 68, 68, 0.2)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    /**
     * Initialize geographic threats chart
     */
    initGeographicChart() {
        const ctx = document.getElementById('geographicChart');
        if (!ctx) return;
        
        this.charts.geographic = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Indonesia', 'China', 'Russia', 'USA', 'Brazil'],
                datasets: [{
                    label: 'Legitimate Traffic',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(34, 197, 94, 0.8)'
                }, {
                    label: 'Threat Traffic',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(239, 68, 68, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    /**
     * Load initial dashboard data
     */
    async loadInitialData() {
        try {
            this.showLoadingState();
            
            const response = await fetch(`/admin/security/dashboard-data?hours=${this.currentTimeRange}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardData(data.data);
                this.hideLoadingState();
            } else {
                throw new Error(data.error || 'Failed to load dashboard data');
            }
            
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showErrorState('Failed to load dashboard data');
        }
    }
    
    /**
     * Update dashboard with new data
     */
    updateDashboardData(data) {
        try {
            // Update overview stats
            this.updateOverviewStats(data.dashboard_data.overview_stats);
            
            // Update charts
            this.updateSecurityEventsChart(data.dashboard_data.security_events);
            this.updateThreatDistributionChart(data.dashboard_data.threat_analysis);
            this.updateBotScoresChart(data.cloudflare_data.bot_management_analytics);
            this.updateBehaviorAnalyticsChart(data.dashboard_data.user_behavior_analytics);
            this.updateGeographicChart(data.dashboard_data.geographic_analysis);
            
            // Update Cloudflare metrics
            this.updateCloudflareMetrics(data.cloudflare_data.protection_overview);
            
            // Update mobile carrier protection
            this.updateMobileCarrierProtection(data.dashboard_data.overview_stats.mobile_carrier_protection);
            
            // Update events timeline
            this.updateEventsTimeline(data.dashboard_data.security_events.recent_events);
            
            // Update last refresh time
            this.updateLastRefreshTime();
            
            console.log('Dashboard data updated successfully');
            
        } catch (error) {
            console.error('Error updating dashboard data:', error);
        }
    }
    
    /**
     * Update overview statistics
     */
    updateOverviewStats(stats) {
        // Total Security Events
        const totalEventsEl = document.getElementById('total-events-count');
        if (totalEventsEl) {
            this.animateNumber(totalEventsEl, stats.total_security_events);
        }
        
        // Blocked Threats
        const blockedThreatsEl = document.getElementById('blocked-threats-count');
        if (blockedThreatsEl) {
            this.animateNumber(blockedThreatsEl, stats.blocked_threats);
        }
        
        // Active Users
        const activeUsersEl = document.getElementById('active-users-count');
        if (activeUsersEl) {
            this.animateNumber(activeUsersEl, stats.active_users.total_active);
        }
        
        // False Positive Reduction
        const fpReductionEl = document.getElementById('fp-reduction-percentage');
        if (fpReductionEl) {
            this.animateNumber(fpReductionEl, stats.false_positive_reduction.reduction_percentage, '%');
        }
        
        // System Health
        const systemHealthEl = document.getElementById('system-health-score');
        if (systemHealthEl) {
            this.updateSystemHealth(systemHealthEl, stats.system_health);
        }
    }
    
    /**
     * Update security events chart
     */
    updateSecurityEventsChart(eventsData) {
        if (!this.charts.securityEvents || !eventsData) return;
        
        // Generate last 24 hours labels
        const labels = [];
        const now = new Date();
        for (let i = 23; i >= 0; i--) {
            const time = new Date(now - i * 60 * 60 * 1000);
            labels.push(time.getHours() + ':00');
        }
        
        // Simulate hourly data based on recent events
        const eventsCount = new Array(24).fill(0);
        const blockedCount = new Array(24).fill(0);
        
        if (eventsData.recent_events) {
            eventsData.recent_events.forEach(event => {
                const eventHour = new Date(event.timestamp).getHours();
                const hourIndex = 23 - (now.getHours() - eventHour);
                if (hourIndex >= 0 && hourIndex < 24) {
                    eventsCount[hourIndex]++;
                    if (event.severity === 'critical' || event.severity === 'high') {
                        blockedCount[hourIndex]++;
                    }
                }
            });
        }
        
        this.charts.securityEvents.data.labels = labels;
        this.charts.securityEvents.data.datasets[0].data = eventsCount;
        this.charts.securityEvents.data.datasets[1].data = blockedCount;
        this.charts.securityEvents.update();
    }
    
    /**
     * Update threat distribution chart
     */
    updateThreatDistributionChart(threatData) {
        if (!this.charts.threatDistribution || !threatData) return;
        
        const distribution = threatData.severity_distribution || {
            critical: 5,
            high: 12,
            medium: 28,
            low: 55
        };
        
        this.charts.threatDistribution.data.datasets[0].data = [
            distribution.critical,
            distribution.high,
            distribution.medium,
            distribution.low
        ];
        this.charts.threatDistribution.update();
    }
    
    /**
     * Update Cloudflare metrics panel
     */
    updateCloudflareMetrics(protectionData) {
        if (!protectionData) return;
        
        // Protection Status
        const statusEl = document.getElementById('cf-protection-status');
        if (statusEl) {
            statusEl.textContent = protectionData.protection_status?.status || 'Active';
        }
        
        // Requests Analyzed
        const requestsEl = document.getElementById('cf-requests-analyzed');
        if (requestsEl) {
            this.animateNumber(requestsEl, protectionData.requests_analyzed?.total_requests || 0);
        }
        
        // Threats Mitigated
        const threatsEl = document.getElementById('cf-threats-mitigated');
        if (threatsEl) {
            this.animateNumber(threatsEl, protectionData.threats_mitigated?.total_threats || 0);
        }
        
        // Edge Cache Rate
        const cacheRateEl = document.getElementById('cf-cache-rate');
        if (cacheRateEl) {
            const rate = protectionData.edge_vs_origin_ratio?.edge_percentage || 95;
            this.animateNumber(cacheRateEl, rate, '%');
        }
    }
    
    /**
     * Update mobile carrier protection section
     */
    updateMobileCarrierProtection(protectionData) {
        if (!protectionData) return;
        
        // Protected Requests
        const requestsEl = document.getElementById('protected-requests-count');
        if (requestsEl) {
            this.animateNumber(requestsEl, protectionData.requests_protected || 0);
        }
        
        // False Positives Prevented
        const preventedEl = document.getElementById('prevented-fp-count');
        if (preventedEl) {
            this.animateNumber(preventedEl, protectionData.false_positives_prevented || 0);
        }
        
        // Protected Carriers List
        const carriersEl = document.getElementById('protected-carriers-list');
        if (carriersEl && protectionData.protected_carriers) {
            carriersEl.innerHTML = protectionData.protected_carriers.map(carrier => 
                `<span class="carrier-badge">${carrier}</span>`
            ).join('');
        }
    }
    
    /**
     * Update events timeline
     */
    updateEventsTimeline(events) {
        const timelineEl = document.getElementById('events-timeline');
        if (!timelineEl || !events) return;
        
        timelineEl.innerHTML = events.slice(0, 10).map(event => {
            const time = new Date(event.timestamp).toLocaleTimeString();
            const severityClass = event.severity || 'medium';
            
            return `
                <div class="timeline-item">
                    <div class="timeline-dot ${severityClass}"></div>
                    <div class="timeline-content">
                        <div class="timeline-time">${time}</div>
                        <div class="timeline-title">${this.getEventTitle(event)}</div>
                        <div class="timeline-description">${this.getEventDescription(event)}</div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    /**
     * Start real-time updates
     */
    startRealTimeUpdates() {
        if (!this.realTimeEnabled) return;
        
        this.updateInterval = setInterval(async () => {
            try {
                await this.fetchRealTimeUpdates();
            } catch (error) {
                console.error('Real-time update error:', error);
            }
        }, 30000); // Update every 30 seconds
    }
    
    /**
     * Fetch real-time updates
     */
    async fetchRealTimeUpdates() {
        try {
            const response = await fetch('/admin/security/realtime-updates', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.updateRealTimeData(data.data);
            }
            
        } catch (error) {
            console.error('Error fetching real-time updates:', error);
        }
    }
    
    /**
     * Update real-time data
     */
    updateRealTimeData(data) {
        // Update quick stats
        if (data.security_updates?.quick_stats) {
            this.updateQuickStats(data.security_updates.quick_stats);
        }
        
        // Update Cloudflare metrics
        if (data.cloudflare_metrics) {
            this.updateRealTimeCloudflareMetrics(data.cloudflare_metrics);
        }
        
        // Add new events to timeline
        if (data.security_updates?.latest_events) {
            this.prependNewEvents(data.security_updates.latest_events);
        }
    }
    
    /**
     * Change time range
     */
    async changeTimeRange(hours) {
        this.currentTimeRange = hours;
        
        // Update active button
        document.querySelectorAll('.time-range-btn').forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.dataset.hours) === hours);
        });
        
        // Reload data
        await this.loadInitialData();
    }
    
    /**
     * Toggle real-time updates
     */
    toggleRealTime(enabled) {
        this.realTimeEnabled = enabled;
        
        if (enabled) {
            this.startRealTimeUpdates();
        } else {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        }
        
        // Update UI status
        const statusEl = document.querySelector('.realtime-status');
        if (statusEl) {
            statusEl.style.display = enabled ? 'flex' : 'none';
        }
    }
    
    /**
     * Refresh all dashboard data
     */
    async refreshAllData() {
        const refreshBtn = document.getElementById('refresh-dashboard');
        if (refreshBtn) {
            refreshBtn.classList.add('loading');
        }
        
        await this.loadInitialData();
        
        if (refreshBtn) {
            refreshBtn.classList.remove('loading');
        }
    }
    
    /**
     * Export dashboard data
     */
    async exportData(format) {
        try {
            const response = await fetch(`/admin/security/export-data?format=${format}&hours=${this.currentTimeRange}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Export failed: ${response.status}`);
            }
            
            // Handle file download
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `security-dashboard-${Date.now()}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            
            this.showSuccessMessage(`Data exported successfully as ${format.toUpperCase()}`);
            
        } catch (error) {
            console.error('Export error:', error);
            this.showErrorMessage('Failed to export data');
        }
    }
    
    /**
     * Utility: Animate number counter
     */
    animateNumber(element, target, suffix = '') {
        const current = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
        const increment = (target - current) / 20;
        let step = current;
        
        const timer = setInterval(() => {
            step += increment;
            if ((increment > 0 && step >= target) || (increment < 0 && step <= target)) {
                element.textContent = target + suffix;
                clearInterval(timer);
            } else {
                element.textContent = Math.round(step) + suffix;
            }
        }, 50);
    }
    
    /**
     * Utility: Update system health indicator
     */
    updateSystemHealth(element, score) {
        element.textContent = score + '%';
        element.className = 'stat-value';
        
        if (score >= 90) {
            element.classList.add('health-excellent');
        } else if (score >= 70) {
            element.classList.add('health-good');
        } else if (score >= 50) {
            element.classList.add('health-warning');
        } else {
            element.classList.add('health-critical');
        }
    }
    
    /**
     * Utility: Get event title from event data
     */
    getEventTitle(event) {
        const titles = {
            'security_event_logged': 'Security Event Detected',
            'suspicious_activity_detected': 'Suspicious Activity',
            'threat_blocked': 'Threat Blocked',
            'behavior_anomaly_detected': 'Behavior Anomaly'
        };
        
        return titles[event.event_type] || 'Security Event';
    }
    
    /**
     * Utility: Get event description from event data
     */
    getEventDescription(event) {
        if (event.details) {
            return event.details.substring(0, 100) + '...';
        }
        
        return `${event.event_type} from ${event.ip_address}`;
    }
    
    /**
     * Show loading state
     */
    showLoadingState() {
        document.querySelectorAll('.chart-container').forEach(container => {
            container.classList.add('loading');
        });
    }
    
    /**
     * Hide loading state
     */
    hideLoadingState() {
        document.querySelectorAll('.chart-container').forEach(container => {
            container.classList.remove('loading');
        });
    }
    
    /**
     * Show error state
     */
    showErrorState(message) {
        const errorEl = document.getElementById('dashboard-error');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
        }
    }
    
    /**
     * Show success message
     */
    showSuccessMessage(message) {
        // Implementation for success message display
        console.log('Success:', message);
    }
    
    /**
     * Show error message
     */
    showErrorMessage(message) {
        // Implementation for error message display
        console.error('Error:', message);
    }
    
    /**
     * Update last refresh time
     */
    updateLastRefreshTime() {
        const timeEl = document.getElementById('last-refresh-time');
        if (timeEl) {
            timeEl.textContent = new Date().toLocaleTimeString();
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.securityDashboard = new EnhancedSecurityDashboard();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnhancedSecurityDashboard;
}