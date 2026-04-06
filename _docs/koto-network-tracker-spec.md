# KOTO Network Pool Tracker — Cursor Spec
**isekai-pool.com/koto-network · Laravel + Chart.js · Historical Pool Data**
**Designed to be replicated for YTN, TDC, and other coins later**

---

## Vision

A public historical dashboard showing all known KOTO mining pools, network hashrate,
and difficulty over time. Data collected every 15 minutes, stored forever.
Becomes the authoritative historical record for KOTO mining since no one else is doing this.

URL: `isekai-pool.com/koto-network`
Future: `isekai-pool.com/ytn-network`, `isekai-pool.com/tdc-network` (same pattern)

---

## Data Sources

| Source | URL | Data |
|--------|-----|------|
| isekai-pool.com | `https://koto.isekai-pool.com/api/stats` | Pool hashrate, miners, workers, blocks |
| mofumofu.me | `https://koto.mofumofu.me/api` | Pool hashrate, miners, blocks |
| leywapool.com | `https://leywapool.com/site/api` | Pool hashrate, miners, blocks |
| KOTO node (RPC) | `http://127.0.0.1:8432` | Network hashrate, difficulty, block height |

---

## Database Schema

### `network_snapshots` table
```php
Schema::create('network_snapshots', function (Blueprint $table) {
    $table->id();
    $table->string('coin', 10);                    // 'KOTO', 'YTN', 'TDC'
    $table->timestamp('captured_at');              // when this snapshot was taken
    $table->integer('block_height');
    $table->decimal('network_hashrate', 20, 4);    // H/s
    $table->decimal('difficulty', 20, 8);
    $table->integer('network_connections');
    $table->timestamps();

    $table->index(['coin', 'captured_at']);
});
```

### `pool_snapshots` table
```php
Schema::create('pool_snapshots', function (Blueprint $table) {
    $table->id();
    $table->string('coin', 10);                    // 'KOTO'
    $table->string('pool_slug');                   // 'isekai', 'mofumofu', 'leywapool', 'unknown'
    $table->string('pool_name');                   // 'isekai-pool.com'
    $table->string('pool_url')->nullable();        // 'https://koto.isekai-pool.com'
    $table->timestamp('captured_at');
    $table->decimal('hashrate', 20, 4);            // H/s
    $table->integer('miners')->default(0);
    $table->integer('workers')->default(0);
    $table->integer('blocks_found')->default(0);   // cumulative from pool API
    $table->decimal('pool_fee', 5, 2)->nullable(); // percentage
    $table->boolean('is_online')->default(true);
    $table->timestamps();

    $table->index(['coin', 'pool_slug', 'captured_at']);
});
```

---

## Scheduled Job

### `App\Console\Commands\CollectNetworkSnapshot`

Runs every 15 minutes via Laravel scheduler.

```php
// In App\Console\Kernel
protected function schedule(Schedule $schedule): void
{
    $schedule->command('network:snapshot KOTO')->everyFifteenMinutes();
    // Future: ->command('network:snapshot YTN')->everyFifteenMinutes();
}
```

### Collection logic:

```php
class CollectNetworkSnapshot extends Command
{
    protected $signature = 'network:snapshot {coin}';

    public function handle(): void
    {
        $coin = strtoupper($this->argument('coin'));
        $capturedAt = now();

        // 1. Collect network stats from RPC node
        $this->collectNetworkStats($coin, $capturedAt);

        // 2. Collect each pool
        $this->collectPool($coin, 'isekai', $capturedAt);
        $this->collectPool($coin, 'mofumofu', $capturedAt);
        $this->collectPool($coin, 'leywapool', $capturedAt);

        // 3. Calculate unknown hashrate
        $this->calculateUnknown($coin, $capturedAt);

        $this->info("Snapshot collected for {$coin} at {$capturedAt}");
    }
}
```

### Pool API parsers:

