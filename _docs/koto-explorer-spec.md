# KOTO Block Explorer — Spec
**explorer.isekai-pool.com · Laravel · Build after faucet MVP**
**Priority: Medium — build once faucet shrine visit works end-to-end**

---

## Why Build This

Both official KOTO explorers are dead:
- `explorer.ko-to.org` — DNS fails
- `insight.kotocoin.info` — unreachable

Your VPS has a fully synced KOTO node with `txindex=1`.
A lightweight RPC-based explorer uses ~0 extra RAM — just queries the existing node on demand.

`explorer.isekai-pool.com` becomes the **only working KOTO block explorer** — massive SEO
and community value. Every KOTO user, miner, and developer needs this.

---

## RAM Constraint

VPS has ~228MB free RAM. No indexer. All data served directly from KOTO RPC node via HTTP.
`txindex=1` is already set in `koto.conf` — full transaction lookup works without extra indexing.

---

## DNS

Add A record: `explorer.isekai-pool.com → 153.75.225.100`
Nginx vhost + Let's Encrypt SSL (same pattern as koto.isekai-pool.com)

---

## Routes

```
GET /                           → latest blocks + network stats
GET /block/{height_or_hash}     → block details + transactions
GET /tx/{txid}                  → transaction details
GET /address/{address}          → address balance + received
GET /search                     → redirect to block/tx/address based on input
```

All routes are Laravel web routes — server-rendered Blade, no JS framework needed.
Fast, SEO-friendly, works without JavaScript.

---

## Pages

### Home `/`

**Network stats bar:**
```
Block Height: 4,342,xxx  |  Network H/s: 19.58 KH/s  |  Difficulty: 157.18  |  Peers: 19
```

**Latest 20 blocks table:**
| Height | Hash (truncated) | Time | Miner | Txs | Size |
|--------|-----------------|------|-------|-----|------|
| 4342224 | 7ab182...ac7d | 2 mins ago | isekai-pool.com | 1 | 1.2 KB |

**Search bar** — accepts block height, block hash, txid, or address.

---

### Block `/block/{height_or_hash}`

RPC calls: `getblock {hash} 2` (verbose=2 for full tx data)
If input is a number: first call `getblockhash {height}` then `getblock`

**Display:**
- Block height, hash, timestamp
- Previous block link / Next block link
- Size, weight, difficulty, nonce
- Mined by (parsed from coinbase)
- Transactions list (txid + value)

---

### Transaction `/tx/{txid}`

RPC call: `getrawtransaction {txid} 1`

**Display:**
- Txid, block height, confirmations, timestamp
- Inputs (vin) — previous txid + vout index
- Outputs (vout) — address + amount
- Total input / output / fee

**Note:** Shielded (z) transactions show limited data by design — that's KOTO's privacy feature.
Display: "Shielded transaction — details private by design."

---

### Address `/address/{address}`

RPC calls:
- `getreceivedbyaddress {address} 0` → total received (0 confirmations)
- `getreceivedbyaddress {address} 1` → confirmed received
- `listunspent 0 9999999 ["{address}"]` → unspent outputs

**Display:**
- Address (k1... or jz...)
- Total received
- Current balance (approximate — no full UTXO index)
- Unspent outputs list

**Limitation note:** Full transaction history requires an indexer.
Display: "For full history, download the KOTO Core Client."

---

## RPC Helper (Laravel Service)

```php
// app/Services/KotoRpcService.php

class KotoRpcService
{
    private function call(string $method, array $params = []): mixed
    {
        $response = Http::withBasicAuth(
            config('koto.rpc_user'),
            config('koto.rpc_pass')
        )->post('http://' . config('koto.rpc_host') . ':' . config('koto.rpc_port'), [
            'jsonrpc' => '1.0',
            'id'      => 'explorer',
            'method'  => $method,
            'params'  => $params,
        ]);

        if ($response->json('error')) {
            throw new \Exception($response->json('error.message'));
        }

        return $response->json('result');
    }

    public function getBlockCount(): int
    {
        return $this->call('getblockcount');
    }

    public function getBlockHash(int $height): string
    {
        return $this->call('getblockhash', [$height]);
    }

    public function getBlock(string $hash, int $verbose = 1): array
    {
        return $this->call('getblock', [$hash, $verbose]);
    }

    public function getTransaction(string $txid): array
    {
        return $this->call('getrawtransaction', [$txid, 1]);
    }

    public function getMiningInfo(): array
    {
        return $this->call('getmininginfo');
    }

    public function getNetworkInfo(): array
    {
        return $this->call('getnetworkinfo');
    }

    public function getReceivedByAddress(string $address, int $minConf = 1): float
    {
        return $this->call('getreceivedbyaddress', [$address, $minConf]);
    }

    public function getLatestBlocks(int $count = 20): array
    {
        $height = $this->getBlockCount();
        $blocks = [];
        for ($i = $height; $i > max(0, $height - $count); $i--) {
            $hash = $this->getBlockHash($i);
            $blocks[] = $this->getBlock($hash, 1);
        }
        return $blocks;
    }
}
```

