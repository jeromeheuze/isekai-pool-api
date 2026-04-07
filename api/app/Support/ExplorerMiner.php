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
        if ($raw === false || $raw === '') {
            return 'Unknown';
        }

        // Case-insensitive — pools often embed hostnames in mixed case in the extranonce area.
        if (stripos($raw, 'isekai-pool.com') !== false) {
            return 'isekai-pool.com';
        }
        if (stripos($raw, 'mofumofu') !== false) {
            return 'mofumofu.me';
        }
        if (stripos($raw, 'leywapool') !== false) {
            return 'leywapool.com';
        }

        return 'Unknown';
    }

    /**
     * @param  array<string, mixed>  $tx  Verbose transaction (getrawtransaction …, 1) or embedded getblock …, 2 tx
     */
    public static function labelFromTransaction(array $tx): string
    {
        $vin = $tx['vin'][0] ?? null;
        if (! is_array($vin)) {
            return 'Unknown';
        }

        $hex = null;
        if (isset($vin['coinbase']) && is_string($vin['coinbase'])) {
            $hex = $vin['coinbase'];
        } elseif (isset($vin['scriptSig']['hex']) && is_string($vin['scriptSig']['hex'])) {
            // Some RPC shapes expose coinbase payload only under scriptSig (compatibility).
            $hex = $vin['scriptSig']['hex'];
        }

        return self::labelFromCoinbaseHex($hex);
    }
}
