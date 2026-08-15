@extends('layouts.app')

@section('title', 'Payment Register Report')

@section('content')
    <x-page-header title="Payment Register Report" :breadcrumbs="['Reports' => route('reports.index'), 'Payment Register' => null]">
        <a href="{{ route('reports.show', ['report' => 'payment-register', 'export' => 'pdf', 'fiscal_year_id' => request('fiscal_year_id'), 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}" class="btn btn-secondary" target="_blank">PDF</a>
        <a href="{{ route('reports.show', ['report' => 'payment-register', 'export' => 'excel', 'fiscal_year_id' => request('fiscal_year_id'), 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}" class="btn btn-success">Excel</a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('reports.show', 'payment-register') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach(\App\Models\FiscalYear::orderBy('start_date', 'desc')->get() as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
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
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
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
                        <th>Dept Voucher</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->date_of_transaction->format('d M Y') }}</td>
                            <td>{{ $payment->treasury_receipt_voucher_number }}</td>
                            <td>{{ $payment->account->account_name }}</td>
                            <td>{{ $payment->economicCode->code }}</td>
                            <td>{{ $payment->from_whom_received_to_whom_paid ?? '—' }}</td>
                            <td>{{ $payment->bank_credit_slip_cheque_mandate_number ?? '—' }}</td>
                            <td>{{ $payment->dept_voucher_number ?? '—' }}</td>
                            <td class="text-end">₦{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $payment->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No payments found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="7">Total</td>
                        <td class="text-end">₦{{ number_format((float) $total, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