```php
// isekai-pool.com (zny-nomp format)
private function parseIsekaiPool(array $data): array
{
    $pool = $data['pools']['koto'] ?? [];
    return [
        'pool_slug'   => 'isekai',
        'pool_name'   => 'isekai-pool.com',
        'pool_url'    => 'https://koto.isekai-pool.com',
        'hashrate'    => $pool['hashrate'] ?? 0,
        'miners'      => $pool['minerCount'] ?? 0,
        'workers'     => $pool['workerCount'] ?? 0,
        'blocks_found'=> $pool['poolStats']['validBlocks'] ?? 0,
        'pool_fee'    => 1.0,
        'is_online'   => true,
    ];
}

// mofumofu.me (zny-nomp format — same structure)
private function parseMofumofu(array $data): array
{
    $pool = $data['pools']['koto'] ?? [];
    return [
        'pool_slug'   => 'mofumofu',
        'pool_name'   => 'mofumofu.me',
        'pool_url'    => 'https://koto.mofumofu.me',
        'hashrate'    => $pool['hashrate'] ?? 0,
        'miners'      => $pool['minerCount'] ?? 0,
        'workers'     => $pool['workerCount'] ?? 0,
        'blocks_found'=> $pool['poolStats']['validBlocks'] ?? 0,
        'pool_fee'    => 0.5,
        'is_online'   => true,
    ];
}

// leywapool.com — check actual API format, adjust parser accordingly
private function parseLeywapool(array $data): array
{
    // TODO: inspect https://leywapool.com/site/api response and parse
    return [
        'pool_slug'   => 'leywapool',
        'pool_name'   => 'leywapool.com',
        'pool_url'    => 'https://leywapool.com',
        'hashrate'    => $data['hashrate'] ?? 0,
        'miners'      => $data['miners'] ?? 0,
        'workers'     => $data['workers'] ?? 0,
        'blocks_found'=> $data['blocks'] ?? 0,
        'pool_fee'    => null,
        'is_online'   => true,
    ];
}
```

### Unknown hashrate calculation:
```php
private function calculateUnknown(string $coin, Carbon $capturedAt): void
{
    $network = NetworkSnapshot::where('coin', $coin)
        ->where('captured_at', $capturedAt)
        ->first();

    $knownHashrate = PoolSnapshot::where('coin', $coin)
        ->where('captured_at', $capturedAt)
        ->where('pool_slug', '!=', 'unknown')
        ->sum('hashrate');

    $unknownHashrate = max(0, $network->network_hashrate - $knownHashrate);

    PoolSnapshot::create([
        'coin'        => $coin,
        'pool_slug'   => 'unknown',
        'pool_name'   => 'Unknown / Private',
        'captured_at' => $capturedAt,
        'hashrate'    => $unknownHashrate,
        'miners'      => 0,
        'is_online'   => true,
    ]);
}
```

---

## API Endpoints (Laravel)

```
GET /api/v1/network/{coin}/current
Returns: latest snapshot — all pools + network stats

GET /api/v1/network/{coin}/history?range=24h|7d|30d|all
Returns: time-series data for chart rendering

GET /api/v1/network/{coin}/pools
Returns: pool list with current stats + fee + url
```

### History response format:
```json
{
  "coin": "KOTO",
  "range": "7d",
  "network": [
    { "time": "2026-04-05T00:00:00Z", "hashrate": 23550, "difficulty": 157.18 }
  ],
  "pools": {
    "isekai": [
      { "time": "2026-04-05T00:00:00Z", "hashrate": 5200, "miners": 2 }
    ],
    "mofumofu": [...],
    "leywapool": [...],
    "unknown": [...]
  }
}
```

---

## Page: `isekai-pool.com/koto-network`

Static HTML page (fits existing isekai-pool.com static site pattern).
Fetches data from `api.isekai-pool.com/api/v1/network/KOTO/` on load.

### Layout:

```
┌─────────────────────────────────────────────────────────┐
│  isekai-pool.com nav                                     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  KOTO Network                          [1D][7D][30D][ALL]│
│  Block: 4,342,xxx · 23.5 KH/s · Diff: 157.18           │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Network Hashrate + Pool Distribution (stacked) │   │
│  │                                                  │   │
│  │  [Chart.js stacked area chart]                   │   │
│  │  ■ isekai-pool.com  ■ mofumofu.me               │   │
│  │  ■ leywapool.com    ■ Unknown                    │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Difficulty History                              │   │
│  │  [Chart.js line chart]                           │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│  Current Pool Stats                                     │
│  ┌──────────┬──────────┬────────────┬──────────────┐   │
│  │ Pool     │ Hashrate │ Share      │ Fee          │   │
│  ├──────────┼──────────┼────────────┼──────────────┤   │
│  │ isekai   │ 5.2 KH/s │ 22.1%  🔵 │ 1%           │   │
│  │ mofumofu │ 5.0 KH/s │ 21.3%  🟡 │ 0.5%         │   │
│  │ leywapool│ 0.2 KH/s │ 0.5%   🔴 │ ?            │   │
│  │ Unknown  │13.1 KH/s │ 55.8%  ⚫ │ —            │   │
│  └──────────┴──────────┴────────────┴──────────────┘   │
│                                                         │
│  About this data                                        │
│  Snapshots collected every 15 minutes since April 2026. │
│  First public historical record of KOTO pool data.      │
│  Data: api.isekai-pool.com/api/v1/network/KOTO          │
└─────────────────────────────────────────────────────────┘
```

