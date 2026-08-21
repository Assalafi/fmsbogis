@extends('layouts.app')

@section('title', 'Virements')

@section('content')
    <x-page-header title="Virements" :breadcrumbs="['Budgets' => route('budgets.index'), 'Virements' => null]">
        @can('virements.create')
        <a href="{{ route('virements.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Create Virement
        </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('virements.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['pending', 'approved', 'rejected'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                        <th>Created By</th>
                        <th>Approved By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($virements as $virement)
                        <tr>
                            <td>{{ $virement->date->format('d M Y') }}</td>
                            <td>{{ $virement->reference_number }}</td>
                            <td class="fw-medium">{{ $virement->fromEconomicCode->code }}</td>
                            <td class="fw-medium">{{ $virement->toEconomicCode->code }}</td>
                            <td class="text-end">₦{{ number_format((float) $virement->amount, 2) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($virement->reason, 30) }}</td>
                            <td>@include('components.status-badge', ['status' => $virement->status])</td>
                            <td>{{ $virement->creator?->name ?? '—' }}</td>
                            <td>{{ $virement->approver?->name ?? '—' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('virements.show', $virement) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    @can('virements.create')
                                    @if(! $virement->isApproved())
                                    <form method="POST" action="{{ route('virements.destroy', $virement) }}" onsubmit="return confirm('Delete this virement? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger border-0 bg-transparent p-0" title="Delete"><i class="material-symbols-outlined fs-20">delete</i></button>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-secondary py-4">No virements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $virements->links() }}
        </div>
    </div>
@endsection
