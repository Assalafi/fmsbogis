<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $obsolete = [
        'budgets.create',
        'budgets.approve',
        'virements.create',
        'virements.approve',
        'virements.cross_type',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $sync = Permission::firstOrCreate([
            'name' => 'budgets.sync',
            'guard_name' => 'web',
        ]);

        $obsoleteIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->obsolete)
            ->pluck('id');

        $roleIds = DB::table('role_has_permissions')
            ->whereIn('permission_id', $obsoleteIds)
            ->pluck('role_id')
            ->unique();

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $sync->id,
                'role_id' => $roleId,
            ]);
        }

        $directAssignments = DB::table('model_has_permissions')
            ->whereIn('permission_id', $obsoleteIds)
            ->select('model_type', 'model_id')
            ->distinct()
            ->get();

        foreach ($directAssignments as $assignment) {
            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $sync->id,
                'model_type' => $assignment->model_type,
                'model_id' => $assignment->model_id,
            ]);
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $this->obsolete)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->obsolete as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Permission::query()
            ->where('name', 'budgets.sync')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
