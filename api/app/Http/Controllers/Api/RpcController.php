<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RpcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RpcController extends Controller
{
    /**
     * GET /api/v1/health
     * All nodes quick status.
     */
    public function health(): JsonResponse
    {
        $coins   = array_keys(array_filter(config('coins'), fn($c) => $c['status'] === 'active'));
        $results = [];

        foreach ($coins as $coin) {
            try {
                $rpc    = new RpcService($coin);
                $blocks = $rpc->call('getblockcount');
                $results[$coin] = [
                    'online' => true,
                    'blocks' => $blocks,
                    'symbol' => config("coins.$coin.symbol"),
                    'algo'   => config("coins.$coin.algo"),
                ];
            } catch (\Throwable) {
                $results[$coin] = [
                    'online' => false,
                    'blocks' => null,
                    'symbol' => config("coins.$coin.symbol"),
                    'algo'   => config("coins.$coin.algo"),
                ];
            }
        }

        return response()->json([
            'timestamp' => now()->toISOString(),
            'nodes'     => $results,
        ]);
    }

    /**
     * GET /api/v1/{coin}/status
     * Detailed blockchain info for one coin.
     */
    public function status(string $coin): JsonResponse
    {
        $coinConfig = config("coins.$coin");
        if (!$coinConfig) {
            return response()->json(['error' => "Unknown coin: $coin"], 404);
        }

        try {
            $rpc  = new RpcService($coin);
            $info = $rpc->call('getblockchaininfo');
            $net  = $rpc->call('getnetworkinfo');

            return response()->json([
                'coin'        => strtoupper($coin),
                'symbol'      => $coinConfig['symbol'],
                'algo'        => $coinConfig['algo'],
                'online'      => true,
                'blocks'      => $info['blocks'] ?? null,
                'headers'     => $info['headers'] ?? null,
                'synced'      => ($info['blocks'] ?? 0) >= ($info['headers'] ?? 1),
                'progress'    => round(($info['verificationprogress'] ?? 0) * 100, 2),
                'difficulty'  => $info['difficulty'] ?? null,
                'peers'       => $net['connections'] ?? null,
                'version'     => $net['subversion'] ?? null,
                'explorer'    => $coinConfig['explorer'] ?? null,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'coin'   => strtoupper($coin),
                'symbol' => $coinConfig['symbol'],
                'online' => false,
                'error'  => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * POST /api/v1/{coin}/rpc
     * Public RPC proxy. Body: { "method": "getblockcount", "params": [] }
     */
    public function proxy(Request $request, string $coin): JsonResponse
    {
        $request->validate([
            'method' => 'required|string|max:64',
            'params' => 'sometimes|array',
        ]);

        try {
            $rpc    = new RpcService($coin);
            $result = $rpc->call(
                $request->input('method'),
                $request->input('params', [])
            );

            return response()->json([
                'coin'   => strtoupper($coin),
                'method' => $request->input('method'),
                'result' => $result,
            ]);

        } catch (RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
