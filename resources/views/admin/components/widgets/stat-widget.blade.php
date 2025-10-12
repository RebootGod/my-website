{{--
    Stat Widget Component
    
    Usage:
    @include('admin.components.widgets.stat-widget', [
        'id' => 'total-users',
        'title' => 'Total Users',
        'value' => $totalUsers,
        'icon' => 'users',
        'color' => 'primary',
        'trend' => '+12%',
        'trendDirection' => 'up',
        'description' => 'Active users this month',
        'link' => route('admin.users.index'),
        'size' => 'default' // default, large, small
    ])
--}}

@php
    $widgetId = $id ?? 'stat-' . uniqid();
    $title = $title ?? 'Stat Title';
    $value = $value ?? '0';
    $icon = $icon ?? 'chart-bar';
    $color = $color ?? 'primary'; // primary, success, warning, error, info
    $trend = $trend ?? null;
    $trendDirection = $trendDirection ?? 'neutral'; // up, down, neutral
    $description = $description ?? null;
    $link = $link ?? null;
    $size = $size ?? 'default'; // default, large, small
    
    // Icon mapping for common stats
    $iconMap = [
        'users' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
        'movies' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" /></svg>',
        'series' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>',
        'storage' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>',
        'views' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>',
        'revenue' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
        'chart-bar' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
        'trending-up' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>',
        'trending-down' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>',
    ];
    
    $iconSvg = $iconMap[$icon] ?? $iconMap['chart-bar'];
    
    // Size classes
    $sizeClasses = [
        'small' => 'text-2xl',
        'default' => 'text-3xl',
        'large' => 'text-4xl'
    ];
    
    $valueSizeClass = $sizeClasses[$size] ?? $sizeClasses['default'];
@endphp

<div class="stat-card stat-card-{{ $color }} dashboard-widget" 
     id="{{ $widgetId }}" 
     data-widget-id="{{ $widgetId }}"
     draggable="true">
    
    {{-- Widget Header --}}
    <div class="widget-header">
        <div class="widget-drag-handle" title="Drag to reorder">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
            </svg>
        </div>
        <div class="widget-controls">
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
    
    {{-- Stat Content --}}
    <div class="stat-content">
        {{-- Icon --}}
        <div class="stat-icon stat-icon-{{ $color }}">
            {!! $iconSvg !!}
        </div>
        
        {{-- Value & Title --}}
        <div class="stat-details">
            <div class="stat-value {{ $valueSizeClass }}" id="{{ $widgetId }}-value">
                {{ $value }}
            </div>
            <div class="stat-title">
                {{ $title }}
            </div>
        </div>
        
        {{-- Trend Indicator (optional) --}}
        @if($trend)
            <div class="stat-trend stat-trend-{{ $trendDirection }}">
                @if($trendDirection === 'up')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                @elseif($trendDirection === 'down')
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14" />
                    </svg>
                @endif
                <span>{{ $trend }}</span>
            </div>
        @endif
        
        {{-- Description (optional) --}}
        @if($description)
            <div class="stat-description">
                {{ $description }}
            </div>
        @endif
        
        {{-- Link (optional) --}}
        @if($link)
            <div class="stat-link">
                <a href="{{ $link }}" class="stat-link-btn">
                    View details
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        @endif
    </div>
</div>

<style>
/* Stat Card Specific Styles (if not in dashboard-modern.css) */
.stat-content {
    padding: 1rem;
    position: relative;
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    background: rgba(255, 255, 255, 0.1);
}

.stat-icon svg {
    width: 1.75rem;
    height: 1.75rem;
}

.stat-details {
    margin-bottom: 0.75rem;
}

.stat-value {
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.stat-title {
    font-size: 0.875rem;
    opacity: 0.8;
    font-weight: 500;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    width: fit-content;
    margin-bottom: 0.5rem;
}

.stat-trend svg {
    width: 1rem;
    height: 1rem;
}

.stat-trend-up {
    color: #10b981;
    background: rgba(16, 185, 129, 0.1);
}

.stat-trend-down {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

.stat-trend-neutral {
    color: #6b7280;
    background: rgba(107, 114, 128, 0.1);
}

.stat-description {
    font-size: 0.8125rem;
    opacity: 0.7;
    margin-bottom: 0.75rem;
    line-height: 1.4;
}

.stat-link {
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-link-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    opacity: 0.8;
    transition: opacity 0.2s;
    text-decoration: none;
}

.stat-link-btn:hover {
    opacity: 1;
}

.stat-link-btn svg {
    width: 1rem;
    height: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-icon {
        width: 2.5rem;
        height: 2.5rem;
    }
    
    .stat-icon svg {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .stat-value {
        font-size: 1.5rem !important;
    }
}
</style>

<script>
// Auto-refresh stat value (optional)
@if(isset($autoRefresh) && $autoRefresh)
document.addEventListener('DOMContentLoaded', function() {
    const statElement = document.getElementById('{{ $widgetId }}-value');
    if (!statElement) return;
    
    // Refresh every 30 seconds
    setInterval(function() {
        // Fetch updated value via AJAX
        fetch('{{ $autoRefreshUrl ?? "#" }}')
            .then(response => response.json())
            .then(data => {
                if (data.value) {
                    statElement.textContent = data.value;
                    
                    // Animate on change
                    statElement.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        statElement.style.transform = 'scale(1)';
                    }, 200);
                }
            })
            .catch(error => console.error('Failed to refresh stat:', error));
    }, 30000);
});
@endif
</script>
