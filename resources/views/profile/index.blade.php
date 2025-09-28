@extends('layouts.app')

@section('title', 'My Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}?v={{ file_exists(public_path('css/profile.css')) ? filemtime(public_path('css/profile.css')) : time() }}">
@endpush

@section('content')
<div class="container mt-4">
    {{-- Header with Gradient --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center p-4 rounded-4 position-relative overflow-hidden" 
                 style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div>
                    <h1 class="h2 text-white mb-1 fw-bold">
                        <i class="fas fa-user-circle me-3"></i>My Profile
                    </h1>
                    <p class="text-white-50 mb-0">Manage your account and preferences</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="btn btn-light btn-lg px-4 shadow-lg">
                    <i class="fas fa-edit me-2"></i>Edit Profile
                </a>
                {{-- Decorative elements --}}
                <div class="position-absolute top-0 end-0 opacity-25">
                    <i class="fas fa-user-circle" style="font-size: 8rem; color: white;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Profile Info Card --}}
        <div class="col-lg-4">
            {{-- Main Profile Card --}}
            <div class="card border-0 shadow-lg mb-4 overflow-hidden" 
                 style="background: linear-gradient(145deg, #2c3e50 0%, #34495e 100%);">
                <div class="card-body text-center p-5">
                    {{-- Animated Avatar --}}
                    <div class="mb-4 position-relative">
                        <div class="mx-auto rounded-circle shadow-lg position-relative" 
                             style="width: 120px; height: 120px; background: linear-gradient(135deg, #3498db, #e74c3c);">
                            <div class="w-100 h-100 rounded-circle d-flex align-items-center justify-content-center text-white">
                                <i class="fas fa-user fa-4x"></i>
                            </div>
                            {{-- Online indicator --}}
                            <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-3 border-white" 
                                 style="width: 25px; height: 25px;"></div>
                        </div>
                    </div>
                    
                    {{-- User Info --}}
                    <h3 class="text-white mb-2 fw-bold">{{ $user->username }}</h3>
                    <p class="text-light mb-3 opacity-75">{{ $user->email }}</p>
                    
                    {{-- Enhanced Status Badge --}}
                    @if(isset($user->role) && $user->role === 'admin')
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                            <i class="fas fa-crown me-1"></i>Admin
                        </span>
                    @elseif(isset($user->role) && $user->role === 'super_admin')
                        <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold">
                            <i class="fas fa-star me-1"></i>Super Admin
                        </span>
                    @else
                        <span class="badge bg-success px-3 py-2 rounded-pill fw-bold">
                            <i class="fas fa-user me-1"></i>Member
                        </span>
                    @endif
                    
                    <hr class="border-light opacity-25 my-4">
                    
                    {{-- Member Info with Icons --}}
                    <div class="text-start">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-25 rounded-circle p-2 me-3">
                                <i class="fas fa-calendar text-primary"></i>
                            </div>
                            <div>
                                <small class="text-light opacity-75 d-block">Member since</small>
                                <span class="text-white fw-semibold">{{ $user->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-25 rounded-circle p-2 me-3">
                                <i class="fas fa-clock text-success"></i>
                            </div>
                            <div>
                                <small class="text-light opacity-75 d-block">Last active</small>
                                <span class="text-white fw-semibold">{{ isset($stats['last_login']) ? $stats['last_login'] : 'Recently' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Quick Actions Section --}}
            <div class="card border-0 shadow-lg" style="background: linear-gradient(145deg, #1a1a2e 0%, #16213e 100%);">
                <div class="card-header bg-transparent border-0 p-4">
                    <h5 class="text-white mb-0 fw-bold">
                        <i class="fas fa-lightning-bolt me-2 text-warning"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('home') }}" 
                               class="btn btn-outline-light w-100 p-4 border-2 modern-action-btn">
                                <div class="text-center">
                                    <i class="fas fa-search fa-2x text-primary mb-3"></i>
                                    <h6 class="text-white fw-bold mb-2">Discover Movies</h6>
                                    <small class="text-light opacity-75">Browse our collection</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('profile.watchlist') }}" 
                               class="btn btn-outline-light w-100 p-4 border-2 modern-action-btn">
                                <div class="text-center">
                                    <i class="fas fa-bookmark fa-2x text-success mb-3"></i>
                                    <h6 class="text-white fw-bold mb-2">My Watchlist</h6>
                                    <small class="text-light opacity-75">{{ $stats['watchlist_count'] }} saved movies</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('profile.edit') }}" 
                               class="btn btn-outline-light w-100 p-4 border-2 modern-action-btn">
                                <div class="text-center">
                                    <i class="fas fa-user-cog fa-2x text-warning mb-3"></i>
                                    <h6 class="text-white fw-bold mb-2">Profile Settings</h6>
                                    <small class="text-light opacity-75">Update your info</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection