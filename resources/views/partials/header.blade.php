<header class="header-area bg-white mb-4 rounded-bottom-15" id="header-area">
    <div class="row align-items-center">
        <div class="col-lg-4 col-sm-6">
            <div class="left-header-content">
                <ul class="d-flex align-items-center ps-0 mb-0 list-unstyled justify-content-center justify-content-sm-start">
                    <li>
                        <button class="header-burger-menu bg-transparent p-0 border-0" id="header-burger-menu">
                            <span class="material-symbols-outlined">menu</span>
                        </button>
                    </li>
                    <li>
                        <span class="fw-semibold fs-15 text-secondary d-none d-md-inline">BOGIS Finance Management System</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8 col-sm-6">
            <div class="right-header-content mt-2 mt-sm-0">
                <ul class="d-flex align-items-center justify-content-center justify-content-sm-end ps-0 mb-0 list-unstyled">
                    <li class="header-right-item me-2">
                        <form id="fy-form" method="POST" action="{{ route('fiscal-years.set-active', \App\Support\ActiveFiscalYear::get()?->id ?? '0') }}">
                            @csrf
                            <div class="d-flex align-items-center">
                                <span class="material-symbols-outlined text-body me-1">event_note</span>
                                <select name="fy" class="form-select form-select-sm w-auto" style="min-width: 130px;"
                                    onchange="this.closest('form').action = this.selectedOptions[0].dataset.url; this.closest('form').submit();">
                                    @foreach(\App\Models\FiscalYear::orderBy('start_date', 'desc')->get() as $fy)
                                        <option value="{{ $fy->id }}"
                                            data-url="{{ route('fiscal-years.set-active', $fy) }}"
                                            @if($fy->id === \App\Support\ActiveFiscalYear::id()) selected @endif>
                                            FY {{ $fy->name }} @if($fy->status === 'open')(Open)@else(Closed)@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </li>
                    <li class="header-right-item">
                        <div class="light-dark">
                            <button class="switch-toggle settings-btn dark-btn p-0 bg-transparent" id="switch-toggle">
                                <span class="dark"><i class="material-symbols-outlined">light_mode</i></span>
                                <span class="light"><i class="material-symbols-outlined">dark_mode</i></span>
                            </button>
                        </div>
                    </li>
                    <li class="header-right-item">
                        <div class="dropdown admin-profile">
                            <div class="d-xxl-flex align-items-center bg-transparent border-0 text-start p-0 cursor dropdown-toggle" data-bs-toggle="dropdown">
                                <div class="flex-shrink-0">
                                    <img class="rounded-circle wh-40 administrator" src="/assets/images/administrator.jpg" alt="admin">
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-none d-xxl-block">
                                            <div class="d-flex align-content-center">
                                                <h3>{{ auth()->user()->name }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown-menu border-0 bg-white dropdown-menu-end">
                                <div class="d-flex align-items-center info">
                                    <div class="flex-shrink-0">
                                        <img class="rounded-circle wh-30 administrator" src="/assets/images/administrator.jpg" alt="admin">
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <h3 class="fw-medium">{{ auth()->user()->name }}</h3>
                                        <span class="fs-12">{{ auth()->user()->roles->pluck('name')->implode(', ') }}</span>
                                    </div>
                                </div>
                                <ul class="admin-link ps-0 mb-0 list-unstyled">
                                    @can('settings.view')
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center text-body" href="{{ route('settings.index') }}">
                                            <i class="material-symbols-outlined">settings</i>
                                            <span class="ms-2">Settings</span>
                                        </a>
                                    </li>
                                    @endcan
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center text-body border-0 bg-transparent w-100">
                                                <i class="material-symbols-outlined">logout</i>
                                                <span class="ms-2">Logout</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                    <li class="header-right-item">
                        <button class="theme-settings-btn p-0 border-0 bg-transparent" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasScrolling" aria-controls="offcanvasScrolling">
                            <i class="material-symbols-outlined" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Click On Theme Settings">settings</i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
