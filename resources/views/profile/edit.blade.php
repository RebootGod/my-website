@extends("layouts.app")

@section("title", "Edit Profile")

@push('styles')
@if(file_exists(public_path('css/profile.css')))
<link rel="stylesheet" href="{{ asset('css/profile.css') }}?v={{ filemtime(public_path('css/profile.css')) }}">
@else
<link rel="stylesheet" href="{{ asset('css/profile.css') }}?v={{ time() }}">
@endif
@endpush

@section("content")
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Enhanced Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 text-white mb-0">
                        <i class="fas fa-user-edit me-2 text-primary"></i>Edit Profile
                    </h1>
                    <p class="text-white-50 mb-0">Manage your account settings and preferences</p>
                </div>
                <a href="{{ route("profile.index") }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Profile
                </a>
            </div>

            <!-- Success & Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Error:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Enhanced Information Card -->
            <div class="card bg-dark border-secondary shadow-lg">
                <div class="card-header bg-gradient-primary border-0">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-info-circle me-2"></i>Current Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-user fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <p class="text-white-50 mb-1 small">Username</p>
                                    <p class="text-white fw-bold mb-0 h6">{{ $user->username }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-envelope fa-2x text-info"></i>
                                </div>
                                <div>
                                    <p class="text-white-50 mb-1 small">Email Address</p>
                                    <p class="text-white fw-bold mb-0 h6">{{ $user->email }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-calendar-alt fa-2x text-success"></i>
                                </div>
                                <div>
                                    <p class="text-white-50 mb-1 small">Member Since</p>
                                    <p class="text-white fw-bold mb-0 h6">{{ $user->created_at->format("M d, Y") }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                                <div>
                                    <p class="text-white-50 mb-1 small">Last Updated</p>
                                    <p class="text-white fw-bold mb-0 h6">{{ $user->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interactive Edit Forms -->
            <div class="card bg-dark border-secondary shadow-lg mt-4">
                <div class="card-header bg-gradient-secondary border-0">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-wrench me-2"></i>Edit Options
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Change Username Button -->
                        <div class="col-md-4">
                            <button onclick="toggleForm('usernameForm')" class="w-100 btn btn-outline-primary h-100 py-3">
                                <i class="fas fa-user fa-2x mb-2"></i>
                                <br><span class="small">Change Username</span>
                            </button>
                        </div>
                        
                        <!-- Change Email Button -->
                        <div class="col-md-4">
                            <button onclick="toggleForm('emailForm')" class="w-100 btn btn-outline-info h-100 py-3">
                                <i class="fas fa-envelope fa-2x mb-2"></i>
                                <br><span class="small">Update Email</span>
                            </button>
                        </div>
                        
                        <!-- Change Password Button -->
                        <div class="col-md-4">
                            <button onclick="toggleForm('passwordForm')" class="w-100 btn btn-outline-warning h-100 py-3">
                                <i class="fas fa-lock fa-2x mb-2"></i>
                                <br><span class="small">Change Password</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Username Form -->
            <div id="usernameForm" class="card bg-dark border-primary shadow-lg mt-4" style="display: none;">
                <div class="card-header bg-gradient-primary border-0">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-user me-2"></i>Change Username
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update.username') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="username" class="form-label text-white">New Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-secondary border-secondary">
                                    <i class="fas fa-user text-primary"></i>
                                </span>
                                <input type="text" class="form-control bg-secondary text-white border-secondary" 
                                       id="username" name="username" value="{{ $user->username }}" required>
                            </div>
                            <div class="form-text text-white-50">
                                <i class="fas fa-info-circle me-1"></i>
                                Username must be 3-20 characters and can only contain letters, numbers, and underscores.
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Username
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="hideForm('usernameForm')">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Email Form -->
            <div id="emailForm" class="card bg-dark border-info shadow-lg mt-4" style="display: none;">
                <div class="card-header bg-gradient-info border-0">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-envelope me-2"></i>Change Email Address
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update.email') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="email" class="form-label text-white">New Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-secondary border-secondary">
                                    <i class="fas fa-envelope text-info"></i>
                                </span>
                                <input type="email" class="form-control bg-secondary text-white border-secondary" 
                                       id="email" name="email" value="{{ $user->email }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="current_password_email" class="form-label text-white">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-secondary border-secondary">
                                    <i class="fas fa-lock text-warning"></i>
                                </span>
                                <input type="password" class="form-control bg-secondary text-white border-secondary" 
                                       id="current_password_email" name="current_password" required>
                            </div>
                            <div class="form-text text-white-50">
                                <i class="fas fa-shield-alt me-1"></i>
                                Required for security verification.
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-save me-2"></i>Update Email
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="hideForm('emailForm')">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Password Form -->
            <div id="passwordForm" class="card bg-dark border-warning shadow-lg mt-4" style="display: none;">
                <div class="card-header bg-gradient-warning border-0">
                    <h5 class="card-title text-dark mb-0">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update.password') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="current_password" class="form-label text-white">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-secondary border-secondary">
                                    <i class="fas fa-lock text-warning"></i>
                                </span>
                                <input type="password" class="form-control bg-secondary text-white border-secondary" 
                                       id="current_password" name="current_password" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label text-white">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-secondary border-secondary">
                                    <i class="fas fa-key text-success"></i>
                                </span>
                                <input type="password" class="form-control bg-secondary text-white border-secondary" 
                                       id="profile_password" name="password" required minlength="8" 
                                       placeholder="At least 8 characters with uppercase, lowercase, number & special character">
                            </div>
                            <div class="mt-2">
                                <small class="text-light">
                                    <strong>Syarat Password:</strong><br>
                                    <i id="profile-check-uppercase" class="fas fa-check me-1" style="color: #adb5bd;"></i> Minimal 1 huruf besar (A-Z)<br>
                                    <i id="profile-check-lowercase" class="fas fa-check me-1" style="color: #adb5bd;"></i> Minimal 1 huruf kecil (a-z)<br>
                                    <i id="profile-check-number" class="fas fa-check me-1" style="color: #adb5bd;"></i> Minimal 1 angka (0-9)<br>
                                    <i id="profile-check-special" class="fas fa-check me-1" style="color: #adb5bd;"></i> Minimal 1 karakter khusus (!@#$%^&*)
                                </small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label text-white">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-secondary border-secondary">
                                    <i class="fas fa-check text-success"></i>
                                </span>
                                <input type="password" class="form-control bg-secondary text-white border-secondary" 
                                       id="password_confirmation" name="password_confirmation" required minlength="8" 
                                       placeholder="Confirm your new password">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning text-dark">
                                <i class="fas fa-save me-2"></i>Update Password
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="hideForm('passwordForm')">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card bg-dark border-danger shadow-lg mt-4">
                <div class="card-header bg-gradient-danger border-0">
                    <h5 class="card-title text-white mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger bg-opacity-25 border-danger">
                        <h6 class="text-danger mb-2">
                            <i class="fas fa-warning me-2"></i>Delete Account
                        </h6>
                        <p class="text-white-50 mb-3">
                            Once you delete your account, there is no going back. Please be certain.
                        </p>
                        <button class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash me-2"></i>Delete My Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
@if(file_exists(public_path('js/profile.js')))
<script src="{{ asset('js/profile.js') }}?v={{ filemtime(public_path('js/profile.js')) }}"></script>
@else
<script src="{{ asset('js/profile.js') }}?v={{ time() }}"></script>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeProfile({
            deleteUrl: '{{ route("profile.delete") }}',
            csrfToken: '{{ csrf_token() }}'
        });
    });
</script>
@endpush
@endsection
