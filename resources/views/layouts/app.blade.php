{{-- ======================================== --}}
{{-- APP.BLADE.PHP - MAIN LAYOUT WITH PROFILE --}}
{{-- ======================================== --}}
{{-- File: resources/views/layouts/app.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Noobz Cinema')</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    {{-- Bootstrap 5 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    {{-- Custom Styles --}}
    @vite('resources/css/layouts/app.css')
    @vite('resources/css/components/mobile.css')
    
    @stack('styles')
</head>
<body>
    {{-- Navigation --}}
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(to right, #00ff88, #66ff99);">
        <div class="container-fluid">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="navbar-brand text-dark">
                <img src="https://github.com/RebootGod/Assets/blob/main/Removal.png?raw=true"
                     alt="Noobz Cinema"
                     height="40"
                     class="d-inline-block align-top">
            </a>
            
            {{-- Navigation Items --}}
            <div class="d-flex align-items-center">
                @auth
                    {{-- Watchlist Link --}}
                    <a href="{{ route('profile.watchlist') }}" 
                       class="btn btn-outline-dark me-2">
                        <i class="fas fa-list me-1"></i> Watchlist
                    </a>
                    
                    {{-- Admin Dashboard (if admin) --}}
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" 
                           class="btn btn-warning me-2">
                            <i class="fas fa-cog me-1"></i> Admin Dashboard
                        </a>
                    @endif
                    
                    {{-- User Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-danger dropdown-toggle d-flex align-items-center px-3 py-2" 
                                type="button" 
                                id="userDropdown"
                                data-bs-toggle="dropdown" 
                                data-bs-auto-close="true"
                                aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <span>{{ auth()->user()->username }}</span>
                        </button>
                        
                        {{-- Dropdown Menu with better positioning --}}
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark shadow-lg border-0 user-dropdown" 
                            aria-labelledby="userDropdown">
                            {{-- User Info Header --}}
                            <li class="dropdown-header px-3 py-2">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-primary me-2">
                                        {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-white">{{ auth()->user()->username }}</div>
                                        <small class="text-white-50">{{ auth()->user()->email }}</small>
                                    </div>
                                </div>
                            </li>
                            
                            <li><hr class="dropdown-divider my-1"></li>
                            
                            {{-- Profile Links --}}
                            <li>
                                <a href="{{ route('profile.index') }}" class="dropdown-item py-2">
                                    <i class="fas fa-user me-3 text-primary"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.watchlist') }}" class="dropdown-item py-2">
                                    <i class="fas fa-bookmark me-3 text-success"></i> My Watchlist
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('profile.edit') }}" class="dropdown-item py-2">
                                    <i class="fas fa-cog me-3 text-warning"></i> Settings
                                </a>
                            </li>
                            
                            {{-- Admin Link in Dropdown --}}
                            @if(auth()->user()->isAdmin())
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a href="{{ route('admin.dashboard') }}" class="dropdown-item py-2 text-warning">
                                    <i class="fas fa-tools me-3 text-warning"></i> Admin Panel
                                </a>
                            </li>
                            @endif
                            
                            {{-- Logout --}}
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="mb-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-danger">
                                        <i class="fas fa-sign-out-alt me-3 text-danger"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                        </ul>
                    </div>
                @else
                    {{-- Guest Links --}}
                    <a href="{{ route('login') }}" 
                       class="btn btn-danger me-2">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                    <a href="{{ route('register') }}" 
                       class="btn btn-danger">
                        <i class="fas fa-user-plus me-1"></i> Register
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Search Bar (if needed) --}}
    @yield('search-bar')

    {{-- Main Content --}}
    <main class="container-fluid px-3 py-4">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-dark mt-5 py-4">
        <div class="container text-center text-light">
            <p class="mb-2">&copy; 2025
                <img src="https://github.com/RebootGod/Assets/blob/main/Removal.png?raw=true"
                     alt="Noobz Cinema"
                     height="20"
                     class="d-inline-block align-baseline">
                All rights reserved.</p>
            <p class="mb-0 small text-white">
                Noobz Cinema does not host any content on this website. All content is taken from third party sources.
            </p>
        </div>
    </footer>

    {{-- Global Scripts --}}
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Bootstrap 5 JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @vite('resources/js/layouts/app.js')
    @stack('scripts')

    @auth
    <script>
        // Initialize app layout with required data
        if (typeof initializeAppLayout === 'function') {
            initializeAppLayout({
                csrfToken: '{{ csrf_token() }}'
            });
        }
    </script>
    @endauth
</body>
</html>