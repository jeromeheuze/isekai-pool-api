<?php

namespace App\Console\Commands;

use App\FaucetClaimStatus;
use App\Models\FaucetBalance;
use App\Models\FaucetClaim;
use App\Services\Faucet\KotoFaucetWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FaucetSyncBalanceCommand extends Command
{
    protected $signature = 'faucet:sync-balance';

    protected $description = 'Set faucet book balance from KOTO JSON-RPC getbalance (RPC wallet = hot wallet)';

    public function handle(KotoFaucetWalletService $rpc): int
    {
        if (! config('faucet.auto_sync_balance')) {
            return self::SUCCESS;
        }

        if (! Schema::hasTable('faucet_balance')) {
            $this->warn('faucet_balance table missing — run migrations.');

            return self::FAILURE;
        }

        if (FaucetClaim::query()->where('status', FaucetClaimStatus::Pending)->exists()) {
            if ($this->output->isVerbose()) {
                $this->comment('Skipped: pending faucet claims (avoids overwriting reserved book balance).');
            }

            return self::SUCCESS;
        }

        try {
            $onChain = $rpc->getBalance();
            $row = FaucetBalance::singleton();
            $row->update([
                'balance' => $onChain,
                'last_sync' => now(),
            ]);
            if ($this->output->isVerbose()) {
                $this->info("Synced book balance to {$onChain} KOTO (getbalance).");
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::warning('faucet.sync-balance failed', ['message' => $e->getMessage()]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
