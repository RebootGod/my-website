{{-- ======================================== --}}
{{-- USER SHOW/DETAILS VIEW --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/users/show.blade.php --}}

@extends('layouts.admin')

@section('title', 'User Details - ' . $user->username)

@section('content')
<div class="container mx-auto">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">User Details: {{ $user->username }}</h1>
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.edit', $user) }}" 
               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition">
                ✏️ Edit User
            </a>
            <a href="{{ route('admin.users.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
                ← Back to List
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- User Information --}}
        <div class="lg:col-span-1">
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">User Information</h2>
                
                <div class="space-y-3">
                    <div>
                        <span class="text-gray-400 text-sm">Username</span>
                        <p class="text-white font-medium">{{ $user->username }}</p>
                    </div>
                    
                    <div>
                        <span class="text-gray-400 text-sm">Email</span>
                        <p class="text-white">{{ $user->email }}</p>
                    </div>
                    
                    <div>
                        <span class="text-gray-400 text-sm">Role</span>
                        <p>
                            @if($user->role === 'super_admin')
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-500 text-white">Super Admin</span>
                            @elseif($user->role === 'admin')
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-500 text-white">Admin</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-500 text-white">Member</span>
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <span class="text-gray-400 text-sm">Status</span>
                        <p>
                            @if($user->status === 'active')
                                <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                            @elseif($user->status === 'inactive')
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-500 text-black">Inactive</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-red-500 text-white">Banned</span>
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <span class="text-gray-400 text-sm">Registered</span>
                        <p class="text-white">{{ $user->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    
                    <div>
                        <span class="text-gray-400 text-sm">Last Login</span>
                        <p class="text-white">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('M d, Y H:i') }}
                                <span class="text-gray-400 text-sm">({{ $user->last_login_at->diffForHumans() }})</span>
                            @else
                                <span class="text-gray-500">Never</span>
                            @endif
                        </p>
                    </div>
                    
                    @if($user->last_login_ip)
                    <div>
                        <span class="text-gray-400 text-sm">Last IP</span>
                        <p class="text-white">{{ $user->last_login_ip }}</p>
                    </div>
                    @endif
                </div>

                {{-- Registration Info --}}
                @if($user->registration)
                <div class="mt-6 pt-6 border-t border-gray-700">
                    <h3 class="text-lg font-semibold mb-3">Registration Details</h3>
                    <div class="space-y-2">
                        <div>
                            <span class="text-gray-400 text-sm">Invite Code</span>
                            <p class="text-white font-mono">{{ $user->registration->inviteCode->code }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-sm">Invited By</span>
                            <p class="text-white">{{ $user->registration->inviteCode->creator->username }}</p>
                        </div>
                        <div>
                            <span class="text-gray-400 text-sm">Registration IP</span>
                            <p class="text-white">{{ $user->registration->ip_address }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Quick Actions --}}
                @if($user->id !== auth()->id())
                <div class="mt-6 pt-6 border-t border-gray-700 space-y-2">
                    <h3 class="text-lg font-semibold mb-3">Quick Actions</h3>
                    
                    @if($user->status !== 'banned')
                    <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition">
                            {{ $user->status === 'active' ? '🔒 Deactivate User' : '🔓 Activate User' }}
                        </button>
                    </form>
                    @endif
                    
                    <form action="{{ route('admin.users.toggle-ban', $user) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full {{ $user->status === 'banned' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600' }} text-white px-4 py-2 rounded-lg transition"
                                onclick="return confirm('Are you sure?')">
                            {{ $user->status === 'banned' ? '✅ Unban User' : '🚫 Ban User' }}
                        </button>
                    </form>
                    
                    @if(!$user->isSuperAdmin() || auth()->user()->isSuperAdmin())
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition"
                                onclick="return confirm('Delete this user permanently? This cannot be undone!')">
                            🗑️ Delete User
                        </button>
                    </form>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Statistics and Activity --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- User Statistics --}}
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">User Statistics</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-700 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-green-400">{{ $stats['total_views'] }}</p>
                        <p class="text-gray-400 text-sm">Total Views</p>
                    </div>
                    <div class="bg-gray-700 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-blue-400">{{ $stats['unique_movies'] }}</p>
                        <p class="text-gray-400 text-sm">Movies Watched</p>
                    </div>
                    <div class="bg-gray-700 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-yellow-400">{{ $stats['series_watched'] }}</p>
                        <p class="text-gray-400 text-sm">Series Watched</p>
                    </div>
                    <div class="bg-gray-700 rounded-lg p-4 text-center">
                        <p class="text-2xl font-bold text-purple-400">{{ $stats['invite_codes_created'] }}</p>
                        <p class="text-gray-400 text-sm">Invites Created</p>
                    </div>
                </div>
            </div>

            {{-- Recent Watch History --}}
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Recent Watch History</h2>
                @if($recentViews->count() > 0)
                <div class="space-y-3">
                    @foreach($recentViews as $view)
                    <div class="flex items-center justify-between bg-gray-700 rounded-lg p-3">
                        <div class="flex items-center space-x-3">
                            @if($view->movie->poster_path)
                            <img src="{{ $view->movie->poster_url }}" 
                                 alt="{{ $view->movie->title }}"
                                 class="w-12 h-16 object-cover rounded">
                            @endif
                            <div>
                                <p class="font-medium">{{ $view->movie->title }}</p>
                                <p class="text-gray-400 text-sm">
                                    {{ $view->watched_at->format('M d, Y H:i') }}
                                    @if($view->watch_duration)
                                    • {{ number_format($view->watch_duration / 60, 0) }} min watched
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('admin.movies.show', $view->movie) }}" 
                           class="text-blue-400 hover:text-blue-300">
                            View →
                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-400">No viewing history yet.</p>
                @endif
            </div>

            {{-- Invite Codes Created by User --}}
            @if($user->inviteCodes->count() > 0)
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Invite Codes Created</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-left py-2 text-gray-400">Code</th>
                                <th class="text-left py-2 text-gray-400">Status</th>
                                <th class="text-left py-2 text-gray-400">Used</th>
                                <th class="text-left py-2 text-gray-400">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->inviteCodes->take(5) as $code)
                            <tr class="border-b border-gray-700">
                                <td class="py-2 font-mono">{{ $code->code }}</td>
                                <td class="py-2">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $code->status === 'active' ? 'bg-green-500' : 'bg-gray-500' }} text-white">
                                        {{ ucfirst($code->status) }}
                                    </span>
                                </td>
                                <td class="py-2">{{ $code->used_count }} / {{ $code->max_uses ?: '∞' }}</td>
                                <td class="py-2 text-gray-400">{{ $code->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection