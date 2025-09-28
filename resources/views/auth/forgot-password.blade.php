{{-- ======================================== --}}
{{-- FORGOT PASSWORD PAGE --}}
{{-- ======================================== --}}
{{-- File: resources/views/auth/forgot-password.blade.php --}}

@extends('layouts.app')

@section('title', 'Forgot Password - Noobz Cinema')

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

    {{-- Forgot Password Form --}}
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 col-sm-9">
                <div class="auth-card p-5">
                    <h2 class="auth-title">🔑 FORGOT PASSWORD</h2>

                    {{-- Instructions --}}
                    <div class="text-center mb-4">
                        <p class="text-light">
                            <i class="fas fa-info-circle me-2"></i>Enter your email to reset your password
                        </p>
                    </div>

                    {{-- Success Message --}}
                    @if (session('status'))
                        <div class="alert alert-success text-center mb-4">
                            <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
                        </div>
                    @endif

                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="alert alert-danger text-center mb-4">
                            @foreach ($errors->all() as $error)
                                <i class="fas fa-exclamation-circle me-1"></i>{{ $error }}
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label text-light fw-bold">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input
                                type="email"
                                name="email"
                                placeholder="Enter your email address"
                                value="{{ old('email') }}"
                                class="form-control form-control-auth @error('email') is-invalid @enderror"
                                required
                                autocomplete="email"
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-auth-primary mb-4">
                            <i class="fas fa-paper-plane me-2"></i>SEND RESET LINK
                        </button>

                        <div class="text-center">
                            <a href="{{ route('login') }}" class="btn btn-outline-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to Login
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

