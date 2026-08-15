@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
    <x-page-header title="Accounts" :breadcrumbs="['Accounts' => null]">
        @can('accounts.create')
        <a href="{{ route('accounts.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Add Account
        </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('accounts.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Account Type</label>
                <select name="account_type" class="form-select">
                    <option value="">All Types</option>
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
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name, bank or number">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Account Name</th>
                        <th>Bank</th>
                        <th>Account Number</th>
                        <th>Type</th>
                        <th class="text-end">Opening Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>{{ $loop->iteration + ($accounts->firstItem() - 1) }}</td>
                            <td>
                                <a href="{{ route('accounts.show', $account) }}" class="text-decoration-none fw-medium">{{ $account->account_name }}</a>
                            </td>
                            <td>{{ $account->bank_name }}</td>
                            <td>{{ substr($account->account_number, 0, 2) }}****{{ substr($account->account_number, -4) }}</td>
                            <td><span class="badge bg-{{ $account->account_type === 'capital' ? 'dark' : 'info' }}">{{ ucfirst($account->account_type) }}</span></td>
                            <td class="text-end">₦{{ number_format((float) $account->opening_balance, 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $account->status])</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('accounts.show', $account) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    <a href="{{ route('cashbook.show', $account) }}" class="text-success" title="Cashbook"><i class="material-symbols-outlined fs-20">menu_book</i></a>
                                    @can('accounts.update')
                                    <a href="{{ route('accounts.edit', $account) }}" class="text-primary" title="Edit"><i class="material-symbols-outlined fs-20">edit</i></a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">
                                No Accounts Found
                                <div class="mt-2">
                                    Create a Capital or Overhead account before recording transactions.
                                </div>
                                @can('accounts.create')
                                <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-primary mt-2">+ Add Account</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $accounts->links() }}
        </div>
    </div>
@endsection
