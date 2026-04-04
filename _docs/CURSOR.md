# isekai-pool.com — Project Brief for Cursor

> Read this before touching any code. It explains what we're building, why, and how everything fits together.

---

## What is isekai-pool.com?

**isekai-pool.com is public infrastructure for obscure CPU-only cryptocurrencies.**

These are coins that anyone can mine with a Raspberry Pi or an old Dell laptop — no GPU, no ASIC, no expensive hardware. They use algorithms like Yespower and Yescrypt that are specifically designed to run best on regular CPUs.

The problem: most of these coins have almost zero supporting infrastructure. No reliable public RPC nodes. Dead block explorers. No wallet API. Developers who want to build something on top of them have nowhere to start.

We are that infrastructure.

---

## The Problem We Solve

A developer wants to check a Yenten (YTN) wallet balance. They have three options:

1. Run their own full node (hours of sync, technical setup, ongoing maintenance)
2. Use a public RPC endpoint (doesn't exist for most of these coins)
3. Give up

We provide option 2 — for free, for the coins nobody else is hosting.

Same story for a Raspberry Pi miner who wants to check their Koto (KOTO) balance without running a full node on their Pi. Or a wallet app developer who needs a reliable JSON API to query transaction history.

**We are the missing layer.**

---

## Vision

> "The Ledger Live of CPU coins — but open, API-first, and built for coins that have been forgotten."

Long term, isekai-pool.com becomes the default infrastructure reference for the yespower/yescrypt coin family:

- Miners point their wallets at our public RPC
- Developers build tools using our API
- Coin communities link to us as their reliable node source
- We add coins as we discover active networks that need infrastructure

The isekai name is intentional — these coins exist in "another world" of crypto, outside the mainstream exchanges and GPU farms. The Japanese aesthetic reinforces the theme. This is infrastructure for the other world.

---

## Target Audience

**Primary: Home miners with modest hardware**
- Raspberry Pi 3/4/5 owners
- People with old desktops/laptops
- Anyone priced out of GPU mining
- They want: simple wallet UI, balance check, basic send/receive

**Secondary: Developers**
- Building wallets or explorers for these coins
- Need reliable JSON RPC they don't have to host themselves
- They want: clean API docs, stable endpoints, multiple coins

**Tertiary: Coin communities**
- Small coins whose own infrastructure has gone dark
- They want: their coin to be usable, their network to stay alive

---

## What We Are NOT

- Not a mining pool (not yet — that's Phase 3 at earliest)
- Not a trading platform
- Not competing with Binance/Coinbase
- Not focused on big coins (XMR already has infrastructure)
- Not chasing volume or price speculation

---

## Supported Coins (Current)

| Coin | Ticker | Algorithm | Why We Run It |
|------|--------|-----------|---------------|
| Yenten | YTN | YespowerR16 | Most active yespower coin, 179 miners, 15+ pools |
| Koto | KOTO | Yescrypt | Japanese privacy coin, TradeOgre listed, active community |
| Tidecoin | TDC | YespowerTIDE | Post-quantum Bitcoin (FALCON-512), unique tech story |

**Coming soon:** Sugarchain (SUGAR), CPUchain (CPU), Documentchain (DMS)

**Dropped:** UraniumX (URX) — dead network. BitZeny (ZNY) — network mismatch, only 6 miners.

---

## Tech Stack

| Layer | Tech | Why |
|-------|------|-----|
| Backend API | Laravel 11 + PHP 8.3 | Clean RPC proxy, Redis caching, rate limiting |
| Frontend | HTML5 + Tailwind CSS | No build step, fast, SEO-friendly, no JS framework bloat |
| Web server | Nginx + PHP-FPM | Standard, reliable |
| Node daemons | Bitcoin/Zcash forks | Each coin runs as a systemd service under `crypto` user |
| Cache | Redis (file fallback) | 15-second cache on read-only RPC calls |
| SSL | Let's Encrypt | Auto-renewed via certbot |
| CI/CD | GitHub Actions → SSH | Push to main → auto-deploy to VPS |

**VPS:** 153.75.225.100 — 4 Slice ($12/mo), 8GB RAM, 160GB SSD, Ubuntu 24.04

---

## Repository Structure

```
isekai-pool-api/
├── .github/workflows/deploy.yml   ← Auto-deploy on push to main
├── api/                           ← Laravel backend
│   ├── app/Http/Controllers/Api/
│   │   └── RpcController.php      ← GET /health, GET /{coin}/status, POST /{coin}/rpc
│   ├── app/Services/
│   │   └── RpcService.php         ← Talks to coin daemons, handles caching
│   ├── config/
│   │   └── coins.php              ← Coin registry (name, algo, ports, RPC creds)
│   └── routes/api.php             ← Route definitions with throttling
├── web/                           ← Static HTML frontend
│   ├── index.html                 ← Homepage — live node status dashboard
│   ├── pages/
│   │   ├── coins.html             ← All coins listing
│   │   └── api-docs.html          ← API reference
│   └── assets/
├── scripts/                       ← Bash scripts to build/install coin daemons
│   ├── build-yenten.sh            ← YTN — tested, working
│   ├── build-koto.sh              ← KOTO — uses prebuilt binary (Zcash fork)
│   ├── build-tidecoin.sh          ← TDC — uses prebuilt binary v0.18.3
│   └── build-bitzeny.sh           ← ZNY — parked, v2/v3 network mismatch
├── nginx.conf                     ← Nginx server blocks for both domains
├── .env.example                   ← Laravel config template
└── README.md                      ← Full technical documentation
```

---

## API Design

Base URL: `https://api.isekai-pool.com/api/v1`

### Endpoints

```
GET  /health                  → All nodes online status + block heights
GET  /{coin}/status           → Blockchain info for one coin
POST /{coin}/rpc              → Public RPC proxy (whitelisted methods)
```

### Public RPC methods

These are the only methods exposed without auth:

```
getblockcount          getblockchaininfo      getnetworkinfo
getmempoolinfo         getblock               getblockhash
getrawtransaction      decoderawtransaction   sendrawtransaction
gettxoutsetinfo        getdifficulty          getconnectioncount
```

### Rate limiting

- 60 requests/minute per IP on the RPC proxy
- Read-only calls cached 15 seconds in Redis
- No API key required (public infrastructure)

---

## Website Roadmap

### Phase 1 — Infrastructure (current)
- [x] VPS provisioned
- [x] YTN, KOTO, TDC nodes running
- [x] SSL on isekai-pool.com + api.isekai-pool.com
- [x] GitHub repo + auto-deploy
- [ ] Laravel API live and serving JSON
- [ ] Homepage showing live node status
- [ ] Add SUGAR and CPU nodes

### Phase 2 — Website
- [ ] `/` — Homepage with live node status dashboard
- [ ] `/coins` — All supported coins with specs and mining guides
- [ ] `/coins/{coin}` — Individual coin page (block explorer link, how to mine, RPC examples)
- [ ] `/api` — API documentation page
- [ ] `/status` — Full system status page (uptime, sync progress, peer counts)
- [ ] RPi mining guide — "How to mine YTN/KOTO/TDC with a Raspberry Pi"

### Phase 3 — Wallet UI (future, gameglass.live integration)
- [ ] Send / receive for each coin
- [ ] Portfolio view — balances across all supported coins
- [ ] Swap via Trocador/XeggeX API where available
- [ ] Connect to gameglass.live as companion wallet

### Phase 4 — Pool (much later)
- [ ] NOMP/v-NOMP pool server for yespower coins
- [ ] Only after establishing node reputation and user base

---

## Coding Guidelines for Cursor

**API (Laravel):**
- All coin config lives in `config/coins.php` — add new coins there first
- `RpcService.php` handles all node communication — do not call nodes directly
- Cache read-only RPC calls for 15 seconds minimum
- Never expose wallet methods (getbalance, listunspent, etc.) via public API
- Return consistent JSON: `{ coin, method, result }` or `{ error }`

**Frontend (HTML + Tailwind):**
- No JavaScript frameworks — vanilla JS only
- Tailwind via CDN — no build step
- Dark theme only — colors defined in `tailwind.config` in each page's `<script>`
- Fetch from `api.isekai-pool.com` — never hardcode node IPs in frontend
- Each page is self-contained HTML — no shared templates (yet)
- Mobile-first, monospace font (JetBrains Mono)

**General:**
- No scope creep — finish what's planned before adding new coins
- Every new coin needs: build script in `/scripts/`, entry in `coins.php`, tested node
- Document issues in `/docs/coins.md` — e.g. why ZNY is parked, why Koto uses prebuilt

---

## Environment Variables (api/.env)

```env
# RPC credentials — pulled from node configs on VPS
YTN_RPC_USER=isekai_ytn
YTN_RPC_PASS=<from /home/crypto/.yenten/yenten.conf>
YTN_RPC_PORT=9982

KOTO_RPC_USER=isekai_koto
KOTO_RPC_PASS=<from /home/crypto/.koto/koto.conf>
KOTO_RPC_PORT=8432

TDC_RPC_USER=isekai_tdc
TDC_RPC_PASS=<from /home/crypto/.tidecoin/tidecoin.conf>
TDC_RPC_PORT=9368
```

Never commit `.env` to git. The `vps-init.sh` script injects these automatically from the node configs.

---

## VPS Quick Reference

```bash
# Node status
isekai-status

# Start/stop nodes
systemctl start isekai-yenten
systemctl start isekai-koto
systemctl start isekai-tidecoin

# Watch logs
journalctl -u isekai-yenten -f
journalctl -u isekai-koto -f
journalctl -u isekai-tidecoin -f

# Test API locally
curl http://localhost/api/v1/health
curl http://localhost/api/v1/yenten/status

# Deploy from GitHub
cd /var/www/isekai-pool-api && git pull origin main
```

---

## Context for AI Assistants

This project is built by a solo developer with 20+ years of web experience
(marketing front-end, technical SEO, Laravel). The codebase should be:

- **Practical over elegant** — working > perfect
- **Minimal dependencies** — the fewer packages the better
- **Self-documenting** — comments explain the *why*, not the *what*
- **Incrementally expandable** — adding a new coin should take 30 minutes max

When suggesting changes, keep the HTML + Tailwind stack. Do not suggest React,
Vue, Next.js, or any JS framework. Do not add a database unless explicitly
asked — SQLite is the fallback, file cache is fine for now.

The target is a lean, fast, informative infrastructure site — not a web app.
