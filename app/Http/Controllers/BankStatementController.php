<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankStatement;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BankStatementController extends Controller
{
    public function index(Request $request)
    {
        $query = BankStatement::with(['account', 'uploader'])
            ->withCount('reconciliations');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('statement_to', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('statement_from', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search): void {
                $builder->where('file_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('account', fn ($account) => $account->where('account_name', 'like', "%{$search}%"));
            });
        }

        $statements = $query->orderByDesc('statement_to')->paginate(20)->withQueryString();

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
        $data = $this->validateStatement($request);
        $this->ensurePeriodDoesNotOverlap($data);
        $attachment = $this->storeAttachment($request);

        try {
            $statement = BankStatement::create($data + $attachment + [
                'status' => 'manual',
                'uploaded_by' => auth()->id(),
            ]);
        } catch (\Throwable $exception) {
            if (isset($attachment['file_path'])) {
                Storage::disk('local')->delete($attachment['file_path']);
            }

            throw $exception;
        }

        app(AuditService::class)->log('Bank Statement Created', $statement, null, [
            'account_id' => $statement->account_id,
            'statement_from' => $statement->statement_from,
            'statement_to' => $statement->statement_to,
            'closing_balance' => $statement->closing_balance,
            'attachment' => $statement->file_name,
        ]);

        return redirect()->route('bank-statements.show', $statement)
            ->with($this->toast('Bank statement opening and closing balances saved.'));
    }

    public function show(BankStatement $statement)
    {
        $statement->load(['account', 'uploader', 'reconciliations']);

        return view('bank-statements.show', compact('statement'));
    }

    public function edit(BankStatement $statement)
    {
        abort_if($statement->isLocked(), 403, 'A reconciled bank statement cannot be edited.');
        abort_if($statement->reconciliations()->exists(), 403, 'Delete the draft reconciliation before changing statement details.');

        return view('bank-statements.edit', [
            'statement' => $statement,
            'accounts' => Account::active()->orderBy('account_name')->get(),
        ]);
    }

    public function update(Request $request, BankStatement $statement)
    {
        abort_if($statement->isLocked(), 403, 'A reconciled bank statement cannot be edited.');
        abort_if($statement->reconciliations()->exists(), 403, 'Delete the draft reconciliation before changing statement details.');

        $data = $this->validateStatement($request);
        $this->ensurePeriodDoesNotOverlap($data, $statement);
        $oldFilePath = $statement->file_path;
        $attachment = $request->hasFile('file') ? $this->storeAttachment($request) : [];

        if ($request->boolean('remove_file') && ! $request->hasFile('file')) {
            $attachment = [
                'file_path' => null,
                'file_name' => null,
                'file_mime' => null,
                'file_size' => null,
            ];
        }

        $statement->update($data + $attachment);

        if (($request->hasFile('file') || $request->boolean('remove_file')) && $oldFilePath) {
            Storage::disk('local')->delete($oldFilePath);
        }

        app(AuditService::class)->log('Bank Statement Updated', $statement, null, $data);

        return redirect()->route('bank-statements.show', $statement)->with($this->toast('Bank statement updated.'));
    }

    public function download(BankStatement $statement)
    {
        abort_unless($statement->hasAttachment(), 404, 'No supporting file is attached.');
        abort_unless(Storage::disk('local')->exists($statement->file_path), 404, 'The supporting file could not be found.');

        return Storage::disk('local')->download(
            $statement->file_path,
            $statement->file_name,
            ['Content-Type' => $statement->file_mime ?: 'application/octet-stream'],
        );
    }

    public function destroy(BankStatement $statement)
    {
        abort_if($statement->isLocked(), 403, 'A reconciled bank statement cannot be deleted.');
        abort_if($statement->reconciliations()->exists(), 403, 'Delete the draft reconciliation before deleting this statement.');

        $filePath = $statement->file_path;
        $statement->delete();

        if ($filePath) {
            Storage::disk('local')->delete($filePath);
        }

        return redirect()->route('bank-statements.index')->with($this->toast('Bank statement deleted.'));
    }

    private function validateStatement(Request $request): array
    {
        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'statement_from' => ['required', 'date'],
            'statement_to' => ['required', 'date', 'after_or_equal:statement_from'],
            'opening_balance' => ['required', 'numeric'],
            'closing_balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'file' => ['nullable', 'file', 'mimes:pdf,csv,xlsx,xls,jpg,jpeg,png', 'max:10240'],
        ]);

        // The uploaded file is stored separately; it is never parsed or
        // written into the bank_statements table as request data.
        unset($data['file']);

        return $data;
    }

    private function ensurePeriodDoesNotOverlap(array $data, ?BankStatement $except = null): void
    {
        $overlap = BankStatement::where('account_id', $data['account_id'])
            ->whereDate('statement_from', '<=', $data['statement_to'])
            ->whereDate('statement_to', '>=', $data['statement_from'])
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'statement_from' => 'A bank statement already covers part of this period for the selected account.',
            ]);
        }
    }

    private function storeAttachment(Request $request): array
    {
        if (! $request->hasFile('file')) {
            return [];
        }

        $file = $request->file('file');
        $path = $file->store('bank-statements', 'local');

        if (! $path) {
            throw ValidationException::withMessages(['file' => 'The supporting file could not be stored.']);
        }

        return [
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_mime' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }
}
