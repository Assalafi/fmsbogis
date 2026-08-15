@extends('layouts.app')

@section('title', 'Audit Log Detail')

@section('content')
    <x-page-header title="Audit Log Detail" :breadcrumbs="['Audit Logs' => route('audit-logs.index'), $auditLog->event => null]">
        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <h4 class="mb-4">Log Information</h4>
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th class="ps-0 fs-14 text-secondary" style="width: 25%;">Event</th><td class="pe-0 fw-medium">{{ $auditLog->event }}</td></tr>
                <tr><th class="ps-0 fs-14 text-secondary">User</th><td class="pe-0">{{ $auditLog->user?->name ?? 'System' }}</td></tr>
                <tr><th class="ps-0 fs-14 text-secondary">Timestamp</th><td class="pe-0">{{ $auditLog->created_at->format('d M Y H:i:s') }}</td></tr>
                <tr><th class="ps-0 fs-14 text-secondary">IP Address</th><td class="pe-0">{{ $auditLog->ip_address ?? '—' }}</td></tr>
                <tr><th class="ps-0 fs-14 text-secondary">User Agent</th><td class="pe-0">{{ $auditLog->user_agent ?? '—' }}</td></tr>
                <tr><th class="ps-0 fs-14 text-secondary">Record Type</th><td class="pe-0">{{ $auditLog->auditable_type ?? '—' }}</td></tr>
                <tr><th class="ps-0 fs-14 text-secondary">Record UUID</th><td class="pe-0">{{ $auditLog->auditable_id ?? '—' }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Old Values</h4>
                @if($auditLog->old_values)
                    <pre class="bg-body-bg p-3 rounded-3 mb-0">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                @else
                    <p class="text-secondary mb-0">No old values recorded.</p>
                @endif
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">New Values</h4>
                @if($auditLog->new_values)
                    <pre class="bg-body-bg p-3 rounded-3 mb-0">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                @else
                    <p class="text-secondary mb-0">No new values recorded.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
