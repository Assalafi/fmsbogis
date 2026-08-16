@extends('layouts.app')

@section('title', 'Receipt Register')

@section('content')
    <x-page-header title="Receipt Register" :breadcrumbs="['Receipts' => null]">
        <button type="button" class="btn btn-danger" id="bulk-download-btn" disabled>
            <i class="material-symbols-outlined align-middle fs-18">folder_zip</i>
            Download Selected (ZIP)
        </button>
        <button type="button" class="btn btn-outline-secondary" id="bulk-download-all-btn">
            <i class="material-symbols-outlined align-middle fs-18">all_inbox</i>
            Download All Filtered (ZIP)
        </button>
        @can('receipts.create')
        <a href="{{ route('receipts.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Create Receipt
        </a>
        @endcan
    </x-page-header>

    @if($summary)
    <div class="row">
        <x-stat-card label="TODAY'S RECEIPTS" value="₦{{ number_format((float) $summary['today'], 2) }}" icon="today" color="primary" />
        <x-stat-card label="THIS MONTH" value="₦{{ number_format((float) $summary['month'], 2) }}" icon="calendar_view_month" color="info" />
        <x-stat-card label="YEAR TO DATE" value="₦{{ number_format((float) $summary['year_to_date'], 2) }}" icon="trending_up" color="success" />
        <x-stat-card label="RECEIPT COUNT" value="{{ $summary['count'] }}" icon="receipt_long" color="secondary" />
    </div>
    @endif

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('receipts.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fs-14">Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                    placeholder="Name, payment reference or phone...">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Account</label>
                <select name="account_id" class="form-select">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ request('account_id') === $account->id ? 'selected' : '' }}>{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Economic Code</label>
                <select name="economic_code_id" class="form-select">
                    <option value="">All Codes</option>
                    @foreach($revenueCodes as $code)
                        <option value="{{ $code->id }}" {{ request('economic_code_id') === $code->id ? 'selected' : '' }}>{{ $code->code }} — {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['draft', 'pending', 'approved', 'posted', 'reversed'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
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
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 36px;">
                            <input type="checkbox" class="form-check-input" id="select-all" title="Select all on this page">
                        </th>
                        <th>Date</th>
                        <th>Treasury Receipt No.</th>
                        <th>Account</th>
                        <th>Economic Code</th>
                        <th>From Whom Received</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input receipt-check" value="{{ $receipt->id }}">
                            </td>
                            <td>{{ $receipt->date_of_transaction->format('d M Y') }}</td>
                            <td class="fw-medium">{{ $receipt->treasury_receipt_voucher_number }}</td>
                            <td>{{ $receipt->account->account_name }}</td>
                            <td>{{ $receipt->economicCode->code }}</td>
                            <td>{{ $receipt->from_whom_received_to_whom_paid ?? '—' }}</td>
                            <td><span class="badge bg-{{ $receipt->payment_method === 'bank' ? 'info' : 'warning' }}">{{ strtoupper($receipt->payment_method) }}</span></td>
                            <td class="text-end fw-medium text-success">₦{{ number_format((float) $receipt->amount, 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $receipt->status])</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('receipts.show', $receipt) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    @can('receipts.create')
                                    @if(in_array($receipt->status, ['draft', 'pending']))
                                    <a href="{{ route('receipts.edit', $receipt) }}" class="text-primary" title="Edit"><i class="material-symbols-outlined fs-20">edit</i></a>
                                    @endif
                                    @endcan
                                    <a href="{{ route('receipts.pdf', $receipt) }}" class="text-secondary" title="Print Receipt PDF" target="_blank"><i class="material-symbols-outlined fs-20">print</i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-secondary py-4">
                                No receipts found.
                                @can('receipts.create')
                                <div class="mt-2">
                                    <a href="{{ route('receipts.create') }}" class="btn btn-sm btn-primary">+ Create Receipt</a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $receipts->links() }}
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mt-4" id="bulk-progress-card" style="display: none;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0" id="bulk-status-text">Preparing ZIP…</h6>
            <span class="badge bg-info" id="bulk-percent-text">0%</span>
        </div>
        <div class="progress mb-3" style="height: 12px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" id="bulk-progress-bar"
                role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="row fs-14">
            <div class="col-md-4"><span class="text-secondary">Total:</span> <strong id="bulk-total">0</strong></div>
            <div class="col-md-4"><span class="text-secondary">Packed:</span> <strong id="bulk-done" class="text-success">0</strong></div>
            <div class="col-md-4"><span class="text-secondary">Failed:</span> <strong id="bulk-failed" class="text-danger">0</strong></div>
        </div>
        <div id="bulk-message" class="alert alert-success mt-3 mb-0" style="display: none;"></div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const selectAll = document.getElementById('select-all');
    const checks = document.querySelectorAll('.receipt-check');
    const bulkBtn = document.getElementById('bulk-download-btn');
    const bulkAllBtn = document.getElementById('bulk-download-all-btn');
    const progressCard = document.getElementById('bulk-progress-card');
    const progressBar = document.getElementById('bulk-progress-bar');
    const percentText = document.getElementById('bulk-percent-text');
    const statusText = document.getElementById('bulk-status-text');
    const messageBox = document.getElementById('bulk-message');
    let pollTimer = null;

    function updateButton() {
        const selected = [...checks].filter(c => c.checked);
        bulkBtn.disabled = selected.length === 0;
        bulkBtn.innerHTML = '<i class="material-symbols-outlined align-middle fs-18">folder_zip</i> Download Selected (' + selected.length + ')';
    }

    selectAll.addEventListener('change', function () {
        checks.forEach(c => c.checked = selectAll.checked);
        updateButton();
    });

    checks.forEach(c => c.addEventListener('change', updateButton));

    function postBulk(ids) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('receipts.bulk-download') }}';
        form.style.display = 'none';
        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
        ids.forEach(id => {
            form.innerHTML += '<input type="hidden" name="receipt_ids[]" value="' + id + '">';
        });
        document.body.appendChild(form);
        form.submit();
    }

    bulkBtn.addEventListener('click', function () {
        const ids = [...checks].filter(c => c.checked).map(c => c.value);
        if (ids.length === 0) return;
        if (!confirm('Download ' + ids.length + ' receipt PDF(s) as a ZIP?')) return;
        postBulk(ids);
    });

    bulkAllBtn.addEventListener('click', function () {
        if (!confirm('Download ALL receipts matching the current filters as a ZIP? This may take a while for large sets.')) return;
        postBulk([]);
    });

    function render(state) {
        if (state.status === 'idle') return;

        progressCard.style.display = 'block';
        progressBar.style.width = state.percent + '%';
        progressBar.setAttribute('aria-valuenow', state.percent);
        percentText.textContent = state.percent + '%';
        document.getElementById('bulk-total').textContent = state.total;
        document.getElementById('bulk-done').textContent = state.done;
        document.getElementById('bulk-failed').textContent = state.failed;

        if (state.status === 'running') {
            statusText.textContent = 'Preparing ZIP…';
            messageBox.style.display = 'none';
            bulkBtn.disabled = true;
            bulkAllBtn.disabled = true;
            if (!pollTimer) pollTimer = setInterval(poll, 2000);
        } else if (state.status === 'done') {
            statusText.textContent = 'ZIP ready — downloading…';
            progressBar.classList.remove('progress-bar-animated');
            clearInterval(pollTimer);
            pollTimer = null;
            bulkBtn.disabled = false;
            bulkAllBtn.disabled = false;
            updateButton();
            window.location.href = '{{ route('receipts.bulk-download-file', ['token' => '__TOKEN__']) }}'.replace('__TOKEN__', state.token);
        } else if (state.status === 'error') {
            statusText.textContent = 'Bulk download failed';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.remove('bg-danger');
            progressBar.classList.add('bg-danger');
            clearInterval(pollTimer);
            pollTimer = null;
            bulkBtn.disabled = false;
            bulkAllBtn.disabled = false;
            updateButton();
            messageBox.style.display = 'block';
            messageBox.className = 'alert alert-danger mt-3 mb-0';
            messageBox.textContent = state.message;
        }
    }

    function poll() {
        fetch('{{ route('receipts.bulk-download-progress') }}', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(r => r.json())
        .then(render)
        .catch(() => {});
    }

    poll();
    updateButton();
})();
</script>
@endpush
