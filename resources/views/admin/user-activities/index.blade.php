@extends('layouts.admin')

@section('title', 'User Activities')

@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">User Activities</h1>
    <p class="admin-page-subtitle">Monitor user activities and behavior</p>
</div>

{{-- Filters --}}
<div class="admin-card mb-6">
    <div class="admin-card-header">
        <h3 class="admin-card-title">Filters</h3>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="admin-label">Activity Type</label>
                <select name="activity_type" class="admin-input">
                    <option value="">All Types</option>
                    @foreach($activityTypes as $type)
                        <option value="{{ $type }}" {{ request('activity_type') == $type ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="admin-label">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="admin-input">
            </div>
            
            <div>
                <label class="admin-label">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="admin-input">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="admin-btn admin-btn-primary mr-2">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="{{ route('admin.user-activities.index') }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Activities Table --}}
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">Activities ({{ $activities->total() }} total)</h3>
    </div>
    <div class="admin-card-body p-0">
        @if($activities->count() > 0)
            <div class="admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Activity</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>
                                    <div class="text-sm">
                                        <div>{{ $activity->activity_at->format('M d, Y') }}</div>
                                        <div class="text-gray-500">{{ $activity->activity_at->format('H:i:s') }}</div>
                                    </div>
                                </td>
                                <td>
                                    @if($activity->user)
                                        <div class="text-sm">
                                            <div class="font-medium">{{ $activity->user->username }}</div>
                                            <div class="text-gray-500">{{ $activity->user->email }}</div>
                                        </div>
                                    @else
                                        <span class="text-gray-400">User Deleted</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="admin-badge admin-badge-info">
                                        {{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-sm max-w-xs truncate" title="{{ $activity->description }}">
                                        {{ $activity->description }}
                                    </div>
                                </td>
                                <td>
                                    <span class="text-sm font-mono">{{ $activity->ip_address }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.user-activities.show', $activity) }}" 
                                       class="admin-btn admin-btn-small admin-btn-outline">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="admin-card-footer">
                {{ $activities->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-history text-gray-300 text-4xl mb-4"></i>
                <p class="text-gray-500 text-lg">No activities found</p>
                <p class="text-gray-400">Activities will appear here as users interact with the system</p>
            </div>
        @endif
    </div>
</div>
@endsection