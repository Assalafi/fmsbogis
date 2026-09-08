@extends('layouts.app')

@section('title', 'Virements')

@section('content')
    <x-page-header title="Approved Virements" :breadcrumbs="['Budgets' => route('budgets.index'), 'Virements' => null]" />

    <div class="alert alert-primary d-flex gap-2 align-items-start">
        <span class="material-symbols-outlined">verified</span>
        <div>Only approved eBudget virements involving BOGIS are shown. Incoming and outgoing amounts are automatically reflected in the approved budget balances.</div>
    </div>

    @include('budgets.partials.sync-status')

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('virements.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ (string) request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === (string) $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fs-14">Direction</label>
                <select name="direction" class="form-select">
                    <option value="">All directions</option>
                    <option value="in" {{ request('direction') === 'in' ? 'selected' : '' }}>Incoming to BOGIS</option>
                    <option value="out" {{ request('direction') === 'out' ? 'selected' : '' }}>Outgoing from BOGIS</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('virements.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date / Reference</th>
                        <th>Direction</th>
                        <th>From</th>
                        <th>To</th>
                        <th class="text-end">Amount</th>
                        <th>Approval</th>
                        <th>Reason</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($virements as $virement)
                        @php
                            $incoming = $virement->to_mda_code === config('services.ebudget.mda_code');
                        @endphp
                        <tr>
                            <td>
                                <div>{{ $virement->date->format('d M Y') }}</div>
                                <div class="fs-12 text-secondary">{{ $virement->reference_number }}</div>
                            </td>
                            <td><span class="badge bg-{{ $incoming ? 'success' : 'danger' }}">{{ $incoming ? 'Incoming' : 'Outgoing' }}</span></td>
                            <td>
                                <div class="fw-medium">{{ $virement->fromEconomicCode->code }}</div>
                                <div class="fs-12 text-secondary">{{ $virement->from_mda_name }}</div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $virement->toEconomicCode->code }}</div>
                                <div class="fs-12 text-secondary">{{ $virement->to_mda_name }}</div>
                            </td>
                            <td class="text-end fw-semibold">₦{{ number_format((float) $virement->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-success">Approved</span>
                                @if($virement->approval_type)<div class="fs-12 text-secondary mt-1">{{ $virement->approval_type }}</div>@endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($virement->reason, 45) }}</td>
                            <td><a href="{{ route('virements.show', $virement) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-5">
                                <span class="material-symbols-outlined d-block fs-1 mb-2">swap_horiz</span>
                                No approved eBudget virements were found for this fiscal year.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">{{ $virements->links() }}</div>
    </div>
@endsection
