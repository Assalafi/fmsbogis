@extends('layouts.app')

@section('title', 'Create Economic Code')

@section('content')
    <x-page-header title="Create Economic Code" :breadcrumbs="['Economic Codes' => route('economic-codes.index'), 'Create' => null]">
        <a href="{{ route('economic-codes.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="POST" action="{{ route('economic-codes.store') }}">
            @csrf
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="22020101" required>
                    <small class="text-muted">Example: 12010101 (Revenue), 22020101 (Overhead Expense), 23010101 (Capital Expense)</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="revenue" {{ old('type') === 'revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Local Travel" required>
                </div>
                <div class="col-md-6" id="account-type-wrapper" style="display: {{ old('type') === 'expense' ? 'block' : 'none' }};">
                    <label class="form-label">Account Type</label>
                    <select name="account_type" class="form-select">
                        <option value="">Select Account Type</option>
                        <option value="capital" {{ old('account_type') === 'capital' ? 'selected' : '' }}>Capital</option>
                        <option value="overhead" {{ old('account_type') === 'overhead' ? 'selected' : '' }}>Overhead</option>
                <option value="personnel" {{ old('account_type') === 'personnel' ? 'selected' : '' }}>Personnel</option>
                    </select>
                    <small class="text-muted">Only required for Expense Economic Codes.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Optional description">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Save Economic Code</button>
                    <a href="{{ route('economic-codes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    const codeInput = document.getElementById('code');
    const typeSelect = document.getElementById('type');
    const accountTypeSelect = document.querySelector('select[name="account_type"]');
    const accountTypeWrapper = document.getElementById('account-type-wrapper');
    const rules = @json(\App\Support\AccountTypes::detectRules());

    function detectType() {
        const code = (codeInput.value || '').trim();
        let detected = null;
        for (const [prefix, info] of Object.entries(rules)) {
            if (code.startsWith(prefix)) { detected = info; break; }
        }
        if (!detected) return;
        typeSelect.value = detected[0];
        accountTypeSelect.value = detected[1];
        accountTypeWrapper.style.display = detected[0] === 'expense' ? 'block' : 'none';
    }

    codeInput.addEventListener('input', detectType);

    typeSelect.addEventListener('change', function () {
        accountTypeWrapper.style.display = this.value === 'expense' ? 'block' : 'none';
    });
</script>
@endpush
