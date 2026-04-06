<?php

namespace App\Support;

/**
 * Parse coinbase / pool identifiers for display on the KOTO explorer.
 */
final class ExplorerMiner
{
    public static function labelFromCoinbaseHex(?string $coinbaseHex): string
    {
        if ($coinbaseHex === null || $coinbaseHex === '') {
            return 'Unknown';
        }

        $raw = @hex2bin($coinbaseHex);
        if ($raw === false) {
            return 'Unknown';
        }

        if (str_contains($raw, 'isekai-pool.com')) {
            return 'isekai-pool.com';
        }
        if (str_contains($raw, 'mofumofu')) {
            return 'mofumofu.me';
        }
        if (str_contains($raw, 'leywapool')) {
            return 'leywapool.com';
        }

        return 'Unknown';
    }

    /**
     * @param  array<string, mixed>  $tx  Verbose transaction (getrawtransaction …, true)
     */
    public static function labelFromTransaction(array $tx): string
    {
        $vin = $tx['vin'][0] ?? null;
        if (! is_array($vin)) {
            return 'Unknown';
        }

        $coinbase = $vin['coinbase'] ?? null;

        return self::labelFromCoinbaseHex(is_string($coinbase) ? $coinbase : null);
    }
}
