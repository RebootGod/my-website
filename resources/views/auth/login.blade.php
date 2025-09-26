{{-- ======================================== --}}
{{-- 2. ENHANCED LOGIN PAGE --}}
{{-- ======================================== --}}
{{-- File: resources/views/auth/login.blade.php --}}

@extends('layouts.app')

@section('title', 'Login - Noobz Cinema')

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

.form-check-input:checked {
    background-color: #3498db;
    border-color: #3498db;
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

    {{-- Login Form --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="auth-card p-5">
                    <h2 class="auth-title">🎬 LOGIN</h2>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label text-light fw-bold">
                                <i class="fas fa-user me-2"></i>Username
                            </label>
                            <input
                                type="text"
                                name="username"
                                placeholder="Masukkan username Anda"
                                value="{{ old('username') }}"
                                class="form-control form-control-auth @error('username') is-invalid @enderror"
                                required
                                autofocus
                                minlength="3"
                                maxlength="20"
                                pattern="[a-zA-Z0-9_]{3,20}"
                                title="Username hanya boleh huruf, angka, underscore (3-20 karakter)"
                                autocomplete="username"
                            >
                            @error('username')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-light fw-bold">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <input
                                type="password"
                                name="password"
                                placeholder="Masukkan password Anda"
                                class="form-control form-control-auth @error('password') is-invalid @enderror"
                                required
                                minlength="8"
                                maxlength="128"
                                autocomplete="current-password"
                            >
                            @error('password')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                                <label class="form-check-label text-light" for="remember">
                                    <i class="fas fa-remember me-1"></i>Ingat saya
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-auth-primary mb-4">
                            <i class="fas fa-sign-in-alt me-2"></i>MASUK SEKARANG
                        </button>

                        <div class="text-center">
                            <p class="text-light mb-2">
                                <a href="{{ route('password.request') }}" class="auth-link">
                                    <i class="fas fa-key me-1"></i>Lupa Password?
                                </a>
                            </p>
                            <p class="text-light mb-3">Belum punya akun?</p>
                            <a href="{{ route('register') }}" class="btn btn-outline-light">
                                <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
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
// Security Functions for Login Form
function sanitizeInput(input) {
    // Remove HTML tags and trim whitespace
    return input.replace(/<[^>]*>/g, '').trim();
}

function validateInputSecurity(input) {
    // Check for potentially dangerous patterns
    const dangerousPatterns = [
        /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
        /javascript:/gi,
        /on\w+\s*=/gi,
        /data:text\/html/gi,
        /<iframe\b/gi,
        /<object\b/gi
    ];

    return !dangerousPatterns.some(pattern => pattern.test(input));
}

// Login form security validation
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form[action*="login"]');
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');

    if (loginForm) {
        // Username input validation
        usernameInput.addEventListener('input', function() {
            let value = this.value;

            // Sanitize input
            const sanitized = sanitizeInput(value);

            if (sanitized !== value) {
                this.value = sanitized;
            }

            // Validate security
            if (!validateInputSecurity(this.value)) {
                this.setCustomValidity('Input mengandung karakter berbahaya');
            } else if (!/^[a-zA-Z0-9_]*$/.test(this.value)) {
                this.setCustomValidity('Username hanya boleh huruf, angka, dan underscore');
            } else if (this.value.length > 0 && this.value.length < 3) {
                this.setCustomValidity('Username minimal 3 karakter');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form submission security check
        loginForm.addEventListener('submit', function(e) {
            const username = usernameInput.value;
            const password = passwordInput.value;

            // Final security validation before submission
            if (!validateInputSecurity(username)) {
                e.preventDefault();
                alert('Username mengandung karakter berbahaya');
                return false;
            }

            if (username.length < 3 || username.length > 20) {
                e.preventDefault();
                alert('Username harus 3-20 karakter');
                return false;
            }

            if (password.length < 8 || password.length > 128) {
                e.preventDefault();
                alert('Password harus 8-128 karakter');
                return false;
            }

            // Add loading state to prevent multiple submissions
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';

            // Re-enable after 3 seconds to prevent indefinite lock
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>MASUK SEKARANG';
            }, 3000);
        });
    }
});
</script>
@endpush