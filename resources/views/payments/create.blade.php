@extends('layouts.app')

@section('title', 'Create Payment')

@section('content')
    <x-page-header title="Create Payment" :breadcrumbs="['Payments' => route('payments.index'), 'Create' => null]">
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back to Register
        </a>
    </x-page-header>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 p-4 bg-white rounded-3 mb-4">
                <h4 class="mb-4">1. Account &amp; Economic Code</h4>
                <x-validation-errors />

                <form method="POST" action="{{ route('payments.store') }}" id="payment-form">
                    @csrf
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Account <span class="text-danger">*</span></label>
                            <select name="account_id" id="account_id" class="form-select" required>
                                <option value="">Select Account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" data-account-type="{{ $account->account_type }}" {{ old('account_id') === $account->id ? 'selected' : '' }}>
                                        {{ $account->account_name }} ({{ ucfirst($account->account_type) }})
                                    </option>
                                @endforeach
                            </select>
                            <div id="account-type-badge" class="mt-2"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                            <select name="fiscal_year_id" class="form-select" required>
                                @foreach($fiscalYears as $fy)
                                    <option value="{{ $fy->id }}" {{ old('fiscal_year_id', $selectedFiscalYearId) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Economic Code <span class="text-danger">*</span></label>
                            <select name="economic_code_id" id="economic_code_id" class="form-select" required disabled>
                                <option value="">Select Account First</option>
                            </select>
                            <small class="text-muted">Only Expense codes matching the selected Account Type with an Approved Budget are shown.</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-4">2. Payment Details</h4>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_transaction" class="form-control" value="{{ old('date_of_transaction', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TREASURY VOUCHER No. <span class="text-danger">*</span></label>
                            <input type="text" name="treasury_receipt_voucher_number" class="form-control" value="{{ old('treasury_receipt_voucher_number') }}" placeholder="TV-00125" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">TO WHOM PAID</label>
                            <input type="text" name="from_whom_received_to_whom_paid" class="form-control" value="{{ old('from_whom_received_to_whom_paid') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CHEQUE/MANDATE No.</label>
                            <input type="text" name="bank_credit_slip_cheque_mandate_number" class="form-control" value="{{ old('bank_credit_slip_cheque_mandate_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DEPT. VOUCHER No.</label>
                            <input type="text" name="dept_voucher_number" class="form-control" value="{{ old('dept_voucher_number') }}">
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
                            <button type="submit" class="btn btn-primary px-4" id="submit-btn">Save Payment</button>
                            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">3. Budget Information</h4>
                <div id="budget-panel">
                    <p class="text-secondary fs-14">Select an Account and Economic Code to view the budget.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const accountSelect = document.getElementById('account_id');
    const codeSelect = document.getElementById('economic_code_id');
    const budgetPanel = document.getElementById('budget-panel');
    const amountInput = document.getElementById('amount');
    const submitBtn = document.getElementById('submit-btn');
    const badgeDiv = document.getElementById('account-type-badge');

    function loadEconomicCodes() {
        const accountId = accountSelect.value;
        codeSelect.innerHTML = '<option value="">Loading…</option>';
        codeSelect.disabled = true;
        budgetPanel.innerHTML = '<p class="text-secondary fs-14">Select an Account and Economic Code to view the budget.</p>';

        if (!accountId) {
            codeSelect.innerHTML = '<option value="">Select Account First</option>';
            return;
        }

        const selectedOption = accountSelect.selectedOptions[0];
        const badgeColor = selectedOption.dataset.accountType === 'capital' ? 'dark' : (selectedOption.dataset.accountType === 'personnel' ? 'warning' : 'info');
        badgeDiv.innerHTML = '<span class="badge bg-' + badgeColor + '">' + selectedOption.dataset.accountType.toUpperCase() + ' ACCOUNT</span>';

        fetch('{{ url('api/economic-codes/payment') }}?account_id=' + accountId, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(r => r.json())
        .then(codes => {
            codeSelect.innerHTML = '<option value="">Select Economic Code</option>';
            codes.forEach(c => {
                codeSelect.innerHTML += '<option value="' + c.id + '">' + c.code + ' — ' + c.name + '</option>';
            });
            codeSelect.disabled = false;
            if (codes.length === 0) {
                budgetPanel.innerHTML = '<div class="alert alert-warning">No Expense Economic Codes with an Approved Budget exist for this Account Type.</div>';
            }
        })
        .catch(() => {
            codeSelect.innerHTML = '<option value="">Error loading codes</option>';
        });
    }

    function loadBudget() {
        const codeId = codeSelect.value;
        if (!codeId) {
            budgetPanel.innerHTML = '<p class="text-secondary fs-14">Select an Economic Code to view the budget.</p>';
            return;
        }

        fetch('{{ url('api/economic-codes') }}/' + codeId + '/budget', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(r => r.json())
        .then(b => {
            const fmt = v => '₦' + parseFloat(v).toLocaleString('en-NG', { minimumFractionDigits: 2 });

            let html = '<table class="table table-sm table-borderless mb-0">';
            html += '<tr><td class="ps-0 fs-14 text-secondary">Original Budget</td><td class="pe-0 text-end fw-medium">' + fmt(b.original_budget) + '</td></tr>';
            html += '<tr><td class="ps-0 fs-14 text-secondary">Supplementary</td><td class="pe-0 text-end">' + fmt(b.supplementary_budget) + '</td></tr>';
            html += '<tr><td class="ps-0 fs-14 text-secondary">Virement In</td><td class="pe-0 text-end">' + fmt(b.virement_in) + '</td></tr>';
            html += '<tr><td class="ps-0 fs-14 text-secondary">Virement Out</td><td class="pe-0 text-end">' + fmt(b.virement_out) + '</td></tr>';
            html += '<tr><td class="ps-0 fs-14 text-secondary">Paid Payments</td><td class="pe-0 text-end">' + fmt(b.paid_payments) + '</td></tr>';
            html += '<tr><td class="ps-0 fs-14 text-secondary">Approved Unpaid</td><td class="pe-0 text-end">' + fmt(b.approved_unpaid) + '</td></tr>';
            html += '<tr class="border-top"><td class="ps-0 fs-14 fw-medium text-success">Available Budget</td><td class="pe-0 text-end fw-medium text-success">' + fmt(b.available_budget) + '</td></tr>';
            html += '</table>';

            const requested = parseFloat(amountInput.value || 0);
            if (requested > 0) {
                const available = parseFloat(b.available_budget);
                html += '<div class="alert mt-3 mb-0 ' + (requested <= available ? 'alert-success' : 'alert-danger') + '">';
                html += '<strong>STATUS: ' + (requested <= available ? 'ALLOWED' : 'INSUFFICIENT BUDGET') + '</strong><br>';
                html += 'Requested Payment: ' + fmt(requested) + '<br>';
                html += 'After Payment: ' + fmt(Math.max(0, available - requested));
                if (requested > available) {
                    html += '<br>Requested amount exceeds available budget by ' + fmt(requested - available);
                }
                html += '</div>';
                submitBtn.disabled = requested > available;
            }

            budgetPanel.innerHTML = html;
        });
    }

    accountSelect.addEventListener('change', function () {
        codeSelect.value = '';
        loadEconomicCodes();
    });

    codeSelect.addEventListener('change', loadBudget);
    amountInput.addEventListener('input', loadBudget);

    @if(old('account_id'))
        loadEconomicCodes();
        @if(old('economic_code_id'))
            codeSelect.value = '{{ old('economic_code_id') }}';
            loadBudget();
        @endif
    @endif
});
</script>
@endpush
