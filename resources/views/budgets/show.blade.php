@extends('layouts.app')

@section('title', 'Budget — '.$budget->economicCode->code)

@section('content')
    <x-page-header title="{{ $budget->economicCode->code }} — {{ $budget->economicCode->name }}" :breadcrumbs="['Budgets' => route('budgets.index'), $budget->economicCode->code => null]">
        @if($budget->status === 'draft')
            @can('budgets.create')
            <form method="POST" action="{{ route('budgets.submit', $budget) }}">
                @csrf
                <button type="submit" class="btn btn-warning">Submit for Approval</button>
            </form>
            @endcan
        @endif
        @if($budget->status === 'pending')
            @can('budgets.approve')
            <form method="POST" action="{{ route('budgets.approve', $budget) }}">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this budget?');">Approve</button>
            </form>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
            @endcan
        @endif
    </x-page-header>

    <div class="row">
        <x-stat-card label="ORIGINAL BUDGET" value="₦{{ number_format((float) $budget->original_budget, 2) }}" icon="account_balance_wallet" color="secondary" />
        <x-stat-card label="SUPPLEMENTARY" value="₦{{ number_format((float) $budget->supplementary_budget, 2) }}" icon="add" color="info" />
        <x-stat-card label="AVAILABLE BUDGET" value="₦{{ number_format((float) $stats['available'], 2) }}" icon="savings" color="success" />
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Budget Information</h4>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th class="ps-0 fs-14 text-secondary" style="width: 45%;">Fiscal Year</th><td class="pe-0">FY {{ $budget->fiscalYear->name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Economic Code</th><td class="pe-0">{{ $budget->economicCode->code }} — {{ $budget->economicCode->name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Account Type</th><td class="pe-0">{{ ucfirst($budget->economicCode->account_type) }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Virement In</th><td class="pe-0">₦{{ number_format((float) $budget->virement_in, 2) }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Virement Out</th><td class="pe-0">₦{{ number_format((float) $budget->virement_out, 2) }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Paid Payments</th><td class="pe-0">₦{{ number_format((float) $stats['paid'], 2) }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Approved Unpaid</th><td class="pe-0">₦{{ number_format((float) $stats['approved_unpaid'], 2) }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Status</th><td class="pe-0">@include('components.status-badge', ['status' => $budget->status])</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Created By</th><td class="pe-0">{{ $budget->creator?->name ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Approved By</th><td class="pe-0">{{ $budget->approver?->name ?? '—' }} @if($budget->approved_at)<span class="fs-13 text-secondary">({{ $budget->approved_at->format('d M Y H:i') }})</span>@endif</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Approval History</h4>
                <ul class="list-unstyled ps-0 mb-0">
                    @forelse($budget->approvals as $approval)
                        <li class="py-2 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-{{ $approval->action === 'approved' ? 'success' : 'danger' }}">{{ ucfirst($approval->action) }}</span>
                                <span class="ms-2 fw-medium">{{ $approval->approver?->name ?? '—' }}</span>
                                @if($approval->comment)<div class="fs-13 text-secondary mt-1">{{ $approval->comment }}</div>@endif
                            </div>
                            <span class="fs-13 text-secondary">{{ $approval->created_at->format('d M Y H:i') }}</span>
                        </li>
                    @empty
                        <li class="text-center text-secondary py-4">No approval actions yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @if($budget->status === 'pending' && auth()->user()->can('budgets.approve'))
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('budgets.reject', $budget) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Comment</label>
                    <textarea name="comment" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection
