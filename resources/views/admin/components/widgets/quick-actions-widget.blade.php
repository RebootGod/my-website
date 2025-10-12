{{--
    Quick Actions Widget Component
    
    Usage:
    @include('admin.components.widgets.quick-actions-widget', [
        'id' => 'quick-actions',
        'title' => 'Quick Actions',
        'actions' => [
            [
                'label' => 'Add Movie',
                'url' => route('admin.movies.create'),
                'icon' => 'plus',
                'color' => 'primary'
            ],
            // ... more actions
        ]
    ])
--}}

@php
    $widgetId = $id ?? 'quick-actions-' . uniqid();
    $title = $title ?? 'Quick Actions';
    $actions = $actions ?? [
        [
            'label' => 'Add Movie',
            'url' => route('admin.movies.create'),
            'icon' => 'movie',
            'color' => 'primary',
            'shortcut' => 'Alt+M'
        ],
        [
            'label' => 'Add Series',
            'url' => route('admin.series.create'),
            'icon' => 'series',
            'color' => 'primary',
            'shortcut' => 'Alt+S'
        ],
        [
            'label' => 'View Users',
            'url' => route('admin.users.index'),
            'icon' => 'users',
            'color' => 'info'
        ],
        [
            'label' => 'System Settings',
            'url' => route('admin.settings.index'),
            'icon' => 'settings',
            'color' => 'secondary'
        ],
        [
            'label' => 'Export Data',
            'url' => route('admin.export.index'),
            'icon' => 'download',
            'color' => 'success'
        ],
        [
            'label' => 'View Reports',
            'url' => route('admin.reports.index'),
            'icon' => 'chart',
            'color' => 'warning'
        ],
    ];
    
    // Icon SVG mapping
    $iconMap = [
        'plus' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>',
        'movie' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" /></svg>',
        'series' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>',
        'users' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
        'settings' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        'download' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>',
        'chart' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
        'refresh' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>',
        'backup' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>',
        'search' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>',
    ];
@endphp

<div class="dashboard-widget quick-actions-widget" 
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
    
    {{-- Actions Grid --}}
    <div class="widget-content">
        <div class="actions-grid">
            @foreach($actions as $action)
                @php
                    $actionIcon = $iconMap[$action['icon'] ?? 'plus'] ?? $iconMap['plus'];
                    $actionColor = $action['color'] ?? 'primary';
                    $actionUrl = $action['url'] ?? '#';
                    $actionLabel = $action['label'] ?? 'Action';
                    $actionShortcut = $action['shortcut'] ?? null;
                    $actionMethod = $action['method'] ?? 'GET';
                    $actionConfirm = $action['confirm'] ?? null;
                @endphp
                
                <a href="{{ $actionUrl }}" 
                   class="action-btn action-btn-{{ $actionColor }}"
                   @if($actionConfirm)
                       onclick="return confirm('{{ $actionConfirm }}')"
                   @endif
                   @if($actionMethod !== 'GET')
                       data-method="{{ $actionMethod }}"
                       data-csrf="{{ csrf_token() }}"
                   @endif>
                    <div class="action-icon">
                        {!! $actionIcon !!}
                    </div>
                    <div class="action-label">
                        {{ $actionLabel }}
                        @if($actionShortcut)
                            <span class="action-shortcut">{{ $actionShortcut }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<style>
.quick-actions-widget .widget-content {
    padding: 1.25rem;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem 1rem;
    border-radius: 0.75rem;
    text-decoration: none;
    transition: all 0.2s;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid transparent;
    cursor: pointer;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Color variants */
.action-btn-primary {
    border-color: rgba(59, 130, 246, 0.3);
}

.action-btn-primary:hover {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(147, 51, 234, 0.15) 100%);
    border-color: #3b82f6;
}

.action-btn-success {
    border-color: rgba(16, 185, 129, 0.3);
}

.action-btn-success:hover {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%);
    border-color: #10b981;
}

.action-btn-warning {
    border-color: rgba(251, 191, 36, 0.3);
}

.action-btn-warning:hover {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%);
    border-color: #fbbf24;
}

.action-btn-error {
    border-color: rgba(239, 68, 68, 0.3);
}

.action-btn-error:hover {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.15) 100%);
    border-color: #ef4444;
}

.action-btn-info {
    border-color: rgba(99, 102, 241, 0.3);
}

.action-btn-info:hover {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(79, 70, 229, 0.15) 100%);
    border-color: #6366f1;
}

.action-btn-secondary {
    border-color: rgba(107, 114, 128, 0.3);
}

.action-btn-secondary:hover {
    background: linear-gradient(135deg, rgba(107, 114, 128, 0.15) 0%, rgba(75, 85, 99, 0.15) 100%);
    border-color: #6b7280;
}

.action-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
}

.action-icon svg {
    width: 1.5rem;
    height: 1.5rem;
    color: var(--dashboard-text-primary);
}

.action-label {
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--dashboard-text-primary);
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.action-shortcut {
    font-size: 0.6875rem;
    opacity: 0.6;
    font-weight: 400;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    background: rgba(255, 255, 255, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .actions-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 0.75rem;
    }
    
    .action-btn {
        padding: 1rem 0.75rem;
    }
    
    .action-icon {
        width: 2.5rem;
        height: 2.5rem;
    }
    
    .action-icon svg {
        width: 1.25rem;
        height: 1.25rem;
    }
    
    .action-label {
        font-size: 0.8125rem;
    }
}

/* Density modes */
.density-compact .actions-grid {
    gap: 0.625rem;
}

.density-compact .action-btn {
    padding: 0.875rem 0.75rem;
    gap: 0.5rem;
}

.density-compact .action-icon {
    width: 2.25rem;
    height: 2.25rem;
}

.density-comfortable .actions-grid {
    gap: 1.25rem;
}

.density-comfortable .action-btn {
    padding: 1.5rem 1.25rem;
    gap: 1rem;
}

.density-comfortable .action-icon {
    width: 3.5rem;
    height: 3.5rem;
}
</style>

<script>
// Handle POST/DELETE actions
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.action-btn[data-method]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const method = this.dataset.method;
            const url = this.href;
            const csrf = this.dataset.csrf;
            
            if (method === 'GET') {
                window.location.href = url;
                return;
            }
            
            // Create hidden form for POST/DELETE
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';
            
            // CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrf;
            form.appendChild(csrfInput);
            
            // Method spoofing for DELETE/PUT/PATCH
            if (method !== 'POST') {
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = method;
                form.appendChild(methodInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        });
    });
});
</script>
