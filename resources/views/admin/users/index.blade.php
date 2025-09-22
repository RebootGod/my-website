{{-- ======================================== --}}
{{-- USERS INDEX VIEW --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/users/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Manage Users - Admin')

@section('content')
<div class="container mx-auto">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Users</h1>
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.export') }}" 
               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                📥 Export CSV
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Total Users</h3>
            <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Active</h3>
            <p class="text-2xl font-bold text-green-400">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Inactive</h3>
            <p class="text-2xl font-bold text-yellow-400">{{ $stats['inactive'] }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-gray-400 text-sm">Banned</h3>
            <p class="text-2xl font-bold text-red-400">{{ $stats['banned'] }}</p>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="bg-gray-800 rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-3">
            {{-- Search --}}
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Search username or email..." 
                   class="bg-gray-700 text-white px-4 py-2 rounded-lg flex-1 min-w-[200px]">
            
            {{-- Role Filter --}}
            <select name="role" class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                <option value="">All Roles</option>
                <option value="member" {{ request('role') == 'member' ? 'selected' : '' }}>Member</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="super_admin" {{ request('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            </select>
            
            {{-- Status Filter --}}
            <select name="status" class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="banned" {{ request('status') == 'banned' ? 'selected' : '' }}>Banned</option>
            </select>
            
            {{-- Sort Options --}}
            <select name="sort" class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Registration Date</option>
                <option value="last_login" {{ request('sort') == 'last_login' ? 'selected' : '' }}>Last Login</option>
                <option value="username" {{ request('sort') == 'username' ? 'selected' : '' }}>Username</option>
            </select>
            
            <select name="order" class="bg-gray-700 text-white px-4 py-2 rounded-lg">
                <option value="desc" {{ request('order') == 'desc' ? 'selected' : '' }}>Newest First</option>
                <option value="asc" {{ request('order') == 'asc' ? 'selected' : '' }}>Oldest First</option>
            </select>
            
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition">
                🔍 Search
            </button>
            
            @if(request()->hasAny(['search', 'role', 'status', 'sort']))
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
                ✕ Clear
            </a>
            @endif
        </form>
    </div>

    {{-- Users Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Registered</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Last Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($users as $user)
                <tr class="hover:bg-gray-700 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div>
                                <div class="text-sm font-medium text-white">
                                    {{ $user->username }}
                                </div>
                                @if($user->registration && $user->registration->inviteCode)
                                <div class="text-xs text-gray-400">
                                    Invite: {{ $user->registration->inviteCode->code }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ $user->email }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->role === 'super_admin')
                            <span class="px-2 py-1 text-xs rounded-full bg-purple-500 text-white">Super Admin</span>
                        @elseif($user->role === 'admin')
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-500 text-white">Admin</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-500 text-white">Member</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->status === 'active')
                            <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Active</span>
                        @elseif($user->status === 'inactive')
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-500 text-black">Inactive</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-red-500 text-white">Banned</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        {{ $user->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        @if($user->last_login_at)
                            {{ $user->last_login_at->diffForHumans() }}
                        @else
                            <span class="text-gray-500">Never</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.users.show', $user) }}" 
                               class="text-blue-400 hover:text-blue-300" title="View Details">
                                👁️
                            </a>
                            
                            @if(auth()->user()->canEdit($user))
                            <a href="{{ route('admin.users.edit', $user) }}" 
                               class="text-yellow-400 hover:text-yellow-300" title="Edit">
                                ✏️
                            </a>
                            @endif
                            
                            @if($user->id !== auth()->id() && auth()->user()->canBan($user))
                                @if($user->status !== 'banned')
                                <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="text-orange-400 hover:text-orange-300"
                                            title="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                        {{ $user->status === 'active' ? '🔒' : '🔓' }}
                                    </button>
                                </form>
                                @endif
                                
                                <form action="{{ route('admin.users.toggle-ban', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="text-red-400 hover:text-red-300"
                                            title="{{ $user->status === 'banned' ? 'Unban' : 'Ban' }}"
                                            onclick="return confirm('Are you sure you want to {{ $user->status === 'banned' ? 'unban' : 'ban' }} this user?')">
                                        {{ $user->status === 'banned' ? '✅' : '🚫' }}
                                    </button>
                                </form>
                                
                                @if(auth()->user()->canManage($user))
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="text-red-500 hover:text-red-400"
                                            title="Delete"
                                            onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!')">
                                        🗑️
                                    </button>
                                </form>
                                @endif
                            @elseif($user->id === auth()->id())
                                <span class="text-gray-500 text-xs">You</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-400">
                        No users found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
@endsection