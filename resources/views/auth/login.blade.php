{{-- ======================================== --}}
{{-- 2. ENHANCED LOGIN PAGE --}}
{{-- ======================================== --}}
{{-- File: resources/views/auth/login.blade.php --}}

@extends('layouts.app')

@section('title', 'Login - Noobz Cinema')

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

    {{-- Login Form --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="auth-card p-5">
                    <h2 class="auth-title">🎬 LOGIN</h2>

                    {{-- Invite Code Info --}}
                    <div class="text-center mb-4">
                        <p class="text-light mb-2">
                            <i class="fas fa-info-circle me-2"></i>Butuh Invite Code? Join Telegram Channel kami:
                        </p>
                        <a href="https://t.me/noobzspace" class="auth-link" target="_blank">
                            <i class="fab fa-telegram me-2"></i>t.me/noobzspace
                        </a>
                    </div>

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
<script src="{{ asset('js/auth/login.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeLoginForm({
            csrfToken: '{{ csrf_token() }}'
        });
    });
</script>
@endpush