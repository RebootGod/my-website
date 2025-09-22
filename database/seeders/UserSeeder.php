<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Get roles
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'User')->first();

        // 1. Create Super Admin (existing user or new)
        $existingAdmin = User::where('username', 'admin')->first();
        if ($existingAdmin) {
            // Update existing admin to use Role relation instead of string
            $existingAdmin->update([
                'role_id' => $superAdminRole->id,
                'role' => 'super_admin' // Keep string role for compatibility
            ]);
            echo "✅ Updated existing admin user to use Role relation\n";
        } else {
            User::create([
                'username' => 'admin',
                'email' => 'admin@noobzmovie.com', 
                'password' => Hash::make('admin123'),
                'role_id' => $superAdminRole->id,
                'role' => 'super_admin',
                'status' => 'active'
            ]);
            echo "✅ Created new Super Admin: admin\n";
        }

        // 2. Create Syuhada as Admin
        $syuhada = User::firstOrCreate(
            ['username' => 'syuhada'],
            [
                'email' => 'syuhada@noobzmovie.com',
                'password' => Hash::make('syuhada123'), 
                'role_id' => $adminRole->id,
                'role' => 'admin',
                'status' => 'active'
            ]
        );

        if ($syuhada->wasRecentlyCreated) {
            echo "✅ Created new Admin user: syuhada (password: syuhada123)\n";
        } else {
            // Update existing user to Admin role
            $syuhada->update([
                'role_id' => $adminRole->id,
                'role' => 'admin'
            ]);
            echo "✅ Updated existing user 'syuhada' to Admin role\n";
        }

        // 3. Create some test users
        $testUsers = [
            ['username' => 'testuser1', 'email' => 'user1@test.com'],
            ['username' => 'testuser2', 'email' => 'user2@test.com'],
        ];

        foreach ($testUsers as $userData) {
            User::firstOrCreate(
                ['username' => $userData['username']], 
                [
                    'email' => $userData['email'],
                    'password' => Hash::make('password123'),
                    'role_id' => $userRole->id,
                    'role' => 'user',
                    'status' => 'active'
                ]
            );
        }

        echo "✅ Created test users\n";
        echo "📊 User Summary:\n";
        foreach (User::with('role')->get() as $user) {
            $roleName = $user->role_id && $user->role ? $user->role->name : ($user->role ?? 'No Role');
            echo "  - {$user->username} ({$user->email}) → {$roleName}\n";
        }
    }
}