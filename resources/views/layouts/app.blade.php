<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $metaOrg = \App\Models\Setting::get('organization_name', 'BOGIS Finance');
            $metaDescription = \App\Models\Setting::get('meta_description', 'BOGIS Finance Management System — budget control, receipts, payments, cashbook, bank reconciliation, performance and reports for Borno State Geographic Information Service.');
            $metaKeywords = \App\Models\Setting::get('meta_keywords', 'BOGIS, Borno State, finance management, budget, receipts, payments, cashbook, bank reconciliation, treasury');
            $metaLogo = \App\Models\Setting::get('organization_logo')
                ? \Illuminate\Support\Facades\Storage::disk('uploads')->url(\App\Models\Setting::get('organization_logo'))
                : url('/assets/images/logo-icon.png');
        @endphp

        <title>@yield('title', 'Dashboard') — {{ $metaOrg }}</title>

        <meta name="description" content="{{ $metaDescription }}">
        <meta name="keywords" content="{{ $metaKeywords }}">
        <meta name="author" content="{{ $metaOrg }}">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#075629">
        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $metaOrg }}">
        <meta property="og:title" content="@yield('title', 'Dashboard') — {{ $metaOrg }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $metaLogo }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="@yield('title', 'Dashboard') — {{ $metaOrg }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $metaLogo }}">

        @include('partials.styles')
        @stack('styles')
    </head>
    <body class="boxed-size">
        @include('partials.preloader')
        @include('partials.sidebar')

        <div class="container-fluid">
            <div class="main-content d-flex flex-column">
                @include('partials.header')

                <div class="main-content-container overflow-hidden">
                    @if(session('toast'))
                        <div class="alert alert-{{ session('toast.type') }} alert-dismissible fade show mt-3">
                            {{ session('toast.message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>

                <div class="flex-grow-1"></div>

                @include('partials.footer')
            </div>
        </div>

        @include('partials.theme_settings')
        @include('partials.scripts')
        @stack('scripts')
    </body>
</html>
