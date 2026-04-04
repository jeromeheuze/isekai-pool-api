# isekai-pool.com

> Public infrastructure for CPU-minable coins — the ones your Raspberry Pi can mine.

**Live:** https://isekai-pool.com  
**API:** https://api.isekai-pool.com  
**VPS:** 153.75.225.100 (4 Slice — 8GB RAM, 160GB SSD)

---

## What this is

isekai-pool.com runs full nodes for obscure CPU-only coins that have no public RPC infrastructure.
Miners, developers, and wallet apps can use our public API without running their own node.

**Supported coins:**

| Coin | Algorithm | Ports P2P/RPC | Status |
|------|-----------|---------------|--------|
| Yenten (YTN) | YespowerR16 | 9981 / 9982 | ✅ Synced |
| Koto (KOTO) | Yescrypt | 8433 / 8432 | 🔄 Syncing |
| Tidecoin (TDC) | YespowerTIDE | 9369 / 9368 | 🔄 Syncing |
| Sugarchain (SUGAR) | YespowerSugar | TBD | 🗓 Planned |
| CPUchain (CPU) | CPUpower | TBD | 🗓 Planned |

---

## Repo structure

```
isekai-pool-api/
├── .github/workflows/deploy.yml   ← Auto-deploy on push to main
├── api/                           ← Laravel 11 — RPC proxy + JSON API
│   ├── app/Http/Controllers/Api/
│   │   └── RpcController.php      ← /health, /status, /rpc endpoints
│   ├── app/Services/
│   │   └── RpcService.php         ← Node communication + caching
│   ├── config/
│   │   └── coins.php              ← Coin registry (ports, RPC creds)
│   └── routes/api.php
├── web/                           ← HTML + Tailwind frontend
│   ├── index.html                 ← Homepage — live node status
│   ├── pages/
│   │   ├── coins.html             ← All supported coins
│   │   ├── api-docs.html          ← API documentation
│   │   └── coin.html              ← Single coin detail page
│   ├── components/
│   │   ├── nav.html               ← Shared navigation
│   │   └── coin-card.html         ← Reusable coin status card
│   └── assets/
│       └── css/app.css            ← Tailwind + custom styles
├── scripts/                       ← Node build/install scripts
│   ├── build-yenten.sh
│   ├── build-koto.sh
│   ├── build-tidecoin.sh
│   ├── build-uraniumx.sh          ← Dead coin — template only
│   └── build-bitzeny.sh           ← Parked — v2 vs v3 mismatch
└── docs/
    ├── api.md                     ← Public API reference
    ├── coins.md                   ← Coin details + infra notes
    └── vps-setup.md               ← VPS bootstrap documentation
```

---

## API

Base URL: `https://api.isekai-pool.com/api/v1`

### Endpoints

```
GET  /health                  All nodes status + block heights
GET  /{coin}/status           Single coin blockchain info
POST /{coin}/rpc              Public RPC proxy (whitelisted methods only)
```

### Example

```bash
# All nodes health
curl https://api.isekai-pool.com/api/v1/health

# Yenten block count
curl -X POST https://api.isekai-pool.com/api/v1/yenten/rpc \
  -H "Content-Type: application/json" \
  -d '{"method":"getblockcount","params":[]}'

# Koto blockchain info
curl https://api.isekai-pool.com/api/v1/koto/status
```

### Supported RPC methods (public)

`getblockcount` `getblockchaininfo` `getnetworkinfo` `getmempoolinfo`
`getblock` `getblockhash` `getrawtransaction` `decoderawtransaction`
`sendrawtransaction` `gettxoutsetinfo` `getdifficulty` `getconnectioncount`

---

## Local development

```bash
git clone git@github.com:jeromeheuze/isekai-pool-api.git
cd isekai-pool-api/api
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

---

## VPS setup

Fresh Ubuntu 22/24 setup:

```bash
bash scripts/build-yenten.sh
bash scripts/build-koto.sh
bash scripts/build-tidecoin.sh
```

Node data directories:
- `/home/crypto/.yenten/`
- `/home/crypto/.koto/`
- `/home/crypto/.tidecoin/`

Node status: `isekai-status`

---

## Coin research notes

**Coins we dropped:**
- UraniumX (URX) — site down, one dead seed node, abandoned
- BitZeny (ZNY) — 6 miners total, website dead, v2/v3 network mismatch

**Coins to add next:**
- Sugarchain (SUGAR) — world's fastest PoW (5s blocks), XeggeX listed
- CPUchain (CPU) — cpuchain.org active, EVM support, cpupower algo
- Documentchain (DMS) — document notarization niche, Yescrypt

**Architecture note on Koto:**
Building from source failed due to pinned Rust 1.55 + broken Zcash crate
dependencies. Using official prebuilt binary v4.5.7 instead.
zk-SNARK params downloaded from Zcash CDN (ko-to.org splits fail SHA256).

---

## Tech stack

| Layer | Tech |
|-------|------|
| Backend API | Laravel 11, PHP 8.3 |
| Frontend | HTML5, Tailwind CSS |
| Web server | Nginx + PHP-FPM |
| Cache | Redis |
| Node daemons | Bitcoin/Zcash forks via systemd |
| SSL | Let's Encrypt via certbot |
| CI/CD | GitHub Actions → SSH deploy |

---

## License

MIT
