<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Permissions
        $permissions = [
            'access_admin_panel' => 'Access Admin Panel',
            'manage_users' => 'Manage Users',
            'manage_roles' => 'Manage Roles & Permissions', 
            'manage_movies' => 'Manage Movies',
            'manage_genres' => 'Manage Genres',
            'manage_reports' => 'Manage Reports',
            'manage_invite_codes' => 'Manage Invite Codes',
            'view_analytics' => 'View Analytics',
            'moderate_content' => 'Moderate Content',
            'edit_movies' => 'Edit Movies',
            'delete_movies' => 'Delete Movies',
            'ban_users' => 'Ban Users',
            'reset_passwords' => 'Reset User Passwords',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        // 2. Create Roles with Hierarchy
        $rolesData = [
            [
                'name' => 'Super Admin',
                'hierarchy' => 100,
                'permissions' => array_keys($permissions) // All permissions
            ],
            [
                'name' => 'Admin', 
                'hierarchy' => 80,
                'permissions' => [
                    'access_admin_panel',
                    'manage_users',
                    'manage_movies', 
                    'manage_genres',
                    'manage_reports',
                    'manage_invite_codes',
                    'view_analytics',
                    'moderate_content',
                    'edit_movies',
                    'delete_movies',
                    'ban_users',
                    'reset_passwords'
                ]
            ],
            [
                'name' => 'Moderator',
                'hierarchy' => 60, 
                'permissions' => [
                    'access_admin_panel',
                    'manage_reports',
                    'moderate_content',
                    'edit_movies',
                    'view_analytics'
                ]
            ],
            [
                'name' => 'Editor',
                'hierarchy' => 40,
                'permissions' => [
                    'access_admin_panel',
                    'manage_movies',
                    'edit_movies',
                    'manage_genres'
                ]
            ],
            [
                'name' => 'User',
                'hierarchy' => 0,
                'permissions' => []
            ]
        ];

        foreach ($rolesData as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']], 
                ['hierarchy' => $roleData['hierarchy']]
            );

            // Attach permissions
            $permissionIds = Permission::whereIn('name', $roleData['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        echo "✅ Roles and Permissions created successfully!\n";
        echo "📊 Created Roles:\n";
        foreach (Role::orderBy('hierarchy', 'desc')->get() as $role) {
            echo "  - {$role->name} (Level: {$role->hierarchy}) - {$role->permissions->count()} permissions\n";
        }
    }
}