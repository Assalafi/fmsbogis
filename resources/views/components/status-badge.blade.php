@php
    $statusColors = [
        'draft' => 'secondary',
        'pending' => 'warning',
        'approved' => 'primary',
        'posted' => 'success',
        'paid' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'dark',
        'reversed' => 'dark',
        'reconciled' => 'success',
        'active' => 'success',
        'inactive' => 'secondary',
        'open' => 'success',
        'closed' => 'secondary',
        'imported' => 'info',
        'manual' => 'info',
        'matched' => 'success',
        'unmatched' => 'danger',
        'bank_only' => 'info',
        'cashbook_only' => 'warning',
        'never' => 'secondary',
        'bank_adjustment' => 'info',
    ];
@endphp
<span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} bg-opacity-75 text-white">
    {{ ucfirst(str_replace('_', ' ', $status)) }}
</span>
