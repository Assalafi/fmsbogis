<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Receipt;
use App\Services\AuditService;
use App\Services\ReceiptService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        $query = $this->applyIndexFilters(Receipt::with(['account', 'economicCode', 'fiscalYear', 'creator']), $request)
            ->where('fiscal_year_id', $fiscalYearId);

        $receipts = $query->orderBy('date_of_transaction', 'desc')->paginate(20)->withQueryString();

        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $accounts = Account::orderBy('account_name')->get();
        $revenueCodes = EconomicCode::revenue()->orderBy('code')->get();

        $receiptService = app(ReceiptService::class);
        $fiscalYear = FiscalYear::find($fiscalYearId);
        $summary = $fiscalYear ? $receiptService->totalsForPeriod($fiscalYear) : null;

        return view('receipts.index', compact('receipts', 'fiscalYears', 'accounts', 'revenueCodes', 'summary'));
    }

    public function create(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        return view('receipts.create', [
            'accounts' => Account::active()->orderBy('account_name')->get(),
            'revenueCodes' => EconomicCode::revenue()->active()->orderBy('code')->get(),
            'fiscalYears' => FiscalYear::open()->orderBy('start_date')->get(),
            'selectedFiscalYearId' => $fiscalYearId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'date_of_transaction' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'treasury_receipt_voucher_number' => ['required', 'string', 'max:255'],
            'from_whom_received_to_whom_paid' => ['nullable', 'string', 'max:255'],
            'bank_credit_slip_cheque_mandate_number' => ['nullable', 'string', 'max:255'],
            'expenditure_credits' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:bank,cash'],
            'details' => ['required', 'string'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $economicCode = EconomicCode::findOrFail($data['economic_code_id']);
        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);

        $error = app(ReceiptService::class)->validate($economicCode, $account);
        if ($error) {
            return back()->withInput()->withErrors(['economic_code_id' => $error]);
        }

        if (! $fiscalYear->isOpen()) {
            return back()->withInput()->withErrors(['fiscal_year_id' => 'The selected Fiscal Year is closed.']);
        }

        $receipt = DB::transaction(function () use ($data) {
            $receipt = Receipt::create($data + [
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            app(AuditService::class)->log('Receipt Created', $receipt, null, $data);

            return $receipt;
        });

        return redirect()->route('receipts.show', $receipt)->with($this->toast('Receipt saved successfully.'));
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['account', 'economicCode', 'fiscalYear', 'creator', 'approver', 'poster', 'cashbookEntry']);

        return view('receipts.show', compact('receipt'));
    }

    public function edit(Receipt $receipt)
    {
        abort_unless(in_array($receipt->status, ['draft', 'pending']), 403, 'Only draft or pending receipts can be edited.');

        return view('receipts.edit', [
            'receipt' => $receipt,
            'accounts' => Account::active()->orderBy('account_name')->get(),
            'revenueCodes' => EconomicCode::revenue()->active()->orderBy('code')->get(),
            'fiscalYears' => FiscalYear::open()->orderBy('start_date')->get(),
        ]);
    }

    public function update(Request $request, Receipt $receipt)
    {
        abort_unless(in_array($receipt->status, ['draft', 'pending']), 403, 'Only draft or pending receipts can be edited.');

        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'date_of_transaction' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'treasury_receipt_voucher_number' => ['required', 'string', 'max:255'],
            'from_whom_received_to_whom_paid' => ['nullable', 'string', 'max:255'],
            'bank_credit_slip_cheque_mandate_number' => ['nullable', 'string', 'max:255'],
            'expenditure_credits' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:bank,cash'],
            'details' => ['required', 'string'],
        ]);

        $old = $receipt->only(array_keys($data));
        $receipt->update($data);

        app(AuditService::class)->log('Receipt Updated', $receipt, $old, $data);

        return redirect()->route('receipts.show', $receipt)->with($this->toast('Receipt updated.'));
    }

    public function approve(Receipt $receipt)
    {
        $receipt->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(AuditService::class)->log('Receipt Approved', $receipt, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with($this->toast('Receipt approved.'));
    }

    public function post(Receipt $receipt)
    {
        app(ReceiptService::class)->post($receipt);

        return back()->with($this->toast('Receipt posted. Cashbook entry generated.'));
    }

    public function reverse(Receipt $receipt)
    {
        app(ReceiptService::class)->reverse($receipt);

        return back()->with($this->toast('Receipt reversed. Reversing cashbook entry created.', 'danger'));
    }

    public function print(Receipt $receipt)
    {
        $pdf = Pdf::loadView('receipts.print', [
            'receipt' => $receipt->load(['account', 'economicCode', 'fiscalYear', 'creator', 'approver']),
            'amountInWords' => Money::inWords($receipt->amount),
        ])->setPaper('a4');

        return $pdf->stream('receipt-'.$receipt->treasury_receipt_voucher_number.'.pdf');
    }

    /**
     * Bulk download receipt PDFs as a ZIP.
     */
    public function bulkDownload(Request $request)
    {
        $request->validate([
            'receipt_ids' => ['nullable', 'array', 'max:2000'],
            'receipt_ids.*' => ['uuid', 'exists:receipts,id'],
        ]);

        $ids = $request->input('receipt_ids', []);

        $query = Receipt::query();

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
            $this->applyIndexFilters($query, $request)->where('fiscal_year_id', $fiscalYearId);
        }

        $total = $query->count();

        if ($total === 0) {
            return back()->with($this->toast('No receipts matched. Select receipts or apply filters first.', 'warning'));
        }

        $filters = null;
        if (empty($ids)) {
            $filters = $request->only(['fiscal_year_id', 'account_id', 'economic_code_id', 'status', 'payment_method', 'search', 'date_from', 'date_to']);
        }

        if ($total <= \App\Support\BulkDownloadProgress::MAX_SYNC) {
            return $this->buildZipAndDownload($query, $total);
        }

        // Large selection: run in the background with progress.
        if (\App\Support\BulkDownloadProgress::isRunning()) {
            return back()->with($this->toast('A bulk download is already running. Please wait.', 'warning'));
        }

        $token = \App\Support\BulkDownloadProgress::start($total);

        $command = [
            (string) config('services.external_receipts.php_binary', 'php8.3'),
            base_path('artisan'),
            'receipts:bulk-download',
            '--token='.$token,
        ];

        if (! empty($ids)) {
            $command[] = '--ids='.implode(',', $ids);
        } else {
            $command[] = '--filters='.escapeshellarg(json_encode($filters));
        }

        try {
            $shellCommand = implode(' ', array_map('escapeshellarg', $command)).' > /dev/null 2>&1 & echo $!';
            shell_exec($shellCommand);

            return back()->with($this->toast("Bulk download started for {$total} receipts. Progress is shown below.", 'success'));
        } catch (\Throwable $e) {
            \App\Support\BulkDownloadProgress::fail($token, $e->getMessage());

            return back()->with($this->toast('Could not start bulk download: '.$e->getMessage(), 'danger'));
        }
    }

    /**
     * Build the ZIP synchronously and stream it.
     */
    protected function buildZipAndDownload($query, int $total)
    {
        set_time_limit(0);

        $pdfService = app(\App\Services\ReceiptPdfService::class);

        $token = (string) \Illuminate\Support\Str::uuid();
        $zipName = 'BOGIS-Receipts-'.now()->format('Ymd-His').'.zip';
        $zipPath = \Illuminate\Support\Facades\Storage::disk('local')->path('bulk/'.$token.'.zip');

        \Illuminate\Support\Facades\Storage::disk('local')->makeDirectory('bulk');

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with($this->toast('Could not create the ZIP file.', 'danger'));
        }

        $failed = 0;

        $query->chunkById(50, function ($receipts) use ($zip, $pdfService, &$failed) {
            foreach ($receipts as $receipt) {
                try {
                    $zip->addFromString($pdfService->filename($receipt), $pdfService->generate($receipt));
                } catch (\Throwable) {
                    $failed++;
                }
            }
        });

        $zip->close();

        app(AuditService::class)->log('Receipts Bulk Downloaded', null, null, [
            'total' => $total,
            'failed' => $failed,
        ]);

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    /**
     * JSON progress for the bulk download UI.
     */
    public function bulkDownloadProgress()
    {
        $state = \App\Support\BulkDownloadProgress::state();

        return response()->json([
            'token' => $state['token'],
            'status' => $state['status'],
            'total' => (int) $state['total'],
            'done' => (int) $state['done'],
            'failed' => (int) $state['failed'],
            'percent' => \App\Support\BulkDownloadProgress::percent((string) $state['token']),
            'message' => $state['message'],
        ]);
    }

    /**
     * Download a finished background ZIP.
     */
    public function bulkDownloadFile(string $token)
    {
        $state = \App\Support\BulkDownloadProgress::state($token);

        abort_unless($state['status'] === 'done' && ! empty($state['zip_path']), 404, 'Bulk download not ready.');

        $zipPath = \Illuminate\Support\Facades\Storage::disk('local')->path($state['zip_path']);

        abort_unless(file_exists($zipPath), 404, 'ZIP file missing.');

        return response()->download($zipPath, 'BOGIS-Receipts-'.$token.'.zip')->deleteFileAfterSend(true);
    }

    protected function applyIndexFilters($query, Request $request)
    {
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('economic_code_id')) {
            $query->where('economic_code_id', $request->economic_code_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_of_transaction', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_of_transaction', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('from_whom_received_to_whom_paid', 'like', "%{$search}%")
                    ->orWhere('treasury_receipt_voucher_number', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhere('payer_phone', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
