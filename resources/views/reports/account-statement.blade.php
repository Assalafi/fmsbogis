@extends('layouts.app')

@section('title', 'Account Statement Report')

@section('content')
    <x-page-header title="Account Statement Report" :breadcrumbs="['Reports' => route('reports.index'), 'Account Statement' => null]" />

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Type</th>
                        <th>Bank</th>
                        <th>Number</th>
                        <th class="text-end">Opening Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td class="fw-medium">{{ $account->account_name }}</td>
                            <td>{{ ucfirst($account->account_type) }}</td>
                            <td>{{ $account->bank_name }}</td>
                            <td>{{ $account->account_number }}</td>
                            <td class="text-end">₦{{ number_format((float) $account->opening_balance, 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $account->status])</td>
                            <td>
                                <a href="{{ route('cashbook.print', $account) }}" class="btn btn-sm btn-secondary" target="_blank">PDF Statement</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
