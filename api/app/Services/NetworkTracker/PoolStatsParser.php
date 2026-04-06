<?php

namespace App\Services\NetworkTracker;

class PoolStatsParser
{
    /**
     * zny-nomp / NOMP style /api/stats — pool key is coin name (e.g. koto).
     *
     * @return array{hashrate: float|int, miners: int, workers: int, blocks_found: int}
     */
    public static function parseNomp(string $coinSlug, array $data): array
    {
        $pools = $data['pools'] ?? [];
        $pool = $pools[$coinSlug] ?? $pools[strtoupper($coinSlug)] ?? [];

        $stats = $pool['poolStats'] ?? [];

        return [
            'hashrate' => (float) ($pool['hashrate'] ?? 0),
            'miners' => (int) ($pool['minerCount'] ?? 0),
            'workers' => (int) ($pool['workerCount'] ?? 0),
            'blocks_found' => (int) ($stats['validBlocks'] ?? $pool['blocks'] ?? 0),
        ];
    }

    /**
     * Best-effort parser for leywapool.com/site/api — structure may vary.
     */
    public static function parseLeywapool(string $coinSlug, array $data): array
    {
        $pools = $data['pools'] ?? null;
        if (is_array($pools)) {
            $pool = $pools[$coinSlug] ?? $pools[strtoupper($coinSlug)] ?? [];
            if ($pool !== []) {
                return [
                    'hashrate' => (float) ($pool['hashrate'] ?? 0),
                    'miners' => (int) ($pool['minerCount'] ?? $pool['miners'] ?? 0),
                    'workers' => (int) ($pool['workerCount'] ?? $pool['workers'] ?? 0),
                    'blocks_found' => (int) ($pool['poolStats']['validBlocks'] ?? $pool['blocks'] ?? 0),
                ];
            }
        }

        return [
            'hashrate' => (float) ($data['hashrate'] ?? $data['poolHashrate'] ?? 0),
            'miners' => (int) ($data['miners'] ?? $data['minerCount'] ?? 0),
            'workers' => (int) ($data['workers'] ?? $data['workerCount'] ?? 0),
            'blocks_found' => (int) ($data['blocks'] ?? $data['validBlocks'] ?? 0),
        ];
    }
}