---

## Caching Strategy

Since the VPS is RAM-constrained, cache aggressively:

```php
// Home page stats — refresh every 30 seconds
Cache::remember('koto:network_stats', 30, fn() => $rpc->getMiningInfo());

// Latest blocks — refresh every 30 seconds  
Cache::remember('koto:latest_blocks', 30, fn() => $rpc->getLatestBlocks(20));

// Individual blocks — cache forever (immutable once confirmed)
Cache::rememberForever("koto:block:{$hash}", fn() => $rpc->getBlock($hash, 2));

// Transactions — cache forever (immutable)
Cache::rememberForever("koto:tx:{$txid}", fn() => $rpc->getTransaction($txid));

// Address balance — refresh every 60 seconds
Cache::remember("koto:address:{$address}", 60, fn() => ...);
```

Use Laravel file cache driver — no extra Redis memory needed for explorer data.

---

## Search Logic

```php
public function search(string $query): RedirectResponse
{
    $query = trim($query);

    // Block height (pure number)
    if (ctype_digit($query)) {
        return redirect()->route('explorer.block', $query);
    }

    // Block hash or txid (64 hex chars)
    if (strlen($query) === 64 && ctype_xdigit($query)) {
        // Try as block hash first
        try {
            $block = $rpc->getBlock($query);
            return redirect()->route('explorer.block', $query);
        } catch (\Exception $e) {
            // Try as txid
            return redirect()->route('explorer.tx', $query);
        }
    }

    // Address (starts with k1 or jz)
    if (preg_match('/^(k1|jz)[a-zA-Z0-9]{38,}$/', $query)) {
        return redirect()->route('explorer.address', $query);
    }

    return back()->with('error', 'Not found — try a block height, hash, txid, or address');
}
```

---

## "Mined By" Detection

Parse the coinbase transaction to show pool name instead of raw address:

```php
function getMinerLabel(array $block): string
{
    // Get coinbase tx
    $coinbaseTxid = $block['tx'][0];
    $tx = $rpc->getTransaction($coinbaseTxid);
    
    // Decode coinbase script
    $coinbaseHex = $tx['vin'][0]['coinbase'] ?? '';
    $decoded = hex2bin($coinbaseHex);
    
    // Check for known pool identifiers
    if (str_contains($decoded, 'isekai-pool.com')) return 'isekai-pool.com';
    if (str_contains($decoded, 'mofumofu'))        return 'mofumofu.me';
    if (str_contains($decoded, 'leywapool'))        return 'leywapool.com';
    
    return 'Unknown';
}
```

---

## Design

Match isekai-pool.com dark theme exactly:
- Background: `#0d0f14`
- Accent: `#7c6af7`
- Monospace font for hashes, addresses, amounts
- Gold `#f0c040` for KOTO amounts
- Truncate long hashes: `7ab182...ac7d` with full hash on hover/click to copy
- Mobile responsive — tables scroll horizontally on small screens
- Isekai Pool logo in nav, link back to isekai-pool.com

---

## Nginx Config

```nginx
server {
    listen 80;
    server_name explorer.isekai-pool.com;

    root /var/www/isekai-pool-api/api/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Same Laravel app, just a new subdomain pointing to the same `public/` directory.
Add SSL: `certbot --nginx -d explorer.isekai-pool.com`

---

## Build Order (after faucet MVP)

1. `KotoRpcService` — shared with faucet, likely already exists
2. DNS + nginx + SSL for `explorer.isekai-pool.com`
3. Home page (latest blocks + network stats)
4. Block page
5. Transaction page
6. Address page
7. Search
8. Update `isekai-pool.com/faucet.html` explorer link
9. Update `koto.isekai-pool.com` explorer links
10. Post in KOTO community — "working explorer at explorer.isekai-pool.com"

---

## SEO Value

- `explorer.isekai-pool.com/address/k1xxx` — every wallet address becomes a page
- `explorer.isekai-pool.com/block/4342086` — every block mined by isekai-pool.com is a page
- With both official explorers dead, this ranks #1 for "KOTO block explorer" with minimal effort
- Internal links from koto.isekai-pool.com, isekai-pool.com/faucet.html, miningpoolstats.stream

---

## Notes for Cursor

- Build AFTER faucet shrine visit works end-to-end
- `KotoRpcService` should be shared between faucet payouts and explorer — create once, use everywhere
- Use Laravel file cache (not Redis) for explorer data to save RAM
- No JavaScript framework — pure Blade templates, fast page loads
- The `getLatestBlocks` method loops N RPC calls — cache aggressively or it's slow
- Shielded transactions (z→z) show minimal data by design — don't try to decode them
- `txindex=1` is confirmed in koto.conf — full tx lookup works
