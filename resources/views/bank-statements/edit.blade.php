@extends('layouts.app')

@section('title', 'Edit Bank Statement')

@section('content')
    <x-page-header title="Edit Bank Statement" :breadcrumbs="['Bank Statements' => route('bank-statements.index'), $statement->account->account_name => route('bank-statements.show', $statement), 'Edit' => null]">
        <a href="{{ route('bank-statements.show', $statement) }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />
        <form method="POST" action="{{ route('bank-statements.update', $statement) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('bank-statements.partials.form', ['statement' => $statement])
        </form>
    </div>
@endsection
