@extends('layouts.app')

@section('title', 'Virement — '.$virement->reference_number)

@section('content')
    <x-page-header title="Virement — {{ $virement->reference_number }}" :breadcrumbs="['Virements' => route('virements.index'), $virement->reference_number => null]">
        @if($virement->status === 'pending' && auth()->user()->can('virements.approve'))
        <form method="POST" action="{{ route('virements.approve', $virement) }}">
            @csrf
            <button type="submit" class="btn btn-success" onclick="return confirm('Approve this virement? Budgets will be updated.');">Approve</button>
        </form>
        <form method="POST" action="{{ route('virements.reject', $virement) }}">
            @csrf
            <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this virement?');">Reject</button>
        </form>
        @endif
    </x-page-header>

    <div class="row">
        <x-stat-card label="AMOUNT" value="₦{{ number_format((float) $virement->amount, 2) }}" icon="swap_horiz" color="primary" />
        <x-stat-card label="SOURCE AVAILABLE" value="₦{{ number_format((float) $sourceAvailable, 2) }}" icon="savings" color="warning" />
        <x-stat-card label="FROM" value="{{ $virement->fromEconomicCode->code }}" icon="north_west" color="danger" />
        <x-stat-card label="TO" value="{{ $virement->toEconomicCode->code }}" icon="south_east" color="success" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <h4 class="mb-4">Virement Details</h4>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr><th class="ps-0 fs-14 text-secondary" style="width: 30%;">Reference</th><td class="pe-0">{{ $virement->reference_number }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Date</th><td class="pe-0">{{ $virement->date->format('d M Y') }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Fiscal Year</th><td class="pe-0">FY {{ $virement->fiscalYear->name }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">From</th><td class="pe-0">{{ $virement->fromEconomicCode->code }} — {{ $virement->fromEconomicCode->name }} ({{ ucfirst($virement->fromEconomicCode->account_type) }})</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">To</th><td class="pe-0">{{ $virement->toEconomicCode->code }} — {{ $virement->toEconomicCode->name }} ({{ ucfirst($virement->toEconomicCode->account_type) }})</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Reason</th><td class="pe-0">{{ $virement->reason }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Status</th><td class="pe-0">@include('components.status-badge', ['status' => $virement->status])</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Created By</th><td class="pe-0">{{ $virement->creator?->name ?? '—' }} ({{ $virement->created_at->format('d M Y H:i') }})</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Approved By</th><td class="pe-0">{{ $virement->approver?->name ?? '—' }} @if($virement->approved_at)<span class="fs-13 text-secondary">({{ $virement->approved_at->format('d M Y H:i') }})</span>@endif</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
