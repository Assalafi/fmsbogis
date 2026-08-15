@extends('layouts.app')

@section('title', 'Edit Receipt')

@section('content')
    <x-page-header title="Edit Receipt — {{ $receipt->treasury_receipt_voucher_number }}" :breadcrumbs="['Receipts' => route('receipts.index'), 'Edit' => null]">
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back to Register
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="POST" action="{{ route('receipts.update', $receipt) }}">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Account <span class="text-danger">*</span></label>
                    <select name="account_id" class="form-select" required>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('account_id', $receipt->account_id) === $account->id ? 'selected' : '' }}>
                                {{ $account->account_name }} ({{ ucfirst($account->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Economic Code <span class="text-danger">*</span></label>
                    <select name="economic_code_id" class="form-select" required>
                        @foreach($revenueCodes as $code)
                            <option value="{{ $code->id }}" {{ old('economic_code_id', $receipt->economic_code_id) === $code->id ? 'selected' : '' }}>
                                {{ $code->code }} — {{ $code->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                    <select name="fiscal_year_id" class="form-select" required>
                        @foreach($fiscalYears as $fy)
                            <option value="{{ $fy->id }}" {{ old('fiscal_year_id', $receipt->fiscal_year_id) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_transaction" class="form-control" value="{{ old('date_of_transaction', $receipt->date_of_transaction->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $receipt->amount) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">TREASURY RECEIPT No. <span class="text-danger">*</span></label>
                    <input type="text" name="treasury_receipt_voucher_number" class="form-control" value="{{ old('treasury_receipt_voucher_number', $receipt->treasury_receipt_voucher_number) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">FROM WHOM RECEIVED</label>
                    <input type="text" name="from_whom_received_to_whom_paid" class="form-control" value="{{ old('from_whom_received_to_whom_paid', $receipt->from_whom_received_to_whom_paid) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">BANK CREDIT SLIP No.</label>
                    <input type="text" name="bank_credit_slip_cheque_mandate_number" class="form-control" value="{{ old('bank_credit_slip_cheque_mandate_number', $receipt->bank_credit_slip_cheque_mandate_number) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">EXPENDITURE CREDITS</label>
                    <input type="text" name="expenditure_credits" class="form-control" value="{{ old('expenditure_credits', $receipt->expenditure_credits) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                    <select name="payment_method" class="form-select" required>
                        <option value="bank" {{ old('payment_method', $receipt->payment_method) === 'bank' ? 'selected' : '' }}>BANK</option>
                        <option value="cash" {{ old('payment_method', $receipt->payment_method) === 'cash' ? 'selected' : '' }}>CASH</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Details <span class="text-danger">*</span></label>
                    <textarea name="details" class="form-control" rows="3" required>{{ old('details', $receipt->details) }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Update Receipt</button>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
