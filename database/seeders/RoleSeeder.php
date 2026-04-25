<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $roles = [
            'Admin' => $allPermissions,
            'Manager' => [
                'users.view',
                'users.create',
                'users.edit',
                'roles.view',
                'gallery.folders.create',
                'gallery.folders.view',
                'gallery.folders.edit',
                'gallery.images.create',
                'gallery.images.view',
                'gallery.images.edit',
                'gallery.images.delete',
            ],
            'Viewer' => [
                'users.view',
                'roles.view',
                'gallery.folders.view',
                'gallery.images.view',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
