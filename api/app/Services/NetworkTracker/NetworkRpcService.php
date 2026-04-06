<?php

namespace App\Services\NetworkTracker;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Privileged JSON-RPC for network stats (getmininginfo, getblockchaininfo, getnetworkinfo).
 */
class NetworkRpcService
{
    public function __construct(private string $coinConfigKey) {}

    /**
     * @return array{block_height: int, network_hashrate: string, difficulty: string, network_connections: int}
     */
    public function fetchNetworkStats(): array
    {
        $chain = $this->call('getblockchaininfo', []);
        $mining = $this->call('getmininginfo', []);
        $net = $this->call('getnetworkinfo', []);

        $height = (int) ($chain['blocks'] ?? 0);
        $difficulty = (string) ($chain['difficulty'] ?? 0);

        $networkHashPs = $mining['networkhashps']
            ?? $mining['networkhashrate']
            ?? $mining['hashespersec']
            ?? 0;

        $connections = (int) ($net['connections'] ?? 0);

        return [
            'block_height' => $height,
            'network_hashrate' => (string) $networkHashPs,
            'difficulty' => $difficulty,
            'network_connections' => $connections,
        ];
    }

    private function call(string $method, array $params): array
    {
        $rpc = config('coins.'.$this->coinConfigKey.'.rpc');
        if (! $rpc) {
            throw new RuntimeException("Missing RPC config for {$this->coinConfigKey}");
        }

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
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::warning("Network RPC curl error [$method]: $error");
            throw new RuntimeException('RPC unreachable');
        }

        $data = json_decode($response, true);

        if (isset($data['error']) && $data['error']) {
            $msg = $data['error']['message'] ?? 'RPC error';
            throw new RuntimeException($msg);
        }

        $result = $data['result'] ?? [];

        return is_array($result) ? $result : [];
    }
}
