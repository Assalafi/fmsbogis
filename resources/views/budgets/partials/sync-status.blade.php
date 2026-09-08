@if($selectedFiscalYear)
    @php
        $syncType = match($lastSync?->status) {
            'completed' => 'success',
            'failed' => 'danger',
            'running' => 'warning',
            default => 'secondary',
        };
    @endphp
    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-primary">sync</span>
                    <h5 class="mb-0">eBudget Connection</h5>
                    <span class="badge bg-{{ $syncType }}">{{ $lastSync ? ucfirst($lastSync->status) : 'Not synced' }}</span>
                </div>
                @if($lastSync)
                    <div class="fs-14 text-secondary">
                        {{ $lastSync->message ?: 'Synchronisation is in progress.' }}
                        <span class="d-inline-block ms-1">
                            {{ ($lastSync->finished_at ?? $lastSync->started_at)->format('d M Y, H:i') }}
                        </span>
                    </div>
                @else
                    <div class="fs-14 text-secondary">FY {{ $selectedFiscalYear->name }} has not yet been synchronised from eBudget.</div>
                @endif
            </div>

            @can('budgets.sync')
                <form method="POST" action="{{ route('budgets.sync') }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('.sync-label').textContent = 'Synchronising...';">
                    @csrf
                    <input type="hidden" name="fiscal_year_id" value="{{ $selectedFiscalYear->id }}">
                    <button type="submit" class="btn btn-primary" {{ $lastSync?->status === 'running' ? 'disabled' : '' }}>
                        <i class="material-symbols-outlined align-middle fs-18">sync</i>
                        <span class="sync-label">Sync FY {{ $selectedFiscalYear->name }}</span>
                    </button>
                </form>
            @endcan
        </div>
    </div>
@endif
