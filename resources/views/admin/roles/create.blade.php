@extends('layouts.admin')
@section('title', 'Add Role')
@section('content')
<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">Add New Role</h1>
    <form action="{{ route('admin.roles.store') }}" method="POST" class="bg-gray-800 p-6 rounded-lg">
        @csrf
        <div class="mb-4">
            <label class="block text-gray-300 mb-2">Role Name</label>
            <input type="text" name="name" class="w-full px-4 py-2 rounded bg-gray-700 text-white" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-300 mb-2">Hierarchy</label>
            <select name="hierarchy" class="w-full px-4 py-2 rounded bg-gray-700 text-white" required>
                <option value="0">0 - User/Member (Level terendah)</option>
                <option value="20">20 - Contributor (Kontributor)</option>
                <option value="40">40 - Editor (Editor konten)</option>
                <option value="60">60 - Moderator (Moderasi)</option>
                <option value="80">80 - Admin (Administrator)</option>
                <option value="100">100 - Super Admin (Level tertinggi)</option>
            </select>
            <small class="text-gray-400 text-sm mt-1 block">
                💡 Semakin tinggi angka = semakin tinggi level. Super Admin bisa manage semua role di bawahnya.
            </small>
        </div>
        <div class="mb-4">
            <label class="block text-gray-300 mb-2">Permissions</label>
            @foreach($permissions as $perm)
                <label class="inline-flex items-center mr-4">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="form-checkbox">
                    <span class="ml-2 text-white">{{ $perm->name }}</span>
                </label>
            @endforeach
        </div>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Save</button>
    </form>
</div>
@endsection
