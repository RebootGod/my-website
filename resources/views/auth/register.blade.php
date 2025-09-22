{{-- ======================================== --}}
{{-- 3. ENHANCED REGISTER PAGE --}}
{{-- ======================================== --}}
{{-- File: resources/views/auth/register.blade.php --}}

@extends('layouts.app')

@section('title', 'Register - Noobz Cinema')

@push('styles')
<style>
.auth-container {
    min-height: calc(100vh - 200px);
    background: linear-gradient(135deg, #1a1a1a 0%, #2c3e50 50%, #34495e 100%);
    position: relative;
    overflow: hidden;
}

.auth-bg-pattern {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 80%, rgba(52, 152, 219, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(46, 204, 113, 0.1) 0%, transparent 50%);
}

.auth-card {
    background: linear-gradient(145deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 25px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    position: relative;
    z-index: 10;
}

.auth-title {
    background: linear-gradient(135deg, #3498db, #2ecc71);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    font-size: 2.5rem;
    text-align: center;
    margin-bottom: 2rem;
}

.form-control-auth {
    background: rgba(52, 73, 94, 0.8);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 15px;
    color: #ecf0f1;
    padding: 1rem 1.25rem;
    transition: all 0.3s ease;
}

.form-control-auth:focus {
    background: rgba(52, 73, 94, 1);
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    color: #ecf0f1;
}

.form-control-auth::placeholder {
    color: rgba(236, 240, 241, 0.6);
}

.btn-auth-primary {
    background: linear-gradient(135deg, #3498db, #2ecc71);
    border: none;
    border-radius: 15px;
    padding: 1rem 2rem;
    font-weight: 600;
    color: white;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-auth-primary:hover {
    background: linear-gradient(135deg, #2980b9, #27ae60);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
    color: white;
}

.auth-link {
    color: #3498db;
    text-decoration: none;
    transition: all 0.3s ease;
}

.auth-link:hover {
    color: #2ecc71;
    text-shadow: 0 0 10px rgba(52, 152, 219, 0.5);
}

.floating-shapes {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
}

.shape {
    position: absolute;
    background: linear-gradient(45deg, rgba(52, 152, 219, 0.1), rgba(46, 204, 113, 0.1));
    border-radius: 50%;
    animation: float 6s ease-in-out infinite;
}

.shape:nth-child(1) {
    width: 80px;
    height: 80px;
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.shape:nth-child(2) {
    width: 120px;
    height: 120px;
    top: 60%;
    right: 10%;
    animation-delay: 2s;
}

.shape:nth-child(3) {
    width: 60px;
    height: 60px;
    bottom: 20%;
    left: 20%;
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.invite-feedback {
    font-size: 0.875rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.invite-feedback.valid {
    background: rgba(46, 204, 113, 0.2);
    color: #2ecc71;
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.invite-feedback.invalid {
    background: rgba(231, 76, 60, 0.2);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.3);
}
</style>
@endpush

@section('content')
<div class="auth-container d-flex align-items-center justify-content-center py-5">
    {{-- Background Pattern --}}
    <div class="auth-bg-pattern"></div>
    
    {{-- Floating Shapes --}}
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    {{-- Register Form --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="auth-card p-5">
                    <h2 class="auth-title">🎬 REGISTER</h2>

                    <form method="POST" action="{{ route('register') }}" id="registerForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-light fw-bold">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <input 
                                    type="text" 
                                    name="username" 
                                    placeholder="Username unik Anda"
                                    value="{{ old('username') }}"
                                    class="form-control form-control-auth @error('username') is-invalid @enderror"
                                    required
                                    pattern="[a-zA-Z0-9_]{3,20}"
                                    title="Username hanya boleh huruf, angka, underscore (3-20 karakter)"
                                >
                                @error('username')
                                    <div class="invalid-feedback d-block">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label text-light fw-bold">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    placeholder="email@example.com"
                                    value="{{ old('email') }}"
                                    class="form-control form-control-auth @error('email') is-invalid @enderror"
                                    required
                                >
                                @error('email')
                                    <div class="invalid-feedback d-block">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-light fw-bold">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="register_password"
                                    placeholder="Minimal 8 karakter (huruf besar, kecil, angka, karakter khusus)"
                                    class="form-control form-control-auth @error('password') is-invalid @enderror"
                                    required
                                    minlength="8"
                                >
                                @error('password')
                                    <div class="invalid-feedback d-block">
                                        <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <strong>Syarat Password:</strong><br>
                                        <i id="check-uppercase" class="fas fa-check me-1" style="color: #6c757d;"></i> Minimal 1 huruf besar (A-Z)<br>
                                        <i id="check-lowercase" class="fas fa-check me-1" style="color: #6c757d;"></i> Minimal 1 huruf kecil (a-z)<br>
                                        <i id="check-number" class="fas fa-check me-1" style="color: #6c757d;"></i> Minimal 1 angka (0-9)<br>
                                        <i id="check-special" class="fas fa-check me-1" style="color: #6c757d;"></i> Minimal 1 karakter khusus (!@#$%^&*)
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label text-light fw-bold">
                                    <i class="fas fa-lock me-2"></i>Konfirmasi Password
                                </label>
                                <input 
                                    type="password" 
                                    name="password_confirmation" 
                                    placeholder="Ulangi password"
                                    class="form-control form-control-auth"
                                    required
                                    minlength="8"
                                >
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-light fw-bold">
                                <i class="fas fa-ticket-alt me-2"></i>Invite Code
                            </label>
                            <input 
                                type="text" 
                                name="invite_code" 
                                placeholder="Masukkan kode undangan"
                                value="{{ old('invite_code') }}"
                                class="form-control form-control-auth @error('invite_code') is-invalid @enderror"
                                required
                            >
                            @error('invite_code')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                            <div id="inviteCodeFeedback" class="invite-feedback"></div>
                        </div>

                        <button type="submit" class="btn btn-auth-primary mb-4">
                            <i class="fas fa-user-plus me-2"></i>DAFTAR SEKARANG
                        </button>

                        <div class="text-center">
                            <p class="text-light mb-3">Sudah punya akun?</p>
                            <a href="{{ route('login') }}" class="btn btn-outline-light">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Sekarang
                            </a>
                        </div>
                    </form>

                    <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">

                    <div class="text-center">
                        <p class="text-light mb-2">
                            <i class="fas fa-info-circle me-2"></i>Butuh Invite Code?
                        </p>
                        <a href="https://t.me/noobzspace" class="auth-link" target="_blank">
                            <i class="fab fa-telegram me-2"></i>t.me/noobzspace
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Live invite code validation
const inviteCodeInput = document.querySelector('input[name="invite_code"]');
const inviteCodeFeedback = document.getElementById('inviteCodeFeedback');
let inviteValidationTimeout;

function validateInviteCode() {
    const code = inviteCodeInput.value;
    
    if (code.length > 0) {
        // Show loading state
        inviteCodeFeedback.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memvalidasi kode...';
        inviteCodeFeedback.className = 'invite-feedback';
        
        fetch(`/check-invite-code?code=${code}`)
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    inviteCodeFeedback.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
                    inviteCodeFeedback.className = 'invite-feedback valid';
                } else {
                    inviteCodeFeedback.innerHTML = '<i class="fas fa-times-circle me-2"></i>' + data.message;
                    inviteCodeFeedback.className = 'invite-feedback invalid';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                inviteCodeFeedback.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Gagal memvalidasi kode';
                inviteCodeFeedback.className = 'invite-feedback invalid';
            });
    } else {
        inviteCodeFeedback.innerHTML = '';
        inviteCodeFeedback.className = 'invite-feedback';
    }
}

// Validate on blur (when user leaves the field)
inviteCodeInput.addEventListener('blur', validateInviteCode);

// Validate on input with debounce (when user types)
inviteCodeInput.addEventListener('input', function() {
    clearTimeout(inviteValidationTimeout);
    inviteValidationTimeout = setTimeout(validateInviteCode, 500); // Wait 500ms after user stops typing
});

// Password confirmation validation
document.querySelector('input[name="password_confirmation"]').addEventListener('blur', function() {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = this.value;
    
    if (confirmPassword.length > 0) {
        if (password !== confirmPassword) {
            this.style.borderColor = '#e74c3c';
        } else {
            this.style.borderColor = '#2ecc71';
        }
    }
});

// Real-time password validation with green checkmarks
document.getElementById('register_password').addEventListener('input', function() {
    const password = this.value;
    
    // Check uppercase letters
    const hasUppercase = /[A-Z]/.test(password);
    const uppercaseCheck = document.getElementById('check-uppercase');
    uppercaseCheck.style.color = hasUppercase ? '#28a745' : '#6c757d';
    
    // Check lowercase letters
    const hasLowercase = /[a-z]/.test(password);
    const lowercaseCheck = document.getElementById('check-lowercase');
    lowercaseCheck.style.color = hasLowercase ? '#28a745' : '#6c757d';
    
    // Check numbers
    const hasNumber = /[0-9]/.test(password);
    const numberCheck = document.getElementById('check-number');
    numberCheck.style.color = hasNumber ? '#28a745' : '#6c757d';
    
    // Check special characters
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    const specialCheck = document.getElementById('check-special');
    specialCheck.style.color = hasSpecial ? '#28a745' : '#6c757d';
});
</script>
@endpush