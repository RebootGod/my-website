@extends("layouts.app")

@section("title", "Edit Profile")

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

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #6c5ce7, #a855f7) !important;
}
.bg-gradient-secondary {
    background: linear-gradient(135deg, #444, #666) !important;
}
.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8, #20c997) !important;
}
.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14) !important;
}
.bg-gradient-success {
    background: linear-gradient(135deg, #198754, #20c997) !important;
}
.bg-gradient-danger {
    background: linear-gradient(135deg, #dc3545, #e74c3c) !important;
}
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-2px);
}
.btn {
    transition: all 0.3s ease;
}
.btn:hover {
    transform: translateY(-1px);
}
.input-group-text {
    border-right: none;
}
.form-control, .form-select {
    border-left: none;
}
.form-control:focus, .form-select:focus {
    border-color: #6c5ce7;
    box-shadow: 0 0 0 0.2rem rgba(108, 92, 231, 0.25);
}
.form-check-input:checked {
    background-color: #6c5ce7;
    border-color: #6c5ce7;
}
.alert-success {
    background-color: rgba(25, 135, 84, 0.1);
    border-color: #198754;
    color: #75b798;
}
.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    border-color: #dc3545;
    color: #f1aeb5;
}
.alert {
    border-radius: 12px;
}
</style>

<script>
function toggleForm(formId) {
    // Hide all forms first
    const forms = ['usernameForm', 'emailForm', 'passwordForm'];
    forms.forEach(id => {
        const form = document.getElementById(id);
        if (form && id !== formId) {
            form.style.display = 'none';
        }
    });
    
    // Toggle the selected form
    const targetForm = document.getElementById(formId);
    if (targetForm) {
        if (targetForm.style.display === 'none' || targetForm.style.display === '') {
            targetForm.style.display = 'block';
            targetForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Focus on first input after a short delay
            setTimeout(() => {
                const firstInput = targetForm.querySelector('input, select');
                if (firstInput) firstInput.focus();
            }, 300);
        } else {
            targetForm.style.display = 'none';
        }
    }
}

function hideForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.style.display = 'none';
    }
}

function confirmDelete() {
    const userConfirm = confirm('⚠️ WARNING: This action cannot be undone!\n\nAre you absolutely sure you want to delete your account?\n\nThis will permanently:\n• Delete your profile\n• Remove your watchlist\n• Clear your viewing history\n• Cancel any subscriptions');

    if (userConfirm) {
        const finalConfirm = prompt('To confirm, please type "DELETE" in capital letters:');
        if (finalConfirm === 'DELETE') {
            const password = prompt('Enter your current password to confirm account deletion:');
            if (password) {
                deleteAccount(password);
            } else {
                alert('Account deletion cancelled - password is required.');
            }
        } else {
            alert('Account deletion cancelled - confirmation text did not match.');
        }
    }
}

function deleteAccount(password) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("profile.delete") }}';
    form.style.display = 'none';

    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrfInput);

    // Add method override for DELETE
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);

    // Add password
    const passwordInput = document.createElement('input');
    passwordInput.type = 'hidden';
    passwordInput.name = 'current_password';
    passwordInput.value = password;
    form.appendChild(passwordInput);

    // Add confirmation
    const confirmationInput = document.createElement('input');
    confirmationInput.type = 'hidden';
    confirmationInput.name = 'confirmation';
    confirmationInput.value = 'DELETE';
    form.appendChild(confirmationInput);

    document.body.appendChild(form);
    form.submit();
}

// Add form validation feedback
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds in case of issues
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    });
});

// Real-time password validation for profile edit
document.addEventListener('DOMContentLoaded', function() {
    const profilePasswordField = document.getElementById('profile_password');
    if (profilePasswordField) {
        profilePasswordField.addEventListener('input', function() {
            const password = this.value;
            
            // Check uppercase letters
            const hasUppercase = /[A-Z]/.test(password);
            const uppercaseCheck = document.getElementById('profile-check-uppercase');
            if (uppercaseCheck) uppercaseCheck.style.color = hasUppercase ? '#28a745' : '#adb5bd';
            
            // Check lowercase letters
            const hasLowercase = /[a-z]/.test(password);
            const lowercaseCheck = document.getElementById('profile-check-lowercase');
            if (lowercaseCheck) lowercaseCheck.style.color = hasLowercase ? '#28a745' : '#adb5bd';
            
            // Check numbers
            const hasNumber = /[0-9]/.test(password);
            const numberCheck = document.getElementById('profile-check-number');
            if (numberCheck) numberCheck.style.color = hasNumber ? '#28a745' : '#adb5bd';
            
            // Check special characters
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            const specialCheck = document.getElementById('profile-check-special');
            if (specialCheck) specialCheck.style.color = hasSpecial ? '#28a745' : '#adb5bd';
        });
    }
});
</script>
@endsection
