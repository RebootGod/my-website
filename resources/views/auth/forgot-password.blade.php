@extends('layouts.app')

@section('title', 'Forgot Password - Noobz Cinema')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
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

                    <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm"
                          x-data="forgotPasswordHandler()" @submit.prevent="handleSubmit">
                        @csrf

                        <div class="form-group">
                            <input type="email"
                                   class="form-control-auth @error('email') is-invalid @enderror"
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
                                class="btn-auth-primary"
                                :disabled="isSubmitting || !canSubmit">
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

                        <div class="auth-links mt-4 text-center">
                            <p class="mb-2">
                                <a href="{{ route('login') }}" class="auth-link">
                                    <i class="fas fa-arrow-left me-1"></i>
                                    Kembali ke Login
                                </a>
                            </p>
                            <p class="mb-0">
                                Belum punya akun?
                                <a href="{{ route('register') }}" class="auth-link">Daftar di sini</a>
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

    {{-- Floating shapes --}}
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth/forgot-password.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeForgotPasswordForm({
            email: '{{ old('email') }}',
            csrfToken: '{{ csrf_token() }}',
            rateLimitUrl: '{{ route('password.rate-limit-status') }}'
        });
    });
</script>
@endpush