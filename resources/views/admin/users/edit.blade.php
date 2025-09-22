{{-- ======================================== --}}
{{-- USER EDIT VIEW --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/users/edit.blade.php --}}

@extends('layouts.admin')

@section('title', 'Edit User - ' . $user->username)

@section('content')
<div class="container mx-auto max-w-4xl">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Edit User: {{ $user->username }}</h1>
        <a href="{{ route('admin.users.index') }}" 
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            ← Back to List
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Edit User Form --}}
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">User Information</h2>
            
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    {{-- Username --}}
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-400 mb-1">
                            Username
                        </label>
                        <input type="text" 
                               id="username"
                               name="username" 
                               value="{{ old('username', $user->username) }}"
                               class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-green-400 @error('username') border-red-500 @enderror"
                               required>
                        @error('username')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-400 mb-1">
                            Email Address
                        </label>
                        <input type="email" 
                               id="email"
                               name="email" 
                               value="{{ old('email', $user->email) }}"
                               class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-green-400 @error('email') border-red-500 @enderror"
                               required>
                        @error('email')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    {{-- Role --}}
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-400 mb-1">
                            User Role
                        </label>
                        <select id="role"
                                name="role" 
                                class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-green-400 @error('role') border-red-500 @enderror"
                                {{ !auth()->user()->canChangeRole($user) ? 'disabled' : '' }}>
                            
                            {{-- Show current role if editing self --}}
                            @if($user->id === auth()->id())
                                <option value="{{ $user->role }}" selected>
                                    {{ ucfirst(str_replace('_', ' ', $user->role)) }} (Your Current Role)
                                </option>
                            @else
                                {{-- Only show roles with lower hierarchy than current user --}}
                                @php
                                    $currentUserLevel = auth()->user()->getHierarchyLevel();
                                    $availableRoles = [
                                        'user' => ['name' => 'User/Member', 'level' => 0],
                                        'moderator' => ['name' => 'Moderator', 'level' => 60],
                                        'admin' => ['name' => 'Admin', 'level' => 80],
                                        'super_admin' => ['name' => 'Super Admin', 'level' => 100],
                                    ];
                                @endphp
                                
                                @foreach($availableRoles as $roleKey => $roleData)
                                    @if($roleData['level'] < $currentUserLevel)
                                    <option value="{{ $roleKey }}" {{ old('role', $user->role) == $roleKey ? 'selected' : '' }}>
                                        {{ $roleData['name'] }} (Level {{ $roleData['level'] }})
                                    </option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                        
                        @if($user->id === auth()->id())
                        <p class="text-blue-400 text-sm mt-1">
                            👤 You cannot change your own role for security reasons.
                        </p>
                        @elseif(!auth()->user()->canChangeRole($user))
                        <p class="text-yellow-400 text-sm mt-1">
                            🔒 You cannot change roles for users with equal or higher privileges.
                        </p>
                        @else
                        <p class="text-gray-400 text-sm mt-1">
                            💡 You can only assign roles with lower hierarchy than yours ({{ auth()->user()->getHierarchyLevel() }}).
                        </p>
                        @endif
                        
                        @error('role')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-400 mb-1">
                            Account Status
                        </label>
                        <select id="status"
                                name="status" 
                                class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-green-400 @error('status') border-red-500 @enderror"
                                {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                            <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="banned" {{ old('status', $user->status) == 'banned' ? 'selected' : '' }}>Banned</option>
                        </select>
                        @if($user->id === auth()->id())
                        <p class="text-yellow-400 text-sm mt-1">You cannot change your own status</p>
                        @endif
                        @error('status')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    {{-- Submit Button --}}
                    <div class="pt-4">
                        <button type="submit" 
                                class="w-full bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg transition font-medium">
                            💾 Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Reset Password --}}
        <div class="space-y-6">
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Reset Password</h2>
                
                @if(auth()->user()->canResetPassword($user))
                <form action="{{ route('admin.users.reset-password', $user) }}" method="POST">
                    @csrf
                    
                    <div class="space-y-4">
                        {{-- New Password --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-400 mb-1">
                                New Password
                            </label>
                            <input type="text" 
                                   id="password"
                                   name="password" 
                                   class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-green-400 @error('password') border-red-500 @enderror"
                                   placeholder="Enter new password"
                                   required>
                            @error('password')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        {{-- Confirm Password --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-400 mb-1">
                                Confirm Password
                            </label>
                            <input type="text" 
                                   id="password_confirmation"
                                   name="password_confirmation" 
                                   class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg focus:ring-2 focus:ring-green-400"
                                   placeholder="Confirm new password"
                                   required>
                        </div>
                        
                        {{-- Generate Password Button --}}
                        <button type="button" 
                                onclick="generatePassword()"
                                class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                            🎲 Generate Random Password
                        </button>
                        
                        {{-- Submit Button --}}
                        <button type="submit" 
                                class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg transition font-medium">
                            @if($user->id === auth()->id())
                                🔐 Reset My Password
                            @else
                                🔐 Reset User Password
                            @endif
                        </button>
                    </div>
                </form>
                
                @if($user->id === auth()->id())
                <p class="text-blue-400 text-sm mt-3">
                    👤 You are resetting your own password. You will need to use the new password to login.
                </p>
                @else
                <p class="text-gray-400 text-sm mt-3">
                    📧 The new password will be sent to the user's email address.
                </p>
                @endif
                
                @else
                <div class="bg-red-800/30 border border-red-600 rounded-lg p-4">
                    <p class="text-red-400 flex items-center">
                        🔒 <span class="ml-2">Access denied. You cannot reset passwords for users with equal or higher privileges.</span>
                    </p>
                </div>
                @endif
            </div>

            {{-- User Information --}}
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Account Details</h2>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">User ID:</span>
                        <span class="text-white">#{{ $user->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Registered:</span>
                        <span class="text-white">{{ $user->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Last Login:</span>
                        <span class="text-white">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('M d, Y H:i') }}
                            @else
                                Never
                            @endif
                        </span>
                    </div>
                    @if($user->last_login_ip)
                    <div class="flex justify-between">
                        <span class="text-gray-400">Last IP:</span>
                        <span class="text-white">{{ $user->last_login_ip }}</span>
                    </div>
                    @endif
                    @if($user->registration)
                    <div class="flex justify-between">
                        <span class="text-gray-400">Invite Code:</span>
                        <span class="text-white font-mono">{{ $user->registration->inviteCode->code }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                
                <div class="space-y-2">
                    <a href="{{ route('admin.users.show', $user) }}" 
                       class="block w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition text-center">
                        👁️ View Full Details
                    </a>
                    
                    @if($user->id !== auth()->id() && (!$user->isSuperAdmin() || auth()->user()->isSuperAdmin()))
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" 
                          onsubmit="return confirm('Delete this user permanently? This cannot be undone!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                            🗑️ Delete User
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generatePassword() {
    fetch('{{ route("admin.users.generate-password") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('password').value = data.password;
            document.getElementById('password_confirmation').value = data.password;
            alert('Generated password: ' + data.password + '\n\nMake sure to copy this password!');
        });
}
</script>
@endpush
@endsection