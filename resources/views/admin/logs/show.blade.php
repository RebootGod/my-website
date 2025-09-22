@extends('layouts.admin')

@section('title', 'Admin Log Details')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Admin Log Details</h1>
        <a href="{{ route('admin.logs.index') }}" class="bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Logs
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Basic Information --}}
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-info-circle mr-2 text-blue-400"></i>Basic Information
            </h2>
            
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-400">Date & Time:</span>
                    <span class="font-medium">{{ $log->created_at->format('M d, Y H:i:s') }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-400">Admin User:</span>
                    <span class="font-medium text-green-400">
                        {{ $log->admin ? $log->admin->username : 'Unknown' }}
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-400">Action:</span>
                    <span class="bg-blue-600 text-white text-sm px-2 py-1 rounded">
                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-400">Action Type:</span>
                    <span class="font-medium">{{ $log->action_type ?: 'N/A' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-400">Severity:</span>
                    <span class="bg-{{ $log->severity === 'high' ? 'red' : ($log->severity === 'medium' ? 'yellow' : 'green') }}-600 text-white text-sm px-2 py-1 rounded">
                        {{ ucfirst($log->severity ?: 'normal') }}
                    </span>
                </div>
                
                @if($log->description)
                <div class="pt-4 border-t border-gray-700">
                    <span class="text-gray-400 block mb-2">Description:</span>
                    <p class="text-sm bg-gray-700 p-3 rounded">{{ $log->description }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Target Information --}}
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-bullseye mr-2 text-red-400"></i>Target Information
            </h2>
            
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-400">Target Type:</span>
                    <span class="font-medium">{{ $log->target_type ?: 'N/A' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-400">Target ID:</span>
                    <span class="font-medium">{{ $log->target_id ?: 'N/A' }}</span>
                </div>
                
                @if($log->target_user_id && $log->targetUser)
                <div class="flex justify-between">
                    <span class="text-gray-400">Target User:</span>
                    <span class="font-medium text-blue-400">{{ $log->targetUser->username }}</span>
                </div>
                @endif
                
                @if($log->old_values)
                <div class="pt-4 border-t border-gray-700">
                    <span class="text-gray-400 block mb-2">Old Values:</span>
                    <pre class="text-sm bg-gray-700 p-3 rounded overflow-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif
                
                @if($log->new_values)
                <div class="pt-4 border-t border-gray-700">
                    <span class="text-gray-400 block mb-2">New Values:</span>
                    <pre class="text-sm bg-gray-700 p-3 rounded overflow-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif
            </div>
        </div>

        {{-- Technical Information --}}
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-network-wired mr-2 text-purple-400"></i>Technical Information
            </h2>
            
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-400">IP Address:</span>
                    <span class="font-medium">{{ $log->ip_address ?: 'N/A' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-400">Request Method:</span>
                    <span class="bg-gray-600 text-white text-sm px-2 py-1 rounded">
                        {{ $log->request_method ?: 'N/A' }}
                    </span>
                </div>
                
                @if($log->request_url)
                <div>
                    <span class="text-gray-400 block mb-2">Request URL:</span>
                    <p class="text-sm bg-gray-700 p-3 rounded break-all">{{ $log->request_url }}</p>
                </div>
                @endif
                
                @if($log->user_agent)
                <div>
                    <span class="text-gray-400 block mb-2">User Agent:</span>
                    <p class="text-sm bg-gray-700 p-3 rounded">{{ $log->user_agent }}</p>
                </div>
                @endif
                
                @if($log->session_id)
                <div class="flex justify-between">
                    <span class="text-gray-400">Session ID:</span>
                    <span class="font-mono text-sm">{{ substr($log->session_id, 0, 16) }}...</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Metadata --}}
        @if($log->metadata && (is_array($log->metadata) ? count($log->metadata) > 0 : !empty($log->metadata)))
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-database mr-2 text-yellow-400"></i>Additional Metadata
            </h2>
            
            <div class="bg-gray-700 p-4 rounded">
                <pre class="text-sm overflow-auto">{{ is_array($log->metadata) ? json_encode($log->metadata, JSON_PRETTY_PRINT) : $log->metadata }}</pre>
            </div>
        </div>
        @endif
    </div>

    {{-- Security Badge --}}
    @if($log->is_sensitive)
    <div class="mt-6 bg-red-900 border border-red-600 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-red-400 mr-3 text-xl"></i>
            <div>
                <h3 class="text-lg font-bold text-red-400">Sensitive Action</h3>
                <p class="text-red-200">This action involved sensitive data or security-related changes.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="mt-6 flex space-x-4">
        <a href="{{ route('admin.logs.index', ['admin_id' => $log->admin_id]) }}" 
           class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
            <i class="fas fa-filter mr-2"></i>Show all logs by this admin
        </a>
        
        @if($log->target_type === 'user' && $log->target_id)
        <a href="{{ route('admin.users.show', $log->target_id) }}" 
           class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition">
            <i class="fas fa-user mr-2"></i>View target user
        </a>
        @endif
    </div>
</div>
@endsection