@extends('layouts.app')

@section('title', 'Create Virement')

@section('content')
    <x-page-header title="Create Virement" :breadcrumbs="['Virements' => route('virements.index'), 'Create' => null]">
        <a href="{{ route('virements.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <div class="alert alert-info">
            <i class="material-symbols-outlined align-middle">info</i>
            A virement transfers approved budget from one Expense Economic Code to another. It does not move cash.
        </div>

        <form method="POST" action="{{ route('virements.store') }}">
            @csrf
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                    <select name="fiscal_year_id" class="form-select" required>
                        @foreach(\App\Models\FiscalYear::open()->orderBy('start_date')->get() as $fy)
                            <option value="{{ $fy->id }}" {{ $fiscalYear->id === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reference No. <span class="text-danger">*</span></label>
                    <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', 'VIR-'.strtoupper(\Illuminate\Support\Str::random(6))) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">From Economic Code <span class="text-danger">*</span></label>
                    <select name="from_economic_code_id" class="form-select" required>
                        <option value="">Select Source Code</option>
                        @foreach($economicCodes as $code)
                            <option value="{{ $code->id }}" {{ old('from_economic_code_id') === $code->id ? 'selected' : '' }}>
                                {{ $code->code }} — {{ $code->name }} ({{ ucfirst($code->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">To Economic Code <span class="text-danger">*</span></label>
                    <select name="to_economic_code_id" class="form-select" required>
                        <option value="">Select Destination Code</option>
                        @foreach($economicCodes as $code)
                            <option value="{{ $code->id }}" {{ old('to_economic_code_id') === $code->id ? 'selected' : '' }}>
                                {{ $code->code }} — {{ $code->name }} ({{ ucfirst($code->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required>{{ old('reason') }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">Create Virement</button>
                    <a href="{{ route('virements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
@endsection
