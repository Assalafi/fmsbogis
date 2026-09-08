@extends('layouts.app')

@section('title', 'Approved Budget Clearances')

@section('content')
    <x-page-header title="Approved Budget Clearances" :breadcrumbs="['Budgets' => route('budgets.index'), 'Budget Clearances' => null]" />

    <div class="alert alert-primary d-flex gap-2 align-items-start">
        <span class="material-symbols-outlined">verified</span>
        <div>
            Only approved BOGIS clearances from eBudget are displayed here. Pending, returned, and rejected requests are never imported.
            These are read-only authorization records; BOGIS payment activity remains the source for local expenditure deductions.
        </div>
    </div>

    @include('budgets.partials.sync-status')

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 p-4 bg-white rounded-3 h-100">
                <div class="fs-14 text-secondary">Approved Clearances</div>
                <div class="fs-3 fw-semibold">{{ number_format($clearances->total()) }}</div>
                <div class="fs-12 text-secondary">Matching the current filters</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 p-4 bg-white rounded-3 h-100">
                <div class="fs-14 text-secondary">Approved Amount</div>
                <div class="fs-3 fw-semibold text-success">&#8358;{{ number_format((float) $totalApproved, 2) }}</div>
                <div class="fs-12 text-secondary">Matching the current filters</div>
            </div>
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('budget-clearances.index') }}" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ (string) request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === (string) $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label fs-14">Approval Type</label>
                <select name="approval_type" class="form-select">
                    <option value="">All approval types</option>
                    @foreach($approvalTypes as $type)
                        <option value="{{ $type }}" {{ request('approval_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4 col-md-8">
                <label class="form-label fs-14">Search</label>
                <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reference, payee, purpose, or economic code">
            </div>
            <div class="col-lg-2 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('budget-clearances.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Approved / Reference</th>
                        <th>Economic Code</th>
                        <th>Payee</th>
                        <th>Purpose</th>
                        <th>Approval</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance After</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clearances as $clearance)
                        <tr>
                            <td>
                                <div>{{ $clearance->approved_at?->format('d M Y') ?? 'Date not supplied' }}</div>
                                <div class="fs-12 text-secondary">EBUDGET-CLR-{{ $clearance->source_id }}</div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $clearance->economicCode->code }}</div>
                                <div class="fs-12 text-secondary">{{ \Illuminate\Support\Str::limit($clearance->economicCode->name, 36) }}</div>
                            </td>
                            <td>{{ $clearance->payee_name ?: 'Not supplied' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($clearance->purpose, 55) }}</td>
                            <td>
                                <span class="badge bg-success">Approved</span>
                                @if($clearance->approval_type)<div class="fs-12 text-secondary mt-1">{{ $clearance->approval_type }}</div>@endif
                            </td>
                            <td class="text-end fw-semibold">&#8358;{{ number_format((float) $clearance->amount, 2) }}</td>
                            <td class="text-end">&#8358;{{ number_format((float) $clearance->balance_after, 2) }}</td>
                            <td>
                                <a href="{{ route('budget-clearances.show', $clearance) }}" class="text-info" title="View clearance">
                                    <i class="material-symbols-outlined fs-20">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-5">
                                <span class="material-symbols-outlined d-block fs-1 mb-2">approval</span>
                                No approved eBudget clearances were found for this fiscal year.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">{{ $clearances->links() }}</div>
    </div>
@endsection
