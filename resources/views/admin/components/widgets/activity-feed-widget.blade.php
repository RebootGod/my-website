{{--
    Activity Feed Widget Component
    
    Usage:
    @include('admin.components.widgets.activity-feed-widget', [
        'id' => 'recent-activity',
        'title' => 'Recent Activity',
        'activities' => $recentActivities, // Collection of activity logs
        'limit' => 10,
        'showUserAvatar' => true,
        'showTimestamp' => true,
        'refreshUrl' => route('admin.dashboard.activity-feed')
    ])
    
    Activity structure:
    [
        'user' => User model,
        'action' => 'created', 'updated', 'deleted', 'published', etc.
        'subject_type' => 'Movie', 'Series', 'User', etc.
        'subject_title' => 'Movie Title',
        'subject_id' => 123,
        'created_at' => Carbon instance
    ]
--}}

@php
    $widgetId = $id ?? 'activity-' . uniqid();
    $title = $title ?? 'Recent Activity';
    
    // Convert array to collection if needed
    if (is_array($activities ?? null)) {
        $activities = collect($activities);
    } elseif (!($activities instanceof \Illuminate\Support\Collection)) {
        $activities = collect();
    }
    
    $limit = $limit ?? 10;
    $showUserAvatar = $showUserAvatar ?? true;
    $showTimestamp = $showTimestamp ?? true;
    $refreshUrl = $refreshUrl ?? null;
    
    // Action icon mapping
    $actionIcons = [
        'created' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>',
        'updated' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
        'deleted' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>',
        'published' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
        'archived' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>',
        'login' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>',
        'logout' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>',
    ];
    
    // Action color mapping
    $actionColors = [
        'created' => 'success',
        'updated' => 'info',
        'deleted' => 'error',
        'published' => 'primary',
        'archived' => 'warning',
        'login' => 'info',
        'logout' => 'secondary',
    ];
@endphp

<div class="dashboard-widget activity-feed-widget" 
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
            <h3 class="widget-title">{{ $title }}</h3>
        </div>
        <div class="widget-controls">
            @if($refreshUrl)
                <button type="button" 
                        class="widget-control-btn widget-refresh-btn" 
                        onclick="refreshActivityFeed{{ $widgetId }}()"
                        title="Refresh">
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
    
    {{-- Activity List --}}
    <div class="widget-content" id="{{ $widgetId }}-content">
        @if($activities->isEmpty())
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p>No recent activity</p>
            </div>
        @else
            <div class="activity-list">
                @foreach($activities->take($limit) as $activity)
                    @php
                        $action = $activity['action'] ?? 'updated';
                        $actionIcon = $actionIcons[$action] ?? $actionIcons['updated'];
                        $actionColor = $actionColors[$action] ?? 'info';
                        $user = $activity['user'] ?? null;
                        $subjectType = $activity['subject_type'] ?? 'Item';
                        $subjectTitle = $activity['subject_title'] ?? 'Unknown';
                        $subjectId = $activity['subject_id'] ?? null;
                        $createdAt = $activity['created_at'] ?? now();
                    @endphp
                    
                    <div class="activity-item">
                        {{-- Action Icon --}}
                        <div class="activity-icon activity-icon-{{ $actionColor }}">
                            {!! $actionIcon !!}
                        </div>
                        
                        {{-- Activity Details --}}
                        <div class="activity-details">
                            {{-- User Info --}}
                            @if($showUserAvatar && $user)
                                <div class="activity-user">
                                    @if(isset($user->avatar))
                                        <img src="{{ $user->avatar }}" 
                                             alt="{{ $user->username }}"
                                             class="user-avatar">
                                    @else
                                        <div class="user-avatar-placeholder">
                                            {{ substr($user->username ?? 'U', 0, 1) }}
                                        </div>
                                    @endif
                                    <span class="user-name">{{ $user->username ?? 'System' }}</span>
                                </div>
                            @endif
                            
                            {{-- Activity Description --}}
                            <div class="activity-description">
                                <span class="activity-action">{{ ucfirst($action) }}</span>
                                <span class="activity-subject-type">{{ strtolower($subjectType) }}</span>
                                @if($subjectId)
                                    <a href="{{ route('admin.' . strtolower($subjectType) . 's.edit', $subjectId) }}" 
                                       class="activity-subject-title">
                                        "{{ Str::limit($subjectTitle, 30) }}"
                                    </a>
                                @else
                                    <span class="activity-subject-title">
                                        "{{ Str::limit($subjectTitle, 30) }}"
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Timestamp --}}
                            @if($showTimestamp)
                                <div class="activity-timestamp">
                                    {{ $createdAt->diffForHumans() }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
.activity-feed-widget .widget-content {
    padding: 0;
    max-height: 600px;
    overflow-y: auto;
}

.activity-list {
    display: flex;
    flex-direction: column;
}

.activity-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--dashboard-border, rgba(255, 255, 255, 0.1));
    transition: background 0.2s;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.activity-icon {
    flex-shrink: 0;
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
}

