{{--
    Chart Widget Component
    
    Usage:
    @include('admin.components.widgets.chart-widget', [
        'id' => 'user-growth-chart',
        'title' => 'User Growth',
        'type' => 'line', // line, bar, pie, doughnut, area
        'data' => $chartData,
        'height' => '300px',
        'options' => $chartOptions
    ])
    
    Chart Data Format:
    $chartData = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'datasets' => [
            [
                'label' => 'Users',
                'data' => [10, 20, 30, 45, 60, 80],
                'borderColor' => '#3b82f6',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)'
            ]
        ]
    ];
--}}

@php
    $widgetId = $id ?? 'chart-' . uniqid();
    $title = $title ?? 'Chart';
    $type = $type ?? 'line'; // line, bar, pie, doughnut, area
    $data = $data ?? ['labels' => [], 'datasets' => []];
    $height = $height ?? '300px';
    $options = $options ?? [];
    $subtitle = $subtitle ?? null;
    $showLegend = $showLegend ?? true;
    $showGrid = $showGrid ?? true;
    $animated = $animated ?? true;
    $refreshInterval = $refreshInterval ?? null; // seconds
    $refreshUrl = $refreshUrl ?? null;
@endphp

<div class="dashboard-widget chart-widget" 
     id="{{ $widgetId }}" 
     data-widget-id="{{ $widgetId }}"
     draggable="true">
    
    {{-- Widget Header --}}
    <div class="widget-header">
        <div class="widget-info">
            <div class="widget-drag-handle" title="Drag to reorder">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
            </div>
            <div class="widget-title-section">
                <h3 class="widget-title">{{ $title }}</h3>
                @if($subtitle)
                    <p class="widget-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        <div class="widget-controls">
            @if($refreshUrl)
                <button type="button" 
                        class="widget-control-btn widget-refresh-btn" 
                        onclick="refreshChart{{ $widgetId }}()"
                        title="Refresh chart">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            @endif
            <button type="button" 
                    class="widget-control-btn widget-hide-btn" 
                    onclick="window.dashboardWidgets?.hideWidget('{{ $widgetId }}')"
                    title="Hide widget">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
    
    {{-- Chart Canvas --}}
    <div class="chart-container" style="height: {{ $height }}; position: relative;">
        <canvas id="{{ $widgetId }}-canvas"></canvas>
        
        {{-- Loading State --}}
        <div id="{{ $widgetId }}-loading" class="chart-loading" style="display: none;">
            <div class="loading-spinner"></div>
            <p>Loading chart data...</p>
        </div>
        
        {{-- Empty State --}}
        <div id="{{ $widgetId }}-empty" class="chart-empty" style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p>No data available</p>
        </div>
    </div>
</div>

<style>
.chart-widget {
    min-height: 350px;
}

.widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--dashboard-border, rgba(255, 255, 255, 0.1));
}

.widget-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.widget-title-section {
    display: flex;
    flex-direction: column;
}

.widget-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--dashboard-text-primary);
}

.widget-subtitle {
    font-size: 0.875rem;
    opacity: 0.7;
    margin: 0.25rem 0 0;
}

.widget-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.widget-control-btn {
    padding: 0.5rem;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s;
    color: var(--dashboard-text-primary);
}

.widget-control-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.widget-control-btn svg {
    width: 1.25rem;
    height: 1.25rem;
}

.chart-container {
    padding: 1.25rem;
    position: relative;
}

.chart-loading,
.chart-empty {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: var(--dashboard-card-bg);
    gap: 1rem;
}

