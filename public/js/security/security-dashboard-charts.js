/**
 * ========================================
 * SECURITY DASHBOARD CHARTS JS
 * Chart.js integration for Enhanced Security Dashboard
 * Following workinginstruction.md: Modular JavaScript architecture
 * ========================================
 */

class SecurityDashboardCharts {
    constructor(dashboard) {
        this.dashboard = dashboard;
        this.charts = {};
        this.chartConfigs = {};
        this.chartData = {};
        
        // Chart.js default configuration
        this.defaultConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#667eea',
                    borderWidth: 1
                }
            },
            animation: {
                duration: 750,
                easing: 'easeInOutQuart'
            }
        };
    }

    /**
     * Initialize all charts
     */
    async initializeCharts() {
        console.log('📊 Initializing dashboard charts...');
        
        try {
            // Wait for Chart.js to be available
            if (typeof Chart === 'undefined') {
                await this.loadChartJS();
            }

            // Initialize individual charts
            await Promise.all([
                this.initThreatTimelineChart(),
                this.initResponseTimeChart(),
                this.initGeoDistributionChart(),
                this.initEventDistributionChart(),
                this.initPerformanceChart(),
                this.initAttackPatternChart()
            ]);

            console.log('✅ All charts initialized successfully');
            
        } catch (error) {
            console.error('❌ Chart initialization failed:', error);
            throw error;
        }
    }

    /**
     * Load Chart.js if not available
     */
    async loadChartJS() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Initialize Threat Timeline Chart
     */
    async initThreatTimelineChart() {
        const canvas = document.getElementById('threatTimelineChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.threatTimeline = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'High Risk Events',
                        data: [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Medium Risk Events',
                        data: [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Low Risk Events',
                        data: [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                ...this.defaultConfig,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                },
                plugins: {
                    ...this.defaultConfig.plugins,
                    title: {
                        display: true,
                        text: 'Security Events Timeline'
                    }
                }
            }
        });
    }

    /**
     * Initialize Response Time Chart
     */
    async initResponseTimeChart() {
        const canvas = document.getElementById('responseTimeChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.responseTime = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Firewall', 'DDoS Protection', 'Bot Detection', 'Rate Limiting', 'Geo Blocking'],
                datasets: [
                    {
                        label: 'Average Response Time (ms)',
                        data: [],
                        backgroundColor: [
                            '#667eea',
                            '#764ba2',
                            '#f093fb',
                            '#f5576c',
                            '#4facfe'
                        ],
                        borderRadius: 8
                    }
                ]
            },
            options: {
                ...this.defaultConfig,
                plugins: {
                    ...this.defaultConfig.plugins,
                    title: {
                        display: true,
                        text: 'Security Component Response Times'
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Response Time (ms)'
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize Geographic Distribution Chart
     */
    async initGeoDistributionChart() {
        const canvas = document.getElementById('geoDistributionChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.geoDistribution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#43e97b',
                        '#38f9d7',
                        '#ffecd2'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                ...this.defaultConfig,
                cutout: '60%',
                plugins: {
                    ...this.defaultConfig.plugins,
                    title: {
                        display: true,
                        text: 'Traffic by Country'
                    },
                    legend: {
                        position: 'right',
                        labels: {
                            generateLabels: (chart) => {
                                const data = chart.data;
                                return data.labels.map((label, i) => ({
                                    text: `${label}: ${data.datasets[0].data[i]}%`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                }));
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize Event Distribution Chart
     */
    async initEventDistributionChart() {
        const canvas = document.getElementById('eventDistributionChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.eventDistribution = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Bot Attacks', 'DDoS Attempts', 'SQL Injection', 'XSS Attempts', 'Brute Force', 'Other'],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#ef4444',
                        '#f59e0b',
                        '#8b5cf6',
                        '#06b6d4',
                        '#10b981',
                        '#6b7280'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                ...this.defaultConfig,
                plugins: {
                    ...this.defaultConfig.plugins,
                    title: {
                        display: true,
                        text: 'Security Event Types (Last 24h)'
                    }
                }
            }
        });
    }

    /**
     * Initialize Performance Chart
     */
    async initPerformanceChart() {
        const canvas = document.getElementById('performanceChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.performance = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Response Time', 'Throughput', 'Success Rate', 'Error Rate', 'Uptime', 'Resource Usage'],
                datasets: [
                    {
                        label: 'Current Performance',
                        data: [],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.2)',
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: '#667eea'
                    },
                    {
                        label: 'Baseline',
                        data: [80, 75, 95, 5, 99, 70],
                        borderColor: '#e5e7eb',
                        backgroundColor: 'rgba(229, 231, 235, 0.1)',
                        pointBackgroundColor: '#e5e7eb',
                        pointBorderColor: '#fff',
                        borderDash: [5, 5]
                    }
                ]
            },
            options: {
                ...this.defaultConfig,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        angleLines: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                },
                plugins: {
                    ...this.defaultConfig.plugins,
                    title: {
                        display: true,
                        text: 'Security Performance Metrics'
                    }
                }
            }
        });
    }

    /**
     * Initialize Attack Pattern Chart
     */
    async initAttackPatternChart() {
        const canvas = document.getElementById('attackPatternChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        this.charts.attackPattern = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Attack Attempts',
                        data: [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Blocked Attacks',
                        data: [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                ...this.defaultConfig,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'hour',
                            displayFormats: {
                                hour: 'HH:mm'
                            }
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Events'
                        }
                    }
                },
                plugins: {
                    ...this.defaultConfig.plugins,
                    title: {
                        display: true,
                        text: 'Attack Patterns (24h)'
                    }
                }
            }
        });
    }

    /**
     * Update chart data
     */
    updateChartData(chartName, data) {
        const chart = this.charts[chartName];
        if (!chart) {
            console.warn(`Chart '${chartName}' not found`);
            return;
        }

        try {
            // Update data based on chart type
            switch (chartName) {
                case 'threatTimeline':
                    this.updateThreatTimelineData(chart, data);
                    break;
                case 'responseTime':
                    this.updateResponseTimeData(chart, data);
                    break;
                case 'geoDistribution':
                    this.updateGeoDistributionData(chart, data);
                    break;
                case 'eventDistribution':
                    this.updateEventDistributionData(chart, data);
                    break;
                case 'performance':
                    this.updatePerformanceData(chart, data);
                    break;
                case 'attackPattern':
                    this.updateAttackPatternData(chart, data);
                    break;
            }

            // Update chart
            chart.update('active');
            
        } catch (error) {
            console.error(`Failed to update chart '${chartName}':`, error);
        }
    }

    /**
     * Update threat timeline chart data
     */
    updateThreatTimelineData(chart, data) {
        chart.data.labels = data.labels || [];
        chart.data.datasets[0].data = data.high_risk || [];
        chart.data.datasets[1].data = data.medium_risk || [];
        chart.data.datasets[2].data = data.low_risk || [];
    }

    /**
     * Update response time chart data
     */
    updateResponseTimeData(chart, data) {
        chart.data.datasets[0].data = [
            data.firewall || 0,
            data.ddos_protection || 0,
            data.bot_detection || 0,
            data.rate_limiting || 0,
            data.geo_blocking || 0
        ];
    }

    /**
     * Update geographic distribution chart data
     */
    updateGeoDistributionData(chart, data) {
        chart.data.labels = data.countries || [];
        chart.data.datasets[0].data = data.percentages || [];
    }

    /**
     * Update event distribution chart data
     */
    updateEventDistributionData(chart, data) {
        chart.data.datasets[0].data = [
            data.bot_attacks || 0,
            data.ddos_attempts || 0,
            data.sql_injection || 0,
            data.xss_attempts || 0,
            data.brute_force || 0,
            data.other || 0
        ];
    }

    /**
     * Update performance chart data
     */
    updatePerformanceData(chart, data) {
        chart.data.datasets[0].data = [
            data.response_time || 0,
            data.throughput || 0,
            data.success_rate || 0,
            100 - (data.error_rate || 0), // Invert error rate
            data.uptime || 0,
            data.resource_usage || 0
        ];
    }

    /**
     * Update attack pattern chart data
     */
    updateAttackPatternData(chart, data) {
        chart.data.labels = data.timestamps || [];
        chart.data.datasets[0].data = data.attempts || [];
        chart.data.datasets[1].data = data.blocked || [];
    }

    /**
     * Handle timeline period change
     */
    handleTimelineChange(period) {
        const buttons = document.querySelectorAll('.timeline-btn');
        buttons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.period === period) {
                btn.classList.add('active');
            }
        });

        // Reload timeline chart data
        this.dashboard.fetchAndUpdateChart('threatTimeline', { period });
    }

    /**
     * Handle chart tab change
     */
    handleChartTabChange(chartName, metric) {
        const tabs = document.querySelectorAll(`[data-chart="${chartName}"] .chart-tab`);
        tabs.forEach(tab => {
            tab.classList.remove('active');
            if (tab.dataset.metric === metric) {
                tab.classList.add('active');
            }
        });

        // Reload chart data for selected metric
        this.dashboard.fetchAndUpdateChart(chartName, { metric });
    }

    /**
     * Handle pattern filter change
     */
    handlePatternFilterChange(pattern) {
        const filter = document.querySelector(`[data-pattern="${pattern}"]`);
        if (filter) {
            filter.classList.toggle('active');
        }

        // Get active filters
        const activeFilters = Array.from(document.querySelectorAll('.pattern-filter.active'))
            .map(f => f.dataset.pattern);

        // Reload attack pattern chart with filters
        this.dashboard.fetchAndUpdateChart('attackPattern', { filters: activeFilters });
    }

    /**
     * Destroy all charts
     */
    destroyCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }

    /**
     * Resize charts
     */
    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }
}

// Export for use in main dashboard
window.SecurityDashboardCharts = SecurityDashboardCharts;