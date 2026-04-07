# CPU Coins Index — Cursor Spec
**Location:** `isekai-pool.com/cpu-coins`  
**Stack:** Laravel 11 + Blade (or API endpoint + vanilla JS)  
**Goal:** Single-page quick-view of all major CPU-minable coins with live network data

---

## 1. Page Purpose

A lightweight reference page showing real-time stats for CPU-minable coins.
Targets people searching "best CPU mining coin 2026" and funnels them toward Isekai Pool.

---

## 2. Data Sources

### 2a. Coin RPC (per supported coin)
Query each coin's local or public RPC node.

```
POST http://<rpc-host>:<port>/
{
  "method": "getmininginfo",
  "params": [],
  "id": 1
}
```

**Fields needed:**
| Field | Source method |
|---|---|
| `networkhashps` | `getmininginfo` or `getnetworkhashps` |
| `difficulty` | `getmininginfo` |
| `blocks` | `getmininginfo` |

For KOTO specifically use the already-running pool RPC connection.

---

### 2b. Pool API Aggregation
Query known public pools per coin to count active pools and get additional hashrate confirmation.

**Fields needed per pool:**
| Field | Notes |
|---|---|
| `hashrate` | Pool hashrate (cross-reference with network) |
| `workers` | Active miner count |
| `lastblock` | Last block found (freshness check) |

**Known pool API formats:**
- MPOS-style: `GET /api?page=api&action=getpoolstatus`
- PoolUI / nodejs-pool: `GET /stats`
- Custom: varies — add per coin in config

---

### 2c. CoinGecko API (free tier)
```
GET https://api.coingecko.com/api/v3/simple/price
  ?ids=koto,monero,zephyr,dero
  &vs_currencies=usd
  &include_24hr_change=true
  &include_market_cap=true
```

**Fields needed:**
| Field | Notes |
|---|---|
| `usd` | Current price |
| `usd_24h_change` | % change |
| `usd_market_cap` | Market cap |

---

### 2d. Static Config (no API needed)
Stored in `config/cpu_coins.php` or a JSON file. Human-maintained, updated on deploy.

```php
[
  'koto' => [
    'name'          => 'KOTO',
    'algo'          => 'yescrypt-r16',
    'coingecko_id'  => 'koto',
    'rpc_host'      => env('KOTO_RPC_HOST'),
    'rpc_port'      => env('KOTO_RPC_PORT'),
    'rpc_user'      => env('KOTO_RPC_USER'),
    'rpc_pass'      => env('KOTO_RPC_PASS'),
    'pool_apis'     => [
      'https://koto.isekai-pool.com/api/stats',
    ],
    'best_cpu'      => 'Intel i7-8700 / Ryzen 5 2600',
    'min_ram_gb'    => 4,
    'est_hashrate'  => '5–8 kH/s (i7-8th gen)',
    'isekai_pool'   => true, // show "Mine with us" CTA
    'explorer_url'  => 'https://explorer.ko-to.org',
  ],
  'monero' => [
    'name'          => 'Monero',
    'algo'          => 'RandomX',
    'coingecko_id'  => 'monero',
    'rpc_host'      => null, // use public node
    'pool_apis'     => [
      'https://supportxmr.com/api/pool/stats',
    ],
    'best_cpu'      => 'Ryzen 9 5900X / i9-10900',
    'min_ram_gb'    => 2,
    'est_hashrate'  => '8–15 kH/s (Ryzen 9)',
    'isekai_pool'   => false,
    'explorer_url'  => 'https://xmrchain.net',
  ],
  // ... ZEPH, DERO, RYO, LOKI, etc.
]
```

---

## 3. Laravel Backend

### 3a. Service Class
`app/Services/CpuCoinService.php`

**Methods:**
- `getAllCoins(): array` — returns merged data for all coins from cache
- `refreshCoin(string $coinKey): array` — fetches fresh data for one coin
- `fetchRpc(array $coinConfig): array` — hits coin RPC
- `fetchPoolApis(array $coinConfig): array` — aggregates pool APIs
- `fetchPrice(string $coingeckoId): array` — hits CoinGecko

### 3b. Cache Strategy
Use Laravel cache with tagged keys:

```php
Cache::remember("cpu_coin_{$key}", now()->addMinutes(5), fn() =>
    $this->refreshCoin($key)
);
```

**Refresh cadence:**
| Data type | TTL |
|---|---|
| Price / market | 5 minutes |
| Network hashrate | 5 minutes |
| Pool count / workers | 5 minutes |
| Static config | On deploy only |

### 3c. API Endpoint
`GET /api/cpu-coins`

Returns JSON array — used by the frontend to hydrate the page.

