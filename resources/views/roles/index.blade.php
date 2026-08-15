@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
    <x-page-header title="Roles &amp; Permissions" :breadcrumbs="['Roles' => null]" />

    <div class="card border-0 p-4 bg-white rounded-3">
        @foreach($roles as $role)
            <div class="mb-5 {{ $loop->last ? 'mb-0' : '' }}">
                <h4 class="mb-3">{{ $role->name }}</h4>
                <form method="POST" action="{{ route('roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-2">
                        @foreach($permissions as $permission)
                            <div class="col-md-4 col-lg-3">
                                <label class="form-check">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                        class="form-check-input" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                    <span class="form-check-label fs-14">{{ $permission->name }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary px-4">Save Permissions</button>
                    </div>
                </form>
                @if(!$loop->last)<hr class="my-4">@endif
            </div>
        @endforeach
    </div>
@endsection
