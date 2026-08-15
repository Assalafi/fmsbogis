<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $permissions = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ])['permissions'] ?? [];

        $role->syncPermissions($permissions);

        app(AuditService::class)->log('Role Permissions Updated', $role, null, [
            'role' => $role->name,
            'permissions' => $permissions,
        ]);

        return back()->with($this->toast('Role permissions saved.'));
    }
}
