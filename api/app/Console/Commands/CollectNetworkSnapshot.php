<?php

namespace App\Console\Commands;

use App\Models\NetworkSnapshot;
use App\Models\PoolSnapshot;
use App\Services\NetworkTracker\NetworkRpcService;
use App\Services\NetworkTracker\PoolStatsParser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CollectNetworkSnapshot extends Command
{
    protected $signature = 'network:snapshot {coin : Coin symbol e.g. KOTO}';

    protected $description = 'Collect network + pool hashrate snapshots for the tracker';

    public function handle(): int
    {
        if (! config('network_tracker.enabled')) {
            return self::SUCCESS;
        }

        $coin = strtoupper(trim($this->argument('coin')));
        $config = config('network_tracker.coins.'.$coin);

        if (! $config) {
            $this->error("Unknown coin for tracker: {$coin}");

            return self::FAILURE;
        }

        $capturedAt = Carbon::now()->utc();
        $rpcKey = $config['rpc_config_key'];

        try {
            $rpc = new NetworkRpcService($rpcKey);
            $net = $rpc->fetchNetworkStats();

            NetworkSnapshot::query()->create([
                'coin' => $coin,
                'captured_at' => $capturedAt,
                'block_height' => $net['block_height'],
                'network_hashrate' => $net['network_hashrate'],
                'difficulty' => $net['difficulty'],
                'network_connections' => $net['network_connections'],
            ]);
        } catch (Throwable $e) {
            Log::error('network:snapshot RPC failed', ['coin' => $coin, 'message' => $e->getMessage()]);
            $this->error('RPC failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $coinSlug = strtolower($coin);

        foreach ($config['pools'] as $slug => $meta) {
            $this->collectPool(
                $coin,
                $slug,
                $meta['name'],
                $meta['stats_url'],
                $meta['parser'],
                $meta['pool_fee'] ?? null,
                $capturedAt,
                $coinSlug
            );
        }

        $this->calculateUnknown($coin, $capturedAt);

        $this->info("Snapshot OK for {$coin} at {$capturedAt->toIso8601String()}");

        return self::SUCCESS;
    }

    private function collectPool(
        string $coin,
        string $poolSlug,
        string $poolName,
        string $statsUrl,
        string $parser,
        ?float $defaultFee,
        Carbon $capturedAt,
        string $coinSlug
    ): void {
        $defaults = [
            'hashrate' => 0.0,
            'miners' => 0,
            'workers' => 0,
            'blocks_found' => 0,
        ];

        $parsed = $defaults;
        $online = false;
        $poolUrl = null;

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get($statsUrl);

            if (! $response->successful()) {
                throw new RuntimeException('HTTP '.$response->status());
            }

            $data = $response->json();
            if (! is_array($data)) {
                throw new RuntimeException('Invalid JSON');
            }

            $parsed = match ($parser) {
                'nomp' => PoolStatsParser::parseNomp($coinSlug, $data),
                'leywapool' => PoolStatsParser::parseLeywapool($coinSlug, $data),
                default => PoolStatsParser::parseNomp($coinSlug, $data),
            };

            $online = true;

            $poolUrl = match ($poolSlug) {
                'isekai' => 'https://koto.isekai-pool.com',
                'mofumofu' => 'https://koto.mofumofu.me',
                'leywapool' => 'https://leywapool.com',
                default => null,
            };
        } catch (Throwable $e) {
            Log::warning('network:snapshot pool fetch failed', [
                'pool' => $poolSlug,
                'url' => $statsUrl,
                'message' => $e->getMessage(),
            ]);
        }

        PoolSnapshot::query()->create([
            'coin' => $coin,
            'pool_slug' => $poolSlug,
            'pool_name' => $poolName,
            'pool_url' => $poolUrl,
            'captured_at' => $capturedAt,
            'hashrate' => $parsed['hashrate'],
            'miners' => $parsed['miners'],
            'workers' => $parsed['workers'],
            'blocks_found' => $parsed['blocks_found'],
            'pool_fee' => $defaultFee,
            'is_online' => $online,
        ]);
    }

    private function calculateUnknown(string $coin, Carbon $capturedAt): void
    {
        $network = NetworkSnapshot::query()
            ->where('coin', $coin)
            ->where('captured_at', $capturedAt)
            ->first();

        if (! $network) {
            return;
        }

        $known = (float) PoolSnapshot::query()
            ->where('coin', $coin)
            ->where('captured_at', $capturedAt)
            ->where('pool_slug', '!=', 'unknown')
            ->sum('hashrate');

        $networkHs = (float) (string) $network->network_hashrate;
        $unknown = max(0, $networkHs - $known);

        PoolSnapshot::query()->create([
            'coin' => $coin,
            'pool_slug' => 'unknown',
            'pool_name' => 'Unknown / Private',
            'pool_url' => null,
            'captured_at' => $capturedAt,
            'hashrate' => $unknown,
            'miners' => 0,
            'workers' => 0,
            'blocks_found' => 0,
            'pool_fee' => null,
            'is_online' => true,
        ]);
    }
}
