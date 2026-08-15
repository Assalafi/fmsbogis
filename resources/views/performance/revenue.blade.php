@extends('layouts.app')

@section('title', 'Revenue Performance')

@section('content')
    <x-page-header title="Revenue Performance" :breadcrumbs="['Performance' => null, 'Revenue' => null]" />

    <div class="row">
        <x-stat-card label="TOTAL REVENUE" value="₦{{ number_format((float) $totalRevenue, 2) }}" icon="trending_up" color="success" />
        <x-stat-card label="REVENUE CODES" value="{{ $codes->count() }}" icon="123" color="primary" />
        <x-stat-card label="FISCAL YEAR" value="FY {{ $fiscalYear?->name ?? '—' }}" icon="event_note" color="info" />
        <x-stat-card label="TOTAL RECEIPTS" value="{{ $codes->sum('receipts_count') }}" icon="receipt_long" color="secondary" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <h4 class="mb-4">Revenue by Economic Code</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Economic Code</th>
                        <th>Description</th>
                        <th class="text-end">Total Receipts</th>
                        <th class="text-end">Number of Receipts</th>
                        <th class="text-end">% of Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $code)
                        <tr>
                            <td class="fw-medium">{{ $code->code }}</td>
                            <td>{{ $code->name }}</td>
                            <td class="text-end text-success">₦{{ number_format((float) ($code->receipts_total ?? 0), 2) }}</td>
                            <td class="text-end">{{ $code->receipts_count }}</td>
                            <td class="text-end">
                                @php
                                    $pct = $totalRevenue > 0 ? round((float) $code->receipts_total / (float) $totalRevenue * 100, 2) : 0;
                                @endphp
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <span class="fs-14">{{ $pct }}%</span>
                                    <div class="progress" style="width: 80px; height: 6px;">
                                        <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No revenue economic codes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
