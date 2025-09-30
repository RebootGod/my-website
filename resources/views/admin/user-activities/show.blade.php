@extends('layouts.admin')

@section('title', 'User Activity Detail')

@section('content')
<div class="admin-page-header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="admin-page-title">Activity Detail</h1>
            <p class="admin-page-subtitle">View detailed information about this activity</p>
        </div>
        <a href="{{ route('admin.user-activities.index') }}" class="admin-btn admin-btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Activities
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">Activity Information</h3>
    </div>
    <div class="admin-card-body">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Basic Information --}}
            <div class="space-y-4">
                <div>
                    <label class="admin-label">Date & Time</label>
                    <div class="admin-display-field">
                        {{ $userActivity->activity_at->format('F d, Y \a\t H:i:s') }}
                        <span class="text-gray-500 text-sm ml-2">
                            ({{ $userActivity->activity_at->diffForHumans() }})
                        </span>
                    </div>
                </div>

                <div>
                    <label class="admin-label">Activity Type</label>
                    <div class="admin-display-field">
                        <span class="admin-badge admin-badge-info">
                            {{ ucfirst(str_replace('_', ' ', $userActivity->activity_type)) }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="admin-label">Description</label>
                    <div class="admin-display-field">
                        {{ $userActivity->description }}
                    </div>
                </div>

                <div>
                    <label class="admin-label">IP Address</label>
                    <div class="admin-display-field">
                        <span class="font-mono">{{ $userActivity->ip_address }}</span>
                    </div>
                </div>
            </div>

            {{-- User Information --}}
            <div class="space-y-4">
                <div>
                    <label class="admin-label">User</label>
                    @if($userActivity->user)
                        <div class="admin-display-field">
                            <div class="flex items-center space-x-3">
                                <div>
                                    <div class="font-medium">{{ $userActivity->user->username }}</div>
                                    <div class="text-gray-500 text-sm">{{ $userActivity->user->email }}</div>
                                    <div class="text-gray-500 text-sm">ID: {{ $userActivity->user->id }}</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="admin-display-field">
                            <span class="text-gray-400">User has been deleted</span>
                        </div>
                    @endif
                </div>

                <div>
                    <label class="admin-label">User Agent</label>
                    <div class="admin-display-field">
                        <div class="text-sm break-all">
                            {{ $userActivity->user_agent ?? 'Not available' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metadata Section --}}
        @if($userActivity->metadata && count($userActivity->metadata) > 0)
            <div class="mt-6 pt-6 border-t border-gray-200">
                <label class="admin-label">Additional Data (Metadata)</label>
                <div class="admin-display-field">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <pre class="text-sm overflow-x-auto">{{ json_encode($userActivity->metadata, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Quick Actions --}}
@if($userActivity->user)
<div class="admin-card mt-6">
    <div class="admin-card-header">
        <h3 class="admin-card-title">Quick Actions</h3>
    </div>
    <div class="admin-card-body">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.users.show', $userActivity->user) }}" 
               class="admin-btn admin-btn-primary">
                <i class="fas fa-user"></i> View User Profile
            </a>
            <a href="{{ route('admin.user-activities.index', ['user_id' => $userActivity->user_id]) }}" 
               class="admin-btn admin-btn-secondary">
                <i class="fas fa-list"></i> View User's All Activities
            </a>
            <a href="{{ route('admin.user-activities.index', ['activity_type' => $userActivity->activity_type]) }}" 
               class="admin-btn admin-btn-secondary">
                <i class="fas fa-filter"></i> View Similar Activities
            </a>
        </div>
    </div>
</div>
@endif
@endsection