<?php

namespace App\Support;

/**
 * Transparent / shielded address shape checks for KOTO (k1… / jz…).
 * Full on-chain validity is enforced by the node on send.
 */
final class KotoAddress
{
    /**
     * True if the string matches expected KOTO address prefixes and length.
     * Typical k1 transparent Bech32 is ~35 characters total (k1 + 33 payload).
     */
    public static function isValid(string $address): bool
    {
        return (bool) preg_match('/^(k1|jz)[a-zA-Z0-9]{30,128}$/', $address);
    }
}
