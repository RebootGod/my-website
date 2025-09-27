/* Admin User Activity Dashboard JavaScript - Extracted from admin/user-activity/index.blade.php */

// User Activity Charts - Chart.js Integration

// Prevent double initialization
let chartsInitialized = false;

// Global variable untuk chart data (akan di-initialize dari blade template)
let chartData = {};

// Initialize function untuk dipanggil dari blade template dengan data
function initializeUserActivityDashboard(data) {
    chartData = data;
    initializeCharts();
}

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

// Make functions globally available
window.showContentTab = showContentTab;
window.refreshDashboard = refreshDashboard;
window.initializeUserActivityDashboard = initializeUserActivityDashboard;