@extends('layouts.app')

@section('title', 'Edit Account')

@section('content')
    <x-page-header title="Edit Account — {{ $account->account_name }}" :breadcrumbs="['Accounts' => route('accounts.index'), 'Edit' => null]">
        <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="POST" action="{{ route('accounts.update', $account) }}">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label">Account Name <span class="text-danger">*</span></label>
                    <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $account->account_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $account->bank_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $account->account_number) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select" required>
                        <option value="capital" {{ old('account_type', $account->account_type) === 'capital' ? 'selected' : '' }}>Capital</option>
                        <option value="overhead" {{ old('account_type', $account->account_type) === 'overhead' ? 'selected' : '' }}>Overhead</option>
                <option value="personnel" {{ old('account_type', $account->account_type) === 'personnel' ? 'selected' : '' }}>Personnel</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opening Balance <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', $account->opening_balance) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', $account->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $account->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Update Account</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
