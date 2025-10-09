@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-bell text-primary me-2"></i>
                    Notifications
                </h2>
                
                <div>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-check-double me-1"></i>
                                Mark All as Read
                            </button>
                        </form>
                    @endif
                    
                    @if(auth()->user()->notifications()->whereNotNull('read_at')->count() > 0)
                        <form action="{{ route('notifications.delete-all-read') }}" method="POST" class="d-inline" 
                              onsubmit="return confirm('Are you sure you want to delete all read notifications?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-trash me-1"></i>
                                Delete Read
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Success Message --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Notifications List --}}
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    @forelse($notifications as $notification)
                        <div class="notification-item {{ is_null($notification->read_at) ? 'unread' : 'read' }}">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    {{-- Notification Icon based on type --}}
                                    @php
                                        $iconClass = 'fa-bell';
                                        $iconColor = 'text-primary';
                                        
                                        if(str_contains($notification->type, 'Welcome')) {
                                            $iconClass = 'fa-star';
                                            $iconColor = 'text-success';
                                        } elseif(str_contains($notification->type, 'Security')) {
                                            $iconClass = 'fa-shield-alt';
                                            $iconColor = 'text-warning';
                                        } elseif(str_contains($notification->type, 'NewUserRegistered')) {
                                            $iconClass = 'fa-user-plus';
                                            $iconColor = 'text-info';
                                        }
                                    @endphp
                                    
                                    <div class="notification-icon {{ $iconColor }}">
                                        <i class="fas {{ $iconClass }} fa-2x"></i>
                                    </div>
                                </div>
                                
                                <div class="col">
                                    <a href="{{ route('notifications.show', $notification->id) }}" 
                                       class="text-decoration-none text-dark">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold">
                                                    {{ $notification->data['title'] ?? 'Notification' }}
                                                    @if(is_null($notification->read_at))
                                                        <span class="badge bg-primary ms-2">New</span>
                                                    @endif
                                                </h6>
                                                <p class="mb-1 text-muted">
                                                    {{ $notification->data['message'] ?? 'You have a new notification' }}
                                                </p>
                                                <small class="text-muted">
                                                    <i class="far fa-clock me-1"></i>
                                                    {{ $notification->created_at->diffForHumans() }}
                                                    @if($notification->read_at)
                                                        · Read {{ $notification->read_at->diffForHumans() }}
                                                    @endif
                                                </small>
                                            </div>
                                            
                                            <div class="ms-3">
                                                <form action="{{ route('notifications.destroy', $notification->id) }}" 
                                                      method="POST" 
                                                      onsubmit="return confirm('Delete this notification?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        @if(!$loop->last)
                            <hr class="my-0">
                        @endif
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No notifications yet</h5>
                            <p class="text-muted">When you receive notifications, they will appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Pagination --}}
            @if($notifications->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $notifications->links() }}
                </div>
            @endif

            {{-- Stats --}}
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card border-0 bg-primary text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ auth()->user()->unreadNotifications->count() }}</h3>
                            <small>Unread</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-success text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ auth()->user()->notifications()->whereNotNull('read_at')->count() }}</h3>
                            <small>Read</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 bg-info text-white">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ auth()->user()->notifications->count() }}</h3>
                            <small>Total</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item {
    padding: 1.25rem;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f8ff;
}

.notification-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: rgba(0, 123, 255, 0.1);
}

.text-success .notification-icon {
    background-color: rgba(40, 167, 69, 0.1);
}

.text-warning .notification-icon {
    background-color: rgba(255, 193, 7, 0.1);
}

.text-info .notification-icon {
    background-color: rgba(23, 162, 184, 0.1);
}
</style>
@endsection
