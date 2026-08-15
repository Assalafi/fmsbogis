@extends('layouts.app')

@section('title', 'Create Budget')

@section('content')
    <x-page-header title="Create Budget" :breadcrumbs="['Budgets' => route('budgets.index'), 'Create' => null]">
        <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <div class="alert alert-info">
            <i class="material-symbols-outlined align-middle">info</i>
            Only Expense Economic Codes can receive expenditure budgets.
        </div>

        <form method="POST" action="{{ route('budgets.store') }}">
            @csrf
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                    <select name="fiscal_year_id" class="form-select" required>
                        @foreach(\App\Models\FiscalYear::open()->orderBy('start_date')->get() as $fy)
                            <option value="{{ $fy->id }}" {{ $fiscalYear->id === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                        @endforeach
                    </select>
                    @if(!\App\Models\FiscalYear::open()->exists())
                        <small class="text-danger">No open Fiscal Year. Create one first.</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expense Economic Code <span class="text-danger">*</span></label>
                    <select name="economic_code_id" id="economic_code_id" class="form-select" required>
                        <option value="">Select Economic Code</option>
                        @foreach($economicCodes as $code)
                            <option value="{{ $code->id }}"
                                data-type="{{ $code->account_type }}"
                                {{ old('economic_code_id') === $code->id ? 'selected' : '' }}>
                                {{ $code->code }} — {{ $code->name }} ({{ ucfirst($code->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Original Budget (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="original_budget" class="form-control" value="{{ old('original_budget') }}" placeholder="20000000" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Supplementary Budget (₦)</label>
                    <input type="number" step="0.01" name="supplementary_budget" class="form-control" value="{{ old('supplementary_budget', 0) }}" placeholder="0">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Create Budget</button>
                    <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
