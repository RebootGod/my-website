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

    {{-- Bootstrap 5 CSS (Latest with SRI) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
          crossorigin="anonymous">
    {{-- Font Awesome (Latest with SRI) --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" 
          rel="stylesheet" 
          integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" 
          crossorigin="anonymous" 
          referrerpolicy="no-referrer">
    
    {{-- Custom Styles --}}
    @vite('resources/css/design-system.css')
    @vite('resources/css/utilities.css')
    @vite('resources/css/layouts/navigation.css')
    @vite('resources/css/layouts/app.css')
    @vite('resources/css/components/mobile.css')
    
    @stack('styles')
</head>
<body>
    {{-- Modern Compact Navigation --}}
    <nav class="modern-navbar" id="mainNavbar">
        <div class="navbar-container">
            {{-- Left: Logo --}}
            <a href="{{ route('home') }}" class="navbar-logo">
                <img src="{{ asset('Removal.png') }}" alt="Noobz Cinema" height="36">
            </a>
            
            {{-- Right: Actions --}}
            <div class="navbar-actions">
                @auth
                    {{-- Watchlist --}}
                    <a href="{{ route('profile.watchlist') }}" class="nav-btn" title="Watchlist">
                        <i class="fas fa-bookmark"></i>
                        <span class="nav-label">Watchlist</span>
                    </a>
                    
                    {{-- Admin (if admin) --}}
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-btn nav-btn-admin" title="Admin Dashboard">
                            <i class="fas fa-tools"></i>
                            <span class="nav-label">Admin</span>
                        </a>
                    @endif
                    
                    {{-- Notifications --}}
                    <div class="dropdown">
                        <button class="nav-btn" type="button" id="notificationDropdown" 
                                data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                            <i class="fas fa-bell"></i>
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <span class="notification-badge">
                                    {{ auth()->user()->unreadNotifications->count() > 9 ? '9+' : auth()->user()->unreadNotifications->count() }}
                                </span>
                            @endif
                        </button>
                        
                        {{-- Notifications Dropdown --}}
                        <ul class="dropdown-menu dropdown-menu-redesign dropdown-menu-end" 
                            aria-labelledby="notificationDropdown">
                            <li class="dropdown-header-redesign d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Notifications</span>
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm text-decoration-none p-0">
                                            Mark all read
                                        </button>
                                    </form>
                                @endif
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            
                            @forelse(auth()->user()->notifications()->take(10)->get() as $notification)
                                <li>
                                    <a href="{{ route('notifications.show', $notification->id) }}" 
                                       class="dropdown-item py-2 {{ is_null($notification->read_at) ? 'bg-light' : '' }}">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-dark">
                                                    {{ $notification->data['title'] ?? 'Notification' }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $notification->data['message'] ?? 'You have a new notification' }}
                                                </small>
                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                            @if(is_null($notification->read_at))
                                                <div class="ms-2">
                                                    <span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px;"></span>
                                                </div>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                                @if(!$loop->last)
                                    <li><hr class="dropdown-divider my-0"></li>
                                @endif
                            @empty
                                <li class="dropdown-item text-center text-muted py-3">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <div>No notifications</div>
                                </li>
                            @endforelse
                            
                            @if(auth()->user()->notifications->count() > 0)
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a href="{{ route('notifications.index') }}" class="dropdown-item text-center text-primary fw-bold py-2">
                                        View All Notifications
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    
                    {{-- User Dropdown --}}
                    <div class="dropdown">
                        <button class="nav-btn nav-btn-user" type="button" id="userDropdown"
                                data-bs-toggle="dropdown" data-bs-auto-close="true" 
                                aria-expanded="false" title="Account">
                            <i class="fas fa-user-circle"></i>
                            <span class="nav-label">{{ auth()->user()->username }}</span>
                        </button>
                        
                        {{-- Dropdown Menu with better positioning --}}
                        <ul class="dropdown-menu dropdown-menu-redesign dropdown-menu-end user-dropdown-menu" 
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
                    {{-- Guest Actions --}}
                    <a href="{{ route('login') }}" class="nav-btn nav-btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="nav-label">Login</span>
                    </a>
                    <a href="{{ route('register') }}" class="nav-btn nav-btn-register">
                        <i class="fas fa-user-plus"></i>
                        <span class="nav-label">Register</span>
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
                <img src="{{ asset('Removal.png') }}"
                     alt="Noobz Cinema"
                     height="20"
                     class="d-inline-block align-baseline">
                All rights reserved.</p>
            <p class="mb-0 small text-white">
                Noobz Cinema does not host any content on this website. All content is taken from third party sources.
            </p>
        </div>
    </footer>

    {{-- Global Scripts (Updated with SRI) --}}
    <!-- jQuery CDN (Latest with integrity) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
            crossorigin="anonymous"></script>

    <!-- Bootstrap 5 JavaScript Bundle with Popper (Latest with SRI) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
            crossorigin="anonymous"></script>

    <!-- Alpine.js - Latest version -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

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