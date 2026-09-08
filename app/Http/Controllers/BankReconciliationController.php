<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use App\Services\ReconciliationService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $query = BankReconciliation::with(['account', 'bankStatement', 'preparer', 'approver'])
            ->withCount('items');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('reconciliation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('reconciliation_date', '<=', $request->date_to);
        }

        $reconciliations = $query->orderByDesc('reconciliation_date')->paginate(20)->withQueryString();

        return view('reconciliations.index', [
            'reconciliations' => $reconciliations,
            'accounts' => Account::orderBy('account_name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $accounts = Account::active()->orderBy('account_name')->get();
        $selectedStatement = $request->filled('statement_id')
            ? BankStatement::find($request->statement_id)
            : null;
        $accountId = $selectedStatement?->account_id
            ?? ($request->filled('account_id') ? $request->account_id : $accounts->first()?->id);
        $account = Account::find($accountId);

        $statements = BankStatement::where('account_id', $accountId)
            ->where('status', '!=', 'reconciled')
            ->doesntHave('reconciliations')
            ->orderByDesc('statement_to')
            ->get();

        return view('reconciliations.create', compact('accounts', 'account', 'statements', 'selectedStatement'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'bank_statement_id' => ['required', 'uuid', 'exists:bank_statements,id'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $statement = BankStatement::findOrFail($data['bank_statement_id']);

        try {
            $reconciliation = app(ReconciliationService::class)->createFor($account, $statement);
        } catch (\DomainException $exception) {
            return back()->withInput()->with($this->toast($exception->getMessage(), 'danger'));
        }

        return redirect()->route('reconciliations.show', $reconciliation)
            ->with($this->toast('Reconciliation draft created. Add any required reconciling adjustments.'));
    }

    public function show(BankReconciliation $reconciliation)
    {
        $service = app(ReconciliationService::class);
        if (! $reconciliation->isApproved()) {
            $service->recalculate($reconciliation);
            $reconciliation->refresh();
        }

        $reconciliation->load([
            'account',
            'bankStatement',
            'preparer',
            'approver',
            'items.cashbookEntry',
            'items.bankStatementLine',
        ]);

        $breakdown = $service->breakdown($reconciliation);
        $canApprove = Money::isZero($reconciliation->difference);

        return view('reconciliations.show', compact(
            'reconciliation',
            'breakdown',
            'canApprove',
        ));
    }

    public function unmatch(BankReconciliation $reconciliation, BankReconciliationItem $item)
    {
        $this->ensureDraft($reconciliation);
        abort_unless($item->bank_reconciliation_id === $reconciliation->id, 404);

        DB::transaction(function () use ($reconciliation, $item): void {
            if ($item->bankStatementLine) {
                $item->bankStatementLine->update(['match_status' => 'unmatched']);
            }

            $item->delete();
            app(ReconciliationService::class)->recalculate($reconciliation);
        });

        return back()->with($this->toast('Classification removed.'));
    }

    public function addAdjustment(Request $request, BankReconciliation $reconciliation)
    {
        $this->ensureDraft($reconciliation);

        $data = $request->validate([
            'adjustment_category' => ['required', Rule::in(array_keys(BankReconciliationItem::ADJUSTMENT_CATEGORIES))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $category = BankReconciliationItem::ADJUSTMENT_CATEGORIES[$data['adjustment_category']];

        BankReconciliationItem::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'item_type' => $category['type'],
            'amount' => $data['amount'],
            'notes' => $category['label'].(filled($data['notes'] ?? null) ? ': '.$data['notes'] : ''),
        ]);

        app(ReconciliationService::class)->recalculate($reconciliation);

        return back()->with($this->toast('Manual reconciliation adjustment added.'));
    }

    public function approve(BankReconciliation $reconciliation)
    {
        try {
            app(ReconciliationService::class)->approve($reconciliation);
        } catch (\DomainException $exception) {
            return back()->with($this->toast($exception->getMessage(), 'danger'));
        }

        return back()->with($this->toast('Reconciliation approved and locked.'));
    }

    public function print(BankReconciliation $reconciliation)
    {
        $service = app(ReconciliationService::class);
        if (! $reconciliation->isApproved()) {
            $service->recalculate($reconciliation);
            $reconciliation->refresh();
        }

        $reconciliation->load(['account', 'bankStatement', 'preparer', 'approver', 'items.cashbookEntry', 'items.bankStatementLine']);
        $breakdown = $service->breakdown($reconciliation);

        $pdf = Pdf::loadView('reconciliations.print', compact('reconciliation', 'breakdown'))->setPaper('a4');

        return $pdf->stream('reconciliation-'.$reconciliation->account->account_name.'.pdf');
    }

    public function destroy(BankReconciliation $reconciliation)
    {
        if ($reconciliation->isApproved()) {
            return back()->with($this->toast('An approved reconciliation cannot be deleted.', 'danger'));
        }

        DB::transaction(function () use ($reconciliation): void {
            $reconciliation->bankStatement->lines()->update(['match_status' => 'unmatched']);
            $reconciliation->items()->delete();
            $reconciliation->delete();
        });

        return redirect()->route('reconciliations.index')->with($this->toast('Draft reconciliation deleted.'));
    }

    public function excel(BankReconciliation $reconciliation)
    {
        $service = app(ReconciliationService::class);
        if (! $reconciliation->isApproved()) {
            $service->recalculate($reconciliation);
            $reconciliation->refresh();
        }

        $reconciliation->load(['account', 'bankStatement', 'preparer', 'approver', 'items.cashbookEntry', 'items.bankStatementLine']);
        $breakdown = $service->breakdown($reconciliation);

        $headings = ['Classification', 'Source', 'Effect', 'Notes', 'Amount (₦)'];
        $rows = $reconciliation->items->map(function (BankReconciliationItem $item) {
            $source = 'Manual adjustment';
            if ($item->cashbookEntry && $item->bankStatementLine) {
                $source = 'Cashbook '.$item->cashbookEntry->reference.' / Bank '.($item->bankStatementLine->reference ?? $item->bankStatementLine->description);
            } elseif ($item->cashbookEntry) {
                $source = 'Cashbook: '.($item->cashbookEntry->reference ?? $item->cashbookEntry->details);
            } elseif ($item->bankStatementLine) {
                $source = 'Bank: '.($item->bankStatementLine->reference ?? $item->bankStatementLine->description);
            }

            return [
                $item->displayType(),
                $source,
                $item->effectLabel(),
                $item->notes,
                (float) $item->amount,
            ];
        })->values()->all();

        $rows[] = [];
        $rows[] = ['', 'Cashbook Balance', '', '', (float) $reconciliation->cashbook_balance];
        $rows[] = ['', 'Add to Cashbook', '', '', (float) $breakdown['cashbook_additions']];
        $rows[] = ['', 'Less from Cashbook', '', '', (float) $breakdown['cashbook_deductions']];
        $rows[] = ['', 'Adjusted Cashbook Balance', '', '', (float) $reconciliation->adjusted_cashbook_balance];
        $rows[] = ['', 'Bank Statement Balance', '', '', (float) $reconciliation->bank_statement_balance];
        $rows[] = ['', 'Add to Bank Balance', '', '', (float) $breakdown['bank_additions']];
        $rows[] = ['', 'Less from Bank Balance', '', '', (float) $breakdown['bank_deductions']];
        $rows[] = ['', 'Adjusted Bank Balance', '', '', (float) $reconciliation->adjusted_bank_balance];
        $rows[] = ['', 'Difference', '', '', (float) $reconciliation->difference];

        $filename = 'reconciliation-'.str_replace([' ', '/'], '-', $reconciliation->account->account_name).'-'.$reconciliation->reconciliation_date->format('Y-m-d');

        return Excel::download(new ArrayExport($headings, $rows, 'Reconciliation'), $filename.'.xlsx');
    }

    private function ensureDraft(BankReconciliation $reconciliation): void
    {
        abort_if($reconciliation->isApproved(), 403, 'This reconciliation is approved and locked.');
    }
}
