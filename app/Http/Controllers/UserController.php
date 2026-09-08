<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->assignableRoles(auth()->user());

        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $assignableRoleNames = $this->assignableRoles($request->user())->pluck('name')->all();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($assignableRoleNames)],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
        ]);

        $user->assignRole($data['role']);

        app(AuditService::class)->log('User Created', $user, null, [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        return redirect()->route('users.index')->with($this->toast('User created successfully.'));
    }

    public function edit(User $user)
    {
        $this->ensureUserCanBeManaged(auth()->user(), $user);
        $roles = $this->assignableRoles(auth()->user());

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureUserCanBeManaged($request->user(), $user);
        $assignableRoleNames = $this->assignableRoles($request->user())->pluck('name')->all();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($assignableRoleNames)],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles($data['role']);

        app(AuditService::class)->log('User Updated', $user);

        return redirect()->route('users.index')->with($this->toast('User updated successfully.'));
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');
        $this->ensureUserCanBeManaged(auth()->user(), $user);

        $user->update(['status' => 'inactive']);

        app(AuditService::class)->log('User Disabled', $user);

        return back()->with($this->toast('User disabled.'));
    }

    private function assignableRoles(User $administrator): Collection
    {
        $roles = Role::query()
            ->with('permissions')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        if ($administrator->hasRole('Super Admin')) {
            return $roles;
        }

        $ownedPermissions = $administrator->getAllPermissions()->pluck('name');

        return $roles
            ->reject(fn (Role $role) => in_array($role->name, config('finance_permissions.protected_roles', []), true))
            ->filter(fn (Role $role) => $role->permissions->pluck('name')->diff($ownedPermissions)->isEmpty())
            ->values();
    }

    private function ensureUserCanBeManaged(User $administrator, User $user): void
    {
        if ($administrator->hasRole('Super Admin') || $administrator->is($user)) {
            return;
        }

        abort_if(
            $user->hasAnyRole(config('finance_permissions.protected_roles', [])),
            403,
            'Only a Super Admin can manage a protected administrator account.',
        );

        $ownedPermissions = $administrator->getAllPermissions()->pluck('name');
        $targetHasHigherPermissions = $user->getAllPermissions()
            ->pluck('name')
            ->diff($ownedPermissions)
            ->isNotEmpty();

        abort_if($targetHasHigherPermissions, 403, 'You cannot manage a user with permissions above your own access level.');
    }
}
