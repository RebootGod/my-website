@extends('layouts.admin')
@section('title', 'Edit Role')
@section('content')
<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">Edit Role</h1>
    <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="bg-gray-800 p-6 rounded-lg">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label class="block text-gray-300 mb-2">Role Name</label>
            <input type="text" name="name" class="w-full px-4 py-2 rounded bg-gray-700 text-white" value="{{ $role->name }}" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-300 mb-2">Hierarchy</label>
            <select name="hierarchy" class="w-full px-4 py-2 rounded bg-gray-700 text-white" required>
                <option value="0" {{ $role->hierarchy == 0 ? 'selected' : '' }}>0 - User/Member (Level terendah)</option>
                <option value="20" {{ $role->hierarchy == 20 ? 'selected' : '' }}>20 - Contributor (Kontributor)</option>
                <option value="40" {{ $role->hierarchy == 40 ? 'selected' : '' }}>40 - Editor (Editor konten)</option>
                <option value="60" {{ $role->hierarchy == 60 ? 'selected' : '' }}>60 - Moderator (Moderasi)</option>
                <option value="80" {{ $role->hierarchy == 80 ? 'selected' : '' }}>80 - Admin (Administrator)</option>
                <option value="100" {{ $role->hierarchy == 100 ? 'selected' : '' }}>100 - Super Admin (Level tertinggi)</option>
            </select>
            <small class="text-gray-400 text-sm mt-1 block">
                💡 Semakin tinggi angka = semakin tinggi level. Super Admin bisa manage semua role di bawahnya.
            </small>
        </div>
        <div class="mb-4">
            <label class="block text-gray-300 mb-2">Permissions</label>
            @foreach($permissions as $perm)
                <label class="inline-flex items-center mr-4">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="form-checkbox" @if($role->permissions->contains($perm)) checked @endif>
                    <span class="ml-2 text-white">{{ $perm->name }}</span>
                </label>
            @endforeach
        </div>
        <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded">Update</button>
    </form>
</div>
@endsection
