@extends('layouts.app')

@section('title', 'Account Performance')

@section('content')
    <x-page-header title="Account Performance" :breadcrumbs="['Performance' => null, 'Accounts' => null]">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-secondary" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">picture_as_pdf</i>
            PDF
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Type</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Receipts</th>
                        <th class="text-end">Payments</th>
                        <th class="text-end">Cashbook Balance</th>
                        <th class="text-end">Bank Balance</th>
                        <th class="text-end">Difference</th>
                        <th>Last Reconciled</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $row)
                        @php
                            $difference = (float) $row['cashbook_balance'] - (float) $row['bank_balance'];
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('accounts.show', $row['account']) }}" class="text-decoration-none fw-medium">{{ $row['account']->account_name }}</a>
                            </td>
                            <td><span class="badge bg-{{ \App\Support\AccountTypes::badgeColor($row['account']->account_type) }}">{{ ucfirst($row['account']->account_type) }}</span></td>
                            <td class="text-end">₦{{ number_format((float) $row['opening'], 2) }}</td>
                            <td class="text-end text-success">₦{{ number_format((float) $row['receipts'], 2) }}</td>
                            <td class="text-end text-danger">₦{{ number_format((float) $row['payments'], 2) }}</td>
                            <td class="text-end fw-medium">₦{{ number_format((float) $row['cashbook_balance'], 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['bank_balance'], 2) }}</td>
                            <td class="text-end {{ $difference == 0 ? 'text-success' : 'text-danger' }} fw-medium">₦{{ number_format($difference, 2) }}</td>
                            <td>{{ $row['last_reconciled']?->reconciliation_date->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
