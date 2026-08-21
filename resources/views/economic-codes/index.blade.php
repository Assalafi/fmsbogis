@extends('layouts.app')

@section('title', 'Economic Codes')

@section('content')
    <x-page-header title="Economic Codes" :breadcrumbs="['Economic Codes' => null]">
        @can('economic_codes.create')
        <a href="{{ route('economic-codes.upload') }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">upload</i>
            Upload Economic Codes
        </a>
        <a href="{{ route('economic-codes.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Add Economic Code
        </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('economic-codes.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-14">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    <option value="revenue" {{ request('type') === 'revenue' ? 'selected' : '' }}>Revenue</option>
                    <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Account Type</label>
                <select name="account_type" class="form-select">
                    <option value="">All</option>
                    <option value="capital" {{ request('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                    <option value="overhead" {{ request('account_type') === 'overhead' ? 'selected' : '' }}>Overhead</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-14">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Code or name">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('economic-codes.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Account Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($economicCodes as $code)
                        <tr>
                            <td class="fw-medium">{{ $code->code }}</td>
                            <td><a href="{{ route('economic-codes.show', $code) }}" class="text-decoration-none">{{ $code->name }}</a></td>
                            <td>
                                <span class="badge bg-{{ $code->isRevenue() ? 'success' : 'primary' }}">
                                    {{ ucfirst($code->type) }}
                                </span>
                            </td>
                            <td>{{ $code->account_type ? ucfirst($code->account_type) : '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($code->description, 40) }}</td>
                            <td>@include('components.status-badge', ['status' => $code->status])</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('economic-codes.show', $code) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    @can('economic_codes.update')
                                    <a href="{{ route('economic-codes.edit', $code) }}" class="text-primary" title="Edit"><i class="material-symbols-outlined fs-20">edit</i></a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                No Economic Codes Found
                                @can('economic_codes.create')
                                <div class="mt-2">
                                    <a href="{{ route('economic-codes.create') }}" class="btn btn-sm btn-primary">+ Add Economic Code</a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $economicCodes->links() }}
        </div>
    </div>
@endsection