```json
[
  {
    "key": "koto",
    "name": "KOTO",
    "algo": "yescrypt-r16",
    "price_usd": 0.0023,
    "price_change_24h": 2.4,
    "market_cap": 180000,
    "network_hashrate_khs": 4820,
    "difficulty": 12043.5,
    "block_height": 1482910,
    "pool_count": 3,
    "total_pool_hashrate_khs": 3200,
    "best_cpu": "Intel i7-8700 / Ryzen 5 2600",
    "est_hashrate": "5–8 kH/s (i7-8th gen)",
    "min_ram_gb": 4,
    "isekai_pool": true,
    "explorer_url": "https://explorer.ko-to.org",
    "updated_at": "2026-04-07T14:05:00Z"
  }
]
```

### 3d. Scheduler
`app/Console/Kernel.php`

```php
$schedule->call(fn() => app(CpuCoinService::class)->refreshAll())
         ->everyFiveMinutes();
```

### 3e. Route
```php
Route::get('/cpu-coins', [CpuCoinController::class, 'index']);
Route::get('/api/cpu-coins', [CpuCoinController::class, 'apiIndex']);
```

---

## 4. Frontend — Single Page

### 4a. Layout
One page, no pagination. Coin cards in a responsive grid.

**Page structure:**
```
<header>  CPU Mining Index — Quick View
<subhead> Live network data. Best coins to mine with your CPU right now.
<last-updated> Updated 2 min ago

<grid>
  [CoinCard] [CoinCard] [CoinCard]
  [CoinCard] [CoinCard] ...
</grid>

<footer-note> Hashrate estimates based on consumer desktop CPUs.
              KOTO is minable on Isekai Pool — plug in and go.
```

### 4b. Coin Card Fields
Each card shows:

| Field | Display |
|---|---|
| Coin name + ticker | Large, bold |
| Algorithm | Badge/pill |
| Price USD | With 24h % change indicator |
| Network hashrate | Formatted (kH/s, MH/s, GH/s auto) |
| Difficulty | Formatted number |
| Active pools | Count only |
| Best CPU | Text |
| Est. hashrate | Text |
| Min RAM | e.g. "4 GB+" |
| Mine with Isekai | CTA button (only if `isekai_pool: true`) |
| Explorer link | Small external link |
| Last updated | Relative timestamp |

### 4c. Sort / Filter (lightweight)
Simple client-side JS only — no backend query:
- Sort by: Price, Hashrate, Difficulty, Market Cap
- Filter by algo: All / yescrypt / RandomX / Argon2 / etc.

### 4d. Data Freshness Indicator
Show a colored dot per card:
- Green = updated < 10 min ago
- Yellow = 10–30 min
- Red = stale > 30 min (RPC unreachable)

### 4e. Auto-refresh
Poll `/api/cpu-coins` every 5 minutes via `setInterval` — update cards in place without full page reload.

---

## 5. Error Handling

| Scenario | Behavior |
|---|---|
| RPC unreachable | Show last cached value + stale indicator |
| CoinGecko rate limit | Use cached price, show "Price delayed" |
| Pool API down | Show "N/A" for pool count, don't block render |
| Coin has no RPC config | Pull hashrate from pool APIs only |

---

## 6. SEO Considerations

- Page title: `CPU Mining Coins — Live Hashrate & Difficulty | Isekai Pool`
- Meta description: `Compare CPU-minable coins by hashrate, difficulty, and price. yescrypt, RandomX, Argon2 and more. Updated every 5 minutes.`
- H1: `CPU Mining Index`
- Each coin card should use semantic markup (`<article>`, `<h2>` for coin name)
- Static render via Blade for initial HTML (SSR) — JS hydrates with live data after load

---

## 7. Files to Create

```
app/
  Services/
    CpuCoinService.php
  Http/
    Controllers/
      CpuCoinController.php
config/
  cpu_coins.php
resources/
  views/
    cpu-coins/
      index.blade.php
      _card.blade.php
public/
  js/
    cpu-coins.js         ← polling + sort/filter logic
routes/
  web.php                ← add route
  api.php                ← add API route
```

---

## 8. Environment Variables to Add

```env
KOTO_RPC_HOST=127.0.0.1
KOTO_RPC_PORT=8432
KOTO_RPC_USER=rpcuser
KOTO_RPC_PASS=rpcpass
COINGECKO_API_KEY=          # optional, free tier works without
```

---

## 9. Supported Coins (Initial List)

| Coin | Algo | Notes |
|---|---|---|
| KOTO | yescrypt-r16 | Isekai Pool native — feature prominently |
| Monero (XMR) | RandomX | Biggest CPU coin — credibility anchor |
| Zephyr (ZEPH) | RandomX | Growing CPU community |
| DERO | AstroBWT | Interesting algo, small community |
| Ryo (RYO) | CN-GPU | Small but active |
| Salvium (SAL) | RandomX | New privacy coin |

Add more via `config/cpu_coins.php` — no code changes needed.

---

## 10. Future Enhancements (out of scope for v1)

- Per-coin profitability calculator (input your hashrate → daily earnings)
- CPU benchmark database (community-submitted)
- Alert system (notify when difficulty drops = good time to mine)
- Embed widget for other sites
