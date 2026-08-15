@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
    <x-page-header title="Audit Logs" :breadcrumbs="['Audit Logs' => null]" />

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Event</label>
                <input type="text" name="event" class="form-control" value="{{ request('event') }}" placeholder="e.g. Payment Approved">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">User</label>
                <select name="user_id" class="form-select">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') === $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
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
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Record Type</th>
                        <th>Record Reference</th>
                        <th>IP Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="fw-medium">{{ $log->user?->name ?? 'System' }}</td>
                            <td><span class="badge bg-primary bg-opacity-75 text-white">{{ $log->event }}</span></td>
                            <td>{{ $log->auditable_type ? class_basename($log->auditable_type) : '—' }}</td>
                            <td><span class="fs-13">{{ $log->auditable_id ? substr($log->auditable_id, 0, 8).'…' : '—' }}</span></td>
                            <td>{{ $log->ip_address ?? '—' }}</td>
                            <td>
                                <a href="{{ route('audit-logs.show', $log) }}" class="text-info" title="View Changes">
                                    <i class="material-symbols-outlined fs-20">visibility</i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No audit logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
