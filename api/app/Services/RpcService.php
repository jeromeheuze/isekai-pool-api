<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RpcService
{
    // Public methods — no auth required
    private const PUBLIC_METHODS = [
        'getblockcount',
        'getblockchaininfo',
        'getnetworkinfo',
        'getmempoolinfo',
        'getblock',
        'getblockhash',
        'getrawtransaction',
        'decoderawtransaction',
        'sendrawtransaction',
        'gettxoutsetinfo',
        'getdifficulty',
        'getconnectioncount',
    ];

    private array $config;
    private string $coin;

    public function __construct(string $coin)
    {
        $config = config("coins.$coin");
        if (!$config) {
            throw new RuntimeException("Unknown coin: $coin");
        }
        if ($config['status'] !== 'active') {
            throw new RuntimeException("Coin $coin is not yet active on this node");
        }
        $this->config = $config;
        $this->coin   = $coin;
    }

    public function call(string $method, array $params = []): mixed
    {
        if (!in_array($method, self::PUBLIC_METHODS)) {
            throw new RuntimeException("Method '$method' is not available on the public API");
        }

        // Cache read-only calls for 15 seconds
        $readOnly = in_array($method, [
            'getblockcount', 'getblockchaininfo', 'getdifficulty',
            'getconnectioncount', 'gettxoutsetinfo', 'getmempoolinfo',
            'getnetworkinfo',
        ]);

        if ($readOnly) {
            $key = "rpc_{$this->coin}_{$method}_" . md5(json_encode($params));
            return Cache::remember($key, 15, fn() => $this->execute($method, $params));
        }

        return $this->execute($method, $params);
    }

    private function execute(string $method, array $params): mixed
    {
        $rpc = $this->config['rpc'];
        $url = "http://{$rpc['user']}:{$rpc['pass']}@{$rpc['host']}:{$rpc['port']}/";

        $payload = json_encode([
            'jsonrpc' => '1.0',
            'id'      => uniqid(),
            'method'  => $method,
            'params'  => $params,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error("RPC curl error [{$this->coin}/$method]: $error");
            throw new RuntimeException("Node unreachable");
        }

        $data = json_decode($response, true);

        if (isset($data['error']) && $data['error']) {
            $msg = $data['error']['message'] ?? 'Unknown RPC error';
            throw new RuntimeException("RPC error: $msg");
        }

        return $data['result'] ?? null;
    }

    public function isAlive(): bool
    {
        try {
            $this->execute('getblockcount', []);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
