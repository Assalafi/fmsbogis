@extends('layouts.app')

@section('title', 'Approved Budgets')

@section('content')
    <x-page-header title="Approved Budgets" :breadcrumbs="['Budgets' => null]" />

    <div class="alert alert-primary d-flex gap-2 align-items-start">
        <span class="material-symbols-outlined">verified</span>
        <div>
            <strong>eBudget is the source of truth.</strong>
            Approved allocations and virements are synchronised securely from the State eBudget system. BOGIS payment commitments and paid amounts are then applied locally to calculate the usable balance.
        </div>
    </div>

    @include('budgets.partials.sync-status')

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('budgets.index') }}" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-4">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ (string) request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === (string) $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-4">
                <label class="form-label fs-14">Account Type</label>
                <select name="account_type" class="form-select">
                    <option value="">All account types</option>
                    <option value="capital" {{ request('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                    <option value="overhead" {{ request('account_type') === 'overhead' ? 'selected' : '' }}>Overhead / Nutrition</option>
                    <option value="personnel" {{ request('account_type') === 'personnel' ? 'selected' : '' }}>Personnel</option>
                </select>
            </div>
            <div class="col-lg-4 col-md-4">
                <label class="form-label fs-14">Economic Code or Name</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search approved budgets">
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Economic Code</th>
                        <th>Type</th>
                        <th class="text-end">Approved</th>
                        <th class="text-end">Virement In</th>
                        <th class="text-end">Virement Out</th>
                        <th class="text-end">Revised</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Committed</th>
                        <th class="text-end">BOGIS Available</th>
                        <th>Source</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $budget)
                        @php
                            $paid = $budgetService->paidPayments($budget->economicCode, $budget->fiscalYear);
                            $committed = $budgetService->approvedUnpaidPayments($budget->economicCode, $budget->fiscalYear);
                            $available = $budgetService->availableBudget($budget->economicCode, $budget->fiscalYear);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('budgets.show', $budget) }}" class="text-decoration-none fw-medium">{{ $budget->economicCode->code }}</a>
                                <div class="fs-13 text-secondary">{{ $budget->economicCode->name }}</div>
                            </td>
                            <td><span class="badge bg-{{ \App\Support\AccountTypes::badgeColor($budget->economicCode->account_type) }}">{{ ucfirst($budget->economicCode->account_type) }}</span></td>
                            <td class="text-end">₦{{ number_format((float) $budget->original_budget, 2) }}</td>
                            <td class="text-end text-success">₦{{ number_format((float) $budget->virement_in, 2) }}</td>
                            <td class="text-end text-danger">₦{{ number_format((float) $budget->virement_out, 2) }}</td>
                            <td class="text-end fw-medium">₦{{ number_format((float) $budget->revised_budget, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $paid, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $committed, 2) }}</td>
                            <td class="text-end text-success fw-semibold">₦{{ number_format((float) $available, 2) }}</td>
                            <td>
                                <span class="badge bg-primary">eBudget</span>
                                @if($budget->source_synced_at)
                                    <div class="fs-12 text-secondary mt-1">{{ $budget->source_synced_at->diffForHumans() }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-secondary py-5">
                                <span class="material-symbols-outlined d-block fs-1 mb-2">cloud_off</span>
                                No approved eBudget records are available for this fiscal year.
                                @can('budgets.sync')
                                    <div class="mt-1">Use the sync button above to retrieve them.</div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $budgets->links() }}
        </div>
    </div>
@endsection
