<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PurchasesNotesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_shop::purchase',
            'view_any_shop::purchase',
            'create_shop::purchase',
            'update_shop::purchase',
            'delete_shop::purchase',
            'view_team::note',
            'view_any_team::note',
            'create_team::note',
            'update_team::note',
            'delete_team::note',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $permissionModels = Permission::whereIn('name', $permissions)->get();

        foreach (Role::where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo($permissionModels);
        }

        $superAdmin = Role::where('name', 'super_admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());
        }
    }
}
