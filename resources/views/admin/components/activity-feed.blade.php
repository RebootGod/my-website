{{-- ======================================== --}}
{{-- ACTIVITY FEED COMPONENT --}}
{{-- Real-time activity feed for dashboard --}}
{{-- ======================================== --}}

@props([
    'activities' => [],
    'title' => 'Recent Activity',
    'maxItems' => 10
])

<div class="activity-feed">
    <div class="activity-feed-header">
        <h3 class="activity-feed-title">{{ $title }}</h3>
        <button class="activity-feed-refresh" onclick="Admin.Charts.refreshCharts()" title="Refresh">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>

    <div class="activity-list">
        @forelse($activities->take($maxItems) as $activity)
            <div class="activity-item">
                <div class="activity-icon {{ $activity['type'] ?? 'default' }}">
                    @switch($activity['type'] ?? 'default')
                        @case('movie')
                            <i class="fas fa-film"></i>
                            @break
                        @case('user')
                            <i class="fas fa-user"></i>
                            @break
                        @case('series')
                            <i class="fas fa-tv"></i>
                            @break
                        @case('report')
                            <i class="fas fa-exclamation-triangle"></i>
                            @break
                        @default
                            <i class="fas fa-circle"></i>
                    @endswitch
                </div>

                <div class="activity-content">
                    <div class="activity-title">
                        {{ $activity['title'] ?? 'Unknown Activity' }}
                    </div>
                    <div class="activity-description">
                        {{ ucfirst($activity['action'] ?? 'performed an action') }}
                        @if(isset($activity['status']))
                            • Status: <span class="status-badge {{ $activity['status'] }}">{{ $activity['status'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="activity-time">
                    @if(isset($activity['date']))
                        {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}
                    @else
                        Just now
                    @endif
                </div>
            </div>
        @empty
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">No recent activity</div>
                    <div class="activity-description">
                        Activity will appear here as users interact with the system.
                    </div>
                </div>
                <div class="activity-time">—</div>
            </div>
        @endforelse
    </div>

    @if(count($activities) > $maxItems)
        <div class="activity-footer" style="margin-top: 1rem; text-align: center;">
            <a href="{{ route('admin.logs.index') }}" class="btn-modern">
                <i class="fas fa-eye"></i>
                View All Activity
            </a>
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
// Auto-refresh activity feed every 30 seconds
function startActivityFeedRefresh() {
    setInterval(function() {
        refreshActivityFeed();
    }, 30000); // 30 seconds
}

function refreshActivityFeed() {
    fetch('{{ route("admin.dashboard") }}?ajax=activity')
        .then(response => response.json())
        .then(data => {
            if (data.activities) {
                updateActivityFeed(data.activities);
            }
        })
        .catch(error => {
            console.log('Failed to refresh activity feed:', error);
        });
}

function updateActivityFeed(activities) {
    const activityList = document.querySelector('.activity-list');
    if (!activityList) return;

    // Create new activity items
    let newHTML = '';
    activities.slice(0, {{ $maxItems }}).forEach(activity => {
        newHTML += createActivityItemHTML(activity);
    });

    if (activities.length === 0) {
        newHTML = `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">No recent activity</div>
                    <div class="activity-description">
                        Activity will appear here as users interact with the system.
                    </div>
                </div>
                <div class="activity-time">—</div>
            </div>
        `;
    }

    // Animate the update
    activityList.style.opacity = '0.5';
    setTimeout(() => {
        activityList.innerHTML = newHTML;
        activityList.style.opacity = '1';
    }, 200);
}

function createActivityItemHTML(activity) {
    const iconMap = {
        'movie': 'fas fa-film',
        'user': 'fas fa-user',
        'series': 'fas fa-tv',
        'report': 'fas fa-exclamation-triangle'
    };

    const icon = iconMap[activity.type] || 'fas fa-circle';
    const timeAgo = moment(activity.date).fromNow();
    const statusBadge = activity.status
        ? `• Status: <span class="status-badge ${activity.status}">${activity.status}</span>`
        : '';

    return `
        <div class="activity-item">
            <div class="activity-icon ${activity.type || 'default'}">
                <i class="${icon}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-title">${activity.title || 'Unknown Activity'}</div>
                <div class="activity-description">
                    ${activity.action || 'performed an action'} ${statusBadge}
                </div>
            </div>
            <div class="activity-time">${timeAgo}</div>
        </div>
    `;
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.activity-feed')) {
        // Only start if moment.js is available
        if (typeof moment !== 'undefined') {
            startActivityFeedRefresh();
        }
    }
});
</script>
@endpush
@endonce