@php
    $masterDataActive = request()->routeIs('accounts.*', 'economic-codes.*', 'fiscal-years.*');
    $budgetActive = request()->routeIs('budgets.*', 'virements.*');
    $transactionsActive = request()->routeIs('receipts.*', 'payments.*');
    $cashbookActive = request()->routeIs('cashbook.*');
    $bankingActive = $cashbookActive || request()->routeIs('bank-statements.*', 'reconciliations.*');
    $performanceActive = request()->routeIs('performance.*');
    $reportingActive = request()->routeIs('reports.*');
    $controlActive = request()->routeIs('audit-logs.*', 'users.*', 'roles.*', 'settings.*');
    $activeCashbookAccountId = $cashbookActive
        ? data_get(request()->route('account'), 'id', request()->route('account'))
        : null;
@endphp

<div class="sidebar-area" id="sidebar-area">
    <div class="logo position-relative">
        <a href="{{ route('dashboard') }}" class="d-block text-decoration-none position-relative">
            <img src="{{ \App\Models\Setting::get('organization_logo') ? \Illuminate\Support\Facades\Storage::disk('uploads')->url(\App\Models\Setting::get('organization_logo')) : '/assets/images/logo-icon.png' }}" alt="logo-icon" style="width: 60px; height: 60px; object-fit: contain;">
        </a>
        <button class="sidebar-burger-menu bg-transparent p-0 border-0 opacity-0 z-n1 position-absolute top-50 end-0 translate-middle-y" id="sidebar-burger-menu" type="button" aria-label="Close sidebar">
            <i data-feather="x"></i>
        </button>
    </div>

    <aside id="layout-menu" class="layout-menu menu-vertical menu active" data-simplebar>
        <ul class="menu-inner">
            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">MAIN</span>
            </li>
            <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}" class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="material-symbols-outlined menu-icon">dashboard</span>
                    <span class="title">Dashboard</span>
                </a>
            </li>

            <li class="menu-title small text-uppercase">
                <span class="menu-title-text">NAVIGATION</span>
            </li>

            @canany(['accounts.view', 'economic_codes.view', 'fiscal_years.view'])
                <li class="menu-item {{ $masterDataActive ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ $masterDataActive ? 'active' : '' }}" role="button" aria-expanded="{{ $masterDataActive ? 'true' : 'false' }}" aria-controls="sidebar-master-data">
                        <span class="material-symbols-outlined menu-icon">database</span>
                        <span class="title">Master Data</span>
                    </a>
                    <ul class="menu-sub" id="sidebar-master-data">
                        @can('accounts.view')
                            <li class="menu-item {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                                <a href="{{ route('accounts.index') }}" class="menu-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">Accounts</a>
                            </li>
                        @endcan
                        @can('economic_codes.view')
                            <li class="menu-item {{ request()->routeIs('economic-codes.*') ? 'active' : '' }}">
                                <a href="{{ route('economic-codes.index') }}" class="menu-link {{ request()->routeIs('economic-codes.*') ? 'active' : '' }}">Economic Codes</a>
                            </li>
                        @endcan
                        @can('fiscal_years.view')
                            <li class="menu-item {{ request()->routeIs('fiscal-years.*') ? 'active' : '' }}">
                                <a href="{{ route('fiscal-years.index') }}" class="menu-link {{ request()->routeIs('fiscal-years.*') ? 'active' : '' }}">Fiscal Years</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['budgets.view', 'virements.view'])
                <li class="menu-item {{ $budgetActive ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ $budgetActive ? 'active' : '' }}" role="button" aria-expanded="{{ $budgetActive ? 'true' : 'false' }}" aria-controls="sidebar-budget">
                        <span class="material-symbols-outlined menu-icon">account_balance_wallet</span>
                        <span class="title">Budget</span>
                    </a>
                    <ul class="menu-sub" id="sidebar-budget">
                        @can('budgets.view')
                            <li class="menu-item {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                                <a href="{{ route('budgets.index') }}" class="menu-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}">Approved Budgets</a>
                            </li>
                        @endcan
                        @can('virements.view')
                            <li class="menu-item {{ request()->routeIs('virements.*') ? 'active' : '' }}">
                                <a href="{{ route('virements.index') }}" class="menu-link {{ request()->routeIs('virements.*') ? 'active' : '' }}">Virements</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['receipts.view', 'payments.view', 'payments.approve'])
                <li class="menu-item {{ $transactionsActive ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ $transactionsActive ? 'active' : '' }}" role="button" aria-expanded="{{ $transactionsActive ? 'true' : 'false' }}" aria-controls="sidebar-transactions">
                        <span class="material-symbols-outlined menu-icon">receipt_long</span>
                        <span class="title">Transactions</span>
                    </a>
                    <ul class="menu-sub" id="sidebar-transactions">
                        @can('receipts.view')
                            <li class="menu-item {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                                <a href="{{ route('receipts.index') }}" class="menu-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">Receipts</a>
                            </li>
                        @endcan
                        @can('payments.view')
                            <li class="menu-item {{ request()->routeIs('payments.index', 'payments.show', 'payments.create') ? 'active' : '' }}">
                                <a href="{{ route('payments.index') }}" class="menu-link {{ request()->routeIs('payments.index', 'payments.show', 'payments.create') ? 'active' : '' }}">Payments</a>
                            </li>
                        @endcan
                        @can('payments.approve')
                            <li class="menu-item {{ request()->routeIs('payments.approval') ? 'active' : '' }}">
                                <a href="{{ route('payments.approval') }}" class="menu-link {{ request()->routeIs('payments.approval') ? 'active' : '' }}">Payment Approval</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @canany(['cashbook.view', 'bank_statements.view', 'bank_reconciliation.view'])
                <li class="menu-item {{ $bankingActive ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ $bankingActive ? 'active' : '' }}" role="button" aria-expanded="{{ $bankingActive ? 'true' : 'false' }}" aria-controls="sidebar-banking">
                        <span class="material-symbols-outlined menu-icon">account_balance</span>
                        <span class="title">Banking</span>
                    </a>
                    <ul class="menu-sub" id="sidebar-banking">
                        @can('cashbook.view')
                            <li class="menu-item {{ $cashbookActive ? 'open active' : '' }}">
                                <a href="javascript:void(0);" class="menu-link menu-toggle {{ $cashbookActive ? 'active' : '' }}" role="button" aria-expanded="{{ $cashbookActive ? 'true' : 'false' }}" aria-controls="sidebar-cashbooks">
                                    <span class="title">Cashbook</span>
                                </a>
                                <ul class="menu-sub" id="sidebar-cashbooks">
                                    @forelse(\App\Models\Account::active()->orderBy('account_name')->get() as $cbAccount)
                                        @php
                                            $cashbookAccountActive = (string) $activeCashbookAccountId === (string) $cbAccount->getKey();
                                        @endphp
                                        <li class="menu-item {{ $cashbookAccountActive ? 'active' : '' }}">
                                            <a href="{{ route('cashbook.show', $cbAccount) }}" class="menu-link {{ $cashbookAccountActive ? 'active' : '' }}">{{ $cbAccount->account_name }}</a>
                                        </li>
                                    @empty
                                        <li class="menu-item">
                                            <span class="menu-link text-muted">No active account</span>
                                        </li>
                                    @endforelse
                                </ul>
                            </li>
                        @endcan
                        @can('bank_statements.view')
                            <li class="menu-item {{ request()->routeIs('bank-statements.*') ? 'active' : '' }}">
                                <a href="{{ route('bank-statements.index') }}" class="menu-link {{ request()->routeIs('bank-statements.*') ? 'active' : '' }}">Bank Statements</a>
                            </li>
                        @endcan
                        @can('bank_reconciliation.view')
                            <li class="menu-item {{ request()->routeIs('reconciliations.*') ? 'active' : '' }}">
                                <a href="{{ route('reconciliations.index') }}" class="menu-link {{ request()->routeIs('reconciliations.*') ? 'active' : '' }}">Bank Reconciliation</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            @can('performance.view')
                <li class="menu-item {{ $performanceActive ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ $performanceActive ? 'active' : '' }}" role="button" aria-expanded="{{ $performanceActive ? 'true' : 'false' }}" aria-controls="sidebar-performance">
                        <span class="material-symbols-outlined menu-icon">monitoring</span>
                        <span class="title">Performance</span>
                    </a>
                    <ul class="menu-sub" id="sidebar-performance">
                        <li class="menu-item {{ request()->routeIs('performance.revenue') ? 'active' : '' }}">
                            <a href="{{ route('performance.revenue') }}" class="menu-link {{ request()->routeIs('performance.revenue') ? 'active' : '' }}">Revenue Performance</a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('performance.expenditure') ? 'active' : '' }}">
                            <a href="{{ route('performance.expenditure') }}" class="menu-link {{ request()->routeIs('performance.expenditure') ? 'active' : '' }}">Expenditure Performance</a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('performance.economic-codes') ? 'active' : '' }}">
                            <a href="{{ route('performance.economic-codes') }}" class="menu-link {{ request()->routeIs('performance.economic-codes') ? 'active' : '' }}">Economic Code Performance</a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('performance.capital') ? 'active' : '' }}">
                            <a href="{{ route('performance.capital') }}" class="menu-link {{ request()->routeIs('performance.capital') ? 'active' : '' }}">Capital Performance</a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('performance.overhead') ? 'active' : '' }}">
                            <a href="{{ route('performance.overhead') }}" class="menu-link {{ request()->routeIs('performance.overhead') ? 'active' : '' }}">Overhead Performance</a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('performance.personnel') ? 'active' : '' }}">
                            <a href="{{ route('performance.personnel') }}" class="menu-link {{ request()->routeIs('performance.personnel') ? 'active' : '' }}">Personnel Performance</a>
                        </li>
                        <li class="menu-item {{ request()->routeIs('performance.accounts') ? 'active' : '' }}">
                            <a href="{{ route('performance.accounts') }}" class="menu-link {{ request()->routeIs('performance.accounts') ? 'active' : '' }}">Account Performance</a>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('reports.view')
                <li class="menu-item {{ $reportingActive ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ $reportingActive ? 'active' : '' }}" role="button" aria-expanded="{{ $reportingActive ? 'true' : 'false' }}" aria-controls="sidebar-reporting">
                        <span class="material-symbols-outlined menu-icon">summarize</span>
                        <span class="title">Reporting</span>
                    </a>
                    <ul class="menu-sub" id="sidebar-reporting">
                        <li class="menu-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <a href="{{ route('reports.index') }}" class="menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">Reports</a>
                        </li>
                    </ul>
                </li>
            @endcan

            @canany(['audit_logs.view', 'users.view', 'roles.view', 'settings.view'])
                <li class="menu-item {{ $controlActive ? 'open active' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle {{ $controlActive ? 'active' : '' }}" role="button" aria-expanded="{{ $controlActive ? 'true' : 'false' }}" aria-controls="sidebar-control">
                        <span class="material-symbols-outlined menu-icon">admin_panel_settings</span>
                        <span class="title">Administration</span>
                    </a>
                    <ul class="menu-sub" id="sidebar-control">
                        @can('audit_logs.view')
                            <li class="menu-item {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                                <a href="{{ route('audit-logs.index') }}" class="menu-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">Audit Logs</a>
                            </li>
                        @endcan
                        @can('users.view')
                            <li class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <a href="{{ route('users.index') }}" class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Users</a>
                            </li>
                        @endcan
                        @can('roles.view')
                            <li class="menu-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                <a href="{{ route('roles.index') }}" class="menu-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">Roles &amp; Permissions</a>
                            </li>
                        @endcan
                        @can('settings.view')
                            <li class="menu-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                                <a href="{{ route('settings.index') }}" class="menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">Settings</a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
        </ul>
    </aside>
</div>
