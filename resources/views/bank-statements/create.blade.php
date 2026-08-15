@extends('layouts.app')

@section('title', 'Upload Bank Statement')

@section('content')
    <x-page-header title="Upload Bank Statement" :breadcrumbs="['Bank Statements' => route('bank-statements.index'), 'Upload' => null]">
        <a href="{{ route('bank-statements.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="POST" action="{{ route('bank-statements.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Account <span class="text-danger">*</span></label>
                    <select name="account_id" class="form-select" required>
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') === $account->id ? 'selected' : '' }}>{{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statement From <span class="text-danger">*</span></label>
                    <input type="date" name="statement_from" class="form-control" value="{{ old('statement_from') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statement To <span class="text-danger">*</span></label>
                    <input type="date" name="statement_to" class="form-control" value="{{ old('statement_to') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Opening Balance (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Closing Balance (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="closing_balance" class="form-control" value="{{ old('closing_balance', 0) }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">File Upload (CSV / XLSX)</label>
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls">
                    <small class="text-muted">Expected columns: Date, Reference, Description, Debit, Credit, Balance. If no file is uploaded, the statement is created for manual entry.</small>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Save Statement</button>
                    <a href="{{ route('bank-statements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
