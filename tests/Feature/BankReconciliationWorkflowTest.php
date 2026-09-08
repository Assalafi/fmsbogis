<?php

namespace Tests\Feature;

use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BankStatementController;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use App\Models\CashbookEntry;
use App\Models\User;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class BankReconciliationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_statement_attachment_is_stored_without_importing_transactions(): void
    {
        Storage::fake('local');
        $this->withoutMiddleware();
        config()->set('app.url', 'http://localhost');
        app('url')->forceRootUrl('http://localhost');

        $user = User::factory()->create();
        $account = $this->account();
        $file = UploadedFile::fake()->create('september-statement.pdf', 32, 'application/pdf');

        $response = $this->actingAs($user)->post('/bank-statements', [
            'account_id' => $account->id,
            'statement_from' => '2026-09-01',
            'statement_to' => '2026-09-30',
            'opening_balance' => 1000,
            'closing_balance' => 1000,
            'notes' => 'Supporting bank document',
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $statement = BankStatement::sole();

        $response->assertRedirect(route('bank-statements.show', $statement));
        $this->assertSame('manual', $statement->status);
        $this->assertSame('september-statement.pdf', $statement->file_name);
        $this->assertSame(0, $statement->lines()->count());
        Storage::disk('local')->assertExists($statement->file_path);
    }

    public function test_reconciliation_uses_opening_and_closing_balances_without_statement_records(): void
    {
        $account = $this->account(['opening_balance' => 1000]);
        $statement = $this->statement($account, [
            'opening_balance' => 900,
            'closing_balance' => 850,
        ]);

        $reconciliation = app(ReconciliationService::class)->createFor($account, $statement);

        $this->assertSame(0, $reconciliation->items()->count());
        $this->assertSame(0, $statement->lines()->count());
        $this->assertSame('1000.00', $reconciliation->cashbook_balance);
        $this->assertSame('850.00', $reconciliation->bank_statement_balance);
        $this->assertSame('150.00', $reconciliation->difference);
    }

    public function test_recalculation_applies_timing_differences_to_the_correct_balance(): void
    {
        $account = $this->account(['opening_balance' => 1000]);
        $statement = $this->statement($account, [
            'opening_balance' => 1500,
            'closing_balance' => 1400,
        ]);
        CashbookEntry::create([
            'account_id' => $account->id,
            'transaction_type' => 'receipt',
            'date' => '2026-09-05',
            'details' => 'Receipt',
            'receipt_amount' => 500,
            'payment_amount' => 0,
        ]);
        CashbookEntry::create([
            'account_id' => $account->id,
            'transaction_type' => 'payment',
            'date' => '2026-09-25',
            'details' => 'Unpresented payment',
            'receipt_amount' => 0,
            'payment_amount' => 200,
        ]);
        $reconciliation = BankReconciliation::create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'reconciliation_date' => '2026-09-30',
            'status' => 'draft',
        ]);
        BankReconciliationItem::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'item_type' => 'cashbook_deduction',
            'amount' => 100,
            'notes' => 'Bank charge',
        ]);
        BankReconciliationItem::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'item_type' => 'bank_deduction',
            'amount' => 200,
            'notes' => 'Unpresented payment',
        ]);

        app(ReconciliationService::class)->recalculate($reconciliation);
        $reconciliation->refresh();

        $this->assertSame('1300.00', $reconciliation->cashbook_balance);
        $this->assertSame('1200.00', $reconciliation->adjusted_cashbook_balance);
        $this->assertSame('1200.00', $reconciliation->adjusted_bank_balance);
        $this->assertSame('0.00', $reconciliation->difference);
    }

    public function test_reconciliation_can_be_approved_when_adjusted_balances_agree(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $account = $this->account(['opening_balance' => 1000]);
        $statement = $this->statement($account, [
            'opening_balance' => 1000,
            'closing_balance' => 1000,
        ]);
        $reconciliation = app(ReconciliationService::class)->createFor($account, $statement);

        app(ReconciliationService::class)->approve($reconciliation);

        $this->assertSame('approved', $reconciliation->fresh()->status);
        $this->assertSame($user->id, $reconciliation->fresh()->approved_by);
        $this->assertSame('reconciled', $statement->fresh()->status);
    }

    public function test_reconciliation_print_view_generates_a_pdf(): void
    {
        $account = $this->account(['opening_balance' => 1000]);
        $statement = $this->statement($account, [
            'opening_balance' => 1000,
            'closing_balance' => 1000,
        ]);
        $reconciliation = BankReconciliation::create([
            'account_id' => $account->id,
            'bank_statement_id' => $statement->id,
            'reconciliation_date' => '2026-09-30',
            'cashbook_balance' => 1000,
            'bank_statement_balance' => 1000,
            'adjusted_cashbook_balance' => 1000,
            'adjusted_bank_balance' => 1000,
            'difference' => 0,
            'status' => 'draft',
        ]);

        $response = app(BankReconciliationController::class)->print($reconciliation);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_simplified_statement_and_reconciliation_pages_render_without_statement_lines(): void
    {
        $this->actingAs(User::factory()->create());
        view()->share('errors', new ViewErrorBag);
        $account = $this->account(['opening_balance' => 1000]);
        $statement = $this->statement($account, [
            'opening_balance' => 900,
            'closing_balance' => 850,
        ]);

        $statementHtml = app(BankStatementController::class)->show($statement)->render();
        $this->assertStringContainsString('No transaction-line entry', $statementHtml);

        $reconciliation = app(ReconciliationService::class)->createFor($account, $statement);
        $reconciliationHtml = app(BankReconciliationController::class)->show($reconciliation)->render();
        $this->assertStringContainsString('Add Reconciling Adjustment', $reconciliationHtml);
        $this->assertStringContainsString('Items in Bank Not Cashbook Amount', $reconciliationHtml);
        $this->assertStringContainsString('Items in Cashbook Not Bank Amount', $reconciliationHtml);
        $this->assertStringNotContainsString('Unclassified Bank Transactions', $reconciliationHtml);
    }

    public function test_every_reference_reconciliation_category_is_available(): void
    {
        $requiredCategories = [
            'credit_transfer',
            'interest_received',
            'stale_cheque_reversed',
            'bank_charge',
            'debit_transfer',
            'outstanding_stale_revenue',
            'unpresented_payment',
            'items_in_bank_not_cashbook',
            'uncredited_lodgement',
            'items_in_cashbook_not_bank',
        ];

        foreach ($requiredCategories as $category) {
            $this->assertArrayHasKey($category, BankReconciliationItem::ADJUSTMENT_CATEGORIES);
        }
    }

    private function account(array $attributes = []): Account
    {
        return Account::create($attributes + [
            'account_name' => 'Treasury Account',
            'bank_name' => 'Test Bank',
            'account_number' => '0123456789',
            'account_type' => 'bank',
            'opening_balance' => 0,
            'status' => 'active',
        ]);
    }

    private function statement(Account $account, array $attributes = []): BankStatement
    {
        return BankStatement::create($attributes + [
            'account_id' => $account->id,
            'statement_from' => '2026-09-01',
            'statement_to' => '2026-09-30',
            'opening_balance' => 0,
            'closing_balance' => 0,
            'status' => 'manual',
        ]);
    }
}
