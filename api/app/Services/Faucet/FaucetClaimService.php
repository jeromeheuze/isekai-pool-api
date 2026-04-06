<?php

namespace App\Services\Faucet;

use App\FaucetClaimStatus;
use App\FaucetPayoutBucket;
use App\Jobs\ProcessFaucetPayout;
use App\Models\FaucetBalance;
use App\Models\FaucetClaim;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FaucetClaimService
{
    public function __construct(
        private TurnstileVerifier $turnstile
    ) {}

    /**
     * @return array{success: bool, txid?: string|null, amount?: string, next_claim_at?: string|null, error?: string, claim_id?: int}
     */
    public function processClaim(Request $request): array
    {
        if (! config('faucet.enabled')) {
            return ['success' => false, 'error' => 'Faucet is disabled'];
        }

        $validated = $request->validate([
            'wallet_address' => ['required', 'string', 'max:128'],
            'activity_slug' => ['required', 'string', 'max:64'],
            'turnstile_token' => ['nullable', 'string', 'max:4096'],
            'source_site' => ['sometimes', 'string', 'max:64'],
            'completion_token' => ['nullable', 'string', 'max:512'],
        ]);

        $wallet = $validated['wallet_address'];
        $activity = $validated['activity_slug'];
        $sourceSite = $validated['source_site'] ?? 'isekai-pool';

        if (! $this->isValidKotoAddress($wallet)) {
            return ['success' => false, 'error' => 'Invalid KOTO address format'];
        }

        $activities = config('faucet.activities');
        if (! isset($activities[$activity])) {
            return ['success' => false, 'error' => 'Unknown activity'];
        }

        $amountStr = (string) $activities[$activity]['reward'];
        $amount = (float) $amountStr;
        $bucket = ($activities[$activity]['routine'] ?? true)
            ? FaucetPayoutBucket::Routine
            : FaucetPayoutBucket::Bonus;

        $ip = $request->ip() ?? '0.0.0.0';

        if (! $this->turnstile->verify($validated['turnstile_token'] ?? null, $ip)) {
            return ['success' => false, 'error' => 'Captcha verification failed'];
        }

        if (($validated['completion_token'] ?? null) && $sourceSite !== 'isekai-pool') {
            return ['success' => false, 'error' => 'Partner completion tokens are not yet enabled'];
        }

        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey) {
            $cached = Cache::get('faucet:idempotency:'.$idempotencyKey);
            if (is_array($cached)) {
                return $cached;
            }

            $existing = FaucetClaim::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $this->formatClaimResponse($existing->fresh());
            }
        }

        $lockKey = 'faucet:claim:'.sha1($wallet.'|'.$activity);

        return Cache::lock($lockKey, 30)->block(10, function () use (
            $wallet,
            $activity,
            $amount,
            $amountStr,
            $bucket,
            $ip,
            $sourceSite,
            $idempotencyKey
        ) {
            $balanceRow = FaucetBalance::singleton();
            $balance = (float) (string) $balanceRow->balance;

            $minOp = (float) config('faucet.min_operator_balance');
            if ($balance < $minOp) {
                return ['success' => false, 'error' => 'Faucet is refilling — try again later'];
            }

            if ($balance < $amount) {
                return ['success' => false, 'error' => 'Insufficient faucet balance'];
            }

            try {
                $this->assertCooldowns($wallet, $activity, $ip);
                $this->assertDailyCaps($wallet, $bucket, $amount);
            } catch (RuntimeException $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }

            $claim = null;

            DB::transaction(function () use (
                $wallet,
                $activity,
                $amountStr,
                $bucket,
                $ip,
                $sourceSite,
                $idempotencyKey,
                $amount,
                &$claim
            ) {
                $this->assertCooldowns($wallet, $activity, $ip);
                $this->assertDailyCaps($wallet, $bucket, $amount);

                $balanceRow = FaucetBalance::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                $bal = (float) (string) $balanceRow->balance;
                if ($bal < (float) config('faucet.min_operator_balance') || $bal < (float) $amountStr) {
                    throw new RuntimeException('Insufficient faucet balance');
                }

                $claim = FaucetClaim::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'wallet_address' => $wallet,
                    'ip_address' => $ip,
                    'activity_slug' => $activity,
                    'source_site' => $sourceSite,
                    'payout_bucket' => $bucket,
                    'amount' => $amountStr,
                    'status' => FaucetClaimStatus::Pending,
                ]);

                $balanceRow->decrement('balance', $claim->amount);

                DB::afterCommit(fn () => ProcessFaucetPayout::dispatch($claim));
            });

            if (! $claim) {
                return ['success' => false, 'error' => 'Could not create claim'];
            }

            $claim->refresh();

            $out = $this->formatClaimResponse($claim);

            if ($idempotencyKey) {
                Cache::put('faucet:idempotency:'.$idempotencyKey, $out, now()->addDay());
            }

            return $out;
        });
    }

    private function formatClaimResponse(FaucetClaim $claim): array
    {
        $next = $this->nextClaimAt($claim->wallet_address, $claim->activity_slug);

        $base = [
            'success' => $claim->status === FaucetClaimStatus::Paid,
            'claim_id' => $claim->id,
            'amount' => (string) $claim->amount,
            'txid' => $claim->txid,
            'next_claim_at' => $next?->toIso8601String(),
        ];

        if ($claim->status === FaucetClaimStatus::Failed) {
            $base['success'] = false;
            $base['error'] = $claim->failure_reason ?? 'Payout failed';
        }

        if ($claim->status === FaucetClaimStatus::Pending) {
            return [
                'success' => true,
                'pending' => true,
                'claim_id' => $claim->id,
                'amount' => (string) $claim->amount,
                'txid' => null,
                'next_claim_at' => null,
            ];
        }

        return $base;
    }

    private function nextClaimAt(string $wallet, string $activity): ?Carbon
    {
        $last = FaucetClaim::query()
            ->where('wallet_address', $wallet)
            ->where('activity_slug', $activity)
            ->where('status', FaucetClaimStatus::Paid)
            ->orderByDesc('created_at')
            ->first();

        if (! $last) {
            return null;
        }

        return $last->created_at->copy()->addHours((int) config('faucet.cooldown_hours'));
    }

    private function isValidKotoAddress(string $address): bool
    {
        return (bool) preg_match('/^(k1|jz)[a-zA-Z0-9]{38,}$/', $address);
    }

    private function assertCooldowns(string $wallet, string $activity, string $ip): void
    {
        $hours = (int) config('faucet.cooldown_hours');

        $walletHit = FaucetClaim::query()
            ->where('wallet_address', $wallet)
            ->where('activity_slug', $activity)
            ->whereIn('status', [FaucetClaimStatus::Paid, FaucetClaimStatus::Pending])
            ->where('created_at', '>=', now()->subHours($hours))
            ->exists();

        if ($walletHit) {
            throw new RuntimeException('Cooldown active for this wallet and activity');
        }

        $ipHit = FaucetClaim::query()
            ->where('ip_address', $ip)
            ->where('activity_slug', $activity)
            ->whereIn('status', [FaucetClaimStatus::Paid, FaucetClaimStatus::Pending])
            ->where('created_at', '>=', now()->subHours($hours))
            ->exists();

        if ($ipHit) {
            throw new RuntimeException('Cooldown active for this IP and activity');
        }
    }

    private function assertDailyCaps(string $wallet, FaucetPayoutBucket $bucket, float $amount): void
    {
        $tz = config('faucet.timezone');
        $start = now($tz)->startOfDay()->utc();
        $end = now($tz)->endOfDay()->utc();

        $statuses = [FaucetClaimStatus::Paid, FaucetClaimStatus::Pending];

        $routineSum = (float) FaucetClaim::query()
            ->whereIn('status', $statuses)
            ->where('payout_bucket', FaucetPayoutBucket::Routine)
            ->where('wallet_address', $wallet)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $allSum = (float) FaucetClaim::query()
            ->whereIn('status', $statuses)
            ->where('wallet_address', $wallet)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $globalSum = (float) FaucetClaim::query()
            ->whereIn('status', $statuses)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        if ($bucket === FaucetPayoutBucket::Routine) {
            $maxRoutine = (float) config('faucet.routine_max_per_wallet_per_day');
            if ($routineSum + $amount > $maxRoutine + 1e-8) {
                throw new RuntimeException('Daily routine earning limit reached');
            }
        }

        $hard = (float) config('faucet.hard_max_per_wallet_per_day');
        if ($allSum + $amount > $hard + 1e-8) {
            throw new RuntimeException('Daily earning limit reached');
        }

        $globalMax = (float) config('faucet.global_max_per_day');
        if ($globalSum + $amount > $globalMax + 1e-8) {
            throw new RuntimeException('Faucet daily budget exhausted');
        }
    }
}
