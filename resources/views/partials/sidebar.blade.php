<div class="sidebar-area" id="sidebar-area">
    <div class="logo position-relative">
        <a href="{{ route('dashboard') }}" class="d-block text-decoration-none position-relative">
            <img src="{{ \App\Models\Setting::get('organization_logo') ? \Illuminate\Support\Facades\Storage::disk('uploads')->url(\App\Models\Setting::get('organization_logo')) : '/assets/images/logo-icon.png' }}" alt="logo-icon" style="width: 60px; height: 60px; object-fit: contain;">

        </a>
        <button class="sidebar-burger-menu bg-transparent p-0 border-0 opacity-0 z-n1 position-absolute top-50 end-0 translate-middle-y" id="sidebar-burger-menu">
            <i data-feather="x"></i>
        </button>
    </div>

    <aside id="layout-menu" class="layout-menu menu-vertical menu active" data-simplebar>
        <ul class="menu-inner">

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">MAIN</span>
            </li>
            <li class="menu-item">
                <a href="{{ route('dashboard') }}" class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">dashboard</span>
                    <span class="title">Dashboard</span>
                </a>
            </li>

            @can('accounts.view')
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">MASTER DATA</span>
            </li>
            <li class="menu-item">
                <a href="{{ route('accounts.index') }}" class="menu-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">account_balance</span>
                    <span class="title">Accounts</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{ route('economic-codes.index') }}" class="menu-link {{ request()->routeIs('economic-codes.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">123</span>
                    <span class="title">Economic Codes</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="{{ route('fiscal-years.index') }}" class="menu-link {{ request()->routeIs('fiscal-years.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">calendar_month</span>
                    <span class="title">Fiscal Years</span>
                </a>
            </li>
            @endcan

            @can('budgets.view')
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">BUDGET</span>
            </li>
            <li class="menu-item">
                <a href="{{ route('budgets.index') }}" class="menu-link {{ request()->routeIs('budgets.index') || request()->routeIs('budgets.show') || request()->routeIs('budgets.create') || request()->routeIs('budgets.upload*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                    <span class="title">Approved Budgets</span>
                </a>
            </li>
            @can('budgets.create')
            <li class="menu-item">
                <a href="{{ route('budgets.upload') }}" class="menu-link {{ request()->routeIs('budgets.upload*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">upload_file</span>
                    <span class="title">Upload Budget</span>
                </a>
            </li>
            @endcan
            @can('budgets.approve')
            <li class="menu-item">
                <a href="{{ route('budgets.pending') }}" class="menu-link {{ request()->routeIs('budgets.pending') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">approval</span>
                    <span class="title">Budget Approval</span>
                </a>
            </li>
            @endcan
            @can('virements.view')
            <li class="menu-item">
                <a href="{{ route('virements.index') }}" class="menu-link {{ request()->routeIs('virements.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">swap_horiz</span>
                    <span class="title">Virements</span>
                </a>
            </li>
            @endcan
            @endcan

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">TRANSACTIONS</span>
            </li>
            @can('receipts.view')
            <li class="menu-item">
                <a href="{{ route('receipts.index') }}" class="menu-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">south_west</span>
                    <span class="title">Receipts</span>
                </a>
            </li>
            @endcan
            @can('payments.view')
            <li class="menu-item">
                <a href="{{ route('payments.index') }}" class="menu-link {{ request()->routeIs('payments.index') || request()->routeIs('payments.show') || request()->routeIs('payments.create') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">north_east</span>
                    <span class="title">Payments</span>
                </a>
            </li>
            @can('payments.approve')
            <li class="menu-item">
                <a href="{{ route('payments.approval') }}" class="menu-link {{ request()->routeIs('payments.approval') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">fact_check</span>
                    <span class="title">Payment Approval</span>
                </a>
            </li>
            @endcan
            @endcan

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">BANKING</span>
            </li>
            @can('cashbook.view')
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('cashbook.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">menu_book</span>
                    <span class="title">Cashbook</span>
                </a>
                <ul class="menu-sub">
                    @foreach(\App\Models\Account::active()->orderBy('account_name')->get() as $cbAccount)
                    <li class="menu-item">
                        <a href="{{ route('cashbook.show', $cbAccount) }}" class="menu-link">{{ $cbAccount->account_name }}</a>
                    </li>
                    @endforeach
                </ul>
            </li>
            @endcan
            @can('bank_statements.view')
            <li class="menu-item">
                <a href="{{ route('bank-statements.index') }}" class="menu-link {{ request()->routeIs('bank-statements.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">description</span>
                    <span class="title">Bank Statements</span>
                </a>
            </li>
            @endcan
            @can('bank_reconciliation.view')
            <li class="menu-item">
                <a href="{{ route('reconciliations.index') }}" class="menu-link {{ request()->routeIs('reconciliations.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">rule</span>
                    <span class="title">Bank Reconciliation</span>
                </a>
            </li>
            @endcan

            @can('performance.view')
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">PERFORMANCE</span>
            </li>
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle {{ request()->routeIs('performance.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">monitoring</span>
                    <span class="title">Performance</span>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('performance.revenue') }}" class="menu-link">Revenue Performance</a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('performance.expenditure') }}" class="menu-link">Expenditure Performance</a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('performance.economic-codes') }}" class="menu-link">Economic Code Performance</a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('performance.capital') }}" class="menu-link">Capital Performance</a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('performance.overhead') }}" class="menu-link">Overhead Performance</a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('performance.personnel') }}" class="menu-link">Personnel Performance</a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('performance.accounts') }}" class="menu-link">Account Performance</a>
                    </li>
                </ul>
            </li>
            @endcan

            @can('reports.view')
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">REPORTING</span>
            </li>
            <li class="menu-item">
                <a href="{{ route('reports.index') }}" class="menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">summarize</span>
                    <span class="title">Reports</span>
                </a>
            </li>
            @endcan

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">CONTROL</span>
            </li>
            @can('audit_logs.view')
            <li class="menu-item">
                <a href="{{ route('audit-logs.index') }}" class="menu-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">history</span>
                    <span class="title">Audit Logs</span>
                </a>
            </li>
            @endcan
            @can('users.view')
            <li class="menu-item">
                <a href="{{ route('users.index') }}" class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">group</span>
                    <span class="title">Users</span>
                </a>
            </li>
            @endcan
            @can('roles.view')
            <li class="menu-item">
                <a href="{{ route('roles.index') }}" class="menu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">admin_panel_settings</span>
                    <span class="title">Roles &amp; Permissions</span>
                </a>
            </li>
            @endcan
            @can('settings.view')
            <li class="menu-item">
                <a href="{{ route('settings.index') }}" class="menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">settings</span>
                    <span class="title">Settings</span>
                </a>
            </li>
            @endcan
        </ul>
    </aside>
</div>
