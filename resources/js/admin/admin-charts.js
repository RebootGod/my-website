/**
 * ========================================
 * ADMIN CHARTS & VISUALIZATIONS
 * Chart.js integration for admin dashboard
 * ========================================
 */

// Extend Admin namespace
window.Admin = window.Admin || {};

/**
 * Charts Manager
 */
Admin.Charts = {
    isInitialized: false,
    charts: new Map(),
    chartDefaults: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#d1d5db',
                    font: {
                        size: 12
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#9ca3af',
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: '#374151',
                    borderColor: '#4b5563'
                }
            },
            y: {
                ticks: {
                    color: '#9ca3af',
                    font: {
                        size: 11
                    }
                },
                grid: {
                    color: '#374151',
                    borderColor: '#4b5563'
                }
            }
        }
    },

    init: function() {
        if (this.isInitialized) return;

        console.log('📊 Admin Charts: Initializing...');

        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            this.loadChartJS().then(() => {
                this.setupCharts();
            });
        } else {
            this.setupCharts();
        }

        this.isInitialized = true;
        console.log('✅ Admin Charts: Initialized successfully');
    },

    /**
     * Load Chart.js from CDN
     */
    loadChartJS: function() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    },

    /**
     * Setup all dashboard charts
     */
    setupCharts: function() {
        this.createContentGrowthChart();
        this.createUserActivityChart();
        this.createPopularGenresChart();
        this.createViewsOverTimeChart();
        this.initChartToggles();
    },

    /**
     * Create content growth chart
     */
    createContentGrowthChart: function() {
        const canvas = document.getElementById('contentGrowthChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Use real data from backend or fallback to empty data
        const contentGrowth = window.adminDashboardData?.contentGrowth || {};
        const labels = contentGrowth.labels || this.generateDateLabels(30);
        const moviesData = contentGrowth.daily_breakdown?.movies || Array(30).fill(0);
        const seriesData = contentGrowth.daily_breakdown?.series || Array(30).fill(0);

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Movies Added',
                        data: moviesData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Series Added',
                        data: seriesData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                ...this.chartDefaults,
                plugins: {
                    ...this.chartDefaults.plugins,
                    title: {
                        display: false
                    }
                },
                scales: {
                    ...this.chartDefaults.scales,
                    y: {
                        ...this.chartDefaults.scales.y,
                        beginAtZero: true,
                        suggestedMax: 10
                    }
                }
            }
        });

        this.charts.set('contentGrowth', chart);
    },

    /**
     * Create user activity chart
     */
    createUserActivityChart: function() {
        const canvas = document.getElementById('userActivityChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Use real user activity data from backend
        const userActivity = window.adminDashboardData?.userActivity || {};
        const labels = ['Daily Active', 'Weekly Active', 'Monthly Active', 'New Users'];
        const data = [
            userActivity.daily_active || 0,
            userActivity.weekly_active || 0,
            userActivity.monthly_active || 0,
            userActivity.new_users || 0
        ];

        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderColor: '#1f2937',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#d1d5db',
                            font: {
                                size: 12
                            },
                            padding: 15
                        }
                    }
                }
            }
        });

        this.charts.set('userActivity', chart);
    },

    /**
     * Create popular genres chart
     */
    createPopularGenresChart: function() {
        const canvas = document.getElementById('popularGenresChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        const labels = ['Action', 'Comedy', 'Drama', 'Horror', 'Sci-Fi', 'Romance'];
        const data = [45, 32, 28, 15, 12, 8];

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Movies',
                    data: data,
                    backgroundColor: '#10b981',
                    borderColor: '#059669',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                ...this.chartDefaults,
                scales: {
                    ...this.chartDefaults.scales,
                    y: {
                        ...this.chartDefaults.scales.y,
                        beginAtZero: true
                    }
                }
            }
        });

        this.charts.set('popularGenres', chart);
    },

    /**
     * Create views over time chart
     */
    createViewsOverTimeChart: function() {
        const canvas = document.getElementById('viewsOverTimeChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Use real views data from backend or fallback to empty data
        const viewsData = window.adminDashboardData?.viewsOverTime || {};
        const labels = viewsData.labels || this.generateDateLabels(7);
        const data = viewsData.data || Array(7).fill(0);

        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Views',
                    data: data,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                ...this.chartDefaults,
                scales: {
                    ...this.chartDefaults.scales,
                    y: {
                        ...this.chartDefaults.scales.y,
                        beginAtZero: true
                    }
                }
            }
        });

        this.charts.set('viewsOverTime', chart);
    },

    /**
     * Initialize chart toggle buttons
     */
    initChartToggles: function() {
        const toggles = document.querySelectorAll('.chart-toggle');

        toggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                const chartContainer = e.target.closest('.chart-container');
                const toggleGroup = chartContainer.querySelectorAll('.chart-toggle');
                const period = e.target.dataset.period;

                // Update active state
                toggleGroup.forEach(t => t.classList.remove('active'));
                e.target.classList.add('active');

                // Update chart data based on period
                this.updateChartPeriod(chartContainer, period);
            });
        });
    },

    /**
     * Update chart data based on selected period
     */
    updateChartPeriod: function(container, period) {
        const chartCanvas = container.querySelector('canvas');
        if (!chartCanvas) return;

        const chartId = chartCanvas.id;
        const chart = this.charts.get(chartId.replace('Chart', ''));

        if (!chart) return;

        // Show loading state
        this.showChartLoading(container);

        // TODO: Replace with real API call to fetch updated data
        // For now, show empty data in production
        setTimeout(() => {
            const days = period === '7d' ? 7 : period === '30d' ? 30 : 90;
            const newLabels = this.generateDateLabels(days);
            const newData = Array(days).fill(0); // Empty data for production

            chart.data.labels = newLabels;
            chart.data.datasets[0].data = newData;
            chart.update('active');

            this.hideChartLoading(container);
        }, 500);
    },

    /**
     * Show chart loading state
     */
    showChartLoading: function(container) {
        const chartCanvas = container.querySelector('canvas');
        if (chartCanvas) {
            chartCanvas.style.opacity = '0.5';
        }

        // Add loading indicator if not exists
        if (!container.querySelector('.chart-loading')) {
            const loading = document.createElement('div');
            loading.className = 'chart-loading';
            loading.innerHTML = '<div class="loading-spinner"></div>Loading...';
            container.appendChild(loading);
        }
    },

    /**
     * Hide chart loading state
     */
    hideChartLoading: function(container) {
        const chartCanvas = container.querySelector('canvas');
        const loading = container.querySelector('.chart-loading');

        if (chartCanvas) {
            chartCanvas.style.opacity = '1';
        }

        if (loading) {
            loading.remove();
        }
    },

    /**
     * Generate date labels for charts
     */
    generateDateLabels: function(days) {
        const labels = [];
        const today = new Date();

        for (let i = days - 1; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            }));
        }

        return labels;
    },

    /**
     * Generate random data for demo charts
     */
    generateRandomData: function(length, min, max) {
        const data = [];
        for (let i = 0; i < length; i++) {
            data.push(Math.floor(Math.random() * (max - min + 1)) + min);
        }
        return data;
    },

    /**
     * Refresh all charts with new data
     */
    refreshCharts: function() {
        // This would typically fetch new data from the server
        console.log('Refreshing charts...');

        this.charts.forEach((chart, chartId) => {
            const container = document.getElementById(chartId + 'Chart')?.closest('.chart-container');
            if (container) {
                this.showChartLoading(container);

                // TODO: Replace with real API call to refresh data
                // For now, keep existing data in production
                setTimeout(() => {
                    // Keep the existing data instead of generating random data
                    console.log('Chart refresh completed - using existing data for production');

                    // No data update needed - chart will keep current real data
                    // if (chart.config.type === 'doughnut') {
                    //     chart.data.datasets[0].data = newData;
                    // } else {
                    //     chart.data.datasets.forEach((dataset, index) => {
                    //         dataset.data = this.generateRandomData(
                    //             chart.config.type === 'line' ? 10 : 500
                    //         );
                    //     });
                    // }

                    // chart.update('active'); // Commented out to keep existing data
                    this.hideChartLoading(container);
                }, 1000);
            }
        });
    },

    /**
     * Destroy all charts
     */
    destroyCharts: function() {
        this.charts.forEach(chart => chart.destroy());
        this.charts.clear();
    },

    /**
     * Update chart themes for dark/light mode
     */
    updateTheme: function(isDark) {
        const colors = isDark ? {
            text: '#d1d5db',
            grid: '#374151',
            border: '#4b5563'
        } : {
            text: '#374151',
            grid: '#e5e7eb',
            border: '#d1d5db'
        };

        this.charts.forEach(chart => {
            if (chart.options.scales) {
                if (chart.options.scales.x) {
                    chart.options.scales.x.ticks.color = colors.text;
                    chart.options.scales.x.grid.color = colors.grid;
                }
                if (chart.options.scales.y) {
                    chart.options.scales.y.ticks.color = colors.text;
                    chart.options.scales.y.grid.color = colors.grid;
                }
            }

            if (chart.options.plugins?.legend?.labels) {
                chart.options.plugins.legend.labels.color = colors.text;
            }

            chart.update('none');
        });
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on the dashboard page
    if (document.querySelector('#contentGrowthChart') ||
        document.querySelector('#userActivityChart')) {
        Admin.Charts.init();
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Admin.Charts;
}