.loading-spinner {
    width: 2.5rem;
    height: 2.5rem;
    border: 3px solid rgba(255, 255, 255, 0.1);
    border-top-color: var(--dashboard-primary, #3b82f6);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.chart-empty svg {
    width: 4rem;
    height: 4rem;
    opacity: 0.3;
}

.chart-empty p {
    font-size: 0.875rem;
    opacity: 0.6;
    margin: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .chart-container {
        padding: 1rem;
    }
    
    .widget-title {
        font-size: 0.9375rem;
    }
}
</style>

{{-- Chart.js Integration --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
(function() {
    const widgetId = '{{ $widgetId }}';
    const chartType = '{{ $type }}';
    const chartData = @json($data);
    const chartOptions = @json($options);
    const showLegend = {{ $showLegend ? 'true' : 'false' }};
    const showGrid = {{ $showGrid ? 'true' : 'false' }};
    const animated = {{ $animated ? 'true' : 'false' }};
    const refreshInterval = {{ $refreshInterval ?? 'null' }};
    const refreshUrl = '{{ $refreshUrl ?? '' }}';
    
    let chartInstance = null;
    
    // Initialize chart on page load
    document.addEventListener('DOMContentLoaded', function() {
        initChart();
        
        // Auto-refresh if enabled
        if (refreshInterval && refreshUrl) {
            setInterval(() => refreshChart{{ $widgetId }}(), refreshInterval * 1000);
        }
    });
    
    function initChart() {
        const canvas = document.getElementById(widgetId + '-canvas');
        const emptyState = document.getElementById(widgetId + '-empty');
        
        if (!canvas) return;
        
        // Check if data is empty
        if (!chartData.labels || chartData.labels.length === 0) {
            canvas.style.display = 'none';
            if (emptyState) emptyState.style.display = 'flex';
            return;
        }
        
        canvas.style.display = 'block';
        if (emptyState) emptyState.style.display = 'none';
        
        const ctx = canvas.getContext('2d');
        
        // Default options
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: showLegend,
                    position: 'top',
                    labels: {
                        color: getComputedStyle(document.documentElement)
                            .getPropertyValue('--dashboard-text-primary') || '#fff',
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    cornerRadius: 6
                }
            },
            scales: chartType !== 'pie' && chartType !== 'doughnut' ? {
                x: {
                    grid: {
                        display: showGrid,
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: getComputedStyle(document.documentElement)
                            .getPropertyValue('--dashboard-text-secondary') || '#9ca3af'
                    }
                },
                y: {
                    grid: {
                        display: showGrid,
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: getComputedStyle(document.documentElement)
                            .getPropertyValue('--dashboard-text-secondary') || '#9ca3af'
                    }
                }
            } : {},
            animation: animated ? {
                duration: 750,
                easing: 'easeInOutQuart'
            } : false
        };
        
        // Merge custom options
        const finalOptions = Object.assign({}, defaultOptions, chartOptions);
        
        // Destroy existing chart
        if (chartInstance) {
            chartInstance.destroy();
        }
        
        // Create new chart
        chartInstance = new Chart(ctx, {
            type: chartType,
            data: chartData,
            options: finalOptions
        });
        
        // Store instance globally for refresh
        window['chart_' + widgetId] = chartInstance;
    }
    
    // Refresh chart function
    window['refreshChart' + widgetId] = function() {
        if (!refreshUrl) return;
        
        const loadingState = document.getElementById(widgetId + '-loading');
        const canvas = document.getElementById(widgetId + '-canvas');
        
        if (loadingState) loadingState.style.display = 'flex';
        if (canvas) canvas.style.opacity = '0.5';
        
        fetch(refreshUrl)
            .then(response => response.json())
            .then(data => {
                if (chartInstance && data.labels && data.datasets) {
                    chartInstance.data.labels = data.labels;
                    chartInstance.data.datasets = data.datasets;
                    chartInstance.update();
                    
                    if (window.showToast) {
                        window.showToast('Chart refreshed successfully', 'success');
                    }
                }
            })
            .catch(error => {
                console.error('Failed to refresh chart:', error);
                if (window.showToast) {
                    window.showToast('Failed to refresh chart', 'error');
                }
            })
            .finally(() => {
                if (loadingState) loadingState.style.display = 'none';
                if (canvas) canvas.style.opacity = '1';
            });
    };
    
    // Listen to theme changes and update chart colors
    document.addEventListener('themeChanged', function(e) {
        if (chartInstance) {
            const newTheme = e.detail.theme;
            const textColor = newTheme === 'dark' ? '#fff' : '#1f2937';
            const gridColor = newTheme === 'dark' ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            if (chartInstance.options.plugins.legend) {
                chartInstance.options.plugins.legend.labels.color = textColor;
            }
            
            if (chartInstance.options.scales) {
                if (chartInstance.options.scales.x) {
                    chartInstance.options.scales.x.ticks.color = textColor;
                    chartInstance.options.scales.x.grid.color = gridColor;
                }
                if (chartInstance.options.scales.y) {
                    chartInstance.options.scales.y.ticks.color = textColor;
                    chartInstance.options.scales.y.grid.color = gridColor;
                }
            }
            
            chartInstance.update();
        }
    });
})();
</script>
