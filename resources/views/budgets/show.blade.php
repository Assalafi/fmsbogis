@extends('layouts.app')

@section('title', 'Budget — '.$budget->economicCode->code)

@section('content')
    <x-page-header title="{{ $budget->economicCode->code }} — {{ $budget->economicCode->name }}" :breadcrumbs="['Approved Budgets' => route('budgets.index'), $budget->economicCode->code => null]" />

    <div class="row">
        <x-stat-card label="APPROVED BUDGET" value="₦{{ number_format((float) $budget->original_budget, 2) }}" icon="account_balance_wallet" color="primary" />
        <x-stat-card label="REVISED BUDGET" value="₦{{ number_format((float) $stats['total'], 2) }}" icon="swap_horiz" color="info" />
        <x-stat-card label="COMMITTED & PAID" value="₦{{ number_format((float) \App\Support\Money::add($stats['paid'], $stats['approved_unpaid']), 2) }}" icon="payments" color="warning" />
        <x-stat-card label="BOGIS AVAILABLE" value="₦{{ number_format((float) $stats['available'], 2) }}" icon="savings" color="success" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h4 class="mb-0">Budget Information</h4>
            <span class="badge bg-primary">Synchronised from eBudget</span>
        </div>
        <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr><th class="ps-0 fs-14 text-secondary" style="width: 34%;">Fiscal Year</th><td class="pe-0">FY {{ $budget->fiscalYear->name }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Economic Code</th><td class="pe-0">{{ $budget->economicCode->code }} — {{ $budget->economicCode->name }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Account Type</th><td class="pe-0">{{ ucfirst($budget->economicCode->account_type) }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Approved Allocation</th><td class="pe-0">₦{{ number_format((float) $budget->original_budget, 2) }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Approved Virement In</th><td class="pe-0 text-success">₦{{ number_format((float) $budget->virement_in, 2) }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Approved Virement Out</th><td class="pe-0 text-danger">₦{{ number_format((float) $budget->virement_out, 2) }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Paid Payments in BOGIS</th><td class="pe-0">₦{{ number_format((float) $stats['paid'], 2) }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Approved Unpaid Commitments</th><td class="pe-0">₦{{ number_format((float) $stats['approved_unpaid'], 2) }}</td></tr>
                    <tr>
                        <th class="ps-0 fs-14 text-secondary">eBudget Recorded Balance</th>
                        <td class="pe-0">
                            {{ $budget->source_available_funds !== null ? '₦'.number_format((float) $budget->source_available_funds, 2) : 'Not supplied' }}
                            <div class="fs-12 text-secondary">Shown for reference; BOGIS payment control uses the local available balance above.</div>
                        </td>
                    </tr>
                    <tr><th class="ps-0 fs-14 text-secondary">eBudget Last Updated</th><td class="pe-0">{{ $budget->source_updated_at?->format('d M Y, H:i') ?? 'Not supplied' }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Last Synced to BOGIS</th><td class="pe-0">{{ $budget->source_synced_at?->format('d M Y, H:i') ?? 'Not yet synced' }}</td></tr>
                    <tr><th class="ps-0 fs-14 text-secondary">Status</th><td class="pe-0">@include('components.status-badge', ['status' => $budget->status])</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
