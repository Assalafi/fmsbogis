@extends('layouts.app')

@section('title', 'Budget Clearance — EBUDGET-CLR-'.$budgetClearance->source_id)

@section('content')
    <x-page-header title="Budget Clearance — EBUDGET-CLR-{{ $budgetClearance->source_id }}" :breadcrumbs="['Approved Budget Clearances' => route('budget-clearances.index'), 'EBUDGET-CLR-'.$budgetClearance->source_id => null]" />

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h4 class="mb-1">Clearance Information</h4>
                <div class="text-secondary fs-14">Authoritative, read-only record synchronised from eBudget.</div>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-primary">eBudget</span>
                <span class="badge bg-success">Approved</span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr><th class="ps-0 text-secondary">Fiscal Year</th><td class="pe-0">FY {{ $budgetClearance->fiscalYear->name }}</td></tr>
                            <tr><th class="ps-0 text-secondary">MDA</th><td class="pe-0">{{ $budgetClearance->mda_code }} — {{ $budgetClearance->mda_name }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Economic Code</th><td class="pe-0">{{ $budgetClearance->economicCode->code }} — {{ $budgetClearance->economicCode->name }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Payee</th><td class="pe-0">{{ $budgetClearance->payee_name ?: 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Purpose</th><td class="pe-0">{{ $budgetClearance->purpose }}</td></tr>
                            <tr><th class="ps-0 text-secondary">SDP/STI Activity</th><td class="pe-0">{{ $budgetClearance->activity ?: 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Remark</th><td class="pe-0">{{ $budgetClearance->remark ?: 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Approval Type</th><td class="pe-0">{{ $budgetClearance->approval_type ?: 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Prepared By</th><td class="pe-0">{{ $budgetClearance->prepared_by ?: 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Approved By</th><td class="pe-0">{{ $budgetClearance->approved_by ?: 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Requested At</th><td class="pe-0">{{ $budgetClearance->source_created_at?->format('d M Y, H:i') ?? 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Approved At</th><td class="pe-0">{{ $budgetClearance->approved_at?->format('d M Y, H:i') ?? 'Not supplied' }}</td></tr>
                            <tr><th class="ps-0 text-secondary">Last Synchronised</th><td class="pe-0">{{ $budgetClearance->source_synced_at?->format('d M Y, H:i') ?? 'Not supplied' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="border rounded-3 p-4 bg-light">
                    <h5 class="mb-3">eBudget Balance Snapshot</h5>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Approved Budget</span>
                        <strong>&#8358;{{ number_format((float) $budgetClearance->approved_budget_snapshot, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Fund Before Clearance</span>
                        <strong>&#8358;{{ number_format((float) $budgetClearance->fund_available_before, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Approved Clearance</span>
                        <strong class="text-danger">&#8358;{{ number_format((float) $budgetClearance->amount, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-3">
                        <span class="fw-semibold">Balance After Clearance</span>
                        <strong class="text-success">&#8358;{{ number_format((float) $budgetClearance->balance_after, 2) }}</strong>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 mb-0 fs-14">
                    These figures are the values recorded by eBudget when the clearance was submitted. They are preserved for audit and are not recalculated in BOGIS.
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('budget-clearances.index', ['fiscal_year_id' => $budgetClearance->fiscal_year_id]) }}" class="btn btn-outline-secondary">
        Back to Approved Clearances
    </a>
@endsection
