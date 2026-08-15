@extends('layouts.app')

@section('title', 'Create Receipt')

@section('content')
    <x-page-header title="Create Receipt" :breadcrumbs="['Receipts' => route('receipts.index'), 'Create' => null]">
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back to Register
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <div class="alert alert-info">
            <i class="material-symbols-outlined align-middle">info</i>
            Receipts are strictly Revenue transactions. Only Revenue Economic Codes can be used.
        </div>

        <form method="POST" action="{{ route('receipts.store') }}">
            @csrf
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Account <span class="text-danger">*</span></label>
                    <select name="account_id" class="form-select" required>
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id') === $account->id ? 'selected' : '' }}>
                                {{ $account->account_name }} ({{ ucfirst($account->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Economic Code <span class="text-danger">*</span></label>
                    <select name="economic_code_id" id="economic_code_id" class="form-select" required>
                        <option value="">Select Revenue Economic Code</option>
                        @foreach($revenueCodes as $code)
                            <option value="{{ $code->id }}" {{ old('economic_code_id') === $code->id ? 'selected' : '' }}>
                                {{ $code->code }} — {{ $code->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Revenue codes only.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                    <select name="fiscal_year_id" class="form-select" required>
                        @foreach($fiscalYears as $fy)
                            <option value="{{ $fy->id }}" {{ old('fiscal_year_id', $selectedFiscalYearId) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_transaction" class="form-control" value="{{ old('date_of_transaction', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="0.00" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">TREASURY RECEIPT No. <span class="text-danger">*</span></label>
                    <input type="text" name="treasury_receipt_voucher_number" class="form-control" value="{{ old('treasury_receipt_voucher_number') }}" placeholder="TR-00010" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">FROM WHOM RECEIVED</label>
                    <input type="text" name="from_whom_received_to_whom_paid" class="form-control" value="{{ old('from_whom_received_to_whom_paid') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">BANK CREDIT SLIP No.</label>
                    <input type="text" name="bank_credit_slip_cheque_mandate_number" class="form-control" value="{{ old('bank_credit_slip_cheque_mandate_number') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">EXPENDITURE CREDITS</label>
                    <input type="text" name="expenditure_credits" class="form-control" value="{{ old('expenditure_credits') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="">Select Method</option>
                        <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>BANK</option>
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>CASH</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Details <span class="text-danger">*</span></label>
                    <textarea name="details" class="form-control" rows="3" required>{{ old('details') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Save Receipt</button>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
