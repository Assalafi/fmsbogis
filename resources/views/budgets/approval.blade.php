@extends('layouts.app')

@section('title', 'Budget Approval')

@section('content')
    <x-page-header title="Budget Approval" :breadcrumbs="['Budgets' => route('budgets.index'), 'Approval' => null]" />

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Economic Code</th>
                        <th>Description</th>
                        <th>Fiscal Year</th>
                        <th class="text-end">Original Budget</th>
                        <th>Created By</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $budget)
                        <tr>
                            <td class="fw-medium">{{ $budget->economicCode->code }}</td>
                            <td>{{ $budget->economicCode->name }}</td>
                            <td>FY {{ $budget->fiscalYear->name }}</td>
                            <td class="text-end">₦{{ number_format((float) $budget->original_budget, 2) }}</td>
                            <td>{{ $budget->creator?->name ?? '—' }}</td>
                            <td>{{ $budget->updated_at->format('d M Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-sm btn-info text-white">
                                        <i class="material-symbols-outlined align-middle fs-18">visibility</i> View
                                    </a>
                                    <form method="POST" action="{{ route('budgets.approve', $budget) }}"
                                        onsubmit="return confirm('Approve this budget?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="material-symbols-outlined align-middle fs-18">check</i> Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $budget->id }}">
                                        Reject
                                    </button>
                                </div>

                                <div class="modal fade" id="rejectModal-{{ $budget->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('budgets.reject', $budget) }}" class="modal-content">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject Budget — {{ $budget->economicCode->code }}</h5>
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No pending budget approvals.</td>
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
