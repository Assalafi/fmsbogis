@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <x-page-header title="Reports" :breadcrumbs="['Reports' => null]" />

    @php
        $reportGroups = [
            'BUDGET REPORTS' => [
                'budget-report' => 'Approved Budget / Revised Budget / Available Budget',
                'virement-report' => 'Virement Report',
            ],
            'RECEIPT REPORTS' => [
                'receipt-register' => 'Receipt Register',
            ],
            'PAYMENT REPORTS' => [
                'payment-register' => 'Payment Register',
            ],
            'CASHBOOK REPORTS' => [
                'account-statement' => 'Account Statement (Cashbook)',
            ],
        ];
    @endphp

    @foreach($reportGroups as $group => $reports)
        <h4 class="mb-3">{{ $group }}</h4>
        <div class="row">
            @foreach($reports as $slug => $description)
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-0 p-4 bg-white rounded-3 h-100">
                        <div class="wh-45 lh-45 rounded-3 text-center bg-primary bg-opacity-10 mb-3">
                            <i class="material-symbols-outlined text-primary">description</i>
                        </div>
                        <h5 class="mb-2">{{ \Illuminate\Support\Str::of($slug)->replace('-', ' ')->title() }}</h5>
                        <p class="fs-13 text-secondary mb-3">{{ $description }}</p>
                        <a href="{{ route('reports.show', $slug) }}" class="btn btn-sm btn-primary w-100">Open Report</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
@endsection
