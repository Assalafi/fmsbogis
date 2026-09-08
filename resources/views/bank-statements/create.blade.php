@extends('layouts.app')

@section('title', 'New Bank Statement')

@section('content')
    <x-page-header title="New Bank Statement" :breadcrumbs="['Bank Statements' => route('bank-statements.index'), 'New' => null]">
        <a href="{{ route('bank-statements.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="material-symbols-outlined">info</i>
        <div>
            <strong>The attachment is for record-keeping only.</strong>
            The system will not read or import transactions from it. Only the opening and closing balances are required.
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />
        <form method="POST" action="{{ route('bank-statements.store') }}" enctype="multipart/form-data">
            @csrf
            @include('bank-statements.partials.form')
        </form>
    </div>
@endsection
