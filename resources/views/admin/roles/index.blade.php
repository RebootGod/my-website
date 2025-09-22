@extends('layouts.admin')
@section('title', 'Role & Permission Management')
@section('content')
<div class="container mx-auto">
    <h1 class="text-3xl font-bold mb-6">Role & Permission Management</h1>
    <a href="{{ route('admin.roles.create') }}" class="bg-green-600 text-white px-4 py-2 rounded mb-4 inline-block">+ Add Role</a>
    <table class="w-full bg-gray-800 rounded-lg overflow-hidden">
        <thead class="bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Role</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Hierarchy</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Permissions</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            @foreach($roles as $role)
            <tr>
                <td class="px-4 py-3">{{ $role->name }}</td>
                <td class="px-4 py-3">
                    <span class="font-mono text-green-400">{{ $role->hierarchy }}</span>
                    <small class="block text-gray-400 text-xs">{{ $role->hierarchy_level }}</small>
                </td>
                <td class="px-4 py-3">
                    @foreach($role->permissions as $perm)
                        <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs mr-1">{{ $perm->name }}</span>
                    @endforeach
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-yellow-400 mr-2">Edit</a>
                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-400">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
