<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $editorRole = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // Define all permissions for resources
        $permissions = [
            // Category permissions
            'view_category',
            'create_category',
            'update_category',
            'delete_category',
            'restore_category',
            'force_delete_category',

            // Content permissions
            'view_content',
            'create_content',
            'update_content',
            'delete_content',
            'restore_content',
            'force_delete_content',

            // User permissions
            'view_user',
            'create_user',
            'update_user',
            'delete_user',

            // Role & Permission management
            'view_role',
            'create_role',
            'update_role',
            'delete_role',

            'view_permission',
            'create_permission',
            'update_permission',
            'delete_permission',
        ];

        // Create or get permissions
        $permissionModels = [];
        foreach ($permissions as $permission) {
            $permissionModels[] = Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Assign all permissions to super_admin role (including manually created ones)
        $superAdminRole->syncPermissions($permissionModels);
        
        // Also give super_admin ALL existing permissions (for new resources created by Filament Shield)
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $superAdminRole->syncPermissions($allPermissions);

        // Assign admin permissions (all except permission management)
        $adminPermissions = array_filter($permissions, function ($permission) {
            return !str_contains($permission, 'permission');
        });
        $adminRole->syncPermissions(array_values($adminPermissions));

        // Assign editor permissions (view, create, update only for categories and content)
        $editorPermissions = array_filter($permissions, function ($permission) {
            return in_array($permission, [
                'view_category', 'create_category', 'update_category',
                'view_content', 'create_content', 'update_content',
            ]);
        });
        $editorRole->syncPermissions(array_values($editorPermissions));

        // Assign viewer permissions (view only)
        $viewerPermissions = array_filter($permissions, function ($permission) {
            return str_contains($permission, 'view_');
        });
        $viewerRole->syncPermissions(array_values($viewerPermissions));

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('admin123'),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin2@example.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123'),
            ]
        );
        $admin->assignRole($adminRole);

        // Create editor user
        $editor = User::firstOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Editor',
                'password' => bcrypt('editor123'),
            ]
        );
        $editor->assignRole($editorRole);

        // Create viewer user
        $viewer = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer',
                'password' => bcrypt('viewer123'),
            ]
        );
        $viewer->assignRole($viewerRole);

        $this->command->info('Admin users with roles and permissions created successfully!');
        $this->command->info('');
        $this->command->info('Super Admin: admin@example.com / admin123');
        $this->command->info('Admin: admin2@example.com / admin123');
        $this->command->info('Editor: editor@example.com / editor123');
        $this->command->info('Viewer: viewer@example.com / viewer123');
    }
}
