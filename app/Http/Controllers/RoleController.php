<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderByRaw("CASE WHEN name = 'Super Admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $permissions = $this->manageablePermissions($request->user());
        $permissionGroups = $this->permissionGroups($permissions);
        $protectedRoles = config('finance_permissions.protected_roles', []);

        return view('roles.index', compact(
            'roles',
            'permissionGroups',
            'protectedRoles',
        ));
    }

    public function create(Request $request)
    {
        $permissionGroups = $this->permissionGroups(
            $this->manageablePermissions($request->user()),
        );

        return view('roles.form', [
            'role' => null,
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => old('permissions', ['dashboard.view']),
            'canUpdateRole' => true,
            'managementNotice' => null,
        ]);
    }

    public function edit(Request $request, Role $role)
    {
        $permissionGroups = $this->permissionGroups(
            $this->manageablePermissions($request->user()),
        );
        $managementNotice = $this->roleManagementBlockReason($request->user(), $role);
        $canUpdateRole = $request->user()->can('roles.update') && $managementNotice === null;

        return view('roles.form', [
            'role' => $role->load('permissions'),
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => old('permissions', $role->permissions->pluck('name')->all()),
            'canUpdateRole' => $canUpdateRole,
            'managementNotice' => $managementNotice
                ?? ($canUpdateRole ? null : 'You do not have permission to update roles.'),
        ]);
    }

    public function store(Request $request)
    {
        $manageablePermissions = $this->manageablePermissions($request->user());
        $data = $this->validateRole($request, null, $manageablePermissions);
        $permissions = $this->normalizePermissions(
            $data['permissions'] ?? [],
            $manageablePermissions,
        );

        $role = DB::transaction(function () use ($data, $permissions) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($permissions);

            return $role;
        });

        app(AuditService::class)->log('Role Created', $role, null, [
            'role' => $role->name,
            'permissions' => $permissions,
        ]);

        return redirect()->route('roles.index')->with($this->toast('Role created successfully.'));
    }

    public function update(Request $request, Role $role)
    {
        $this->ensureRoleCanBeManaged($request->user(), $role);

        $manageablePermissions = $this->manageablePermissions($request->user());
        $data = $this->validateRole($request, $role, $manageablePermissions);
        $permissions = $this->normalizePermissions(
            $data['permissions'] ?? [],
            $manageablePermissions,
        );
        $oldValues = [
            'role' => $role->name,
            'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
        ];

        DB::transaction(function () use ($role, $data, $permissions) {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($permissions);
        });

        app(AuditService::class)->log('Role Updated', $role, $oldValues, [
            'role' => $role->name,
            'permissions' => $permissions,
        ]);

        return redirect()->route('roles.index')->with($this->toast('Role and permissions updated.'));
    }

    public function destroy(Request $request, Role $role)
    {
        $this->ensureRoleCanBeManaged($request->user(), $role);

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => "The {$role->name} role is assigned to users. Reassign those users before deleting it.",
            ]);
        }

        $oldValues = [
            'role' => $role->name,
            'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
        ];

        DB::transaction(fn () => $role->delete());

        app(AuditService::class)->log('Role Deleted', null, $oldValues, null);

        return redirect()->route('roles.index')->with($this->toast('Role deleted successfully.'));
    }

    private function validateRole(Request $request, ?Role $role, Collection $manageablePermissions): array
    {
        $request->merge([
            'name' => preg_replace('/\s+/', ' ', trim((string) $request->input('name'))),
        ]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::notIn(config('finance_permissions.protected_roles', [])),
                Rule::unique(config('permission.table_names.roles'), 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'web'))
                    ->ignore($role?->getKey()),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                'distinct',
                Rule::in($manageablePermissions->pluck('name')->all()),
            ],
        ], [
            'name.not_in' => 'That role name is reserved by the system.',
            'permissions.*.in' => 'You cannot grant a permission that your own account does not have.',
        ]);
    }

    private function normalizePermissions(array $selected, Collection $manageablePermissions): array
    {
        $allowed = $manageablePermissions->pluck('name')->flip();
        $normalized = collect($selected)
            ->filter(fn (string $permission) => $allowed->has($permission));

        // Every application page is behind dashboard.view. Also add each
        // module's view permission when a more powerful action is selected.
        if ($allowed->has('dashboard.view')) {
            $normalized->push('dashboard.view');
        }

        $normalized->each(function (string $permission) use ($normalized, $allowed) {
            [$module, $action] = array_pad(explode('.', $permission, 2), 2, null);
            $viewPermission = $module.'.view';

            if ($action !== 'view' && $allowed->has($viewPermission)) {
                $normalized->push($viewPermission);
            }
        });

        return $normalized->unique()->sort()->values()->all();
    }

    private function manageablePermissions(User $user): Collection
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        if ($user->hasRole('Super Admin')) {
            return $permissions;
        }

        $ownedPermissionNames = $user->getAllPermissions()->pluck('name');

        return $permissions->whereIn('name', $ownedPermissionNames)->values();
    }

    private function permissionGroups(Collection $permissions): Collection
    {
        $available = $permissions->keyBy('name');
        $grouped = collect();

        foreach (config('finance_permissions.groups', []) as $groupName => $catalogue) {
            $items = collect($catalogue)
                ->map(function (string $description, string $name) use ($available) {
                    if (! $available->has($name)) {
                        return null;
                    }

                    return [
                        'permission' => $available->get($name),
                        'description' => $description,
                    ];
                })
                ->filter()
                ->values();

            if ($items->isNotEmpty()) {
                $grouped->put($groupName, $items);
            }
        }

        $cataloguedNames = collect(config('finance_permissions.groups', []))
            ->flatMap(fn (array $group) => array_keys($group));
        $other = $permissions
            ->whereNotIn('name', $cataloguedNames)
            ->map(fn (Permission $permission) => [
                'permission' => $permission,
                'description' => 'Additional system permission',
            ])
            ->values();

        if ($other->isNotEmpty()) {
            $grouped->put('Other Permissions', $other);
        }

        return $grouped;
    }

    private function ensureRoleCanBeManaged(User $user, Role $role): void
    {
        $reason = $this->roleManagementBlockReason($user, $role);

        abort_if($reason !== null, 403, $reason);
    }

    private function roleManagementBlockReason(User $user, Role $role): ?string
    {
        if (in_array($role->name, config('finance_permissions.protected_roles', []), true)) {
            return 'This protected system role cannot be changed or deleted.';
        }

        if ($user->roles->contains(fn (Role $assignedRole) => $assignedRole->is($role))) {
            return 'You cannot modify a role assigned to your own account.';
        }

        if (! $user->hasRole('Super Admin')) {
            $ownedPermissions = $user->getAllPermissions()->pluck('name');
            $roleHasHigherPermissions = $role->permissions()
                ->whereNotIn('name', $ownedPermissions)
                ->exists();

            if ($roleHasHigherPermissions) {
                return 'You cannot manage a role with permissions above your own access level.';
            }
        }

        return null;
    }
}
