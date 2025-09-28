{{-- ======================================== --}}
{{-- CREATE INVITE CODE FORM --}}
{{-- ======================================== --}}
{{-- File: resources/views/admin/invite-codes/create.blade.php --}}

@extends('layouts.admin')

@section('title', 'Create Invite Code - Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/forms.css') }}?v={{ filemtime(public_path('css/admin/forms.css')) }}">
@endpush

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Create Invite Code</h1>
        <a href="{{ route('admin.invite-codes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition">
            Back to List
        </a>
    </div>

    <form action="{{ route('admin.invite-codes.store') }}" method="POST" class="bg-gray-800 rounded-lg p-6">
        @csrf

        {{-- Code Input --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-400 mb-2">
                Invite Code (Optional - leave empty for auto-generate)
            </label>
            <div class="flex gap-2">
                <input 
                    type="text" 
                    name="code" 
                    id="code-input"
                    value="{{ old('code') }}"
                    placeholder="e.g., SPECIAL2024"
                    class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400 uppercase"
                    pattern="[A-Z0-9_-]{5,20}"
                    title="5-20 characters, letters, numbers, underscore and hyphen only"
                >
                <button type="button" onclick="generateRandomCode()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                    Generate Random
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate, or enter custom code (5-20 characters)</p>
            @error('code')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-400 mb-2">
                Description (Optional)
            </label>
            <input 
                type="text" 
                name="description" 
                value="{{ old('description') }}"
                placeholder="e.g., Special promotion code"
                class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
            >
            @error('description')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Max Uses --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-400 mb-2">
                Maximum Uses (Optional)
            </label>
            <input 
                type="number" 
                name="max_uses" 
                value="{{ old('max_uses') }}"
                placeholder="Leave empty for unlimited"
                min="1"
                max="1000"
                class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
            >
            <p class="text-xs text-gray-500 mt-1">Leave empty for unlimited uses</p>
            @error('max_uses')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Expiration Date --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-400 mb-2">
                Expiration Date (Optional)
            </label>
            <input 
                type="datetime-local" 
                name="expires_at" 
                value="{{ old('expires_at') }}"
                min="{{ now()->format('Y-m-d\TH:i') }}"
                class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400"
            >
            <p class="text-xs text-gray-500 mt-1">Leave empty for no expiration</p>
            @error('expires_at')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Status --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-400 mb-2">
                Status
            </label>
            <select name="status" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-green-400" required>
                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            @error('status')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit Buttons --}}
        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.invite-codes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                Cancel
            </a>
            <button type="submit" class="bg-green-400 hover:bg-green-500 text-black px-6 py-2 rounded-lg font-semibold transition">
                Create Invite Code
            </button>
        </div>
    </form>

    {{-- Examples --}}
    <div class="mt-8 bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-3">Examples</h3>
        <div class="space-y-2 text-sm text-gray-400">
            <p>• <span class="font-mono text-white">WELCOME2024</span> - General welcome code, unlimited uses</p>
            <p>• <span class="font-mono text-white">VIP100</span> - Limited to 100 uses</p>
            <p>• <span class="font-mono text-white">PROMO_DEC</span> - Expires end of December</p>
            <p>• <span class="font-mono text-white">SPECIAL-50</span> - Special promotion, 50 uses max</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generateRandomCode() {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 10; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    document.getElementById('code-input').value = result;
}

// Auto uppercase code input
document.getElementById('code-input').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
});
</script>
@endpush
@endsection