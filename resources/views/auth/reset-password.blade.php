@extends('layouts.app')

@section('title', 'Reset Password - Noobz Cinema')

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
    max-width: 500px;
}

.auth-title {
    background: linear-gradient(135deg, #3498db, #2ecc71);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin-bottom: 1rem;
}

.auth-subtitle {
    color: #bdc3c7;
    text-align: center;
    margin-bottom: 2rem;
    font-size: 0.95rem;
    line-height: 1.6;
}

.form-group {
    position: relative;
    margin-bottom: 1.5rem;
}

.form-control {
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(255,255,255,0.2);
    border-radius: 15px;
    color: #ecf0f1;
    font-size: 1rem;
    padding: 1rem 1.25rem;
    width: 100%;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: rgba(255,255,255,0.15);
    border-color: #3498db;
    box-shadow: 0 0 20px rgba(52, 152, 219, 0.3);
    outline: none;
}

.form-control.is-valid {
    border-color: #2ecc71;
    box-shadow: 0 0 10px rgba(46, 204, 113, 0.3);
}

.form-control.is-invalid {
    border-color: #e74c3c;
    box-shadow: 0 0 10px rgba(231, 76, 60, 0.3);
}

.form-control::placeholder {
    color: #bdc3c7;
}

.btn-primary {
    background: linear-gradient(135deg, #3498db, #2ecc71);
    border: none;
    border-radius: 15px;
    color: white;
    font-weight: 600;
    padding: 1rem 2rem;
    width: 100%;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.alert {
    border-radius: 15px;
    border: none;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-danger {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.3);
}

.alert-success {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.auth-links {
    text-align: center;
    margin-top: 2rem;
}

.auth-links a {
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.auth-links a:hover {
    color: #2ecc71;
}

.password-strength {
    margin-top: 0.5rem;
    padding: 0.75rem;
    border-radius: 10px;
    font-size: 0.9rem;
    display: none;
}

.password-strength.weak {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.3);
}

.password-strength.medium {
    background: rgba(241, 196, 15, 0.1);
    color: #f1c40f;
    border: 1px solid rgba(241, 196, 15, 0.3);
}

.password-strength.strong {
    background: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 1s ease-in-out infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #bdc3c7;
    cursor: pointer;
    font-size: 1rem;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #3498db;
}

.token-info {
    background: rgba(52, 152, 219, 0.1);
    border: 1px solid rgba(52, 152, 219, 0.3);
    border-radius: 15px;
    color: #3498db;
    padding: 1rem;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.security-requirements {
    background: rgba(52, 152, 219, 0.1);
    border: 1px solid rgba(52, 152, 219, 0.3);
    border-radius: 15px;
    color: #3498db;
    padding: 1rem;
    margin-top: 1rem;
    font-size: 0.85rem;
}

.security-requirements ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1rem;
}

.security-requirements li {
    margin-bottom: 0.25rem;
}
</style>
@endpush

@section('content')
<div class="auth-container py-5">
    <div class="auth-bg-pattern"></div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="auth-card p-5 mx-auto">
                    <h1 class="auth-title">Reset Password</h1>
                    <p class="auth-subtitle">
                        Masukkan password baru untuk akun: <strong>{{ $email }}</strong>
                    </p>

                    <div class="token-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Penting:</strong> Link reset ini hanya berlaku 1 jam dan hanya bisa digunakan sekali.
                    </div>

                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0 list-unstyled">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm"
                          x-data="resetPasswordHandler()" @submit="handleSubmit">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email }}">

                        <div class="form-group">
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password"
                                   placeholder="Password Baru"
                                   required
                                   autocomplete="new-password"
                                   x-model="password"
                                   @input="checkPasswordStrength"
                                   :class="getPasswordClass()">
                            <button type="button" class="password-toggle" @click="togglePassword('password')">
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>

                        <div class="password-strength" :class="passwordStrength.strength" x-show="password.length > 0">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><strong>Kekuatan Password:</strong></span>
                                <span class="text-capitalize" x-text="passwordStrength.strength"></span>
                            </div>
                            <div x-show="passwordStrength.feedback.length > 0">
                                <small>Saran:</small>
                                <ul class="mb-0 mt-1">
                                    <template x-for="feedback in passwordStrength.feedback">
                                        <li x-text="feedback"></li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <input type="password"
                                   class="form-control @error('password_confirmation') is-invalid @enderror"
                                   name="password_confirmation"
                                   placeholder="Konfirmasi Password Baru"
                                   required
                                   autocomplete="new-password"
                                   x-model="passwordConfirmation"
                                   @input="validatePasswordMatch"
                                   :class="getConfirmPasswordClass()">
                            <button type="button" class="password-toggle" @click="togglePassword('password_confirmation')">
                                <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>

                        <div x-show="passwordConfirmation.length > 0 && !passwordsMatch" class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Password tidak cocok
                        </div>

                        <button type="submit"
                                class="btn btn-primary"
                                :disabled="isSubmitting || !canSubmit()">
                            <span x-show="isSubmitting" class="d-flex align-items-center justify-content-center">
                                <span class="loading-spinner"></span>
                                Mereset Password...
                            </span>
                            <span x-show="!isSubmitting" class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-key me-2"></i>
                                Reset Password
                            </span>
                        </button>

                        <div class="auth-links">
                            <p class="mb-2">
                                <a href="{{ route('login') }}">
                                    <i class="fas fa-arrow-left me-1"></i>
                                    Kembali ke Login
                                </a>
                            </p>
                            <p class="mb-0">
                                Belum punya akun?
                                <a href="{{ route('register') }}">Daftar di sini</a>
                            </p>
                        </div>
                    </form>

                    <div class="security-requirements">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Persyaratan Password:</strong>
                        <ul>
                            <li>Minimal 8 karakter</li>
                            <li>Mengandung huruf besar dan kecil</li>
                            <li>Mengandung angka</li>
                            <li>Mengandung simbol (!@#$%^&*)</li>
                            <li>Tidak menggunakan kata yang mudah ditebak</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function resetPasswordHandler() {
    return {
        password: '',
        passwordConfirmation: '',
        showPassword: false,
        showPasswordConfirmation: false,
        isSubmitting: false,
        passwordStrength: {
            strength: 'weak',
            feedback: []
        },
        passwordsMatch: false,

        init() {
            // Reset loading state on page unload
            window.addEventListener('beforeunload', () => {
                this.isSubmitting = false;
            });
        },

        togglePassword(field) {
            const input = document.querySelector(`input[name="${field}"]`);
            if (field === 'password') {
                this.showPassword = !this.showPassword;
                input.type = this.showPassword ? 'text' : 'password';
            } else {
                this.showPasswordConfirmation = !this.showPasswordConfirmation;
                input.type = this.showPasswordConfirmation ? 'text' : 'password';
            }
        },

        async checkPasswordStrength() {
            if (this.password.length === 0) {
                this.passwordStrength = { strength: 'weak', feedback: [] };
                return;
            }

            try {
                const response = await fetch('{{ route('password.strength') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        password: this.password,
                        email: '{{ $email }}'
                    })
                });

                if (response.ok) {
                    this.passwordStrength = await response.json();
                }
            } catch (error) {
                console.log('Password strength check failed:', error);
            }

            this.validatePasswordMatch();
        },

        validatePasswordMatch() {
            this.passwordsMatch = this.passwordConfirmation.length === 0 ||
                                  this.password === this.passwordConfirmation;
        },

        getPasswordClass() {
            if (this.password.length === 0) return '';
            return this.passwordStrength.strength === 'strong' ? 'is-valid' : 'is-invalid';
        },

        getConfirmPasswordClass() {
            if (this.passwordConfirmation.length === 0) return '';
            return this.passwordsMatch ? 'is-valid' : 'is-invalid';
        },

        canSubmit() {
            return this.password.length >= 8 &&
                   this.passwordConfirmation.length >= 8 &&
                   this.passwordsMatch &&
                   this.passwordStrength.strength === 'strong';
        },

        handleSubmit(event) {
            if (!this.canSubmit() || this.isSubmitting) {
                event.preventDefault();
                return;
            }

            this.isSubmitting = true;

            // Reset isSubmitting after form submission completes
            setTimeout(() => {
                this.isSubmitting = false;
            }, 5000); // Reset after 5 seconds as fallback
        }
    }
}
</script>
@endpush