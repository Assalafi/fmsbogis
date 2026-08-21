@extends('layouts.app')

@section('title', 'Approved Budgets')

@section('content')
    <x-page-header title="Approved Budgets" :breadcrumbs="['Budgets' => null]">
        @can('budgets.create')
        <a href="{{ route('budgets.upload') }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">upload</i>
            Upload Budget
        </a>
        <a href="{{ route('budgets.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Create Budget
        </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('budgets.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Account Type</label>
                <select name="account_type" class="form-select">
                    <option value="">All</option>
                    <option value="capital" {{ request('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                    <option value="overhead" {{ request('account_type') === 'overhead' ? 'selected' : '' }}>Overhead</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['draft', 'pending', 'approved', 'rejected'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
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
                        <th class="text-end">Original</th>
                        <th class="text-end">Supplementary</th>
                        <th class="text-end">Virement In</th>
                        <th class="text-end">Virement Out</th>
                        <th class="text-end">Revised</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Available</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $budget)
                        <tr>
                            <td>
                                <a href="{{ route('budgets.show', $budget) }}" class="text-decoration-none fw-medium">
                                    {{ $budget->economicCode->code }}
                                </a>
                                <div class="fs-13 text-secondary">{{ $budget->economicCode->name }}</div>
                            </td>
                            <td><span class="badge bg-{{ $budget->economicCode->account_type === 'capital' ? 'dark' : 'info' }}">{{ ucfirst($budget->economicCode->account_type) }}</span></td>
                            <td class="text-end">₦{{ number_format((float) $budget->original_budget, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $budget->supplementary_budget, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $budget->virement_in, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $budget->virement_out, 2) }}</td>
                            <td class="text-end fw-medium">₦{{ number_format((float) $budgetService->revisedBudget($budget), 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $budgetService->paidPayments($budget->economicCode, $budget->fiscalYear), 2) }}</td>
                            <td class="text-end text-success fw-medium">₦{{ number_format((float) $budgetService->availableBudget($budget->economicCode, $budget->fiscalYear), 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $budget->status])</td>
                            <td>
                                <a href="{{ route('budgets.show', $budget) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-secondary py-4">
                                No Approved Budget Found
                                <div class="mt-2">Create a budget for an Expense Economic Code.</div>
                                @can('budgets.create')
                                <a href="{{ route('budgets.create') }}" class="btn btn-sm btn-primary mt-2">+ Create Budget</a>
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
