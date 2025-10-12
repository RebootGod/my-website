/**
 * Dashboard V2 JavaScript
 * Handles charts, storage calculation, and interactions
 * Max 350 lines per workinginstruction.md
 */

(function() {
    'use strict';

    // Wait for DOM and Chart.js to load
    document.addEventListener('DOMContentLoaded', function() {
        initDashboard();
    });

    /**
     * Initialize dashboard
     */
    function initDashboard() {
        console.log('Dashboard V2 initializing...');

        // Initialize charts
        initContentGrowthChart();
        initUserActivityChart();

        // Calculate storage
        calculateStorage();

        // Setup refresh handler
        setupRefreshHandler();

        console.log('Dashboard V2 initialized');
    }

    /**
     * Initialize Content Growth Chart
     */
    function initContentGrowthChart() {
        const canvas = document.getElementById('contentGrowthChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const data = window.dashboardData?.contentGrowth;

        if (!data || !data.daily_breakdown) {
            showChartError(canvas, 'No content growth data available');
            return;
        }

        // Parse daily breakdown
        const labels = [];
        const moviesData = [];
        const seriesData = [];

        // Process daily breakdown
        if (data.daily_breakdown && Array.isArray(data.daily_breakdown)) {
            // Create date range for last 30 days
            const today = new Date();
            for (let i = 29; i >= 0; i--) {
                const date = new Date(today);
                date.setDate(date.getDate() - i);
                const dateStr = date.toISOString().split('T')[0];
                labels.push(formatDate(date));
                
                // Find data for this date
                const dayData = data.daily_breakdown.find(d => d.date === dateStr);
                moviesData.push(dayData?.movies || 0);
                seriesData.push(dayData?.series || 0);
            }
        }

        // Create chart
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Movies',
                        data: moviesData,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Series',
                        data: seriesData,
                        borderColor: '#f093fb',
                        backgroundColor: 'rgba(240, 147, 251, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: getComputedStyle(document.documentElement)
                                .getPropertyValue('--text-primary').trim(),
                            padding: 15,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: getComputedStyle(document.documentElement)
                                .getPropertyValue('--text-secondary').trim(),
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 8
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: getComputedStyle(document.documentElement)
                                .getPropertyValue('--border-color').trim(),
                            borderDash: [5, 5]
                        },
                        ticks: {
                            color: getComputedStyle(document.documentElement)
                                .getPropertyValue('--text-secondary').trim(),
                            stepSize: 1
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * Initialize User Activity Chart
     */
    function initUserActivityChart() {
        const canvas = document.getElementById('userActivityChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const data = window.dashboardData?.userActivity;

        if (!data) {
            showChartError(canvas, 'No user activity data available');
            return;
        }

        // Create chart
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Daily', 'Weekly', 'Monthly'],
                datasets: [{
                    label: 'Active Users',
                    data: [
                        data.daily_active || 0,
                        data.weekly_active || 0,
                        data.monthly_active || 0
                    ],
                    backgroundColor: [
                        'rgba(79, 172, 254, 0.8)',
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(240, 147, 251, 0.8)'
                    ],
                    borderColor: [
                        '#4facfe',
                        '#667eea',
                        '#f093fb'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 13,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                return 'Active Users: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: getComputedStyle(document.documentElement)
                                .getPropertyValue('--text-secondary').trim(),
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: getComputedStyle(document.documentElement)
                                .getPropertyValue('--border-color').trim(),
                            borderDash: [5, 5]
                        },
                        ticks: {
                            color: getComputedStyle(document.documentElement)
                                .getPropertyValue('--text-secondary').trim(),
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Calculate and display storage usage
     */
    function calculateStorage() {
        const storageValue = document.getElementById('storage-value');
        const storagePercent = document.getElementById('storage-percent');
        
        if (!storageValue || !storagePercent) return;

        const storage = window.dashboardData?.storage;
        
        if (!storage || storage.total === 0) {
            storageValue.textContent = 'N/A';
            storagePercent.textContent = 'Storage info unavailable';
            return;
        }

        // Calculate storage
        const used = storage.used || 0;
        const total = storage.total || 0;
        const usedGB = (used / (1024 ** 3)).toFixed(2);
        const totalGB = (total / (1024 ** 3)).toFixed(2);
        const percentage = total > 0 ? ((used / total) * 100).toFixed(1) : 0;

        // Update display
        storageValue.textContent = `${usedGB} GB`;
        storagePercent.textContent = `${percentage}% of ${totalGB} GB`;

        // Update color based on usage
        if (percentage > 90) {
            storagePercent.classList.remove('stat-change-neutral', 'stat-change-up');
            storagePercent.classList.add('stat-change-down');
        } else if (percentage > 70) {
            storagePercent.classList.remove('stat-change-neutral', 'stat-change-down');
            storagePercent.classList.add('stat-change-up');
        }
    }

    /**
     * Show chart error message
     */
    function showChartError(canvas, message) {
        const container = canvas.parentElement;
        container.innerHTML = `
            <div class="empty-state">
                <p>${message}</p>
            </div>
        `;
    }

    /**
     * Format date for chart labels
     */
    function formatDate(date) {
        const options = { month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    /**
     * Setup refresh handler
     */
    function setupRefreshHandler() {
        // Listen for theme changes to update charts
        window.addEventListener('themeChanged', function(e) {
            console.log('Theme changed to:', e.detail.theme);
            // Charts will automatically adapt via CSS variables
        });
    }

    /**
     * Export dashboard layout (for future drag & drop feature)
     */
    window.exportDashboardLayout = function() {
        console.log('Export layout feature coming soon');
    };

    /**
     * Import dashboard layout (for future drag & drop feature)
     */
    window.importDashboardLayout = function() {
        console.log('Import layout feature coming soon');
    };

    /**
     * Reset dashboard layout (for future drag & drop feature)
     */
    window.resetDashboardLayout = function() {
        if (confirm('Reset dashboard to default layout?')) {
            localStorage.removeItem('admin_dashboard_layout');
            window.location.reload();
        }
    };

})();
