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
        'cashbook_addition' => 'success',
        'cashbook_deduction' => 'danger',
        'bank_addition' => 'success',
        'bank_deduction' => 'danger',
    ];
@endphp
<span class="badge bg-{{ $statusColors[$status] ?? 'secondary' }} bg-opacity-75 text-white">
    {{ $label ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
