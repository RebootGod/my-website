{{-- ======================================== --}}
{{-- 3. ENHANCED REGISTER PAGE --}}
{{-- ======================================== --}}
{{-- File: resources/views/auth/register.blade.php --}}

@extends('layouts.app')

@section('title', 'Register - Noobz Cinema')

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

    {{-- Register Form --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="auth-card p-5">
                    <h2 class="auth-title">🎬 REGISTER</h2>

                    {{-- Invite Code Info --}}
                    <div class="text-center mb-4">
                        <p class="text-light mb-2">
                            <i class="fas fa-info-circle me-2"></i>Butuh Invite Code?
                        </p>
                        <a href="https://t.me/noobzspace" class="auth-link" target="_blank">
                            <i class="fab fa-telegram me-2"></i>t.me/noobzspace
                        </a>
                    </div>

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
                                    maxlength="255"
                                    autocomplete="email"
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
                                    maxlength="128"
                                    autocomplete="new-password"
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
                                    maxlength="128"
                                    autocomplete="new-password"
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
                                maxlength="50"
                                autocomplete="off"
                            >

                            @error('invite_code')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

