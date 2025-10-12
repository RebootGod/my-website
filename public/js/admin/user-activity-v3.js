/**
 * ========================================
 * USER ACTIVITY V3 - JAVASCRIPT
 * Interactive features and chart visualization
 * ======================================== */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        autoRefreshInterval: 30000, // 30 seconds
        chartColors: {
            primary: '#3b82f6',
            primaryLight: 'rgba(59, 130, 246, 0.1)',
            border: '#e2e8f0',
            text: '#64748b'
        }
    };

    // State
    let autoRefreshTimer = null;
    let activityChart = null;

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        initChart();
        initAutoRefresh();
        initExport();
        initTooltips();
    });

    /**
     * Initialize Activity Chart
     */
    function initChart() {
        const canvas = document.getElementById('activityChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const data = window.activityData || {};
        const dailyTrend = data.dailyTrend || {};
        
        // Prepare chart data - handle new structure with labels and data arrays
        let labels = [];
        let values = [];
        
        if (dailyTrend.labels && dailyTrend.data) {
            // New structure: { labels: [...], data: [...] }
            labels = dailyTrend.labels || [];
            values = dailyTrend.data || [];
        } else {
            // Fallback for old structure: { 'date': count, ... }
            labels = Object.keys(dailyTrend).sort();
            values = labels.map(date => dailyTrend[date] || 0);
        }

        // Get theme
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        
        // Update colors based on theme
        if (isDark) {
            CONFIG.chartColors.border = '#334155';
            CONFIG.chartColors.text = '#cbd5e1';
        }

        // Create chart
        activityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Activities',
                    data: values,
                    borderColor: CONFIG.chartColors.primary,
                    backgroundColor: CONFIG.chartColors.primaryLight,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: CONFIG.chartColors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
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
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#64748b',
                        borderColor: CONFIG.chartColors.border,
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' activities';
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
                            color: CONFIG.chartColors.text,
                            font: {
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: CONFIG.chartColors.border,
                            drawBorder: false
                        },
                        ticks: {
                            color: CONFIG.chartColors.text,
                            font: {
                                size: 11
                            },
                            callback: function(value) {
                                return Number.isInteger(value) ? value : '';
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });

        // Update chart on theme change
        document.addEventListener('themeChanged', function(e) {
            const isDark = e.detail.theme === 'dark';
            updateChartTheme(isDark);
        });
    }

    /**
     * Update chart theme colors
     */
    function updateChartTheme(isDark) {
        if (!activityChart) return;

        const borderColor = isDark ? '#334155' : '#e2e8f0';
        const textColor = isDark ? '#cbd5e1' : '#64748b';
        const tooltipBg = isDark ? '#1e293b' : '#ffffff';
        const tooltipTitle = isDark ? '#f1f5f9' : '#0f172a';
        const tooltipBody = isDark ? '#cbd5e1' : '#64748b';

        activityChart.options.scales.x.ticks.color = textColor;
        activityChart.options.scales.y.ticks.color = textColor;
        activityChart.options.scales.y.grid.color = borderColor;
        activityChart.options.plugins.tooltip.backgroundColor = tooltipBg;
        activityChart.options.plugins.tooltip.titleColor = tooltipTitle;
        activityChart.options.plugins.tooltip.bodyColor = tooltipBody;
        activityChart.options.plugins.tooltip.borderColor = borderColor;
        
        activityChart.update();
    }

    /**
     * Initialize auto-refresh functionality
     */
    function initAutoRefresh() {
        const toggleBtn = document.getElementById('autoRefreshToggle');
        if (!toggleBtn) return;

        toggleBtn.addEventListener('click', function() {
            if (autoRefreshTimer) {
                // Disable auto-refresh
                clearInterval(autoRefreshTimer);
                autoRefreshTimer = null;
                toggleBtn.classList.remove('active');
                showToast('Auto-refresh disabled', 'success');
            } else {
                // Enable auto-refresh
                autoRefreshTimer = setInterval(refreshData, CONFIG.autoRefreshInterval);
                toggleBtn.classList.add('active');
                showToast('Auto-refresh enabled', 'success');
            }
        });
    }

    /**
     * Refresh data from server
     */
    function refreshData() {
        const currentUrl = window.activityData?.currentUrl || window.location.href;
        
        fetch(currentUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse the new content
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update timeline content
            const newTimeline = doc.querySelector('.timeline-content');
            const currentTimeline = document.querySelector('.timeline-content');
            
            if (newTimeline && currentTimeline) {
                currentTimeline.innerHTML = newTimeline.innerHTML;
            }
            
            // Update stats
            const statValues = document.querySelectorAll('.stat-value');
            const newStatValues = doc.querySelectorAll('.stat-value');
            
            statValues.forEach((stat, index) => {
                if (newStatValues[index]) {
                    stat.textContent = newStatValues[index].textContent;
                }
            });
            
            showToast('Data refreshed', 'success');
        })
        .catch(error => {
            console.error('Refresh error:', error);
            showToast('Failed to refresh data', 'error');
        });
    }

    /**
     * Initialize export functionality
     */
    function initExport() {
        const exportBtn = document.getElementById('exportBtn');
        if (!exportBtn) return;

        exportBtn.addEventListener('click', function() {
            const currentUrl = window.activityData?.currentUrl || window.location.href;
            const exportUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'export=csv';
            
            showToast('Preparing export...', 'success');
            
            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = exportUrl;
            link.download = 'user-activity-' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            setTimeout(() => {
                showToast('Export completed', 'success');
            }, 1000);
        });
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        // Add hover effects for timeline items
        const timelineItems = document.querySelectorAll('.timeline-item');
        
        timelineItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(4px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'
                }
            </svg>
            <span class="toast-message">${escapeHtml(message)}</span>
        `;

        container.appendChild(toast);

        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                container.removeChild(toast);
            }, 300);
        }, 3000);
    }

    /**
     * Format date for chart labels
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        const month = date.toLocaleString('default', { month: 'short' });
        const day = date.getDate();
        return `${month} ${day}`;
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Reset filters
     */
    window.resetFilters = function() {
        const form = document.getElementById('filterForm');
        if (form) {
            // Reset all select elements
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Submit form to reload page
            form.submit();
        }
    };

    // Add slideOut animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

})();
