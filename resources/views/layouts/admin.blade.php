{{-- ======================================== --}}
{{-- ADMIN.BLADE.PHP - ADMIN LAYOUT --}}
{{-- ======================================== --}}
{{-- File: resources/views/layouts/admin.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Noobz Cinema')</title>

    {{-- Tailwind CDN Only --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- FontAwesome CDN --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Admin Styles --}}
    @vite(['resources/css/admin/admin-core.css', 'resources/css/admin/admin-mobile.css', 'resources/css/admin/admin-tables.css'])

    @stack('styles')
</head>
<body class="admin-layout">
    <div class="admin-container">
        {{-- Sidebar Navigation --}}
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2 class="admin-logo">Admin Panel</h2>
            </div>

            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar admin-nav-icon"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.movies.index') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.movies.*') ? 'active' : '' }}">
                    <i class="fas fa-film admin-nav-icon"></i>
                    <span>Manage Movies</span>
                </a>

                <a href="{{ route('admin.series.index') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.series.*') ? 'active' : '' }}">
                    <i class="fas fa-tv admin-nav-icon"></i>
                    <span>Manage Series</span>
                </a>

                <a href="{{ route('admin.users.index') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users admin-nav-icon"></i>
                    <span>Manage Users</span>
                </a>

                <a href="{{ route('admin.user-activity.index') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.user-activity.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line admin-nav-icon"></i>
                    <span>User Activity</span>
                </a>

                <a href="{{ route('admin.invite-codes.index') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.invite-codes.*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt admin-nav-icon"></i>
                    <span>Invite Codes</span>
                </a>

                <a href="{{ route('admin.reports.index') }}"
                    class="admin-nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-exclamation-triangle admin-nav-icon"></i>
                    <span>Reports</span>
                </a>

                @if(auth()->user()->hasPermission('manage_roles'))
                <a href="{{ route('admin.roles.index') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <i class="fas fa-shield-alt admin-nav-icon"></i>
                    <span>Role & Permission</span>
                </a>
                @endif

                <a href="{{ route('admin.logs.index') }}"
                   class="admin-nav-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list admin-nav-icon"></i>
                    <span>Admin Logs</span>
                </a>

                <div class="admin-divider"></div>

                <a href="{{ route('home') }}"
                   class="admin-nav-item">
                    <i class="fas fa-globe admin-nav-icon"></i>
                    <span>View Site</span>
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="admin-nav-item w-full text-left">
                        <i class="fas fa-sign-out-alt admin-nav-icon"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </nav>
        </aside>

        {{-- Main Content --}}
        <main class="admin-main">
            {{-- Top Header --}}
            <header class="admin-header">
                <h1 class="admin-header-title">Noobz Cinema</h1>
                <div class="admin-header-actions">
                    <span class="admin-header-user">
                        Admin: {{ auth()->user()->username }}
                    </span>
                </div>
            </header>

            {{-- Page Content --}}
            <div class="admin-content">
                {{-- Alert Messages --}}
                @if(session('success'))
                    <div class="admin-alert admin-alert-success">
                        <span>{{ session('success') }}</span>
                        <button class="admin-alert-close">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="admin-alert admin-alert-error">
                        <span>{{ session('error') }}</span>
                        <button class="admin-alert-close">&times;</button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="admin-alert admin-alert-warning">
                        <span>{{ session('warning') }}</span>
                        <button class="admin-alert-close">&times;</button>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    {{-- Admin Scripts --}}
    @vite(['resources/js/admin/admin-core.js', 'resources/js/admin/admin-mobile.js', 'resources/js/admin/admin-bulk.js'])

    @stack('scripts')
</body>
</html>