@props(['activities' => []])

<div class="admin-card activity-feed">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            <i class="fas fa-chart-line"></i>
            Recent Activity
        </h3>
        <a href="{{ route('admin.logs.index') }}" class="btn-modern outline small">
            View All
        </a>
    </div>

    <div class="admin-card-body">
        @if(count($activities) > 0)
            <div class="activity-list">
                @foreach($activities as $activity)
                    <div class="activity-item">
                        <div class="activity-icon activity-{{ $activity['type'] ?? 'default' }}">
                            <i class="{{ $activity['icon'] ?? 'fas fa-circle' }}"></i>
                        </div>

                        <div class="activity-content">
                            <div class="activity-message">
                                {{ $activity['message'] ?? ($activity['title'] ?? ($activity['action'] ?? 'No message')) }}
                            </div>
                            <div class="activity-meta">
                                <span class="activity-user">
                                    by {{ $activity['user'] ?? 'System' }}
                                </span>
                                <span class="activity-time">
                                    {{ $activity['time'] ?? now()->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        @if(isset($activity['badge']))
                            <div class="activity-badge">
                                <span class="badge {{ $activity['badge']['type'] ?? 'primary' }}">
                                    {{ $activity['badge']['text'] }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="activity-empty">
                <div class="empty-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <p class="empty-message">No recent activity to display</p>
                <p class="empty-description">Admin activities will appear here as they occur</p>
            </div>
        @endif
    </div>
</div>

<style>
.activity-feed {
    max-height: 400px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 300px;
    overflow-y: auto;
    padding-right: 0.5rem;
}

.activity-list::-webkit-scrollbar {
    width: 4px;
}

.activity-list::-webkit-scrollbar-track {
    background: var(--admin-bg-light);
    border-radius: 2px;
}

.activity-list::-webkit-scrollbar-thumb {
    background: var(--admin-primary);
    border-radius: 2px;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background-color: var(--admin-bg-light);
    border-radius: 0.75rem;
    border: 1px solid var(--admin-border);
    transition: all 0.2s ease;
}

.activity-item:hover {
    background-color: var(--admin-bg-dark);
    border-color: var(--admin-primary);
    transform: translateY(-1px);
}

.activity-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.activity-default {
    background-color: var(--admin-primary);
    color: white;
}

.activity-success {
    background-color: var(--admin-success);
    color: white;
}

.activity-warning {
    background-color: var(--admin-warning);
    color: #000;
}

.activity-error {
    background-color: var(--admin-error);
    color: white;
}

.activity-info {
    background-color: var(--admin-info);
    color: white;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-message {
    font-size: 0.875rem;
    color: var(--admin-text-white);
    line-height: 1.4;
    margin-bottom: 0.25rem;
    word-wrap: break-word;
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--admin-text-muted);
}

.activity-user {
    font-weight: 500;
}

.activity-time::before {
    content: '•';
    margin-right: 0.5rem;
}

.activity-badge {
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.activity-empty {
    text-align: center;
    padding: 2rem 1rem;
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background-color: var(--admin-bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--admin-text-muted);
}

.empty-message {
    font-size: 1rem;
    font-weight: 500;
    color: var(--admin-text-white);
    margin-bottom: 0.5rem;
}

.empty-description {
    font-size: 0.875rem;
    color: var(--admin-text-muted);
}

@media (max-width: 768px) {
    .activity-item {
        padding: 0.625rem;
        gap: 0.625rem;
    }

    .activity-icon {
        width: 1.75rem;
        height: 1.75rem;
        font-size: 0.6875rem;
    }

    .activity-message {
        font-size: 0.8125rem;
    }

    .activity-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .activity-time::before {
        display: none;
    }

    .admin-card-header .btn-modern {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
    }
}
</style>