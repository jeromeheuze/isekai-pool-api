<?php

namespace App\Services\Faucet;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TurnstileVerifier
{
    public function verify(?string $token, string $remoteIp): bool
    {
        $secret = config('faucet.turnstile.secret');

        if ($secret === null || $secret === '') {
            if (config('app.debug')) {
                Log::warning('FAUCET_TURNSTILE_SECRET empty — skipping Turnstile (debug only)');

                return true;
            }

            return false;
        }

        if ($token === null || $token === '') {
            return false;
        }

        $response = Http::asForm()->timeout(10)->post(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $remoteIp,
            ]
        );

        if (! $response->successful()) {
            Log::warning('Turnstile HTTP error', ['status' => $response->status()]);

            return false;
        }

        return (bool) ($response->json('success') ?? false);
    }
}
