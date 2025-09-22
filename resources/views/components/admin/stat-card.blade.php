@props(['title', 'value', 'icon', 'trend' => null, 'color' => 'primary', 'link' => null])

@if($link)
<a href="{{ $link }}" class="stat-card-link">
@endif

<div class="admin-card stat-card stat-card-{{ $color }}">
    <div class="stat-card-content">
        <div class="stat-icon">
            <i class="{{ $icon }}"></i>
        </div>

        <div class="stat-info">
            <h3 class="stat-title">{{ $title }}</h3>
            <div class="stat-value">{{ $value }}</div>

            @if($trend)
                <div class="stat-trend {{ $trend['direction'] === 'up' ? 'trend-up' : 'trend-down' }}">
                    <i class="fas fa-arrow-{{ $trend['direction'] === 'up' ? 'up' : 'down' }}"></i>
                    <span>{{ $trend['percentage'] }}% from last month</span>
                </div>
            @endif
        </div>
    </div>
</div>

@if($link)
</a>
@endif

<style>
.stat-card {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.stat-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.stat-card-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 4rem;
    height: 4rem;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.stat-card-primary .stat-icon {
    background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.stat-card-secondary .stat-icon {
    background: linear-gradient(135deg, var(--admin-secondary), #4b5563);
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.stat-card-success .stat-icon {
    background: linear-gradient(135deg, var(--admin-success), #059669);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.stat-card-warning .stat-icon {
    background: linear-gradient(135deg, var(--admin-warning), #d97706);
    box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
}

.stat-card-error .stat-icon {
    background: linear-gradient(135deg, var(--admin-error), #dc2626);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}

.stat-info {
    flex: 1;
}

.stat-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--admin-text-muted);
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--admin-text-white);
    line-height: 1;
    margin-bottom: 0.25rem;
}

.stat-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.trend-up {
    color: var(--admin-success);
}

.trend-down {
    color: var(--admin-error);
}

@media (max-width: 768px) {
    .stat-card-content {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }

    .stat-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.25rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }
}
</style>