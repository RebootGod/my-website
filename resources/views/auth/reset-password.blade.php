{{-- ======================================== --}}
{{-- RESET PASSWORD PAGE - MODERN DESIGN --}}
{{-- ======================================== --}}
{{-- File: resources/views/auth/reset-password.blade.php --}}

@extends('layouts.app')

@section('title', 'Reset Password - Noobz Cinema')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
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

    {{-- Reset Password Form --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="auth-card p-5">
                    <h2 class="auth-title">RESET PASSWORD</h2>

                    {{-- Account Info --}}
                    <div class="text-center mb-4">
                        <p class="text-light mb-2">
                            Masukkan password baru untuk akun:
                        </p>
                        <p class="text-light fw-bold mb-0">
                            <i class="fas fa-envelope me-2"></i>{{ $email }}
                        </p>
                    </div>

                    {{-- Info Box --}}
                    <div class="alert alert-info d-flex align-items-start mb-4" role="alert" style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); color: #93c5fd;">
                        <i class="fas fa-info-circle me-3 mt-1"></i>
                        <div>
                            <strong class="d-block mb-2">Informasi Reset</strong>
                            <ul class="mb-0 ps-3" style="font-size: 0.9rem;">
                                <li>Link reset ini hanya berlaku 1 jam</li>
                                <li>Hanya bisa digunakan sekali</li>
                                <li>Password harus memenuhi kriteria keamanan</li>
                            </ul>
                        </div>
                    </div>

                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="alert alert-danger d-flex align-items-start mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                            <ul class="mb-0 list-unstyled">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Reset Password Form --}}
                    <form method="POST" action="{{ route('password.update') }}" x-data="resetPasswordForm()">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email }}">

                        {{-- New Password Field --}}
                        <div class="mb-4">
                            <label class="form-label text-light fw-bold">
                                <i class="fas fa-lock me-2"></i>Password Baru
                            </label>
                            <div class="position-relative">
                                <input type="password"
                                       class="form-control-auth @error('password') is-invalid @enderror"
                                       name="password"
                                       placeholder="Masukkan password baru"
                                       required
                                       autocomplete="new-password"
                                       x-model="password"
                                       :type="showPassword ? 'text' : 'password'">
                                <button type="button"
                                        class="password-toggle position-absolute"
                                        @click="showPassword = !showPassword"
                                        style="right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer;">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>

                            {{-- Password Requirements --}}
                            <div class="mt-3" x-show="password.length > 0" style="font-size: 0.85rem;">
                                <div class="mb-2">
                                    <span :class="hasMinLength ? 'text-success' : 'text-muted'">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Minimal 8 karakter
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span :class="hasUppercase ? 'text-success' : 'text-muted'">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Huruf besar (A-Z)
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span :class="hasLowercase ? 'text-success' : 'text-muted'">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Huruf kecil (a-z)
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span :class="hasNumber ? 'text-success' : 'text-muted'">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Angka (0-9)
                                    </span>
                                </div>
                                <div class="mb-0">
                                    <span :class="hasSpecialChar ? 'text-success' : 'text-muted'">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Karakter khusus (@$!%*?&)
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Confirm Password Field --}}
                        <div class="mb-4">
                            <label class="form-label text-light fw-bold">
                                <i class="fas fa-lock me-2"></i>Konfirmasi Password
                            </label>
                            <div class="position-relative">
                                <input type="password"
                                       class="form-control-auth"
                                       name="password_confirmation"
                                       placeholder="Masukkan ulang password baru"
                                       required
                                       autocomplete="new-password"
                                       x-model="passwordConfirmation"
                                       :type="showPasswordConfirmation ? 'text' : 'password'">
                                <button type="button"
                                        class="password-toggle position-absolute"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        style="right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer;">
                                    <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                                </button>
                            </div>

                            {{-- Password Match Indicator --}}
                            <div class="mt-2" x-show="passwordConfirmation.length > 0" style="font-size: 0.85rem;">
                                <span x-show="passwordsMatch" class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Password cocok
                                </span>
                                <span x-show="!passwordsMatch" class="text-danger">
                                    <i class="fas fa-times-circle me-1"></i>
                                    Password tidak cocok
                                </span>
                            </div>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit" class="btn-auth-primary w-100">
                            <i class="fas fa-key me-2"></i>
                            Reset Password
                        </button>

                        {{-- Back to Login --}}
                        <div class="text-center mt-4">
                            <a href="{{ route('login') }}" class="auth-link">
                                <i class="fas fa-arrow-left me-2"></i>
                                Kembali ke Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('resetPasswordForm', () => ({
        password: '',
        passwordConfirmation: '',
        showPassword: false,
        showPasswordConfirmation: false,

        get hasMinLength() {
            return this.password.length >= 8;
        },

        get hasUppercase() {
            return /[A-Z]/.test(this.password);
        },

        get hasLowercase() {
            return /[a-z]/.test(this.password);
        },

        get hasNumber() {
            return /[0-9]/.test(this.password);
        },

        get hasSpecialChar() {
            return /[@$!%*?&]/.test(this.password);
        },

        get passwordsMatch() {
            return this.password === this.passwordConfirmation && 
                   this.passwordConfirmation.length > 0;
        },

        get isPasswordValid() {
            return this.hasMinLength && 
                   this.hasUppercase && 
                   this.hasLowercase && 
                   this.hasNumber && 
                   this.hasSpecialChar;
        }
    }));
});
</script>
@endpush