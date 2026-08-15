@extends('layouts.app')

@section('title', 'Virement Report')

@section('content')
    <x-page-header title="Virement Report" :breadcrumbs="['Reports' => route('reports.index'), 'Virement Report' => null]">
        <a href="{{ route('reports.show', ['report' => 'virement-report', 'export' => 'pdf', 'fiscal_year_id' => request('fiscal_year_id')]) }}" class="btn btn-secondary" target="_blank">PDF</a>
        <a href="{{ route('reports.show', ['report' => 'virement-report', 'export' => 'excel', 'fiscal_year_id' => request('fiscal_year_id')]) }}" class="btn btn-success">Excel</a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('reports.show', 'virement-report') }}" class="row g-3 align-items-end">
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
                        <th>Date</th>
                        <th>Reference</th>
                        <th>From Code</th>
                        <th>To Code</th>
                        <th class="text-end">Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($virements as $virement)
                        <tr>
                            <td>{{ $virement->date->format('d M Y') }}</td>
                            <td class="fw-medium">{{ $virement->reference_number }}</td>
                            <td>{{ $virement->fromEconomicCode->code }}</td>
                            <td>{{ $virement->toEconomicCode->code }}</td>
                            <td class="text-end">₦{{ number_format((float) $virement->amount, 2) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($virement->reason, 50) }}</td>
                            <td>@include('components.status-badge', ['status' => $virement->status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No virements found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
