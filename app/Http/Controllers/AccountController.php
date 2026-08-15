<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AuditService;
use App\Services\CashbookService;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::query();

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                    ->orWhere('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('account_name')->paginate(20)->withQueryString();

        return view('accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_type' => ['required', 'in:capital,overhead'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $account = Account::create($data + ['created_by' => auth()->id()]);

        app(AuditService::class)->log('Account Created', $account, null, $data);

        return redirect()->route('accounts.index')->with($this->toast('Account created successfully.'));
    }

    public function show(Account $account)
    {
        $cashbookService = app(CashbookService::class);
        $fiscalYear = ActiveFiscalYear::get();

        $summary = [
            'opening_balance' => $account->opening_balance,
            'total_receipts' => $account->receipts()->where('status', 'posted')->sum('amount'),
            'total_payments' => $account->payments()->where('status', 'paid')->sum('amount'),
            'cashbook_balance' => $cashbookService->closingBalance($account, $fiscalYear),
        ];

        return view('accounts.show', compact('account', 'summary'));
    }

    public function edit(Account $account)
    {
        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_type' => ['required', 'in:capital,overhead'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $old = $account->only(array_keys($data));
        $account->update($data);

        app(AuditService::class)->log('Account Updated', $account, $old, $data);

        return redirect()->route('accounts.index')->with($this->toast('Account updated successfully.'));
    }

    public function destroy(Account $account)
    {
        if ($account->receipts()->exists() || $account->payments()->exists()) {
            return back()->with($this->toast('Cannot delete account with transactions. Deactivate instead.', 'danger'));
        }

        $account->delete();

        app(AuditService::class)->log('Account Deleted', $account);

        return redirect()->route('accounts.index')->with($this->toast('Account deleted.'));
    }
}
