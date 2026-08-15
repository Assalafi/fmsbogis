@extends('layouts.app')

@section('title', 'Fiscal Years')

@section('content')
    <x-page-header title="Fiscal Years" :breadcrumbs="['Fiscal Years' => null]">
        @can('fiscal_years.create')
        <a href="{{ route('fiscal-years.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Add Fiscal Year
        </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fiscalYears as $fiscalYear)
                        <tr>
                            <td class="fw-medium">{{ $fiscalYear->name }}</td>
                            <td>{{ $fiscalYear->start_date->format('d M Y') }}</td>
                            <td>{{ $fiscalYear->end_date->format('d M Y') }}</td>
                            <td>@include('components.status-badge', ['status' => $fiscalYear->status])</td>
                            <td>
                                @if($fiscalYear->id === $activeId)
                                    <span class="badge bg-success">Current</span>
                                @else
                                    <form method="POST" action="{{ route('fiscal-years.set-active', $fiscalYear) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Set Active</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    @can('fiscal_years.update')
                                    <a href="{{ route('fiscal-years.edit', $fiscalYear) }}" class="text-primary" title="Edit"><i class="material-symbols-outlined fs-20">edit</i></a>
                                    @if($fiscalYear->isOpen())
                                    <form method="POST" action="{{ route('fiscal-years.close', $fiscalYear) }}"
                                        onsubmit="return confirm('Close Fiscal Year {{ $fiscalYear->name }}? This prevents new budgets, payments and virements.');">
                                        @csrf
                                        <button type="submit" class="text-danger border-0 bg-transparent" title="Close Year"><i class="material-symbols-outlined fs-20">lock</i></button>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                No Fiscal Years Found
                                @can('fiscal_years.create')
                                <div class="mt-2">
                                    <a href="{{ route('fiscal-years.create') }}" class="btn btn-sm btn-primary">+ Add Fiscal Year</a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