### Chart.js config:

```javascript
const API = 'https://api.isekai-pool.com/api/v1/network/KOTO';
const COLORS = {
    isekai:    '#7c6af7',  // violet — isekai brand
    mofumofu:  '#f0c040',  // gold
    leywapool: '#ef4444',  // red
    unknown:   '#374151',  // dark gray
    network:   '#06b6d4',  // cyan for total line
};

// Stacked area chart — pool hashrate over time
const stackedChart = new Chart(ctx, {
    type: 'line',
    data: {
        datasets: [
            { label: 'isekai-pool.com', fill: true, backgroundColor: COLORS.isekai + '40' },
            { label: 'mofumofu.me',     fill: true, backgroundColor: COLORS.mofumofu + '40' },
            { label: 'leywapool.com',   fill: true, backgroundColor: COLORS.leywapool + '40' },
            { label: 'Unknown',         fill: true, backgroundColor: COLORS.unknown + '40' },
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { stacked: true, title: { text: 'H/s' } },
            x: { type: 'time' }
        }
    }
});
```

---

## Coin-Agnostic Design

The page is templated to support other coins. Future pages reuse the same pattern:

```
isekai-pool.com/ytn-network  → coin=YTN
isekai-pool.com/tdc-network  → coin=TDC
```

Each coin needs:
1. A `network:snapshot {COIN}` scheduler entry
2. Pool API URLs for that coin's known pools
3. An RPC node connection config
4. A static HTML page with `coin=YTN` in the API calls

The Laravel backend is already coin-agnostic via the `coin` column.

---

## Data Retention

Keep all snapshots forever — this is the historical record.
At 4 snapshots/hour × 24h × 365d = ~35,000 rows/year per coin.
Tiny — no cleanup needed for years.

---

## "About this data" section

```
This dashboard collects pool and network statistics every 15 minutes
starting April 5, 2026 — the day isekai-pool.com launched as the first
English-language KOTO mining pool.

Data is collected from public pool APIs and the isekai-pool.com KOTO node.
Raw data available at api.isekai-pool.com/api/v1/network/KOTO/history.

isekai-pool.com is not affiliated with mofumofu.me or leywapool.com.
Pool data is fetched from their public APIs.
```

---

## SEO

- Page title: "KOTO Network Stats — Pool Hashrate History | isekai-pool.com"
- Meta description: "Historical hashrate and pool distribution for the KOTO (yescryptR8G) mining network. Updated every 15 minutes since April 2026."
- This page will rank for "KOTO network hashrate" and "KOTO mining pools" — almost no competition

---

## Build Order

1. Migrations (`network_snapshots`, `pool_snapshots`)
2. `CollectNetworkSnapshot` command — test manually first:
   ```bash
   php artisan network:snapshot KOTO
   ```
3. Add to scheduler in `Kernel.php`
4. API endpoints (`/api/v1/network/KOTO/current` + `/history`)
5. Static `web/koto-network.html` with Chart.js
6. Add to isekai-pool.com nav: "KOTO Network"
7. Verify leywapool API format before writing parser

---

## Notes for Cursor

- Check actual leywapool API response format before writing parser —
  `curl https://leywapool.com/site/api` and inspect JSON structure
- If a pool API is unreachable, store `is_online=false` and `hashrate=0` — don't fail the job
- Use `try/catch` on every external HTTP call with a 10s timeout
- The `network_hashrate` from RPC `getmininginfo` returns `networkhashps` in H/s
- Chart.js CDN: `https://cdn.jsdelivr.net/npm/chart.js` +
  `https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns` for time axis
- Time range selector (1D/7D/30D/ALL) filters the `/history` API call — implement as
  query param `?range=7d`
- isekai-pool.com is static HTML — the page fetches from the API, no server-side rendering needed
- Add `koto-network.html` to `sitemap.xml`
