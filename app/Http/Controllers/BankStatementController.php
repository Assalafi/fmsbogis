<?php

namespace App\Http\Controllers;

use App\Imports\BankStatementImport;
use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Services\AuditService;
use App\Support\Money;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BankStatementController extends Controller
{
    public function index(Request $request)
    {
        $query = BankStatement::with(['account', 'uploader']);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $statements = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('bank-statements.index', [
            'statements' => $statements,
            'accounts' => Account::orderBy('account_name')->get(),
        ]);
    }

    public function create()
    {
        return view('bank-statements.create', [
            'accounts' => Account::active()->orderBy('account_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'statement_from' => ['required', 'date'],
            'statement_to' => ['required', 'date', 'after_or_equal:statement_from'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'file' => ['nullable', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
        ]);

        $statement = BankStatement::create($data + [
            'status' => 'draft',
            'uploaded_by' => auth()->id(),
        ]);

        if ($request->hasFile('file')) {
            try {
                Excel::import(
                    new BankStatementImport($statement),
                    $request->file('file')
                );

                $statement->update(['status' => 'imported']);

                if ($statement->lines()->count() === 0) {
                    return back()->withInput()->withErrors(['file' => 'No rows found in the uploaded file.']);
                }

                $expectedDifference = Money::sub($statement->closing_balance, $statement->opening_balance);
                $actualDifference = Money::sub(
                    $statement->lines()->sum('credit'),
                    $statement->lines()->sum('debit')
                );

                if (Money::compare($expectedDifference, $actualDifference) !== 0) {
                    return back()->withInput()->withErrors(['closing_balance' => 'The closing balance does not match the imported lines. Expected difference ₦'.Money::format($expectedDifference).', got ₦'.Money::format($actualDifference).'.']);
                }
            } catch (\Exception $e) {
                $statement->lines()->delete();

                return back()->withInput()->withErrors(['file' => 'Import failed: '.$e->getMessage()]);
            }
        } else {
            $statement->update(['status' => 'manual']);
        }

        app(AuditService::class)->log('Bank Statement Uploaded', $statement, null, [
            'account_id' => $statement->account_id,
            'statement_from' => $statement->statement_from,
            'statement_to' => $statement->statement_to,
            'closing_balance' => $statement->closing_balance,
            'lines' => $statement->lines()->count(),
        ]);

        return redirect()->route('bank-statements.show', $statement)->with($this->toast('Bank Statement saved.'));
    }

    public function show(BankStatement $statement)
    {
        $statement->load(['account', 'uploader', 'lines']);

        return view('bank-statements.show', compact('statement'));
    }

    public function destroy(BankStatement $statement)
    {
        abort_unless($statement->status === 'draft', 403, 'Only draft statements can be deleted.');

        $statement->delete();

        return redirect()->route('bank-statements.index')->with($this->toast('Bank Statement deleted.'));
    }
}
