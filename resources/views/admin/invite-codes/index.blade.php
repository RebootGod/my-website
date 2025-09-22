{{-- ======================================== --}}
{{-- INVITE CODES INDEX WITH ACTIONS --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/invite-codes/index.blade.php --}}

@extends('layouts.admin')

@section('title', 'Manage Invite Codes - Admin')

@section('content')
<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Manage Invite Codes</h1>
        <div class="flex space-x-4">
            <button onclick="quickGenerate()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold transition">
                Quick Generate
            </button>
            <a href="{{ route('admin.invite-codes.create') }}" class="bg-green-400 hover:bg-green-500 text-black px-6 py-2 rounded-lg font-semibold transition">
                + Create Custom Code
            </a>
        </div>
    </div>

    {{-- Invite Codes Table --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Used/Max</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Expires</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($inviteCodes as $code)
                <tr class="hover:bg-gray-700">
                    <td class="px-6 py-4">
                        <span class="text-white font-mono text-lg">{{ $code->code }}</span>
                        <button onclick="copyCode('{{ $code->code }}')" class="ml-2 text-gray-400 hover:text-white">
                            📋
                        </button>
                    </td>
                    <td class="px-6 py-4 text-gray-300">{{ $code->description ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full 
                            {{ $code->status == 'active' ? 'bg-green-600' : 'bg-red-600' }} text-white">
                            {{ ucfirst($code->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        {{ $code->used_count }} / {{ $code->max_uses ?? '∞' }}
                        @if($code->max_uses && $code->used_count >= $code->max_uses)
                            <span class="text-red-400 text-xs">(Full)</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        {{ $code->expires_at ? $code->expires_at->format('Y-m-d') : 'Never' }}
                        @if($code->expires_at && $code->expires_at->isPast())
                            <span class="text-red-400 text-xs">(Expired)</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-300">
                        {{ $code->creator->username }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            <form action="{{ route('admin.invite-codes.toggle-status', $code) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-yellow-400 hover:text-yellow-300">
                                    {{ $code->status == 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form action="{{ route('admin.invite-codes.destroy', $code) }}" method="POST" class="inline" onsubmit="return confirm('Delete this invite code?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-400">No invite codes found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($inviteCodes->hasPages())
    <div class="mt-6">
        {{ $inviteCodes->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function quickGenerate() {
    if(confirm('Generate a new random invite code?')) {
        // Create form data for POST request
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('count', 1);
        
        fetch('{{ route('admin.invite-codes.generate') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if(data.success && data.codes && data.codes.length > 0) {
                alert('Generated code: ' + data.codes[0].code);
                location.reload();
            } else {
                alert('Code generated successfully! Refreshing...');
                location.reload();
            }
        })
        .catch(error => {
            // Even if AJAX fails, the code might have been created
            // So we reload to check
            console.log('Reloading to check for new code...');
            location.reload();
        });
    }
}

function copyCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        alert('Code copied: ' + code);
    }, function(err) {
        console.error('Could not copy text: ', err);
    });
}
</script>
@endpush
@endsection