<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * The permissions seeded for the web guard, grouped by resource.
     *
     * @var array<string, list<string>>
     */
    public const RESOURCE_ACTIONS = [
        'users' => ['create', 'view', 'edit', 'delete'],
        'roles' => ['create', 'view', 'edit', 'delete'],
        'gallery.folders' => ['create', 'view', 'edit', 'delete'],
        'gallery.images' => ['create', 'view', 'edit', 'delete'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::RESOURCE_ACTIONS as $resource => $actions) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$resource}.{$action}", 'web');
            }
        }
    }
}
