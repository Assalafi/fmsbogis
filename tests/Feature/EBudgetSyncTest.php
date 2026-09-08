<?php

namespace Tests\Feature;

use App\Models\EBudgetSyncRun;
use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\EBudgetSyncService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class EBudgetSyncTest extends TestCase
{
    use RefreshDatabase;

    private FiscalYear $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        app('url')->forceRootUrl('http://localhost');
        $this->seed(RolesAndPermissionsSeeder::class);
        config()->set('services.ebudget.api_url', 'https://budget.example.test/api/v1/integrations/bogis');
        config()->set('services.ebudget.api_token', 'test-secret-token');
        config()->set('services.ebudget.mda_code', '016100600100');

        $this->fiscalYear = FiscalYear::create([
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);
    }

    public function test_authorised_user_can_sync_an_idempotent_ebudget_snapshot(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Finance Admin');

        $legacyCode = EconomicCode::create([
            'code' => '99999999',
            'name' => 'Legacy Local Budget',
            'type' => 'expense',
            'account_type' => 'overhead',
            'status' => 'active',
        ]);
        $legacyBudget = EconomicCodeBudget::create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'economic_code_id' => $legacyCode->id,
            'original_budget' => 500,
            'revised_budget' => 500,
            'status' => 'approved',
        ]);

        Http::fake([
            'https://budget.example.test/*' => Http::sequence()
                ->push($this->snapshot('1000.00', '100.00'))
                ->push($this->snapshot('1200.00', '150.00')),
        ]);

        $this->actingAs($admin)
            ->from(route('budgets.index'))
            ->post(route('budgets.sync'), ['fiscal_year_id' => $this->fiscalYear->id])
            ->assertRedirect(route('budgets.index'))
            ->assertSessionHasNoErrors();

        $nutritionCode = EconomicCode::where('code', '22020605')->firstOrFail();
        $budget = EconomicCodeBudget::where('fiscal_year_id', $this->fiscalYear->id)
            ->where('economic_code_id', $nutritionCode->id)
            ->firstOrFail();

        $this->assertSame('overhead', $nutritionCode->account_type);
        $this->assertSame('1000.00', $budget->original_budget);
        $this->assertSame('100.00', $budget->virement_in);
        $this->assertSame('1100.00', $budget->revised_budget);
        $this->assertSame('1100.00', app(BudgetService::class)->availableBudget($nutritionCode, $this->fiscalYear));
        $this->assertSame('superseded', $legacyBudget->fresh()->status);
        $this->assertFalse($legacyBudget->fresh()->source_active);
        $this->assertDatabaseCount('virements', 1);
        $this->assertSame('completed', EBudgetSyncRun::latest('started_at')->first()->status);

        app(EBudgetSyncService::class)->sync($this->fiscalYear, $admin->id);

        $budget->refresh();
        $this->assertSame('1200.00', $budget->original_budget);
        $this->assertSame('150.00', $budget->virement_in);
        $this->assertSame('1350.00', $budget->revised_budget);
        $this->assertDatabaseCount('economic_code_budgets', 3);
        $this->assertDatabaseCount('virements', 1);
        $this->assertDatabaseCount('ebudget_sync_runs', 2);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://budget.example.test/api/v1/integrations/bogis/budget-snapshot?session=2026'
                && $request->hasHeader('Authorization', 'Bearer test-secret-token');
        });
    }

    public function test_invalid_snapshot_is_rejected_without_changing_budgets(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['checksum'] = str_repeat('0', 64);
        Http::fake(['https://budget.example.test/*' => Http::response($snapshot)]);

        try {
            app(EBudgetSyncService::class)->sync($this->fiscalYear);
            $this->fail('An invalid checksum should reject the snapshot.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('integrity check', $e->getMessage());
        }

        $this->assertDatabaseCount('economic_code_budgets', 0);
        $this->assertSame('failed', EBudgetSyncRun::firstOrFail()->status);
    }

    public function test_budget_navigation_is_read_only_and_only_shows_retained_pages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Finance Admin');

        $response = $this->actingAs($admin)->get(route('budgets.index'));

        $response->assertOk()
            ->assertSee('Approved Budgets')
            ->assertSee('eBudget is the source of truth')
            ->assertDontSee('Upload Budget')
            ->assertDontSee('Budget Approval')
            ->assertDontSee('Create Budget');

        $this->actingAs($admin)->get('/budgets/upload')->assertNotFound();
        $this->actingAs($admin)->get('/virements/create')->assertNotFound();
    }

    private function snapshot(string $approvedBudget = '1000.00', string $virementAmount = '100.00'): array
    {
        $budgets = [
            [
                'source_id' => '10278',
                'economic_code' => '22020605',
                'economic_name' => 'CLEANING & FUMIGATION SERVICES',
                'payment_category' => 'NUTRITION',
                'economic_level' => 7,
                'approved_budget' => $approvedBudget,
                'source_available_funds' => '400.00',
                'source_status' => 0,
                'created_at' => '2026-02-03T11:33:11+01:00',
                'updated_at' => '2026-02-03T11:33:11+01:00',
            ],
            [
                'source_id' => '10279',
                'economic_code' => '21020101',
                'economic_name' => 'NON REGULAR ALLOWANCES',
                'payment_category' => 'PERSONNEL',
                'economic_level' => 2,
                'approved_budget' => '500.00',
                'source_available_funds' => '500.00',
                'source_status' => 0,
                'created_at' => '2026-02-03T11:33:11+01:00',
                'updated_at' => '2026-02-03T11:33:11+01:00',
            ],
        ];
        $virements = [[
            'source_id' => '122',
            'from_mda_code' => '023800500100',
            'from_mda_name' => 'SERVICE WIDE VOTE',
            'from_economic_code' => '22021023',
            'from_economic_name' => 'CONTINGENCIES RESERVE',
            'from_payment_category' => 'OVERHEAD',
            'to_mda_code' => '016100600100',
            'to_mda_name' => 'BORNO GEOGRAPHIC INFORMATION SERVICE (BOGIS)',
            'to_economic_code' => '22020605',
            'to_economic_name' => 'CLEANING & FUMIGATION SERVICES',
            'to_payment_category' => 'NUTRITION',
            'amount' => $virementAmount,
            'remark' => 'Approved augmentation',
            'approval_type' => 'MINISTRY',
            'status' => 'approved',
            'created_at' => '2026-03-01T10:00:00+01:00',
            'updated_at' => null,
        ]];
        $canonical = ['budgets' => $budgets, 'virements' => $virements];

        return [
            'schema_version' => '1.0',
            'source' => 'ebudget',
            'generated_at' => '2026-03-01T10:00:00+01:00',
            'session' => '2026',
            'mda' => [
                'code' => '016100600100',
                'name' => 'BORNO GEOGRAPHIC INFORMATION SERVICE (BOGIS)',
            ],
            'checksum' => hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'counts' => ['budgets' => 2, 'virements' => 1],
            'budgets' => $budgets,
            'virements' => $virements,
        ];
    }
}
