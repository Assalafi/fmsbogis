@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <x-page-header title="Settings" :breadcrumbs="['Settings' => null]" />

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Organization Name</label>
                    <input type="text" name="organization_name" class="form-control"
                        value="{{ \App\Models\Setting::get('organization_name', 'Borno State Geographic Information Service') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-control" value="{{ \App\Models\Setting::get('currency', 'NGN') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency Symbol</label>
                    <input type="text" name="currency_symbol" class="form-control" value="{{ \App\Models\Setting::get('currency_symbol', '₦') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date Format</label>
                    <select name="date_format" class="form-select">
                        @foreach(['d M Y' => 'd M Y (15 Aug 2026)', 'd/m/Y' => 'd/m/Y (15/08/2026)', 'm/d/Y' => 'm/d/Y (08/15/2026)', 'Y-m-d' => 'Y-m-d (2026-08-15)'] as $format => $label)
                            <option value="{{ $format }}" {{ \App\Models\Setting::get('date_format', 'd M Y') === $format ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Receipt Prefix</label>
                    <input type="text" name="receipt_prefix" class="form-control" value="{{ \App\Models\Setting::get('receipt_prefix', 'TR') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Voucher Prefix</label>
                    <input type="text" name="payment_voucher_prefix" class="form-control" value="{{ \App\Models\Setting::get('payment_voucher_prefix', 'TV') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pagination Size</label>
                    <input type="number" name="pagination_size" class="form-control" value="{{ \App\Models\Setting::get('pagination_size', 20) }}">
                </div>
                <div class="col-md-12">
                    <h5 class="mb-3">Approval Requirements</h5>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_receipt_approval" value="1"
                            id="require_receipt_approval" {{ \App\Models\Setting::get('require_receipt_approval', '1') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="require_receipt_approval">Require Receipt Approval</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="require_payment_approval" value="1"
                            id="require_payment_approval" {{ \App\Models\Setting::get('require_payment_approval', '1') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="require_payment_approval">Require Payment Approval</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="allow_cross_type_virement" value="1"
                            id="allow_cross_type_virement" {{ \App\Models\Setting::get('allow_cross_type_virement', '0') === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="allow_cross_type_virement">Allow Cross-Type Virement</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Save Settings</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mt-4">
        <h4 class="mb-2">Appearance</h4>
        <p class="fs-14 text-secondary mb-4">
            Customize the system branding — sidebar logo, browser favicon and the login page image.
        </p>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Organization Logo (sidebar &amp; login)</label>
                    <div class="mb-2">
                        <img src="{{ \App\Models\Setting::get('organization_logo') ? \Illuminate\Support\Facades\Storage::disk('uploads')->url(\App\Models\Setting::get('organization_logo')) : '/assets/images/logo-icon.png' }}"
                            class="border rounded-3 p-2 bg-body-bg" style="height: 70px; width: auto;" alt="current logo">
                    </div>
                    <input type="file" name="organization_logo" class="form-control" accept="image/*">
                    <small class="text-muted">PNG, JPG, SVG or WebP. Max 3 MB.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Favicon</label>
                    <div class="mb-2">
                        <img src="{{ \App\Models\Setting::get('favicon') ? \Illuminate\Support\Facades\Storage::disk('uploads')->url(\App\Models\Setting::get('favicon')) : '/assets/images/favicon.png' }}"
                            class="border rounded-3 p-2 bg-body-bg" style="height: 70px; width: auto;" alt="current favicon">
                    </div>
                    <input type="file" name="favicon" class="form-control" accept="image/*,.ico">
                    <small class="text-muted">Shown in the browser tab. PNG, ICO or JPG.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Login Page Image</label>
                    <div class="mb-2">
                        <img src="{{ \App\Models\Setting::get('login_image') ? \Illuminate\Support\Facades\Storage::disk('uploads')->url(\App\Models\Setting::get('login_image')) : '/assets/images/login.jpg' }}"
                            class="border rounded-3" style="height: 70px; width: auto;" alt="current login image">
                    </div>
                    <input type="file" name="login_image" class="form-control" accept="image/*">
                    <small class="text-muted">Left-side image on the login page.</small>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">Save Appearance</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mt-4">
        <h4 class="mb-2">BOGIS Forms Integration</h4>
        <p class="fs-14 text-secondary mb-4">
            When a user pays on BOGIS Forms (Zainpay), a receipt is automatically created and posted here.
            Choose which Account and Economic Codes imported receipts should use.
        </p>

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Receipt Account <span class="text-danger">*</span></label>
                    <select name="external_receipt_account_id" class="form-select" required>
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ \App\Models\Setting::get('external_receipt_account_id') === $account->id ? 'selected' : '' }}>
                                {{ $account->account_name }} ({{ ucfirst($account->account_type) }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">All imported receipts are posted to this account.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Application Fee Economic Code <span class="text-danger">*</span></label>
                    <select name="external_application_fee_code_id" class="form-select" required>
                        <option value="">Select Revenue Code</option>
                        @foreach($revenueCodes as $code)
                            <option value="{{ $code->id }}" {{ \App\Models\Setting::get('external_application_fee_code_id') === $code->id ? 'selected' : '' }}>
                                {{ $code->code }} — {{ $code->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Used for regular application fee payments.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Plot Premium / Allocation Economic Code <span class="text-danger">*</span></label>
                    <select name="external_premium_code_id" class="form-select" required>
                        <option value="">Select Revenue Code</option>
                        @foreach($revenueCodes as $code)
                            <option value="{{ $code->id }}" {{ \App\Models\Setting::get('external_premium_code_id') === $code->id ? 'selected' : '' }}>
                                {{ $code->code }} — {{ $code->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Used for Plot Premium and Allocation Fee payments.</small>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">Save Integration Settings</button>
                </div>
            </div>
        </form>

        <hr class="my-4">

        <h5 class="mb-2">Users Who Already Paid</h5>
        <p class="fs-14 text-secondary mb-3">
            Payments made before this integration (or pushes that failed) can be pulled in with one click.
            Duplicates are skipped automatically — each payment reference only ever creates one receipt.
        </p>
        <form method="POST" action="{{ route('settings.sync-forms-payments') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label fs-14">From Date</label>
                <input type="date" name="since" class="form-control" value="{{ now()->subDays(30)->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fs-14">To Date</label>
                <input type="date" name="until" class="form-control" value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100" id="sync-btn"
                    onclick="return confirm('Sync paid payments from BOGIS Forms? Duplicates will be skipped.');">
                    <i class="material-symbols-outlined align-middle fs-18">sync</i>
                    Sync Paid Payments
                </button>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">
                    API endpoint: <code>{{ url('api/v1/external/receipts') }}</code> ·
                    Forms API: <code>{{ config('services.bogis_forms.api_url') }}/api/paid-payments</code><br>
                    Also available as artisan command: <code>php artisan receipts:sync-from-forms</code>
                </small>
            </div>
        </form>

        <div id="sync-progress-card" class="mt-4" style="display: none;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0" id="sync-status-text">Sync in progress…</h6>
                <span class="badge bg-info" id="sync-percent-text">0%</span>
            </div>
            <div class="progress mb-3" style="height: 12px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" id="sync-progress-bar"
                    role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="row fs-14">
                <div class="col-md-3"><span class="text-secondary">Page:</span> <strong id="sync-page">—</strong></div>
                <div class="col-md-3"><span class="text-secondary">Created:</span> <strong id="sync-created" class="text-success">0</strong></div>
                <div class="col-md-3"><span class="text-secondary">Existing:</span> <strong id="sync-existing" class="text-primary">0</strong></div>
                <div class="col-md-3"><span class="text-secondary">Failed:</span> <strong id="sync-failed" class="text-danger">0</strong></div>
            </div>
            <div id="sync-message" class="alert alert-success mt-3 mb-0" style="display: none;"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const progressCard = document.getElementById('sync-progress-card');
    const progressBar = document.getElementById('sync-progress-bar');
    const percentText = document.getElementById('sync-percent-text');
    const statusText = document.getElementById('sync-status-text');
    const messageBox = document.getElementById('sync-message');
    const syncBtn = document.getElementById('sync-btn');
    let timer = null;

    function render(state) {
        progressCard.style.display = 'block';
        progressBar.style.width = state.percent + '%';
        progressBar.setAttribute('aria-valuenow', state.percent);
        percentText.textContent = state.percent + '%';
        document.getElementById('sync-page').textContent = state.page || '—';
        document.getElementById('sync-created').textContent = state.created;
        document.getElementById('sync-existing').textContent = state.existing;
        document.getElementById('sync-failed').textContent = state.failed;

        if (state.status === 'running') {
            statusText.textContent = 'Sync in progress…';
            messageBox.style.display = 'none';
            syncBtn.disabled = true;
        } else if (state.status === 'done') {
            statusText.textContent = 'Sync finished';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            syncBtn.disabled = false;
            messageBox.style.display = 'block';
            messageBox.className = 'alert alert-success mt-3 mb-0';
            messageBox.textContent = state.message;
            clearInterval(timer);
            timer = null;
        } else if (state.status === 'error') {
            statusText.textContent = 'Sync failed';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('bg-danger');
            syncBtn.disabled = false;
            messageBox.style.display = 'block';
            messageBox.className = 'alert alert-danger mt-3 mb-0';
            messageBox.textContent = state.message;
            clearInterval(timer);
            timer = null;
        } else {
            statusText.textContent = 'No active sync';
        }
    }

    function poll() {
        fetch('{{ route('settings.sync-progress') }}', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
        .then(r => r.json())
        .then(state => {
            render(state);
            if ((state.status === 'running') && !timer) {
                timer = setInterval(poll, 2000);
            }
        })
        .catch(() => {});
    }

    poll();
})();
</script>
@endpush
