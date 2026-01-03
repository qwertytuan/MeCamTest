<?php

namespace Database\Seeders;

use App\Models\Camera;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCameraAccess;
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
        $this->seedRolesAndPermissions();
        $users = $this->seedUsers();
        $cameras = $this->seedCameras();
        $this->seedUserCameraAccess($users, $cameras);

        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Default Login Credentials:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@example.com', 'password'],
                ['User', 'user@example.com', 'password'],
                ['Guest', 'guest@example.com', 'password'],
            ]
        );
    }

    /**
     * Seed roles and their permissions.
     */
    private function seedRolesAndPermissions(): array
    {
        $roles = [];

        // Admin Role - Full access
        $roles['admin'] = Role::create([
            'name' => 'Admin',
            'description' => 'Administrator with full system access',
        ]);

        Permission::create([
            'role_id' => $roles['admin']->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
            'can_manage_users' => true,
            'can_manage_roles' => true,
            'can_manage_permissions' => true,
            'can_manage_cameras' => true,
        ]);

        // User Role - Standard access
        $roles['user'] = Role::create([
            'name' => 'User',
            'description' => 'Regular user with camera management access',
        ]);

        Permission::create([
            'role_id' => $roles['user']->id,
            'can_create' => true,
            'can_read' => true,
            'can_update' => true,
            'can_delete' => true,
            'can_manage_users' => false,
            'can_manage_roles' => false,
            'can_manage_permissions' => false,
            'can_manage_cameras' => true,
        ]);

        // Guest Role - View only
        $roles['guest'] = Role::create([
            'name' => 'Guest',
            'description' => 'Guest user with view-only access',
        ]);

        Permission::create([
            'role_id' => $roles['guest']->id,
            'can_create' => false,
            'can_read' => true,
            'can_update' => false,
            'can_delete' => false,
            'can_manage_users' => false,
            'can_manage_roles' => false,
            'can_manage_permissions' => false,
            'can_manage_cameras' => false,
        ]);

        return $roles;
    }

    /**
     * Seed users.
     */
    private function seedUsers(): array
    {
        $users = [];
        $roles = Role::all()->keyBy('name');

        // Admin User
        $users['admin'] = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        UserRole::create([
            'user_id' => $users['admin']->id,
            'role_id' => $roles['Admin']->id,
        ]);

        // Regular User
        $users['user'] = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        UserRole::create([
            'user_id' => $users['user']->id,
            'role_id' => $roles['User']->id,
        ]);

        // Guest User
        $users['guest'] = User::create([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        UserRole::create([
            'user_id' => $users['guest']->id,
            'role_id' => $roles['Guest']->id,
        ]);

        return $users;
    }

    /**
     * Seed cameras with realistic data.
     */
    private function seedCameras(): array
    {
        $cameras = [];

        // USB Cameras
        $cameras[] = Camera::create([
            'name' => 'Main Entrance Camera',
            'location' => 'Front Door',
            'description' => 'Primary entrance monitoring camera',
            'connection_type' => 'USB',
            'usb_path' => '/dev/video0',
            'detection_type' => 'MOTION_HUMAN',
            'detection_enabled' => true,
            'status' => 'active',
        ]);

        $cameras[] = Camera::create([
            'name' => 'Office Camera',
            'location' => 'Main Office',
            'description' => 'Office workspace monitoring',
            'connection_type' => 'USB',
            'usb_path' => '/dev/video1',
            'detection_type' => 'MOTION',
            'detection_enabled' => true,
            'status' => 'active',
        ]);

        $cameras[] = Camera::create([
            'name' => 'Meeting Room Camera',
            'location' => 'Conference Room A',
            'description' => 'Conference room surveillance',
            'connection_type' => 'USB',
            'usb_path' => '/dev/video2',
            'detection_type' => 'HUMAN',
            'detection_enabled' => false,
            'status' => 'inactive',
        ]);

        // RTSP/Stream Cameras
        $cameras[] = Camera::create([
            'name' => 'Parking Lot Camera',
            'location' => 'Parking Area',
            'description' => 'Outdoor parking lot monitoring',
            'connection_type' => 'STREAM',
            'stream_url' => 'rtsp://192.168.1.101:554/stream1',
            'stream_username' => 'admin',
            'stream_password' => 'admin123',
            'detection_type' => 'MOTION_HUMAN',
            'detection_enabled' => true,
            'status' => 'active',
        ]);

        $cameras[] = Camera::create([
            'name' => 'Back Door Camera',
            'location' => 'Rear Entrance',
            'description' => 'Back entrance security camera',
            'connection_type' => 'STREAM',
            'stream_url' => 'rtsp://192.168.1.102:554/stream1',
            'stream_username' => 'admin',
            'stream_password' => 'admin123',
            'detection_type' => 'MOTION_HUMAN',
            'detection_enabled' => true,
            'status' => 'active',
        ]);

        $cameras[] = Camera::create([
            'name' => 'Warehouse Camera',
            'location' => 'Storage Area',
            'description' => 'Warehouse and storage monitoring',
            'connection_type' => 'STREAM',
            'stream_url' => 'rtsp://192.168.1.103:554/stream1',
            'detection_type' => 'MOTION',
            'detection_enabled' => true,
            'status' => 'inactive',
        ]);

        // Additional cameras with factory for variety
        $factoryCameras = Camera::factory()->count(4)->create();
        
        return array_merge($cameras, $factoryCameras->all());
    }

    /**
     * Seed user camera access permissions.
     */
    private function seedUserCameraAccess(array $users, array $cameras): void
    {
        // Regular user gets access to some cameras
        $userCameras = array_slice($cameras, 0, 4); // First 4 cameras
        foreach ($userCameras as $camera) {
            UserCameraAccess::create([
                'user_id' => $users['user']->id,
                'camera_id' => $camera->id,
                'can_view' => true,
                'can_control' => true,
                'can_download' => true,
            ]);
        }

        // Guest user gets view-only access to 2 cameras
        $guestCameras = array_slice($cameras, 0, 2); // First 2 cameras
        foreach ($guestCameras as $camera) {
            UserCameraAccess::create([
                'user_id' => $users['guest']->id,
                'camera_id' => $camera->id,
                'can_view' => true,
                'can_control' => false,
                'can_download' => false,
            ]);
        }
    }
}
