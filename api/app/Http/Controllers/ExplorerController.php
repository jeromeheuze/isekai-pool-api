<?php

namespace App\Http\Controllers;

use App\Services\RpcService;
use App\Support\ExplorerMiner;
use App\Support\KotoAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ExplorerController extends Controller
{
    private function rpc(): RpcService
    {
        return new RpcService(config('explorer.coin'));
    }

    public function index(): View
    {
        $coin = config('explorer.coin');
        $data = Cache::remember("explorer:{$coin}:home", 30, function () {
            $rpc = $this->rpc();
            $tip = (int) $rpc->call('getblockcount', []);
            $mining = $rpc->call('getmininginfo', []);
            $net = $rpc->call('getnetworkinfo', []);
            $rows = [];
            for ($h = $tip; $h > max(0, $tip - 20); $h--) {
                $hash = $rpc->call('getblockhash', [$h]);
                $block = $rpc->call('getblock', [$hash, 1]);
                $txids = $block['tx'] ?? [];
                $coinbaseTxid = is_array($txids) && isset($txids[0]) ? $txids[0] : null;
                $miner = 'Unknown';
                if (is_string($coinbaseTxid)) {
                    try {
                        $cb = $rpc->call('getrawtransaction', [$coinbaseTxid, true]);
                        if (is_array($cb)) {
                            $miner = ExplorerMiner::labelFromTransaction($cb);
                        }
                    } catch (Throwable) {
                        $miner = 'Unknown';
                    }
                }
                $rows[] = [
                    'height' => $h,
                    'hash' => $hash,
                    'time' => (int) ($block['time'] ?? 0),
                    'miner' => $miner,
                    'txs' => is_array($txids) ? count($txids) : 0,
                    'size' => (int) ($block['size'] ?? 0),
                ];
            }

            return [
                'tip' => $tip,
                'network_hashps' => (float) ($mining['networkhashps'] ?? 0),
                'difficulty' => (float) ($mining['difficulty'] ?? 0),
                'peers' => (int) ($net['connections'] ?? 0),
                'rows' => $rows,
            ];
        });

        return view('explorer.home', ['data' => $data]);
    }

    public function block(string $heightOrHash): View
    {
        $rpc = $this->rpc();
        $coin = config('explorer.coin');

        if (strlen($heightOrHash) === 64 && ctype_xdigit($heightOrHash)) {
            $hash = strtolower($heightOrHash);
        } elseif (ctype_digit($heightOrHash)) {
            $h = (int) $heightOrHash;
            if ($h < 0) {
                abort(404);
            }
            try {
                $hash = $rpc->call('getblockhash', [$h]);
            } catch (Throwable) {
                abort(404);
            }
        } else {
            abort(404);
        }

        $block = Cache::rememberForever("explorer:{$coin}:block:v2:{$hash}", function () use ($rpc, $hash) {
            return $rpc->call('getblock', [$hash, 2]);
        });

        if (! is_array($block)) {
            abort(404);
        }

        $height = (int) ($block['height'] ?? 0);
        $tip = (int) $rpc->call('getblockcount', []);
        $prevHeight = $height > 0 ? $height - 1 : null;
        $nextHeight = $height < $tip ? $height + 1 : null;

        $miner = 'Unknown';
        $txList = $block['tx'] ?? [];
        if (is_array($txList) && isset($txList[0]) && is_array($txList[0])) {
            $miner = ExplorerMiner::labelFromTransaction($txList[0]);
        }

        $txRows = [];
        foreach (is_array($txList) ? $txList : [] as $tx) {
            if (! is_array($tx) || ! isset($tx['txid'])) {
                continue;
            }
            $txRows[] = [
                'txid' => $tx['txid'],
                'value' => $this->sumVout($tx),
            ];
        }

        return view('explorer.block', [
            'block' => $block,
            'miner' => $miner,
            'prevHeight' => $prevHeight,
            'nextHeight' => $nextHeight,
            'txRows' => $txRows,
        ]);
    }

    public function tx(string $txid): View
    {
        if (! preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
            abort(404);
        }

        $txidLower = strtolower($txid);
        $rpc = $this->rpc();
        $coin = config('explorer.coin');

        try {
            $raw = $this->rememberForeverRawTransaction($rpc, $coin, $txidLower);
        } catch (Throwable) {
            abort(404);
        }

        if (! is_array($raw)) {
            abort(404);
        }

        $isCoinbase = isset($raw['vin'][0]['coinbase']);
        $isShielded = ! $isCoinbase && empty($raw['vout']);

        $height = $raw['height'] ?? null;
        if ($height === null && ! empty($raw['blockhash'])) {
            try {
                $bh = $rpc->call('getblock', [$raw['blockhash'], 1]);
                if (is_array($bh)) {
                    $height = $bh['height'] ?? null;
                }
            } catch (Throwable) {
                $height = null;
            }
        }

        $sumOut = $this->sumVout($raw);
        $sumIn = 0.0;
        foreach ($raw['vin'] ?? [] as $vin) {
            if (! is_array($vin)) {
                continue;
            }
            if (isset($vin['value'])) {
                $sumIn += (float) $vin['value'];
            }
        }

        if ($isCoinbase) {
            $fee = null;
        } elseif ($sumIn > 0) {
            $fee = max(0, $sumIn - $sumOut);
        } else {
            $fee = null;
        }

        return view('explorer.tx', [
            'tx' => $raw,
            'txid' => $txidLower,
            'height' => $height,
            'isCoinbase' => $isCoinbase,
            'isShielded' => $isShielded,
            'sumIn' => $sumIn,
            'sumOut' => $sumOut,
            'fee' => $fee,
        ]);
    }

    public function address(string $address): View
    {
        $coin = config('explorer.coin');
        $payload = Cache::remember("explorer:{$coin}:addr:{$address}", 60, function () use ($address) {
            $rpc = $this->rpc();
            try {
                $recv0 = (float) $rpc->call('getreceivedbyaddress', [$address, 0]);
                $recv1 = (float) $rpc->call('getreceivedbyaddress', [$address, 1]);
            } catch (Throwable $e) {
                return ['error' => $e->getMessage(), 'recv0' => null, 'recv1' => null, 'utxos' => []];
            }

            try {
                $utxos = $rpc->call('listunspent', [0, 9999999, [$address]]);
            } catch (Throwable) {
                $utxos = [];
            }

            if (! is_array($utxos)) {
                $utxos = [];
            }

            $balance = 0.0;
            foreach ($utxos as $u) {
                if (is_array($u) && isset($u['amount'])) {
                    $balance += (float) $u['amount'];
                }
            }

            return [
                'error' => null,
                'recv0' => $recv0,
                'recv1' => $recv1,
                'balance' => $balance,
                'utxos' => $utxos,
            ];
        });

        return view('explorer.address', [
            'address' => $address,
            'payload' => $payload,
        ]);
    }

    public function search(Request $request): RedirectResponse
    {
        $query = trim((string) $request->query('q', ''));
        if ($query === '') {
            return redirect()->route('explorer.home')->with('error', 'Enter a block height, hash, txid, or address.');
        }

        $rpc = $this->rpc();
        $coin = config('explorer.coin');

        if (ctype_digit($query)) {
            return redirect()->route('explorer.block', ['heightOrHash' => $query]);
        }

        if (strlen($query) === 64 && ctype_xdigit($query)) {
            try {
                $rpc->call('getblock', [$query, 1]);

                return redirect()->route('explorer.block', ['heightOrHash' => $query]);
            } catch (Throwable) {
                try {
                    $this->rememberForeverRawTransaction($rpc, $coin, strtolower($query));

                    return redirect()->route('explorer.tx', ['txid' => strtolower($query)]);
                } catch (Throwable) {
                    return redirect()->route('explorer.home')->with('error', 'No block or transaction found for that hash.');
                }
            }
        }

        if (KotoAddress::isValid($query)) {
            return redirect()->route('explorer.address', ['address' => $query]);
        }

        return redirect()->route('explorer.home')->with('error', 'Not found — try a block height, hash, txid, or transparent address.');
    }

    /**
     * @param  array<string, mixed>  $tx
     */
    private function sumVout(array $tx): float
    {
        $s = 0.0;
        foreach ($tx['vout'] ?? [] as $o) {
            if (is_array($o) && isset($o['value'])) {
                $s += (float) $o['value'];
            }
        }

        return $s;
    }

    /**
     * @return array<string, mixed>
     */
    private function rememberForeverRawTransaction(RpcService $rpc, string $coin, string $txidLower): array
    {
        return Cache::rememberForever("explorer:{$coin}:tx:{$txidLower}", function () use ($rpc, $txidLower) {
            return $this->fetchRawTransactionWithBlockScan($rpc, $txidLower);
        });
    }

    /**
     * Without txindex, getrawtransaction(txid, true) only works in mempool; confirmed txs need blockhash.
     *
     * @return array<string, mixed>
     */
    private function fetchRawTransactionWithBlockScan(RpcService $rpc, string $txidLower): array
    {
        try {
            $raw = $rpc->call('getrawtransaction', [$txidLower, true]);
            if (is_array($raw)) {
                return $raw;
            }
        } catch (Throwable) {
            // fall through to block scan
        }

        $depth = (int) config('explorer.tx_lookup_block_scan_depth', 2500);
        if ($depth <= 0) {
            throw new RuntimeException('getrawtransaction failed and block scan disabled');
        }

        $tip = (int) $rpc->call('getblockcount', []);
        $min = max(0, $tip - $depth);

        for ($h = $tip; $h >= $min; $h--) {
            try {
                $blockHash = $rpc->call('getblockhash', [$h]);
                if (! is_string($blockHash)) {
                    continue;
                }
                $block = $rpc->call('getblock', [$blockHash, 1]);
                if (! is_array($block)) {
                    continue;
                }
                $txs = $block['tx'] ?? [];
                if (! is_array($txs)) {
                    continue;
                }
                $found = false;
                foreach ($txs as $t) {
                    $id = is_string($t) ? $t : (is_array($t) && isset($t['txid']) ? (string) $t['txid'] : '');
                    if ($id !== '' && strtolower($id) === $txidLower) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    $raw = $rpc->call('getrawtransaction', [$txidLower, true, $blockHash]);
                    if (is_array($raw)) {
                        return $raw;
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }

        throw new RuntimeException('Transaction not found in scanned block range');
    }
}
