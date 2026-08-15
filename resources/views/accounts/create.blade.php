@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
    <x-page-header title="Create Account" :breadcrumbs="['Accounts' => route('accounts.index'), 'Create' => null]">
        <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="POST" action="{{ route('accounts.store') }}">
            @csrf
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label">Account Name <span class="text-danger">*</span></label>
                    <input type="text" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name') }}" placeholder="BOGIS Overhead Account" required>
                    @error('account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" value="{{ old('bank_name') }}" placeholder="Zenith Bank" required>
                    @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" name="account_number" class="form-control @error('account_number') is-invalid @enderror" value="{{ old('account_number') }}" placeholder="1012345678" required>
                    @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        <option value="capital" {{ old('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                        <option value="overhead" {{ old('account_type') === 'overhead' ? 'selected' : '' }}>Overhead</option>
                    </select>
                    @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Capital Payments can only use Capital Accounts. Overhead Payments can only use Overhead Accounts.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opening Balance <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}" required>
                    @error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Save Account</button>
                    <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
