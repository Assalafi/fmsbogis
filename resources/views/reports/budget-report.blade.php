@extends('layouts.app')

@section('title', 'Budget Report')

@section('content')
    <x-page-header title="Budget Report" :breadcrumbs="['Reports' => route('reports.index'), 'Budget Report' => null]">
        <a href="{{ route('reports.show', ['report' => 'budget-report', 'export' => 'pdf', 'fiscal_year_id' => request('fiscal_year_id')]) }}" class="btn btn-secondary" target="_blank">PDF</a>
        <a href="{{ route('reports.show', ['report' => 'budget-report', 'export' => 'excel', 'fiscal_year_id' => request('fiscal_year_id')]) }}" class="btn btn-success">Excel</a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('reports.show', 'budget-report') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach(\App\Models\FiscalYear::orderBy('start_date', 'desc')->get() as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Economic Code</th>
                        <th>Description</th>
                        <th class="text-end">Original</th>
                        <th class="text-end">Supplementary</th>
                        <th class="text-end">Virement In</th>
                        <th class="text-end">Virement Out</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Available</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['budget']->economicCode->code }}</td>
                            <td>{{ $row['budget']->economicCode->name }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['budget']->original_budget, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['budget']->supplementary_budget, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['budget']->virement_in, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['budget']->virement_out, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['paid'], 2) }}</td>
                            <td class="text-end text-success">₦{{ number_format((float) $row['available'], 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $row['budget']->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No budgets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
