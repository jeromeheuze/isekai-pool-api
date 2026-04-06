<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NetworkSnapshot;
use App\Models\PoolSnapshot;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NetworkTrackerController extends Controller
{
    public function current(string $coin): JsonResponse
    {
        $coin = strtoupper($coin);
        if (! $this->coinConfigured($coin)) {
            return response()->json(['error' => 'Coin not tracked'], 404);
        }

        $latest = NetworkSnapshot::query()
            ->where('coin', $coin)
            ->orderByDesc('captured_at')
            ->first();

        if (! $latest) {
            return response()->json([
                'coin' => $coin,
                'message' => 'No snapshots yet — run php artisan network:snapshot '.$coin,
                'network' => null,
                'pools' => [],
            ], 200);
        }

        $at = $latest->captured_at;

        $pools = PoolSnapshot::query()
            ->where('coin', $coin)
            ->where('captured_at', $at)
            ->orderBy('pool_slug')
            ->get();

        $networkHash = (float) (string) $latest->network_hashrate;
        $poolRows = $pools->map(function (PoolSnapshot $p) use ($networkHash) {
            $h = (float) (string) $p->hashrate;
            $share = $networkHash > 0 ? round(100 * $h / $networkHash, 2) : 0;

            return [
                'pool_slug' => $p->pool_slug,
                'pool_name' => $p->pool_name,
                'pool_url' => $p->pool_url,
                'hashrate' => (string) $p->hashrate,
                'hashrate_hs' => (float) (string) $p->hashrate,
                'miners' => $p->miners,
                'workers' => $p->workers,
                'blocks_found' => $p->blocks_found,
                'pool_fee' => $p->pool_fee !== null ? (float) (string) $p->pool_fee : null,
                'network_share_percent' => $share,
                'is_online' => $p->is_online,
            ];
        })->values();

        return response()->json([
            'coin' => $coin,
            'captured_at' => $latest->captured_at->toIso8601String(),
            'network' => [
                'block_height' => $latest->block_height,
                'network_hashrate' => (string) $latest->network_hashrate,
                'network_hashrate_hs' => (float) (string) $latest->network_hashrate,
                'difficulty' => (string) $latest->difficulty,
                'network_connections' => $latest->network_connections,
            ],
            'pools' => $poolRows,
        ]);
    }

    public function history(Request $request, string $coin): JsonResponse
    {
        $coin = strtoupper($coin);
        if (! $this->coinConfigured($coin)) {
            return response()->json(['error' => 'Coin not tracked'], 404);
        }

        $range = $request->query('range', '7d');
        $from = $this->rangeToCarbon($range);

        $networkQuery = NetworkSnapshot::query()
            ->where('coin', $coin)
            ->when($from, fn ($q) => $q->where('captured_at', '>=', $from))
            ->orderBy('captured_at');

        $networkRows = (clone $networkQuery)->limit(25000)->get();

        $network = $networkRows->map(fn (NetworkSnapshot $n) => [
            'time' => $n->captured_at->toIso8601String(),
            'hashrate' => (float) (string) $n->network_hashrate,
            'difficulty' => (float) (string) $n->difficulty,
        ])->values();

        $poolQuery = PoolSnapshot::query()
            ->where('coin', $coin)
            ->when($from, fn ($q) => $q->where('captured_at', '>=', $from))
            ->orderBy('captured_at');

        $poolRows = (clone $poolQuery)->limit(100000)->get();

        $pools = $this->groupPoolHistory($poolRows);

        return response()->json([
            'coin' => $coin,
            'range' => $range,
            'network' => $network,
            'pools' => $pools,
        ]);
    }

    public function pools(string $coin): JsonResponse
    {
        $response = $this->current($coin);

        $data = $response->getData(true);
        if (isset($data['error'])) {
            return $response;
        }

        return response()->json([
            'coin' => $data['coin'],
            'captured_at' => $data['captured_at'] ?? null,
            'pools' => $data['pools'] ?? [],
        ]);
    }

    private function coinConfigured(string $coin): bool
    {
        return is_array(config('network_tracker.coins.'.$coin));
    }

    private function rangeToCarbon(?string $range): ?Carbon
    {
        return match ($range) {
            '24h', '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            'all' => null,
            default => now()->subDays(7),
        };
    }

    /**
     * @param  Collection<int, PoolSnapshot>  $rows
     * @return array<string, list<array{time: string, hashrate: float, miners: int}>>
     */
    private function groupPoolHistory(Collection $rows): array
    {
        /** @var Collection<string, Collection<int, PoolSnapshot>> $grouped */
        $grouped = $rows->groupBy('pool_slug');

        $out = [];
        foreach ($grouped as $slug => $group) {
            $out[$slug] = $group->map(fn (PoolSnapshot $p) => [
                'time' => $p->captured_at->toIso8601String(),
                'hashrate' => (float) (string) $p->hashrate,
                'miners' => $p->miners,
            ])->values()->all();
        }

        return $out;
    }
}
