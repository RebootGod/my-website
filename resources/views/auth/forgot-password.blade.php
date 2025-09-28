@extends('layouts.app')

@section('title', 'Forgot Password - Noobz Cinema')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
@endpush

@section('content')
<div class="forgot-password-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="forgot-password-card">

                    {{-- Header --}}
                    <div class="card-header">
                        <h1 class="page-title">Forgot Password</h1>
                        <p class="page-subtitle">Enter your email to reset your password</p>
                    </div>

                    {{-- Success Message --}}
                    @if (session('status'))
                        <div class="message success">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="message error">
                            @foreach ($errors->all() as $error)
                                {{ $error }}
                            @endforeach
                        </div>
                    @endif

                    {{-- Form --}}
                    <form method="POST" action="{{ route('password.email') }}" class="forgot-form">
                        @csrf

                        <div class="form-field">
                            <label for="email" class="field-label">Email Address</label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="field-input @error('email') error @enderror"
                                   value="{{ old('email') }}"
                                   required
                                   autocomplete="email"
                                   autofocus>
                        </div>

                        <button type="submit" class="submit-btn">
                            Send Reset Link
                        </button>
                    </form>

                    {{-- Footer Links --}}
                    <div class="card-footer">
                        <a href="{{ route('login') }}" class="back-link">← Back to Login</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

