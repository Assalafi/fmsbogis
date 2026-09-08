<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(config('finance_permissions.groups'))
            ->flatMap(fn (array $group) => array_keys($group))
            ->mapWithKeys(function (string $name) {
                $permission = Permission::firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);

                return [$name => $permission];
            });

        Role::query()
            ->whereIn('name', ['Super Admin', 'Finance Admin'])
            ->get()
            ->each(function (Role $role) use ($permissions) {
                if ($role->name === 'Super Admin') {
                    $role->syncPermissions($permissions->values());

                    return;
                }

                $role->givePermissionTo($permissions->only([
                    'roles.create',
                    'roles.delete',
                ])->values());
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['roles.create', 'roles.delete'])
            ->get()
            ->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
