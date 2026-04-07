<?php

namespace App\Http\Controllers\Api;

use App\FaucetClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\FaucetBalance;
use App\Models\FaucetClaim;
use App\Services\Faucet\FaucetActivityCompletionService;
use App\Services\Faucet\FaucetClaimService;
use App\Services\Faucet\KotoFaucetWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class FaucetController extends Controller
{
    public function claim(Request $request, FaucetClaimService $claims): JsonResponse
    {
        $result = $claims->processClaim($request);

        $status = 200;
        if (isset($result['error'])) {
            $status = match ($result['error'] ?? '') {
                'Faucet is disabled' => 503,
                default => 422,
            };
        }

        return response()->json($result, $status);
    }

    public function activitySession(Request $request, FaucetActivityCompletionService $sessions): JsonResponse
    {
        if (! config('faucet.enabled')) {
            return response()->json(['error' => 'Faucet is disabled'], 503);
        }

        $validated = $request->validate([
            'activity_slug' => ['required', 'string', 'max:64'],
        ]);

        try {
            $sessionId = $sessions->createSession($validated['activity_slug']);
        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['session_id' => $sessionId]);
    }

    public function activityComplete(Request $request, FaucetActivityCompletionService $completion): JsonResponse
    {
        if (! config('faucet.enabled')) {
            return response()->json(['error' => 'Faucet is disabled'], 503);
        }

        $validated = $request->validate([
            'wallet_address' => ['required', 'string', 'max:128'],
            'activity_slug' => ['required', 'string', 'max:64'],
            'turnstile_token' => ['nullable', 'string', 'max:4096'],
            'proof' => ['required', 'array'],
        ]);

        try {
            $token = $completion->completeActivity(
                $request,
                $validated['wallet_address'],
                $validated['activity_slug'],
                $validated['proof']
            );
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'completion_token' => $token,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        if (! config('faucet.enabled')) {
            return response()->json(['error' => 'Faucet is disabled'], 503);
        }

        $request->validate([
            'wallet' => ['nullable', 'string', 'max:128'],
        ]);

        $wallet = $request->query('wallet');
        $activities = config('faucet.activities');
        $out = [];

        foreach ($activities as $slug => $meta) {
            $reward = (string) $meta['reward'];
            $available = true;
            $next = null;

            if ($wallet) {
                $last = FaucetClaim::query()
                    ->where('wallet_address', $wallet)
                    ->where('activity_slug', $slug)
                    ->whereIn('status', [FaucetClaimStatus::Paid, FaucetClaimStatus::Pending])
                    ->where('created_at', '>=', now()->subHours((int) config('faucet.cooldown_hours')))
                    ->orderByDesc('created_at')
                    ->first();

                if ($last) {
                    $available = false;
                    $next = $last->created_at->copy()->addHours((int) config('faucet.cooldown_hours'))->toIso8601String();
                }
            }

            $out[] = [
                'slug' => $slug,
                'reward' => $reward,
                'available' => $available,
                'next_claim_at' => $next,
            ];
        }

        $totalEarned = null;
        if ($wallet) {
            $totalEarned = (string) FaucetClaim::query()
                ->where('wallet_address', $wallet)
                ->where('status', FaucetClaimStatus::Paid)
                ->sum('amount');
        }

        $meta = [];
        if (config('faucet.faucet_wallet')) {
            $meta['faucet_wallet'] = config('faucet.faucet_wallet');
        }
        if (config('faucet.turnstile.site_key')) {
            $meta['turnstile_site_key'] = config('faucet.turnstile.site_key');
        }

        return response()->json(array_merge([
            'activities' => $out,
            'total_earned' => $totalEarned,
        ], $meta));
    }

    public function balance(Request $request): JsonResponse
    {
        if (! config('faucet.enabled')) {
            return response()->json(['error' => 'Faucet is disabled'], 503);
        }

        if (! Schema::hasTable('faucet_balance')) {
            return response()->json([
                'error' => 'Faucet tables missing',
                'hint' => 'Run php artisan migrate on the API server',
            ], 503);
        }

        try {
            $row = FaucetBalance::singleton();
        } catch (Throwable $e) {
            Log::error('faucet.balance.singleton', ['message' => $e->getMessage()]);

            return response()->json([
                'error' => 'Could not load faucet balance row',
                'hint' => 'Run php artisan migrate and ensure database is writable',
            ], 503);
        }

        $tz = config('faucet.timezone');
        $start = now($tz)->startOfDay()->utc();
        $end = now($tz)->endOfDay()->utc();

        try {
            $dailyPaid = (string) (FaucetClaim::query()
                ->where('status', FaucetClaimStatus::Paid->value)
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount') ?? '0');
        } catch (Throwable $e) {
            Log::error('faucet.balance.daily', ['message' => $e->getMessage()]);
            $dailyPaid = '0';
        }

        $payload = [
            'balance' => (string) $row->balance,
            'total_paid' => (string) $row->total_paid,
            'total_claims' => (int) $row->total_claims,
            'daily_paid' => $dailyPaid,
            'last_sync' => $row->last_sync?->toIso8601String(),
        ];

        if (config('faucet.faucet_wallet')) {
            $payload['faucet_wallet'] = config('faucet.faucet_wallet');
        }
        if (config('faucet.turnstile.site_key')) {
            $payload['turnstile_site_key'] = config('faucet.turnstile.site_key');
        }

        if ($request->boolean('sync_rpc') && config('faucet.allow_balance_rpc_sync')) {
            try {
                $rpc = app(KotoFaucetWalletService::class);
                $onChain = $rpc->getBalance();
                $row->update([
                    'balance' => $onChain,
                    'last_sync' => now(),
                ]);
                $payload['balance'] = (string) $row->fresh()->balance;
                $payload['last_sync'] = $row->last_sync?->toIso8601String();
            } catch (Throwable) {
                // keep book balance
            }
        }

        return response()->json($payload);
    }

    public function recent(): JsonResponse
    {
        if (! config('faucet.enabled')) {
            return response()->json(['error' => 'Faucet is disabled'], 503);
        }

        $rows = FaucetClaim::query()
            ->where('status', FaucetClaimStatus::Paid)
            ->whereNotNull('txid')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['wallet_address', 'activity_slug', 'amount', 'txid', 'created_at']);

        $data = $rows->map(function (FaucetClaim $c) {
            $w = $c->wallet_address;
            $short = strlen($w) > 12 ? substr($w, 0, 6).'…'.substr($w, -4) : $w;

            return [
                'wallet_short' => $short,
                'activity' => $c->activity_slug,
                'amount' => (string) $c->amount,
                'txid' => $c->txid,
                'time' => $c->created_at->toIso8601String(),
            ];
        });

        return response()->json($data);
    }
}