.activity-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}

.activity-icon-success {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
}

.activity-icon-info {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.activity-icon-error {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.activity-icon-warning {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
}

.activity-icon-primary {
    background: rgba(168, 85, 247, 0.15);
    color: #a855f7;
}

.activity-icon-secondary {
    background: rgba(107, 114, 128, 0.15);
    color: #6b7280;
}

.activity-details {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.activity-user {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-avatar,
.user-avatar-placeholder {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    object-fit: cover;
}

.user-avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dashboard-primary, #3b82f6);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
}

.user-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--dashboard-text-primary);
}

.activity-description {
    font-size: 0.875rem;
    color: var(--dashboard-text-primary);
    line-height: 1.4;
}

.activity-action {
    font-weight: 600;
}

.activity-subject-type {
    opacity: 0.7;
}

.activity-subject-title {
    font-weight: 500;
    color: var(--dashboard-primary, #3b82f6);
    text-decoration: none;
    transition: opacity 0.2s;
}

.activity-subject-title:hover {
    opacity: 0.8;
}

.activity-timestamp {
    font-size: 0.75rem;
    opacity: 0.6;
}

/* Scrollbar styling */
.activity-feed-widget .widget-content::-webkit-scrollbar {
    width: 6px;
}

.activity-feed-widget .widget-content::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
}

.activity-feed-widget .widget-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.activity-feed-widget .widget-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .activity-item {
        padding: 0.875rem 1rem;
        gap: 0.75rem;
    }
    
    .activity-icon {
        width: 2rem;
        height: 2rem;
    }
    
    .activity-icon svg {
        width: 1rem;
        height: 1rem;
    }
    
    .activity-description {
        font-size: 0.8125rem;
    }
}

/* Density modes */
.density-compact .activity-item {
    padding: 0.625rem 1rem;
}

.density-comfortable .activity-item {
    padding: 1.25rem 1.5rem;
}
</style>

@if($refreshUrl)
<script>
function refreshActivityFeed{{ $widgetId }}() {
    const contentEl = document.getElementById('{{ $widgetId }}-content');
    if (!contentEl) return;
    
    // Show loading state
    contentEl.style.opacity = '0.5';
    
    fetch('{{ $refreshUrl }}')
        .then(response => response.json())
        .then(data => {
            if (data.html) {
                contentEl.innerHTML = data.html;
                
                if (window.showToast) {
                    window.showToast('Activity feed refreshed', 'success');
                }
            }
        })
        .catch(error => {
            console.error('Failed to refresh activity feed:', error);
            if (window.showToast) {
                window.showToast('Failed to refresh activity feed', 'error');
            }
        })
        .finally(() => {
            contentEl.style.opacity = '1';
        });
}

// Auto-refresh every 60 seconds
setInterval(() => refreshActivityFeed{{ $widgetId }}(), 60000);
</script>
@endif
