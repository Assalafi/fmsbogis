@extends('layouts.app')

@section('title', 'Virement — '.$virement->reference_number)

@section('content')
    @php
        $incoming = $virement->to_mda_code === config('services.ebudget.mda_code');
    @endphp

    <x-page-header title="Virement — {{ $virement->reference_number }}" :breadcrumbs="['Approved Virements' => route('virements.index'), $virement->reference_number => null]" />

    <div class="row">
        <x-stat-card label="AMOUNT" value="₦{{ number_format((float) $virement->amount, 2) }}" icon="swap_horiz" color="primary" />
        <x-stat-card label="DIRECTION" value="{{ $incoming ? 'Incoming' : 'Outgoing' }}" icon="{{ $incoming ? 'south_east' : 'north_east' }}" color="{{ $incoming ? 'success' : 'danger' }}" />
        <x-stat-card label="FISCAL YEAR" value="FY {{ $virement->fiscalYear->name }}" icon="calendar_month" color="secondary" />
        <x-stat-card label="STATUS" value="Approved" icon="verified" color="success" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h4 class="mb-0">Virement Details</h4>
            <span class="badge bg-primary">Synchronised from eBudget</span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr><th class="ps-0 fs-14 text-secondary" style="width: 30%;">Reference</th><td class="pe-0">{{ $virement->reference_number }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Date</th><td class="pe-0">{{ $virement->date->format('d M Y') }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Fiscal Year</th><td class="pe-0">FY {{ $virement->fiscalYear->name }}</td></tr>
                    <tr>
                        <th class="ps-0 fs-14 text-secondary">From MDA</th>
                        <td class="pe-0">{{ $virement->from_mda_code }} — {{ $virement->from_mda_name }}</td>
                    </tr>
                    <tr>
                        <th class="ps-0 fs-14 text-secondary">From Economic Code</th>
                        <td class="pe-0">{{ $virement->fromEconomicCode->code }} — {{ $virement->fromEconomicCode->name }} ({{ ucfirst($virement->fromEconomicCode->account_type) }})</td>
                    </tr>
                    <tr>
                        <th class="ps-0 fs-14 text-secondary">To MDA</th>
                        <td class="pe-0">{{ $virement->to_mda_code }} — {{ $virement->to_mda_name }}</td>
                    </tr>
                    <tr>
                        <th class="ps-0 fs-14 text-secondary">To Economic Code</th>
                        <td class="pe-0">{{ $virement->toEconomicCode->code }} — {{ $virement->toEconomicCode->name }} ({{ ucfirst($virement->toEconomicCode->account_type) }})</td>
                    </tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Approval Level</th><td class="pe-0">{{ $virement->approval_type ?: 'Approved' }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Reason / Remark</th><td class="pe-0">{{ $virement->reason }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">eBudget Last Updated</th><td class="pe-0">{{ $virement->source_updated_at?->format('d M Y, H:i') ?? 'Not supplied' }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Last Synced to BOGIS</th><td class="pe-0">{{ $virement->source_synced_at?->format('d M Y, H:i') ?? 'Not yet synced' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
