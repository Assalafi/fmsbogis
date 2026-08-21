<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use App\Models\CashbookEntry;
use App\Exports\ArrayExport;
use App\Services\ReconciliationService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $query = BankReconciliation::with(['account', 'bankStatement', 'preparer', 'approver']);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reconciliations = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('reconciliations.index', [
            'reconciliations' => $reconciliations,
            'accounts' => Account::orderBy('account_name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $accounts = Account::active()->orderBy('account_name')->get();

        $accountId = $request->filled('account_id') ? $request->account_id : $accounts->first()?->id;
        $account = Account::find($accountId);

        $statements = BankStatement::where('account_id', $accountId)
            ->whereNotIn('status', ['reconciled'])
            ->doesntHave('reconciliations')
            ->orderBy('statement_to', 'desc')
            ->get();

        return view('reconciliations.create', compact('accounts', 'account', 'statements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'bank_statement_id' => ['required', 'uuid', 'exists:bank_statements,id'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $statement = BankStatement::findOrFail($data['bank_statement_id']);

        $reconciliation = app(ReconciliationService::class)->createFor($account, $statement);

        return redirect()->route('reconciliations.show', $reconciliation)->with($this->toast('Reconciliation draft created with auto-matches.'));
    }

    public function show(BankReconciliation $reconciliation)
    {
        $reconciliation->load(['account', 'bankStatement', 'preparer', 'approver', 'items.cashbookEntry', 'items.bankStatementLine']);

        $cashbookEntries = CashbookEntry::with('economicCode')
            ->where('account_id', $reconciliation->account_id)
            ->whereBetween('date', [$reconciliation->bankStatement->statement_from, $reconciliation->bankStatement->statement_to])
            ->orderBy('date')
            ->get();

        $statementLines = $reconciliation->bankStatement->lines()->orderBy('date_of_transaction')->get();

        $matchedLineIds = $reconciliation->items()->whereNotNull('bank_statement_line_id')->pluck('bank_statement_line_id')->all();
        $matchedEntryIds = $reconciliation->items()->whereNotNull('cashbook_entry_id')->pluck('cashbook_entry_id')->all();

        return view('reconciliations.show', compact(
            'reconciliation',
            'cashbookEntries',
            'statementLines',
            'matchedLineIds',
            'matchedEntryIds'
        ));
    }

    public function match(Request $request, BankReconciliation $reconciliation)
    {
        abort_if($reconciliation->isApproved(), 403, 'This reconciliation is approved and locked.');

        $data = $request->validate([
            'cashbook_entry_id' => ['required', 'uuid', 'exists:cashbook_entries,id'],
            'bank_statement_line_id' => ['required', 'uuid', 'exists:bank_statement_lines,id'],
        ]);

        $entry = CashbookEntry::findOrFail($data['cashbook_entry_id']);
        $line = $reconciliation->bankStatement->lines()->findOrFail($data['bank_statement_line_id']);

        BankReconciliationItem::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'cashbook_entry_id' => $entry->id,
            'bank_statement_line_id' => $line->id,
            'item_type' => 'matched',
            'amount' => $entry->receipt_amount > 0 ? $entry->receipt_amount : $entry->payment_amount,
        ]);

        $line->update(['match_status' => 'matched']);

        app(ReconciliationService::class)->recalculate($reconciliation);

        return back()->with($this->toast('Items matched.'));
    }

    public function unmatch(BankReconciliation $reconciliation, BankReconciliationItem $item)
    {
        abort_if($reconciliation->isApproved(), 403, 'This reconciliation is approved and locked.');

        if ($item->bankStatementLine) {
            $item->bankStatementLine->update(['match_status' => 'unmatched']);
        }

        $item->delete();

        app(ReconciliationService::class)->recalculate($reconciliation);

        return back()->with($this->toast('Match removed.'));
    }

    public function markOutstanding(BankReconciliation $reconciliation, CashbookEntry $entry)
    {
        abort_if($reconciliation->isApproved(), 403, 'This reconciliation is approved and locked.');

        BankReconciliationItem::firstOrCreate(
            ['bank_reconciliation_id' => $reconciliation->id, 'cashbook_entry_id' => $entry->id],
            [
                'item_type' => 'cashbook_only',
                'amount' => $entry->receipt_amount > 0 ? $entry->receipt_amount : $entry->payment_amount,
                'notes' => $entry->details,
            ]
        );

        app(ReconciliationService::class)->recalculate($reconciliation);

        return back()->with($this->toast('Item marked as outstanding.'));
    }

    public function markBankOnly(BankReconciliation $reconciliation, $lineId)
    {
        abort_if($reconciliation->isApproved(), 403, 'This reconciliation is approved and locked.');

        $line = $reconciliation->bankStatement->lines()->findOrFail($lineId);

        BankReconciliationItem::firstOrCreate(
            ['bank_reconciliation_id' => $reconciliation->id, 'bank_statement_line_id' => $line->id],
            [
                'item_type' => 'bank_only',
                'amount' => $line->amount(),
                'notes' => $line->description,
            ]
        );

        $line->update(['match_status' => 'bank_only']);

        app(ReconciliationService::class)->recalculate($reconciliation);

        return back()->with($this->toast('Item marked as bank-only.'));
    }

    public function addAdjustment(Request $request, BankReconciliation $reconciliation)
    {
        abort_if($reconciliation->isApproved(), 403, 'This reconciliation is approved and locked.');

        $data = $request->validate([
            'amount' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        BankReconciliationItem::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'item_type' => 'bank_adjustment',
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? 'Bank adjustment',
        ]);

        app(ReconciliationService::class)->recalculate($reconciliation);

        return back()->with($this->toast('Adjustment added.'));
    }

    public function approve(BankReconciliation $reconciliation)
    {
        try {
            app(ReconciliationService::class)->approve($reconciliation);
        } catch (\DomainException $e) {
            return back()->with($this->toast($e->getMessage(), 'danger'));
        }

        return back()->with($this->toast('Reconciliation approved and locked.'));
    }

    public function print(BankReconciliation $reconciliation)
    {
        $reconciliation->load(['account', 'bankStatement', 'preparer', 'approver', 'items.cashbookEntry', 'items.bankStatementLine']);

        $pdf = Pdf::loadView('reconciliations.print', compact('reconciliation'))->setPaper('a4');

        return $pdf->stream('reconciliation-'.$reconciliation->account->account_name.'.pdf');
    }

    public function destroy(BankReconciliation $reconciliation)
    {
        if ($reconciliation->isApproved()) {
            return back()->with($this->toast('An approved reconciliation cannot be deleted.', 'danger'));
        }

        foreach ($reconciliation->items as $item) {
            if ($item->bankStatementLine) {
                $item->bankStatementLine->update(['match_status' => 'unmatched']);
            }
        }

        $reconciliation->items()->delete();
        $reconciliation->delete();

        return redirect()->route('reconciliations.index')->with($this->toast('Draft reconciliation deleted.'));
    }

    public function excel(BankReconciliation $reconciliation)
    {
        $reconciliation->load(['account', 'bankStatement', 'preparer', 'approver', 'items.cashbookEntry', 'items.bankStatementLine']);

        $headings = ['Type', 'Source', 'Notes', 'Amount (₦)'];

        $rows = $reconciliation->items->map(function (BankReconciliationItem $item) {
            $source = '—';
            if ($item->cashbookEntry) {
                $source = 'Cashbook: '.$item->cashbookEntry->reference;
            } elseif ($item->bankStatementLine) {
                $source = 'Bank: '.($item->bankStatementLine->reference ?? $item->bankStatementLine->description);
            }

            return [
                ucfirst(str_replace('_', ' ', $item->item_type)),
                $source,
                $item->notes,
                (float) $item->amount,
            ];
        })->values()->all();

        $rows[] = [];
        $rows[] = ['', 'Cashbook Balance', '', (float) $reconciliation->cashbook_balance];
        $rows[] = ['', 'Bank Statement Balance', '', (float) $reconciliation->bank_statement_balance];
        $rows[] = ['', 'Adjusted Cashbook Balance', '', (float) $reconciliation->adjusted_cashbook_balance];
        $rows[] = ['', 'Adjusted Bank Balance', '', (float) $reconciliation->adjusted_bank_balance];
        $rows[] = ['', 'Difference', '', (float) $reconciliation->difference];

        $filename = 'reconciliation-'.str_replace([' ', '/'], '-', $reconciliation->account->account_name).'-'.($reconciliation->reconciliation_date->format('Y-m-d'));

        return Excel::download(new ArrayExport($headings, $rows, 'Reconciliation'), $filename.'.xlsx');
    }
}
