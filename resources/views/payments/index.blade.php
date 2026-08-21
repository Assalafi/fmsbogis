@extends('layouts.app')

@section('title', 'Payment Register')

@section('content')
    <x-page-header title="Payment Register" :breadcrumbs="['Payments' => null]">
        @can('payments.create')
        <a href="{{ route('payments.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Create Payment
        </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('payments.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Account</label>
                <select name="account_id" class="form-select">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ request('account_id') === $account->id ? 'selected' : '' }}>{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Account Type</label>
                <select name="account_type" class="form-select">
                    <option value="">All</option>
                    <option value="capital" {{ request('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                    <option value="overhead" {{ request('account_type') === 'overhead' ? 'selected' : '' }}>Overhead</option>
                <option value="personnel" {{ request('account_type') === 'personnel' ? 'selected' : '' }}>Personnel</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Economic Code</label>
                <select name="economic_code_id" class="form-select">
                    <option value="">All Codes</option>
                    @foreach($expenseCodes as $code)
                        <option value="{{ $code->id }}" {{ request('economic_code_id') === $code->id ? 'selected' : '' }}>{{ $code->code }} — {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['draft', 'pending', 'approved', 'paid', 'rejected', 'reversed'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Treasury Voucher No.</th>
                        <th>Account</th>
                        <th>Economic Code</th>
                        <th>To Whom Paid</th>
                        <th>Cheque/Mandate</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->date_of_transaction->format('d M Y') }}</td>
                            <td class="fw-medium">{{ $payment->treasury_receipt_voucher_number }}</td>
                            <td>{{ $payment->account->account_name }}</td>
                            <td>{{ $payment->economicCode->code }}</td>
                            <td>{{ $payment->from_whom_received_to_whom_paid ?? '—' }}</td>
                            <td>{{ $payment->bank_credit_slip_cheque_mandate_number ?? '—' }}</td>
                            <td class="text-end fw-medium text-danger">₦{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $payment->status])</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('payments.show', $payment) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    @can('payments.create')
                                    @if(in_array($payment->status, ['draft', 'pending']))
                                    <a href="{{ route('payments.edit', $payment) }}" class="text-primary" title="Edit"><i class="material-symbols-outlined fs-20">edit</i></a>
                                    @endif
                                    @endcan
                                    <a href="{{ route('payments.print', $payment) }}" class="text-secondary" title="Print" target="_blank"><i class="material-symbols-outlined fs-20">print</i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4">
                                No payments found.
                                @can('payments.create')
                                <div class="mt-2">
                                    <a href="{{ route('payments.create') }}" class="btn btn-sm btn-primary">+ Create Payment</a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $payments->links() }}
        </div>
    </div>
@endsection
