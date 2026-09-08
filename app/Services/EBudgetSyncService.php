<?php

namespace App\Services;

use App\Models\EBudgetSyncRun;
use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Models\Virement;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EBudgetSyncService
{
    public function sync(FiscalYear $fiscalYear, ?string $requestedBy = null): EBudgetSyncRun
    {
        $session = (string) $fiscalYear->name;
        $run = EBudgetSyncRun::create([
            'fiscal_year_id' => $fiscalYear->id,
            'source_session' => $session,
            'status' => 'running',
            'requested_by' => $requestedBy,
            'started_at' => now(),
        ]);

        $lock = Cache::lock('ebudget-sync-'.$session, 180);

        if (! $lock->get()) {
            $message = "An eBudget synchronisation is already running for {$session}.";
            $this->failRun($run, $message);

            throw new RuntimeException($message);
        }

        try {
            $payload = $this->fetchSnapshot($session);
            $this->validateSnapshot($payload, $session);

            $counts = DB::transaction(function () use ($payload, $fiscalYear) {
                return $this->storeSnapshot($payload, $fiscalYear);
            }, 3);

            $run->update([
                'status' => 'completed',
                'budgets_received' => count($payload['budgets']),
                'budgets_synced' => $counts['budgets'],
                'virements_received' => count($payload['virements']),
                'virements_synced' => $counts['virements'],
                'checksum' => $payload['checksum'],
                'message' => "Synchronised {$counts['budgets']} budgets and {$counts['virements']} approved virements from eBudget.",
                'finished_at' => now(),
            ]);

            return $run->fresh();
        } catch (Throwable $e) {
            $this->failRun($run, $e->getMessage());

            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function fetchSnapshot(string $session): array
    {
        $baseUrl = rtrim((string) config('services.ebudget.api_url'), '/');
        $token = (string) config('services.ebudget.api_token');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('eBudget synchronisation is not configured. Set EBUDGET_API_URL and EBUDGET_API_TOKEN.');
        }

        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout(max(5, (int) config('services.ebudget.timeout', 30)))
            ->retry(3, 500, null, false)
            ->get($baseUrl.'/budget-snapshot', ['session' => $session]);

        if (! $response->successful()) {
            $remoteMessage = trim((string) $response->json('message'));
            $message = $remoteMessage !== '' ? $remoteMessage : 'eBudget returned HTTP '.$response->status().'.';

            throw new RuntimeException($message);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('eBudget returned an invalid response.');
        }

        return $payload;
    }

    private function validateSnapshot(array $payload, string $session): void
    {
        Validator::make($payload, [
            'schema_version' => ['required', 'in:1.0'],
            'source' => ['required', 'in:ebudget'],
            'session' => ['required', 'in:'.$session],
            'mda.code' => ['required', 'string', 'max:30'],
            'mda.name' => ['required', 'string', 'max:255'],
            'checksum' => ['required', 'string', 'size:64'],
            'counts.budgets' => ['required', 'integer', 'min:1'],
            'counts.virements' => ['required', 'integer', 'min:0'],
            'budgets' => ['required', 'array', 'min:1'],
            'budgets.*.source_id' => ['required'],
            'budgets.*.economic_code' => ['required', 'string', 'max:30'],
            'budgets.*.economic_name' => ['required', 'string', 'max:255'],
            'budgets.*.payment_category' => ['nullable', 'string', 'max:30'],
            'budgets.*.approved_budget' => ['required', 'numeric', 'min:0'],
            'budgets.*.source_available_funds' => ['nullable', 'numeric'],
            'budgets.*.created_at' => ['nullable', 'date'],
            'budgets.*.updated_at' => ['nullable', 'date'],
            'virements' => ['required', 'array'],
            'virements.*.source_id' => ['required'],
            'virements.*.from_mda_code' => ['required', 'string', 'max:30'],
            'virements.*.from_mda_name' => ['required', 'string', 'max:255'],
            'virements.*.from_economic_code' => ['required', 'string', 'max:30'],
            'virements.*.from_economic_name' => ['required', 'string', 'max:255'],
            'virements.*.from_payment_category' => ['nullable', 'string', 'max:30'],
            'virements.*.to_mda_code' => ['required', 'string', 'max:30'],
            'virements.*.to_mda_name' => ['required', 'string', 'max:255'],
            'virements.*.to_economic_code' => ['required', 'string', 'max:30'],
            'virements.*.to_economic_name' => ['required', 'string', 'max:255'],
            'virements.*.to_payment_category' => ['nullable', 'string', 'max:30'],
            'virements.*.amount' => ['required', 'numeric', 'gt:0'],
            'virements.*.remark' => ['nullable', 'string'],
            'virements.*.approval_type' => ['nullable', 'string', 'max:60'],
            'virements.*.status' => ['required', 'in:approved'],
            'virements.*.created_at' => ['nullable', 'date'],
            'virements.*.updated_at' => ['nullable', 'date'],
        ], [
            'session.in' => 'The eBudget response was for a different fiscal year.',
        ])->validate();

        $expectedMda = (string) config('services.ebudget.mda_code');

        if (! hash_equals($expectedMda, (string) Arr::get($payload, 'mda.code'))) {
            throw new RuntimeException('The eBudget response was for a different MDA.');
        }

        $budgets = collect($payload['budgets']);
        $virements = collect($payload['virements']);

        if ((int) Arr::get($payload, 'counts.budgets') !== $budgets->count()
            || (int) Arr::get($payload, 'counts.virements') !== $virements->count()) {
            throw new RuntimeException('The eBudget response contains inconsistent record counts.');
        }

        if ($budgets->pluck('source_id')->map(fn ($id) => (string) $id)->unique()->count() !== $budgets->count()
            || $budgets->pluck('economic_code')->unique()->count() !== $budgets->count()) {
            throw new RuntimeException('The eBudget response contains duplicate budget records.');
        }

        if ($virements->pluck('source_id')->map(fn ($id) => (string) $id)->unique()->count() !== $virements->count()) {
            throw new RuntimeException('The eBudget response contains duplicate virement records.');
        }

        $canonical = [
            'budgets' => array_values($payload['budgets']),
            'virements' => array_values($payload['virements']),
        ];
        $checksum = hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        if (! hash_equals((string) $payload['checksum'], $checksum)) {
            throw new RuntimeException('The eBudget response failed its integrity check.');
        }
    }

    private function storeSnapshot(array $payload, FiscalYear $fiscalYear): array
    {
        $syncedAt = now();
        $mdaCode = (string) config('services.ebudget.mda_code');
        $budgets = collect($payload['budgets'])->keyBy(fn (array $budget) => (string) $budget['economic_code']);
        $virements = collect($payload['virements']);
        $incoming = [];
        $outgoing = [];

        foreach ($virements as $virement) {
            if ((string) $virement['to_mda_code'] === $mdaCode) {
                $code = (string) $virement['to_economic_code'];
                $incoming[$code] = Money::add($incoming[$code] ?? 0, $virement['amount']);

                if (! $budgets->has($code)) {
                    $budgets->put($code, $this->virementOnlyBudget($virement, 'to', $fiscalYear));
                }
            }

            if ((string) $virement['from_mda_code'] === $mdaCode) {
                $code = (string) $virement['from_economic_code'];
                $outgoing[$code] = Money::add($outgoing[$code] ?? 0, $virement['amount']);

                if (! $budgets->has($code)) {
                    $budgets->put($code, $this->virementOnlyBudget($virement, 'from', $fiscalYear));
                }
            }
        }

        $activeBudgetIds = [];

        foreach ($budgets as $budget) {
            $code = (string) $budget['economic_code'];
            $economicCode = $this->upsertEconomicCode(
                $code,
                (string) $budget['economic_name'],
                $budget['payment_category'] ?? null
            );
            $original = Money::normalize($budget['approved_budget']);
            $virementIn = Money::normalize($incoming[$code] ?? 0);
            $virementOut = Money::normalize($outgoing[$code] ?? 0);
            $revised = Money::sub(Money::add($original, $virementIn), $virementOut);

            $localBudget = EconomicCodeBudget::firstOrNew([
                'fiscal_year_id' => $fiscalYear->id,
                'economic_code_id' => $economicCode->id,
            ]);

            $localBudget->fill([
                'original_budget' => $original,
                'supplementary_budget' => 0,
                'virement_in' => $virementIn,
                'virement_out' => $virementOut,
                'revised_budget' => $revised,
                'status' => 'approved',
                'notes' => 'Authoritative allocation synchronised from eBudget.',
                'approved_by' => null,
                'approved_at' => $this->timestamp($budget['updated_at'] ?? $budget['created_at'] ?? null) ?? $syncedAt,
                'source_system' => 'ebudget',
                'source_id' => (string) $budget['source_id'],
                'source_available_funds' => $budget['source_available_funds'],
                'source_updated_at' => $this->timestamp($budget['updated_at'] ?? null),
                'source_synced_at' => $syncedAt,
                'source_active' => true,
            ]);
            $localBudget->save();
            $activeBudgetIds[] = $localBudget->id;
        }

        EconomicCodeBudget::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->whereNotIn('id', $activeBudgetIds)
            ->where(function ($query) {
                $query->whereNull('source_system')->orWhere('source_system', 'ebudget');
            })
            ->update([
                'status' => 'superseded',
                'source_active' => false,
                'source_synced_at' => $syncedAt,
            ]);

        $activeVirementSourceIds = [];

        foreach ($virements as $virement) {
            $from = $this->upsertEconomicCode(
                (string) $virement['from_economic_code'],
                (string) $virement['from_economic_name'],
                $virement['from_payment_category'] ?? null
            );
            $to = $this->upsertEconomicCode(
                (string) $virement['to_economic_code'],
                (string) $virement['to_economic_name'],
                $virement['to_payment_category'] ?? null
            );
            $sourceId = (string) $virement['source_id'];
            $sourceTimestamp = $this->timestamp($virement['updated_at'] ?? $virement['created_at'] ?? null);

            $localVirement = Virement::firstOrNew([
                'source_system' => 'ebudget',
                'source_id' => $sourceId,
            ]);
            $localVirement->fill([
                'fiscal_year_id' => $fiscalYear->id,
                'from_mda_code' => (string) $virement['from_mda_code'],
                'from_mda_name' => Str::limit((string) $virement['from_mda_name'], 255, ''),
                'from_economic_code_id' => $from->id,
                'to_mda_code' => (string) $virement['to_mda_code'],
                'to_mda_name' => Str::limit((string) $virement['to_mda_name'], 255, ''),
                'to_economic_code_id' => $to->id,
                'amount' => Money::normalize($virement['amount']),
                'date' => $this->timestamp($virement['created_at'] ?? $virement['updated_at'] ?? null)?->toDateString()
                    ?? $fiscalYear->start_date->toDateString(),
                'reference_number' => 'EBUDGET-VIR-'.$sourceId,
                'reason' => trim((string) ($virement['remark'] ?? '')) ?: 'Approved in eBudget',
                'approval_type' => $virement['approval_type'] ?? null,
                'status' => 'approved',
                'created_by' => null,
                'approved_by' => null,
                'approved_at' => $sourceTimestamp ?? $syncedAt,
                'source_updated_at' => $sourceTimestamp,
                'source_synced_at' => $syncedAt,
                'source_active' => true,
            ]);
            $localVirement->save();
            $activeVirementSourceIds[] = $sourceId;
        }

        Virement::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('source_system', 'ebudget')
            ->when($activeVirementSourceIds !== [], fn ($query) => $query->whereNotIn('source_id', $activeVirementSourceIds))
            ->update([
                'status' => 'superseded',
                'source_active' => false,
                'source_synced_at' => $syncedAt,
            ]);

        return [
            'budgets' => count($payload['budgets']),
            'virements' => count($payload['virements']),
        ];
    }

    private function upsertEconomicCode(string $code, string $name, ?string $paymentCategory): EconomicCode
    {
        $economicCode = EconomicCode::firstOrNew(['code' => $code]);
        $economicCode->fill([
            'name' => Str::limit($name !== '' ? $name : 'Economic Code '.$code, 255, ''),
            'type' => 'expense',
            'account_type' => $this->accountType($paymentCategory, $code),
            'status' => 'active',
        ]);
        $economicCode->save();

        return $economicCode;
    }

    private function accountType(?string $category, string $code): string
    {
        return match (strtoupper((string) $category)) {
            'PERSONNEL' => 'personnel',
            'CAPITAL' => 'capital',
            'OVERHEAD', 'NUTRITION' => 'overhead',
            default => match (substr($code, 0, 2)) {
                '21' => 'personnel',
                '23' => 'capital',
                default => 'overhead',
            },
        };
    }

    private function virementOnlyBudget(array $virement, string $side, FiscalYear $fiscalYear): array
    {
        return [
            'source_id' => 'virement-only:'.$fiscalYear->name.':'.$virement[$side.'_economic_code'],
            'economic_code' => (string) $virement[$side.'_economic_code'],
            'economic_name' => (string) $virement[$side.'_economic_name'],
            'payment_category' => $virement[$side.'_payment_category'] ?? null,
            'approved_budget' => '0.00',
            'source_available_funds' => null,
            'created_at' => $virement['created_at'] ?? null,
            'updated_at' => $virement['updated_at'] ?? null,
        ];
    }

    private function timestamp(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    private function failRun(EBudgetSyncRun $run, string $message): void
    {
        $run->update([
            'status' => 'failed',
            'message' => Str::limit($message, 2000, ''),
            'finished_at' => now(),
        ]);
    }
}
