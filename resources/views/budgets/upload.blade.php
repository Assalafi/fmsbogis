@extends('layouts.app')

@section('title', 'Upload Approved Budget')

@section('content')
    <x-page-header title="Upload Approved Budget" :breadcrumbs="['Budgets' => route('budgets.index'), 'Upload' => null]">
        <a href="{{ route('budgets.upload.template') }}" class="btn btn-secondary">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Download Template
        </a>
        <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <div class="alert alert-info">
            <i class="material-symbols-outlined align-middle">info</i>
            Upload multiple approved budgets at once. The file must have two columns:
            <strong>economic_code</strong> and <strong>amount</strong>. Only active Expense Economic
            Codes are accepted, and a budget must not already exist for the same code in the selected Fiscal Year.
            Imported budgets are created as <strong>Approved</strong> immediately.
        </div>

        <form method="POST" action="{{ route('budgets.upload.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                    <select name="fiscal_year_id" class="form-select" required>
                        @foreach($fiscalYears as $fy)
                            <option value="{{ $fy->id }}" {{ old('fiscal_year_id', $activeFiscalYearId) === $fy->id ? 'selected' : '' }}>
                                FY {{ $fy->name }} @if($fy->isOpen())(Open)@else(Closed)@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Defaulted to the active Fiscal Year.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">File <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required>
                    <small class="text-muted">CSV, XLSX or XLS. Max 5 MB.</small>
                </div>
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="max-width: 480px;">
                            <thead class="table-light">
                                <tr>
                                    <th>economic_code</th>
                                    <th>amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>12010101</code></td>
                                    <td><code>20000000</code></td>
                                </tr>
                                <tr>
                                    <td><code>22020101</code></td>
                                    <td><code>15000000</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4"
                        onclick="return confirm('Import these budgets as APPROVED?');">
                        <i class="material-symbols-outlined align-middle fs-18">upload</i>
                        Import Approved Budgets
                    </button>
                    <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
