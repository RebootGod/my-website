@extends('layouts.admin')

@section('title', 'User Activity: ' . $user->username . ' - Admin')

@section('content')
<div class="user-activity-detail">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.user-activity.index') }}" class="btn-modern outline">
                <i class="fas fa-arrow-left"></i>
                Back to Activities
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white">User Activity</h1>
                <p class="text-gray-400">{{ $user->username }} ({{ $user->role }})</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.users.show', $user) }}" class="btn-modern outline">
                <i class="fas fa-user"></i>
                View Profile
            </a>
            <a href="{{ route('admin.user-activity.export', ['user_id' => $user->id, 'period' => $period]) }}" class="btn-modern outline">
                <i class="fas fa-download"></i>
                Export
            </a>
        </div>
    </div>

    {{-- User Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Activities</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($summary['total_activities']) }}</p>
                    </div>
                    <div class="activity-icon bg-blue-500">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Last Activity</p>
                        <p class="text-lg font-semibold text-white">
                            @if($summary['last_activity'])
                                {{ $summary['last_activity']->activity_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                    <div class="activity-icon bg-green-500">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Period</p>
                        <p class="text-lg font-semibold text-white">{{ $period }} days</p>
                    </div>
                    <div class="activity-icon bg-yellow-500">
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">User Status</p>
                        <p class="text-lg font-semibold text-white">
                            <span class="badge {{ $user->status == 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </p>
                    </div>
                    <div class="activity-icon bg-purple-500">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Activity Breakdown and Timeline --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Activity Timeline --}}
        <div class="lg:col-span-2">
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Activity Timeline</h3>
                    <div class="period-selector">
                        <select onchange="changePeriod(this.value)" class="admin-input">
                            <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 days</option>
                        </select>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="activity-timeline">
                        @forelse($activities as $activity)
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <div class="timeline-icon activity-{{ $activity->color_class }}">
                                        <i class="{{ $activity->icon }}"></i>
                                    </div>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <h4 class="timeline-title">{{ $activity->description }}</h4>
                                        <span class="timeline-time">{{ $activity->activity_at->format('M j, Y H:i') }}</span>
                                    </div>
                                    <div class="timeline-meta">
                                        <span class="badge {{ $activity->color_class }}">
                                            {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                                        </span>
                                        @if($activity->ip_address)
                                            <span class="text-gray-400 text-sm">IP: {{ $activity->ip_address }}</span>
                                        @endif
                                    </div>
                                    @if($activity->metadata && count($activity->metadata) > 0)
                                        <div class="timeline-metadata">
                                            <details class="metadata-details">
                                                <summary class="text-sm text-gray-400 cursor-pointer">Additional Details</summary>
                                                <div class="metadata-content">
                                                    @foreach($activity->metadata as $key => $value)
                                                        <div class="metadata-item">
                                                            <span class="metadata-key">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                            <span class="metadata-value">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </details>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="timeline-empty">
                                <div class="empty-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <p class="empty-message">No activities found</p>
                                <p class="empty-description">This user hasn't performed any activities in the selected period</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($activities->hasPages())
                        <div class="mt-6">
                            {{ $activities->appends(['period' => $period])->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar Statistics --}}
        <div class="space-y-6">
            {{-- Activity Breakdown --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Activity Breakdown</h3>
                </div>
                <div class="admin-card-body">
                    @if(count($summary['activity_breakdown']) > 0)
                        <div class="activity-breakdown">
                            @foreach($summary['activity_breakdown'] as $type => $count)
                                <div class="breakdown-item">
                                    <div class="breakdown-label">
                                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                                    </div>
                                    <div class="breakdown-count">{{ $count }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-400 text-sm">No activity data for this period</p>
                    @endif
                </div>
            </div>

            {{-- User Information --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">User Information</h3>
                </div>
                <div class="admin-card-body">
                    <div class="user-info">
                        <div class="info-item">
                            <label class="info-label">Username</label>
                            <span class="info-value">{{ $user->username }}</span>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Email</label>
                            <span class="info-value">{{ $user->email }}</span>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Role</label>
                            <span class="info-value">
                                <span class="badge primary">{{ ucfirst($user->role) }}</span>
                            </span>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Status</label>
                            <span class="info-value">
                                <span class="badge {{ $user->status == 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </span>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Joined</label>
                            <span class="info-value">{{ $user->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="info-item">
                            <label class="info-label">Last Login</label>
                            <span class="info-value">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->format('M j, Y H:i') }}
                                @else
                                    Never
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Quick Actions</h3>
                </div>
                <div class="admin-card-body">
                    <div class="quick-actions">
                        <a href="{{ route('admin.users.show', $user) }}" class="quick-action-btn">
                            <i class="fas fa-user"></i>
                            View Full Profile
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="quick-action-btn">
                            <i class="fas fa-edit"></i>
                            Edit User
                        </a>
                        <button onclick="exportUserData()" class="quick-action-btn">
                            <i class="fas fa-download"></i>
                            Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.user-activity-detail {
    padding: 1rem;
}

.activity-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.period-selector .admin-input {
    background: var(--admin-bg-light);
    border: 1px solid var(--admin-border);
    color: var(--admin-text-white);
    min-width: 120px;
}

.activity-timeline {
    position: relative;
}

.timeline-item {
    position: relative;
    display: flex;
    margin-bottom: 2rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 1.5rem;
    top: 3rem;
    bottom: -2rem;
    width: 2px;
    background: var(--admin-border);
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-marker {
    position: relative;
    z-index: 1;
    margin-right: 1rem;
}

.timeline-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.timeline-content {
    flex: 1;
    background: var(--admin-bg-light);
    border: 1px solid var(--admin-border);
    border-radius: 0.75rem;
    padding: 1.5rem;
}

.timeline-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.timeline-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--admin-text-white);
    flex: 1;
    margin-right: 1rem;
}

.timeline-time {
    font-size: 0.75rem;
    color: var(--admin-text-muted);
    white-space: nowrap;
}

.timeline-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.timeline-metadata {
    margin-top: 1rem;
}

.metadata-details summary {
    margin-bottom: 0.5rem;
}

.metadata-content {
    background: var(--admin-bg-dark);
    border: 1px solid var(--admin-border);
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-top: 0.5rem;
}

.metadata-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
}

.metadata-key {
    color: var(--admin-text-muted);
    font-weight: 500;
}

.metadata-value {
    color: var(--admin-text-white);
}

.timeline-empty {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: var(--admin-bg-light);
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

.activity-breakdown {
    space-y: 0.75rem;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--admin-bg-light);
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
}

.breakdown-label {
    font-size: 0.875rem;
    color: var(--admin-text-white);
}

.breakdown-count {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--admin-primary);
}

.user-info {
    space-y: 0.75rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--admin-border);
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--admin-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.875rem;
    color: var(--admin-text-white);
    text-align: right;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: var(--admin-bg-light);
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    color: var(--admin-text-white);
    text-decoration: none;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.quick-action-btn:hover {
    background: var(--admin-bg-dark);
    border-color: var(--admin-primary);
    color: var(--admin-primary);
}

/* Activity type colors */
.activity-success { background: var(--admin-success); }
.activity-warning { background: var(--admin-warning); }
.activity-primary { background: var(--admin-primary); }
.activity-info { background: var(--admin-info); }
.activity-secondary { background: #6b7280; }
.activity-default { background: var(--admin-primary); }

.badge.success { background: var(--admin-success); }
.badge.warning { background: var(--admin-warning); }
.badge.primary { background: var(--admin-primary); }
.badge.info { background: var(--admin-info); }
.badge.secondary { background: #6b7280; }
.badge.default { background: var(--admin-primary); }

@media (max-width: 768px) {
    .user-activity-detail {
        padding: 0.5rem;
    }

    .timeline-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .timeline-content {
        padding: 1rem;
    }

    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .info-value {
        text-align: left;
    }
}
</style>
@endpush

@push('scripts')
<script>
function changePeriod(period) {
    const url = new URL(window.location);
    url.searchParams.set('period', period);
    window.location.href = url.toString();
}

function exportUserData() {
    const url = '{{ route("admin.user-activity.export") }}?user_id={{ $user->id }}&period={{ $period }}';
    window.open(url, '_blank');
}
</script>
@endpush
@endsection