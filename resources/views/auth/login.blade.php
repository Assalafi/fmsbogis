<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Login — BOGIS Finance</title>

        @include('partials.styles')
    </head>
    <body class="boxed-size bg-white">
        @include('partials.preloader')

        <div class="container">
            <div class="main-content d-flex flex-column p-0">
                <div class="m-auto m-1230">
                    <div class="row align-items-center">
                        <div class="col-lg-6 d-none d-lg-block">
                            <img src="/assets/images/login.jpg" class="rounded-3" alt="login">
                        </div>
                        <div class="col-lg-6">
                            <div class="mw-480 ms-lg-auto">
                                <a href="{{ route('login') }}" class="d-inline-block mb-4">
                                    <img src="/assets/images/logo-icon.png" class="wh-40" alt="logo">
                                </a>
                                <h3 class="fs-28 mb-2">Welcome to BOGIS Finance</h3>
                                <p class="fw-medium fs-16 mb-4">Borno State Geographic Information Service — Finance Management System</p>

                                @if($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show">
                                        <ul class="mb-0">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('login.store') }}">
                                    @csrf
                                    <div class="form-group mb-4">
                                        <label class="label text-secondary">Email Address</label>
                                        <input type="email" name="email" class="form-control h-55 @error('email') is-invalid @enderror" placeholder="name@bogis.gov.ng" value="{{ old('email') }}" required autofocus>
                                    </div>
                                    <div class="form-group mb-4">
                                        <label class="label text-secondary">Password</label>
                                        <input type="password" name="password" class="form-control h-55" placeholder="Type password" required>
                                    </div>
                                    <div class="form-group mb-4">
                                        <button type="submit" class="btn btn-primary fw-medium py-2 px-3 w-100">
                                            <div class="d-flex align-items-center justify-content-center py-1">
                                                <i class="material-symbols-outlined text-white fs-20 me-2">login</i>
                                                <span>Login</span>
                                            </div>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button class="theme-settings-btn p-0 border-0 bg-transparent position-absolute" style="right: 30px; bottom: 30px;" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling">
            <i class="material-symbols-outlined bg-primary wh-35 lh-35 text-white rounded-1" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Click On Theme Settings">settings</i>
        </button>

        @include('partials.theme_settings')
        @include('partials.scripts')
    </body>
</html>
