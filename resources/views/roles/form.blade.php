@php
    $editing = $role !== null;
    $readonly = $editing && ! $canUpdateRole;
    $pageTitle = $editing ? ($readonly ? 'View Role' : 'Edit Role') : 'Create Role';
@endphp

@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
    <x-page-header :title="$pageTitle" :breadcrumbs="['Roles' => route('roles.index'), $pageTitle => null]">
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back to Roles
        </a>
    </x-page-header>

    <x-validation-errors />

    @if($managementNotice)
        <div class="alert alert-warning border-0 d-flex gap-2 align-items-start">
            <span class="material-symbols-outlined fs-20">lock</span>
            <div>{{ $managementNotice }}</div>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('roles.update', $role) : route('roles.store') }}" data-role-form>
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <div class="card border-0 p-4 bg-white rounded-3 mb-4">
            <div class="row align-items-end g-3">
                <div class="col-lg-6">
                    <label for="role-name" class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input id="role-name" type="text" name="name" class="form-control" value="{{ old('name', $role?->name) }}" placeholder="e.g. Treasury Supervisor" maxlength="100" {{ $readonly ? 'readonly' : 'required' }}>
                    <div class="form-text">Use a clear job-based name that users will recognise.</div>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <span class="badge bg-primary-subtle text-primary fs-13 px-3 py-2">
                        {{ $permissionGroups->sum(fn ($items) => $items->count()) }} permissions available
                    </span>
                </div>
            </div>
        </div>

        <div class="card border-0 p-4 bg-white rounded-3">
            <div class="mb-4">
                <h5 class="mb-1">Permissions</h5>
                <p class="text-secondary fs-14 mb-0">Select exactly what users with this role can view and manage.</p>
            </div>

            @include('roles._permission-matrix', [
                'matrixId' => $editing ? 'edit-role-'.$role->getKey() : 'create-role',
                'selectedPermissions' => $selectedPermissions,
                'readonly' => $readonly,
            ])

            @unless($readonly)
                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-4 border-top">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <span class="material-symbols-outlined align-middle fs-18">save</span>
                        {{ $editing ? 'Save Role & Permissions' : 'Create Role' }}
                    </button>
                </div>
            @endunless
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ url('/assets/js/custom/role-permissions.js') }}?v={{ filemtime(public_path('assets/js/custom/role-permissions.js')) }}"></script>
@endpush
