@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if(! $fiscalYear)
        <div class="alert alert-warning">
            <i class="material-symbols-outlined align-middle">warning</i>
            No Fiscal Year exists yet. Create a Fiscal Year to start recording transactions.
        </div>
    @else
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Finance Dashboard</h3>
                <span class="fs-14 text-secondary">Overview of budget, revenue, expenditure, accounts and reconciliation — FY {{ $fiscalYear->name }}</span>
            </div>
        </div>

        <div class="row">
            <x-stat-card label="APPROVED BUDGET" value="₦{{ number_format((float) $totals['original_budget'], 2) }}" icon="account_balance_wallet" color="primary" />
            <x-stat-card label="TOTAL RECEIPTS" value="₦{{ number_format((float) $totals['total_receipts'], 2) }}" icon="south_west" color="success" />
            <x-stat-card label="TOTAL PAYMENTS" value="₦{{ number_format((float) $totals['total_payments'], 2) }}" icon="north_east" color="danger" />
            <x-stat-card label="AVAILABLE BUDGET" value="₦{{ number_format((float) $totals['available_budget'], 2) }}" icon="savings" color="warning" />
        </div>

        <div class="row">
            <x-stat-card label="CASHBOOK BALANCE" value="₦{{ number_format((float) $totals['cashbook_balance'], 2) }}" icon="account_balance" color="success" />
            <x-stat-card label="CAPITAL PAYMENTS" value="₦{{ number_format((float) $totals['capital_payments'], 2) }}" icon="apartment" color="dark" />
            <x-stat-card label="OVERHEAD PAYMENTS" value="₦{{ number_format((float) $totals['overhead_payments'], 2) }}" icon="storefront" color="secondary" />
            <x-stat-card label="PERSONNEL PAYMENTS" value="₦{{ number_format((float) $totals['personnel_payments'], 2) }}" icon="badge" color="warning" />
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card border-0 p-4 bg-white rounded-3 h-100">
                    <h4 class="mb-4">Monthly Receipts vs Payments</h4>
                    <div id="monthly-chart" style="min-height: 320px;"></div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card border-0 p-4 bg-white rounded-3 h-100">
                    <h4 class="mb-4">Pending Actions</h4>
                    <ul class="list-unstyled ps-0 mb-0">
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <a href="{{ route('budgets.pending') }}" class="text-decoration-none text-body">Pending Budgets</a>
                            <span class="badge bg-warning rounded-pill">{{ $pending['budgets'] }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <a href="{{ route('virements.index', ['status' => 'pending']) }}" class="text-decoration-none text-body">Pending Virements</a>
                            <span class="badge bg-warning rounded-pill">{{ $pending['virements'] }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <a href="{{ route('receipts.index', ['status' => 'pending']) }}" class="text-decoration-none text-body">Pending Receipts</a>
                            <span class="badge bg-warning rounded-pill">{{ $pending['receipts'] }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <a href="{{ route('payments.approval') }}" class="text-decoration-none text-body">Pending Payments</a>
                            <span class="badge bg-warning rounded-pill">{{ $pending['payments'] }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2">
                            <a href="{{ route('reconciliations.index', ['status' => 'draft']) }}" class="text-decoration-none text-body">Draft Reconciliations</a>
                            <span class="badge bg-info rounded-pill">{{ $pending['reconciliations'] }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card border-0 p-4 bg-white rounded-3 mb-4">
            <h4 class="mb-4">Reconciliation Status</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Last Reconciled</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reconciliationStatus as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('cashbook.show', $row['account']) }}" class="text-decoration-none">{{ $row['account']->account_name }}</a>
                                </td>
                                <td><span class="badge bg-secondary">{{ ucfirst($row['account']->account_type) }}</span></td>
                                <td>{{ $row['last_reconciled']?->reconciliation_date->format('d M Y') ?? '—' }}</td>
                                <td>@include('components.status-badge', ['status' => $row['status']])</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">
                                    No Accounts Found — Create a Capital or Overhead account before recording transactions.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthly = @json($monthly);

    if (document.getElementById('monthly-chart') && monthly.length) {
        const options = {
            series: [
                { name: 'Receipts', data: monthly.map(m => m.receipts) },
                { name: 'Payments', data: monthly.map(m => m.payments) },
            ],
            chart: { type: 'bar', height: 320, toolbar: { show: false } },
            colors: ['#00c48c', '#ee368c'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
            dataLabels: { enabled: false },
            legend: { position: 'top', horizontalAlign: 'right' },
            xaxis: { categories: monthly.map(m => m.label) },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return '₦' + (val / 1000000).toFixed(0) + 'M';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return '₦' + val.toLocaleString('en-NG', { minimumFractionDigits: 2 });
                    }
                }
            },
        };

        const chart = new ApexCharts(document.querySelector('#monthly-chart'), options);
        chart.render();
    }
});
</script>
@endpush
