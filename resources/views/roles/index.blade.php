@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
    <x-page-header title="Roles &amp; Permissions" :breadcrumbs="['Roles' => null]">
        @can('roles.create')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="material-symbols-outlined align-middle fs-18">add_moderator</i>
                Create Role
            </a>
        @endcan
    </x-page-header>

    <x-validation-errors />

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 p-4 bg-white rounded-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="material-symbols-outlined text-primary fs-36">shield_person</span>
                    <div>
                        <div class="fs-3 fw-bold">{{ $roles->count() }}</div>
                        <div class="text-secondary">Available Roles</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-4 bg-white rounded-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="material-symbols-outlined text-success fs-36">verified_user</span>
                    <div>
                        <div class="fs-3 fw-bold">{{ $permissionGroups->sum(fn ($items) => $items->count()) }}</div>
                        <div class="text-secondary">Available Permissions</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 p-4 bg-white rounded-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <span class="material-symbols-outlined text-warning fs-36">group</span>
                    <div>
                        <div class="fs-3 fw-bold">{{ $roles->sum('users_count') }}</div>
                        <div class="text-secondary">Role Assignments</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid gap-3">
        @forelse($roles as $role)
            @php
                $isProtected = in_array($role->name, $protectedRoles, true);
                $isOwnRole = auth()->user()->roles->contains(fn ($assignedRole) => $assignedRole->is($role));
                $manageableNames = $permissionGroups->flatten(1)->pluck('permission.name');
                $hasHigherPermissions = $role->permissions->pluck('name')->diff($manageableNames)->isNotEmpty();
                $canDeleteRole = auth()->user()->can('roles.delete') && ! $isProtected && ! $isOwnRole && ! $hasHigherPermissions && $role->users_count === 0;
            @endphp

            <div class="card border-0 bg-white rounded-3">
                <div class="p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <span class="material-symbols-outlined">{{ $isProtected ? 'security' : 'admin_panel_settings' }}</span>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h5 class="mb-0">{{ $role->name }}</h5>
                                @if($isProtected)
                                    <span class="badge bg-warning-subtle text-warning">Protected</span>
                                @endif
                                @if($isOwnRole)
                                    <span class="badge bg-primary-subtle text-primary">Your Role</span>
                                @endif
                            </div>
                            <div class="text-secondary fs-14 mt-1 mb-2">
                                {{ $role->permissions->count() }} permissions &middot;
                                {{ $role->users_count }} {{ \Illuminate\Support\Str::plural('user', $role->users_count) }}
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($role->permissions->take(5) as $permission)
                                    <span class="badge bg-light text-secondary fw-normal">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission->name)) }}</span>
                                @endforeach
                                @if($role->permissions->count() > 5)
                                    <span class="badge bg-primary-subtle text-primary fw-normal">+{{ $role->permissions->count() - 5 }} more</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-outline-primary btn-sm">
                            <span class="material-symbols-outlined align-middle fs-18">{{ ($isProtected || $isOwnRole || $hasHigherPermissions || ! auth()->user()->can('roles.update')) ? 'visibility' : 'edit' }}</span>
                            {{ ($isProtected || $isOwnRole || $hasHigherPermissions || ! auth()->user()->can('roles.update')) ? 'View Permissions' : 'Edit Role' }}
                        </a>
                        @if($canDeleteRole)
                            <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Delete the {{ addslashes($role->name) }} role permanently?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <span class="material-symbols-outlined align-middle fs-18">delete</span>
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card border-0 p-5 bg-white rounded-3 text-center text-secondary">
                No roles have been configured.
            </div>
        @endforelse
    </div>
@endsection
