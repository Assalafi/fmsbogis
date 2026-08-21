@extends('layouts.app')

@section('title', 'Account Statement Report')

@section('content')
    <x-page-header title="Account Statement Report" :breadcrumbs="['Reports' => route('reports.index'), 'Account Statement' => null]">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-secondary" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">picture_as_pdf</i>
            PDF
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('reports.show', 'account-statement') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach(\App\Models\FiscalYear::orderBy('start_date', 'desc')->get() as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
            </div>
        </form>
    </div>

    <div class="row">
        <x-stat-card label="TOTAL OPENING" value="₦{{ number_format((float) $totalOpening, 2) }}" icon="play_circle" color="secondary" />
        <x-stat-card label="TOTAL RECEIPTS" value="₦{{ number_format((float) $totalReceipts, 2) }}" icon="south_west" color="success" />
        <x-stat-card label="TOTAL PAYMENTS" value="₦{{ number_format((float) $totalPayments, 2) }}" icon="north_east" color="danger" />
        <x-stat-card label="TOTAL CLOSING" value="₦{{ number_format((float) $totalClosing, 2) }}" icon="account_balance" color="primary" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Type</th>
                        <th>Bank</th>
                        <th>Number</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Receipts</th>
                        <th class="text-end">Payments</th>
                        <th class="text-end">Closing Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['account']->account_name }}</td>
                            <td>{{ ucfirst($row['account']->account_type) }}</td>
                            <td>{{ $row['account']->bank_name }}</td>
                            <td>{{ $row['account']->account_number }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['opening'], 2) }}</td>
                            <td class="text-end text-success">₦{{ number_format((float) $row['receipts'], 2) }}</td>
                            <td class="text-end text-danger">₦{{ number_format((float) $row['payments'], 2) }}</td>
                            <td class="text-end fw-medium">₦{{ number_format((float) $row['closing'], 2) }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('cashbook.show', $row['account']) }}" class="text-primary" title="Cashbook"><i class="material-symbols-outlined fs-20">menu_book</i></a>
                                    <a href="{{ route('cashbook.print', $row['account']) }}" class="text-secondary" title="Statement PDF" target="_blank"><i class="material-symbols-outlined fs-20">picture_as_pdf</i></a>
                                    <a href="{{ route('cashbook.excel', $row['account']) }}" class="text-success" title="Statement Excel"><i class="material-symbols-outlined fs-20">download</i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No accounts found.</td></tr>
                    @endforelse
                </tbody>
                @if($accounts->isNotEmpty())
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="4">TOTAL</td>
                        <td class="text-end">₦{{ number_format((float) $totalOpening, 2) }}</td>
                        <td class="text-end text-success">₦{{ number_format((float) $totalReceipts, 2) }}</td>
                        <td class="text-end text-danger">₦{{ number_format((float) $totalPayments, 2) }}</td>
                        <td class="text-end">₦{{ number_format((float) $totalClosing, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
