<?php

namespace Database\Seeders;

use App\Models\Camera;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles first
        $adminRole = Role::create([
            'name' => 'Admin',
            'description' => 'Administrator with full access',
        ]);

        $userRole = Role::create([
            'name' => 'User',
            'description' => 'Regular user with standard access',
        ]);

        $guestRole = Role::create([
            'name' => 'Guest',
            'description' => 'Guest user with limited access',
        ]);

        // Create permissions for Admin role
        Permission::create([
            'role_id' => $adminRole->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
            'can_manage_users' => true,
            'can_manage_roles' => true,
            'can_manage_permissions' => true,
            'can_manage_cameras' => true,
        ]);

        // Create permissions for User role
        Permission::create([
            'role_id' => $userRole->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
            'can_manage_users' => false,
            'can_manage_roles' => false,
            'can_manage_permissions' => false,
            'can_manage_cameras' => true,
        ]);

        // Create permissions for Guest role
        Permission::create([
            'role_id' => $guestRole->id,
            'can_create' => false,
            'can_read' => true,
            'can_update' => false,
            'can_delete' => false,
            'can_manage_users' => false,
            'can_manage_roles' => false,
            'can_manage_permissions' => false,
            'can_manage_cameras' => false,
        ]);

        // Create users
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $regularUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $guestUser = User::create([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        // Assign roles to users
        UserRole::create([
            'user_id' => $adminUser->id,
            'role_id' => $adminRole->id,
        ]);

        UserRole::create([
            'user_id' => $regularUser->id,
            'role_id' => $userRole->id,
        ]);

        UserRole::create([
            'user_id' => $guestUser->id,
            'role_id' => $guestRole->id,
        ]);

        // Create some cameras for testing
        Camera::factory()->count(10)->create();
    }
}
