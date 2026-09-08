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

        $permission = Permission::query()
            ->where('name', 'virements.cross_type')
            ->where('guard_name', 'web')
            ->first();
        $role = Role::query()
            ->where('name', 'Finance Admin')
            ->where('guard_name', 'web')
            ->first();

        if ($permission && $role) {
            $role->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()
            ->where('name', 'Finance Admin')
            ->where('guard_name', 'web')
            ->first();

        $role?->revokePermissionTo('virements.cross_type');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
