@extends('layouts.app')

@section('title', 'Reset Password - Noobz Cinema')

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
                    <h1 class="auth-title">Reset Password</h1>
                    <p class="auth-subtitle">
                        Masukkan password baru untuk akun: <strong>{{ $email }}</strong>
                    </p>

                    <div class="reset-info">
                        <h4><i class="fas fa-info-circle me-2"></i>Informasi Reset</h4>
                        <ul>
                            <li>Link reset ini hanya berlaku 1 jam</li>
                            <li>Hanya bisa digunakan sekali</li>
                            <li>Password harus memenuhi kriteria keamanan</li>
                        </ul>
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
                          x-data="resetPasswordHandler()" @submit.prevent="handleSubmit">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email }}">

                        <div class="form-group mb-3">
                            <div class="position-relative">
                                <input type="password"
                                       class="form-control-auth @error('password') is-invalid @enderror"
                                       name="password"
                                       placeholder="Password Baru"
                                       required
                                       autocomplete="new-password"
                                       x-model="password"
                                       @input="checkPasswordStrength"
                                       :type="showPassword ? 'text' : 'password'">
                                <button type="button"
                                        class="password-toggle position-absolute"
                                        @click="togglePassword()"
                                        style="right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #ecf0f1;">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>

                            {{-- Password Strength Indicator --}}
                            <div class="password-strength" x-show="password.length > 0">
                                <div class="strength-meter">
                                    <div class="strength-fill" :class="strengthClass"></div>
                                </div>
                                <div class="strength-text" :class="strengthClass" x-text="strengthText"></div>
                                <ul class="password-requirements">
                                    <li :class="password.length >= 8 ? 'valid' : 'invalid'">Minimal 8 karakter</li>
                                    <li :class="/[A-Z]/.test(password) ? 'valid' : 'invalid'">Huruf besar</li>
                                    <li :class="/[a-z]/.test(password) ? 'valid' : 'invalid'">Huruf kecil</li>
                                    <li :class="/[0-9]/.test(password) ? 'valid' : 'invalid'">Angka</li>
                                    <li :class="/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/.test(password) ? 'valid' : 'invalid'">Karakter khusus</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <div class="position-relative">
                                <input type="password"
                                       class="form-control-auth"
                                       name="password_confirmation"
                                       placeholder="Konfirmasi Password"
                                       required
                                       autocomplete="new-password"
                                       x-model="passwordConfirmation"
                                       @input="checkPasswordMatch"
                                       :type="showPasswordConfirmation ? 'text' : 'password'">
                                <button type="button"
                                        class="password-toggle position-absolute"
                                        @click="togglePasswordConfirmation()"
                                        style="right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #ecf0f1;">
                                    <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>

                            {{-- Password Match Indicator --}}
                            <div x-show="passwordConfirmation.length > 0" class="mt-2">
                                <div x-show="passwordsMatch" class="text-success">
                                    <i class="fas fa-check"></i> Password cocok
                                </div>
                                <div x-show="!passwordsMatch && passwordConfirmation.length > 0" class="text-danger">
                                    <i class="fas fa-times"></i> Password tidak cocok
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                                class="btn-auth-primary"
                                :disabled="isSubmitting || !canSubmit"
                                :class="{ 'btn-loading': isSubmitting }">
                            <template x-if="isSubmitting">
                                <span>Mereset Password...</span>
                            </template>
                            <template x-if="!isSubmitting">
                                <span>
                                    <i class="fas fa-key me-2"></i>
                                    Reset Password
                                </span>
                            </template>
                        </button>

                        <div class="auth-links mt-4 text-center">
                            <p class="mb-0">
                                <a href="{{ route('login') }}" class="auth-link">
                                    <i class="fas fa-arrow-left me-1"></i>
                                    Kembali ke Login
                                </a>
                            </p>
                        </div>
                    </form>
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
<script src="{{ asset('js/auth/reset-password.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeResetPasswordForm({
            csrfToken: '{{ csrf_token() }}',
            email: '{{ $email }}',
            token: '{{ $token }}'
        });
    });
</script>
@endpush