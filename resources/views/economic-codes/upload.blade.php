@extends('layouts.app')

@section('title', 'Upload Economic Codes')

@section('content')
    <x-page-header title="Upload Economic Codes" :breadcrumbs="['Economic Codes' => route('economic-codes.index'), 'Upload' => null]">
        <a href="{{ route('economic-codes.upload.template') }}" class="btn btn-secondary">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Download Template
        </a>
        <a href="{{ route('economic-codes.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <div class="alert alert-info">
            <i class="material-symbols-outlined align-middle">info</i>
            Upload multiple economic codes at once. All codes in the file are created
            with the <strong>same type</strong> you select below. The file must have
            columns: <strong>code</strong>, <strong>name</strong> and optional
            <strong>description</strong>. Codes that already exist are skipped.
        </div>

        <form method="POST" action="{{ route('economic-codes.upload.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="revenue" {{ old('type') === 'revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                    <small class="text-muted d-block mt-1">Codes starting with 1 → Revenue · 21 → Personnel · 22 → Overhead · 23 → Capital</small>
                </div>
                <div class="col-md-4" id="account-type-wrapper" style="display: {{ old('type') === 'expense' ? 'block' : 'none' }};">
                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select">
                        <option value="">Select Account Type</option>
                        <option value="capital" {{ old('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                        <option value="overhead" {{ old('account_type') === 'overhead' ? 'selected' : '' }}>Overhead</option>
                <option value="personnel" {{ old('account_type') === 'personnel' ? 'selected' : '' }}>Personnel</option>
                    </select>
                    <small class="text-muted">Required for Expense codes.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">File <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required>
                    <small class="text-muted">CSV, XLSX or XLS. Max 5 MB.</small>
                </div>
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>code</th>
                                    <th>name</th>
                                    <th>description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>12010101</code></td>
                                    <td><code>Ground Rent</code></td>
                                    <td><code>Revenue from ground rent</code></td>
                                </tr>
                                <tr>
                                    <td><code>22020101</code></td>
                                    <td><code>Local Travel</code></td>
                                    <td><code>Overhead travel expenses</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4"
                        onclick="return confirm('Import these economic codes?');">
                        <i class="material-symbols-outlined align-middle fs-18">upload</i>
                        Import Economic Codes
                    </button>
                    <a href="{{ route('economic-codes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    @if(session('upload_errors'))
    <div class="card border-0 p-4 bg-white rounded-3 mt-4">
        <h5 class="mb-3">Upload Summary</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(session('upload_errors') as $index => $error)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $error }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
document.getElementById('type').addEventListener('change', function () {
    document.getElementById('account-type-wrapper').style.display = this.value === 'expense' ? 'block' : 'none';
});
</script>
@endpush
