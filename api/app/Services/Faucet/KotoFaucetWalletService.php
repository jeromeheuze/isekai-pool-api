<?php

namespace App\Services\Faucet;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Privileged KOTO JSON-RPC for the faucet hot wallet (sendtoaddress, getbalance).
 * Uses coins.koto RPC credentials — not exposed via the public RpcService whitelist.
 */
class KotoFaucetWalletService
{
    public function sendToAddress(string $address, string $amount): string
    {
        $result = $this->call('sendtoaddress', [$address, $amount]);

        if (! is_string($result) || strlen($result) < 8) {
            throw new RuntimeException('Unexpected sendtoaddress response');
        }

        return $result;
    }

    public function getBalance(): string
    {
        $result = $this->call('getbalance', []);

        if (is_float($result) || is_int($result)) {
            return (string) $result;
        }

        if (is_string($result)) {
            return $result;
        }

        throw new RuntimeException('Unexpected getbalance response');
    }

    private function call(string $method, array $params): mixed
    {
        $rpc = config('coins.koto.rpc');
        $url = 'http://'.$rpc['host'].':'.$rpc['port'].'/';

        $payload = json_encode([
            'jsonrpc' => '1.0',
            'id' => uniqid('', true),
            'method' => $method,
            'params' => $params,
        ]);

        $auth = base64_encode(($rpc['user'] ?? '').':'.($rpc['pass'] ?? ''));

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic '.$auth,
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error("KOTO faucet RPC curl error [$method]: $error");
            throw new RuntimeException('KOTO node unreachable');
        }

        $data = json_decode($response, true);

        if (isset($data['error']) && $data['error']) {
            $msg = $data['error']['message'] ?? 'Unknown RPC error';
            throw new RuntimeException('KOTO RPC: '.$msg);
        }

        return $data['result'] ?? null;
    }
}
