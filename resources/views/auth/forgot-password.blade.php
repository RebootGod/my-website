@extends('layouts.app')

@section('title', 'Forgot Password - Noobz Cinema')

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
    max-width: 450px;
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

.rate-limit-info {
    background: rgba(241, 196, 15, 0.1);
    border: 1px solid rgba(241, 196, 15, 0.3);
    border-radius: 15px;
    color: #f1c40f;
    padding: 0.75rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    display: none;
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

.security-notice {
    background: rgba(52, 152, 219, 0.1);
    border: 1px solid rgba(52, 152, 219, 0.3);
    border-radius: 15px;
    color: #3498db;
    padding: 1rem;
    margin-top: 1.5rem;
    font-size: 0.85rem;
    line-height: 1.5;
}

.security-notice i {
    margin-right: 0.5rem;
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
                    <h1 class="auth-title">Forgot Password</h1>
                    <p class="auth-subtitle">
                        Masukkan email Anda dan kami akan mengirimkan link untuk mereset password.
                    </p>

                    {{-- Rate Limit Warning --}}
                    <div class="rate-limit-info" id="rateLimitWarning">
                        <i class="fas fa-clock"></i>
                        <span id="rateLimitMessage"></span>
                    </div>

                    {{-- Success Message --}}
                    @if (session('status'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('status') }}
                        </div>
                    @endif

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

                    <!-- Alpine.js Test -->
                    <div x-data="{ test: 'Alpine.js Working!' }" class="mb-3">
                        <small x-text="test" class="text-success"></small>
                    </div>

                    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm"
                          x-data="forgotPasswordHandler()" @submit.prevent="handleSubmit"
                          @keydown.enter="$event.target.form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}))">
                        @csrf

                        <!-- Debug info -->
                        <div class="mb-2">
                            <small class="text-info">
                                Debug: isSubmitting = <span x-text="isSubmitting"></span>,
                                canSubmit = <span x-text="canSubmit"></span>
                            </small>
                        </div>

                        <div class="form-group">
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   name="email"
                                   placeholder="Email Address"
                                   value="{{ old('email') }}"
                                   required
                                   autocomplete="email"
                                   autofocus
                                   x-model="email"
                                   @input="checkRateLimit"
                                   @blur="validateEmail">
                        </div>

                        <button type="submit"
                                class="btn btn-primary"
                                :disabled="isSubmitting || !canSubmit"
                                @click="console.log('Button clicked!', isSubmitting)">
                            <template x-if="isSubmitting">
                                <span class="d-flex align-items-center justify-content-center">
                                    <span class="loading-spinner"></span>
                                    Mengirim Email...
                                </span>
                            </template>
                            <template x-if="!isSubmitting">
                                <span class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-envelope me-2"></i>
                                    Kirim Reset Link
                                </span>
                            </template>
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

                    <div class="security-notice">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Catatan Keamanan:</strong><br>
                        • Link reset hanya berlaku 1 jam<br>
                        • Maksimal 5 percobaan per jam<br>
                        • Periksa folder spam jika email tidak masuk
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Test Alpine.js availability
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    console.log('window.Alpine:', window.Alpine);
    console.log('Alpine available:', typeof window.Alpine !== 'undefined');

    // Test after Alpine loads
    setTimeout(() => {
        console.log('After timeout - window.Alpine:', window.Alpine);
    }, 1000);
});

function forgotPasswordHandler() {
    return {
        email: '{{ old('email') }}',
        isSubmitting: false,
        canSubmit: true,
        rateLimitData: {},

        init() {
            console.log('forgotPasswordHandler init called', {
                isSubmitting: this.isSubmitting,
                canSubmit: this.canSubmit
            });

            if (this.email) {
                this.checkRateLimit();
            }

            // Reset loading state on page unload
            window.addEventListener('beforeunload', () => {
                this.isSubmitting = false;
            });
        },

        async checkRateLimit() {
            if (!this.email || !this.isValidEmail(this.email)) {
                return;
            }

            try {
                const response = await fetch('{{ route('password.rate-limit-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ email: this.email })
                });

                if (response.ok) {
                    this.rateLimitData = await response.json();
                    this.updateRateLimitDisplay();
                }
            } catch (error) {
                console.log('Rate limit check failed:', error);
            }
        },

        updateRateLimitDisplay() {
            const warning = document.getElementById('rateLimitWarning');
            const message = document.getElementById('rateLimitMessage');

            if (!this.rateLimitData.can_attempt) {
                warning.style.display = 'block';
                message.textContent = `Batas percobaan tercapai. Coba lagi dalam ${this.rateLimitData.reset_in_minutes} menit.`;
                this.canSubmit = false;
            } else if (this.rateLimitData.email_attempts_remaining <= 2) {
                warning.style.display = 'block';
                message.textContent = `Sisa percobaan: ${this.rateLimitData.email_attempts_remaining}`;
                this.canSubmit = true;
            } else {
                warning.style.display = 'none';
                this.canSubmit = true;
            }
        },

        validateEmail() {
            const emailField = document.querySelector('input[name="email"]');
            if (this.email && !this.isValidEmail(this.email)) {
                emailField.setCustomValidity('Format email tidak valid');
            } else {
                emailField.setCustomValidity('');
            }
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        handleSubmit(event) {
            console.log('handleSubmit triggered', {
                canSubmit: this.canSubmit,
                isSubmitting: this.isSubmitting
            });

            if (!this.canSubmit || this.isSubmitting) {
                console.log('Form submission prevented');
                return;
            }

            console.log('Setting isSubmitting to true');
            this.isSubmitting = true;

            // Submit the form after setting loading state
            setTimeout(() => {
                event.target.submit();
            }, 100);
        }
    }
}

// Auto-hide success message after 10 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.opacity = '0';
            setTimeout(() => {
                successAlert.style.display = 'none';
            }, 300);
        }, 10000);
    }
});
</script>
@endpush