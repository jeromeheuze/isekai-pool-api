<?php

namespace App\Jobs;

use App\FaucetClaimStatus;
use App\Models\FaucetBalance;
use App\Models\FaucetClaim;
use App\Services\Faucet\KotoFaucetWalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessFaucetPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(public FaucetClaim $claim) {}

    public function handle(KotoFaucetWalletService $rpc): void
    {
        $claim = $this->claim->fresh();

        if (! $claim || $claim->status !== FaucetClaimStatus::Pending) {
            return;
        }

        try {
            $amount = (string) $claim->amount;
            $txid = $rpc->sendToAddress($claim->wallet_address, $amount);

            DB::transaction(function () use ($claim, $txid) {
                $claim->update([
                    'txid' => $txid,
                    'status' => FaucetClaimStatus::Paid,
                ]);

                $row = FaucetBalance::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                $row->increment('total_paid', $claim->amount);
                $row->increment('total_claims');
            });
        } catch (Throwable $e) {
            Log::error('Faucet payout failed', [
                'claim_id' => $claim->id,
                'message' => $e->getMessage(),
            ]);

            DB::transaction(function () use ($claim, $e) {
                $claim->update([
                    'status' => FaucetClaimStatus::Failed,
                    'failure_reason' => $e->getMessage(),
                ]);

                $row = FaucetBalance::query()->whereKey(1)->lockForUpdate()->firstOrFail();
                $row->increment('balance', $claim->amount);
            });
        }
    }
}
