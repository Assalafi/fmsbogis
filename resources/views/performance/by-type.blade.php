@extends('layouts.app')

@section('title', ucfirst($accountType).' Performance')

@section('content')
    <x-page-header title="{{ ucfirst($accountType) }} Performance" :breadcrumbs="['Performance' => null, ucfirst($accountType) => null]">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-secondary" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">picture_as_pdf</i>
            PDF
        </a>
    </x-page-header>

    @php
        $totalBudget = $codes->sum(function ($c) { return (float) $c['total']; });
        $totalPaid = $codes->sum(function ($c) { return (float) $c['paid']; });
        $totalAvailable = $codes->sum(function ($c) { return (float) $c['available']; });
        $utilization = $totalBudget > 0 ? round($totalPaid / $totalBudget * 100, 2) : 0;
    @endphp

    <div class="row">
        <x-stat-card label="{{ strtoupper($accountType) }} PAYMENTS" value="₦{{ number_format($totalPaid, 2) }}" icon="north_east" color="danger" />
        <x-stat-card label="{{ strtoupper($accountType) }} AVAILABLE" value="₦{{ number_format($totalAvailable, 2) }}" icon="savings" color="success" />
        <x-stat-card label="{{ strtoupper($accountType) }} UTILIZATION" value="{{ $utilization }}%" icon="percent" color="warning" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Economic Code</th>
                        <th>Description</th>
                        <th class="text-end">Actual</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Utilization</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['code']->code }}</td>
                            <td>{{ $row['code']->name }}</td>
                            <td class="text-end text-danger">₦{{ number_format((float) $row['paid'], 2) }}</td>
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
                        <tr><td colspan="5" class="text-center text-secondary py-4">No {{ $accountType }} expense codes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
