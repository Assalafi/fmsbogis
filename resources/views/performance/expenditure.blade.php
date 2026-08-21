@extends('layouts.app')

@section('title', 'Expenditure Performance')

@section('content')
    <x-page-header title="Expenditure Performance" :breadcrumbs="['Performance' => null, 'Expenditure' => null]" />

    @php
        $totalOriginal = $codes->sum(function ($c) { return (float) $c['original']; });
        $totalRevised = $codes->sum(function ($c) { return (float) $c['revised']; });
        $totalPaid = $codes->sum(function ($c) { return (float) $c['paid']; });
        $totalAvailable = $codes->sum(function ($c) { return (float) $c['available']; });
        $utilization = $totalRevised > 0 ? round($totalPaid / $totalRevised * 100, 2) : 0;
    @endphp

    <div class="row">
        <x-stat-card label="ORIGINAL BUDGET" value="₦{{ number_format($totalOriginal, 2) }}" icon="account_balance_wallet" color="secondary" />
        <x-stat-card label="REVISED BUDGET" value="₦{{ number_format($totalRevised, 2) }}" icon="tune" color="primary" />
        <x-stat-card label="TOTAL PAID" value="₦{{ number_format($totalPaid, 2) }}" icon="north_east" color="danger" />
        <x-stat-card label="UTILIZATION" value="{{ $utilization }}%" icon="percent" color="success" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('performance.expenditure') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Account Type</label>
                <select name="account_type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="capital" {{ request('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                    <option value="overhead" {{ request('account_type') === 'overhead' ? 'selected' : '' }}>Overhead</option>
                <option value="personnel" {{ request('account_type') === 'personnel' ? 'selected' : '' }}>Personnel</option>
                </select>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Account Type</th>
                        <th class="text-end">Original</th>
                        <th class="text-end">Revised</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Approved Unpaid</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Utilization</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['code']->code }}</td>
                            <td>{{ $row['code']->name }}</td>
                            <td><span class="badge bg-{{ \App\Support\AccountTypes::badgeColor($row['code']->account_type) }}">{{ ucfirst($row['code']->account_type) }}</span></td>
                            <td class="text-end">₦{{ number_format((float) $row['original'], 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['revised'], 2) }}</td>
                            <td class="text-end text-danger">₦{{ number_format((float) $row['paid'], 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['approved_unpaid'], 2) }}</td>
                            <td class="text-end text-success">₦{{ number_format((float) $row['available'], 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <span class="fs-14">{{ $row['utilization'] }}%</span>
                                    <div class="progress" style="width: 80px; height: 6px;">
                                        <div class="progress-bar bg-{{ $row['utilization'] >= 100 ? 'danger' : ($row['utilization'] > 60 ? 'warning' : 'success') }}" style="width: {{ min($row['utilization'], 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No expense economic codes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
