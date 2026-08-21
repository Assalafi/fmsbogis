@extends('layouts.app')

@section('title', 'Economic Code Performance')

@section('content')
    <x-page-header title="Economic Code Performance" :breadcrumbs="['Performance' => null, 'Economic Codes' => null]">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-secondary" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">picture_as_pdf</i>
            PDF
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Account Type</th>
                        <th class="text-end">Original Budget</th>
                        <th class="text-end">Receipts</th>
                        <th class="text-end">Payments</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="fw-medium">{{ $row['code']->code }}</td>
                            <td>
                                <a href="{{ route('economic-codes.show', $row['code']) }}" class="text-decoration-none">{{ $row['code']->name }}</a>
                            </td>
                            <td><span class="badge bg-{{ $row['code']->isRevenue() ? 'success' : 'primary' }}">{{ ucfirst($row['code']->type) }}</span></td>
                            <td>{{ $row['code']->account_type ? ucfirst($row['code']->account_type) : '—' }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['original_budget'], 2) }}</td>
                            <td class="text-end text-success">₦{{ number_format((float) $row['receipts'], 2) }}</td>
                            <td class="text-end text-danger">₦{{ number_format((float) $row['payments'], 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $row['available'], 2) }}</td>
                            <td class="text-end">
                                @if($row['performance'] !== null)
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <span class="fs-14">{{ $row['performance'] }}%</span>
                                        <div class="progress" style="width: 80px; height: 6px;">
                                            <div class="progress-bar bg-{{ $row['performance'] >= 100 ? 'danger' : 'success' }}" style="width: {{ min($row['performance'], 100) }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-secondary py-4">No economic codes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
