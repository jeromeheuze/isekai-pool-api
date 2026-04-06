<?php

namespace App\Support;

use Carbon\Carbon;

final class ExplorerFormat
{
    public static function shortHash(string $hash): string
    {
        if (strlen($hash) <= 16) {
            return $hash;
        }

        return substr($hash, 0, 6).'…'.substr($hash, -4);
    }

    public static function koto(float $amount): string
    {
        return number_format($amount, 8, '.', ',');
    }

    public static function networkHs(float $hs): string
    {
        if ($hs >= 1e9) {
            return number_format($hs / 1e9, 2).' GH/s';
        }
        if ($hs >= 1e6) {
            return number_format($hs / 1e6, 2).' MH/s';
        }
        if ($hs >= 1e3) {
            return number_format($hs / 1e3, 2).' KH/s';
        }

        return number_format($hs, 2).' H/s';
    }

    public static function bytes(int $b): string
    {
        if ($b >= 1048576) {
            return number_format($b / 1048576, 2).' MB';
        }
        if ($b >= 1024) {
            return number_format($b / 1024, 2).' KB';
        }

        return $b.' B';
    }

    public static function ago(int $unix): string
    {
        return Carbon::createFromTimestamp($unix)->diffForHumans();
    }
}
