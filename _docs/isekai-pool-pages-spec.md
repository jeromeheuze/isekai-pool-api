# isekai-pool.com — Missing Pages Cursor Spec
**Stack: Static HTML + Tailwind CSS**  
**VPS: 153.75.225.100**

---

## ⚠️ Spec Errata — Read Before Implementing

These corrections reconcile this spec with `_docs/CURSOR.md` and the existing repo. Where this errata conflicts with anything below, **errata wins**.

### 1. File Paths — use `web/` directory
All new HTML files go under `web/`, not the site root. Update all canonical URLs, sitemap entries, and internal links accordingly:
- `web/coins.html` → `https://isekai-pool.com/coins.html` (server routes `web/` to root)
- Match whatever path convention `_docs/CURSOR.md` defines for the `web/` directory

### 2. RPC Integration — use the Laravel API, never raw node IPs
**Do not** `fetch` directly to `http://153.75.225.100:PORT` from the browser. This violates the API-first rule in `CURSOR.md`, breaks on HTTPS (mixed content), and exposes infrastructure.

All live data (block height, hashrate, difficulty, peers, node status) must go through:
```
https://api.isekai-pool.com/api/v1/
```

Replace every occurrence of the direct RPC fetch pattern in this spec with calls to the Laravel public API. Example:
```javascript
// ❌ Wrong — direct node fetch
fetch('http://153.75.225.100:9982', { method: 'POST', body: ... })

// ✅ Correct — through the Laravel API proxy
fetch('https://api.isekai-pool.com/api/v1/yenten/status')
fetch('https://api.isekai-pool.com/api/v1/koto/status')
fetch('https://api.isekai-pool.com/api/v1/tidecoin/status')
fetch('https://api.isekai-pool.com/api/v1/health')   // all-nodes health check
```
Note: coin slugs are **`yenten` / `koto` / `tidecoin`** — not tickers. The `getmininginfo` fields (hashrate, difficulty) are **not on the public whitelist today** — `status.html` should either use only what `/api/v1/yenten/status` already exposes, or new Laravel endpoints must be added first. Cursor: check `_docs/CURSOR.md` for existing API route definitions before implementing the status dashboard.

### 3. No Real Secrets in Frontend or Spec
The `rpcpassword` and `rpcuser` values shown in miner command examples in this spec are **placeholders for documentation only**. Do not embed them in any HTML, JS, or git-tracked file.

In miner quick-connect examples, use:
```bash
--user=YOUR_RPC_USER --pass=YOUR_RPC_PASS
```
And direct users to the `status.html` page or the coin's community for the current public credentials, or expose them via a dedicated API endpoint if they are intentionally public.

### 4. Theme — align to existing palette
Use the colors already in `index.html`, not the values written in this spec:
- Background: `#0d0f14` (not `#0a0a0f`)
- Primary accent: `#7c6af7` (not `#7c3aed`)
- Pull exact Tailwind classes from `index.html` for consistency

### 5. Nav & Footer — match existing `index.html`
Adopt whatever nav links and footer copy are currently in `index.html`. The nav expansion (adding Guide, Status, About) is correct — but the link labels and footer tagline must match the existing `index.html` exactly, or update `index.html` at the same time so all pages are in sync.

---

---

## Site Overview

isekai-pool.com is a public RPC infrastructure hub for obscure CPU-only yespower/yescrypt cryptocurrencies. The aesthetic is isekai anime-inspired — dark theme, glowing accents, fantasy/portal visual language. Think: "you've been transported to a world where CPU mining still matters."

Existing: `index.html` (home/landing page)  
Missing: everything below.

---

## Global Rules

> ⚠️ **Superseded by Spec Errata where noted.** For colors, footer copy, and nav labels always defer to the errata block above and to the existing `index.html`.

- **Stack**: Plain HTML files + Tailwind CSS (CDN). No build step. No frameworks.
- **Theme**: ~~`#0a0a0f` / `#7c3aed`~~ → **use `#0d0f14` + `#7c6af7`** and pull exact Tailwind classes from `index.html` (see Errata §4).
- **Nav**: Same nav across all pages. Links: Home · Coins · Mining Guide · Node Status · About — sync labels with existing `index.html` (see Errata §5).
- **Footer**: ~~"isekai-pool.com — CPU mining lives on."~~ → **match existing `index.html` footer exactly** (see Errata §5).
- **Code blocks**: Dark `#1a1a2e` background, syntax-highlighted with a `copy` button (JS clipboard API).
- **Responsive**: Mobile-first. All pages usable on phone.

---

## Pages to Build

---

### 1. `coins.html` — Coins Directory

**Purpose**: Master list of all supported coins with key specs and RPC endpoint.

**URL**: `/coins.html`

**Layout**:
- Page hero: "Supported Coins" heading + short description ("Public RPC nodes for CPU-mineable coins. Point your miner, start earning.")
- Grid of coin cards (3 col desktop, 1 col mobile)

**Each coin card contains**:
- Coin logo (use placeholder SVG icon if no logo — colored circle with ticker initials)
- Coin name + ticker
- Algorithm badge (e.g. `yespower 1.0`)
- Network status pill: 🟢 Online / 🔴 Offline (fetched live — see Node Status section)
- Current block height (fetched live via Laravel API)
- Two buttons: `View Details →` (links to coin page) and `Mining Guide →` (links to guide anchor)

**Coins to include**:

| Coin | Ticker | Algorithm | API Slug | Coin Page |
|------|--------|-----------|----------|-----------|
| Yenten | YTN | yespower 1.0 | `yenten` | `ytn.html` |
| Koto | KOTO | yescryptR16 | `koto` | `koto.html` |
| Tidecoin | TDC | yespower-b2b | `tidecoin` | `tdc.html` |

**Live data fetch** (JS, runs on page load):

> ⚠️ **Obsolete below — see Errata §2.** Do not fetch directly to the VPS IP. Use the Laravel API instead.

```javascript
// ✅ Correct pattern — HTTPS API proxy, no raw IPs
const API = 'https://api.isekai-pool.com/api/v1';

async function getCoinStatus(slug) {
  try {
    const res = await fetch(`${API}/${slug}/status`);
    const data = await res.json();
    return data; // { blockHeight, online, ... } — match actual API response shape
  } catch {
    return null; // show Offline pill
  }
}
```
Show "Loading..." skeleton while fetching. On error show "Offline" pill in red.

---

### 2. `ytn.html` — Yenten (YTN) Coin Page

**Purpose**: Deep-dive page for YTN. Source of truth for miners wanting to mine YTN on isekai-pool.com.

**URL**: `/ytn.html`

**Sections**:

#### Hero
- YTN logo/icon, name, ticker
- Tagline: "CPU-mineable. yespower 1.0. Alive and kicking."
- Live stats bar: Block Height · Network Hashrate · Difficulty · Node Status

#### Quick Connect
Highlighted box with the one-liner to start mining:
```bash
./cpuminer --algo=yespower --url=http://153.75.225.100:9982 --user=YOUR_RPC_USER --pass=YOUR_RPC_PASS --coinbase-addr=YOUR_YTN_ADDRESS --threads=4
```
With a copy button. Note below: "Replace `YOUR_YTN_ADDRESS` with your wallet address. RPC credentials are displayed on the [Node Status](/status.html) page."

#### Coin Info Table
| Property | Value |
|----------|-------|
| Algorithm | yespower 1.0 (N=2048, r=32) |
| Block Time | ~60 seconds |
| RPC Host | `153.75.225.100:9982` (see status page for credentials) |
| P2P Port | 9981 |
| Explorer | https://explorer.yentencoin.info/ |
| Source | https://github.com/yentencoin/yenten |

#### Mining Guide (embedded, same content as guide page)
Step-by-step sections (collapsible accordions on mobile):
1. Install dependencies
2. Clone & compile cpuminer-opt (with ARM64 / x86 tabs)
3. Get a wallet address
4. Run the miner
5. Verify you're mining

#### Expected Hashrates Table
| Hardware | Hashrate |
|----------|----------|
| Raspberry Pi 5 | ~3,000–4,000 H/s |
| Raspberry Pi 4 | ~1,500–2,000 H/s |
| Intel i7 (4c/8t) | ~8,000–12,000 H/s |
| AMD Ryzen 9 5900X | ~25,000–35,000 H/s |

#### Links
- YTN Discord / Bitcointalk (link to official community)
- Block Explorer
- GitHub

---

### 3. `koto.html` — Koto (KOTO) Coin Page

Same structure as `ytn.html`. Placeholder content for RPC port (TBD).  
Algorithm: `yescryptR16`  
cpuminer flag: `--algo=yescryptR16`

---

### 4. `tdc.html` — Tidecoin (TDC) Coin Page

Same structure as `ytn.html`. Placeholder content for RPC port (TBD).  
Algorithm: `yespower-b2b`  
cpuminer flag: `--algo=yespower-b2b`

---

### 5. `guide.html` — Mining Guide (Master)

**Purpose**: Universal getting-started guide for CPU mining on isekai-pool.com. Hardware-agnostic introduction, then branches by OS/hardware.

**URL**: `/guide.html`

**Sections**:

#### What Is CPU Mining?
2-3 paragraph explainer. yespower/yescrypt are ASIC-resistant by design. Your CPU is the weapon.

#### Choose Your Coin
Card links to YTN / KOTO / TDC pages.

#### Install cpuminer-opt

Tab switcher (JS tabs, no framework):
- **Linux / RPi (ARM64)**
- **Linux (x86_64)**
- **Windows**

Each tab shows the full install + compile commands as copy-able code blocks.

**ARM64 tab content**:
```bash
sudo apt update && sudo apt install -y git build-essential automake autoconf \
  libcurl4-openssl-dev libssl-dev libjansson-dev libgmp-dev zlib1g-dev screen

git clone https://github.com/JayDDee/cpuminer-opt.git
cd cpuminer-opt
./autogen.sh
./configure CFLAGS="-O3 -march=armv8.2-a+crypto -mtune=cortex-a76"
make -j4
```

**x86_64 tab content**:
```bash
sudo apt update && sudo apt install -y git build-essential automake autoconf \
  libcurl4-openssl-dev libssl-dev libjansson-dev libgmp-dev zlib1g-dev screen

git clone https://github.com/JayDDee/cpuminer-opt.git
cd cpuminer-opt
./autogen.sh
./configure CFLAGS="-O3 -march=native"
make -j$(nproc)
```

**Windows tab content**:
Link to cpuminer-opt releases page for pre-built Windows binaries. Note: Linux recommended for stability.

#### Run the Miner
Generic command template with coin dropdown that swaps `--algo` and `--url` values live via JS. The `--url` value comes from the coin's page or status dashboard — display it as a placeholder here:
```bash
./cpuminer \
  --algo=ALGO \
  --url=http://RPC_HOST:RPC_PORT \
  --user=YOUR_RPC_USER \
  --pass=YOUR_RPC_PASS \
  --coinbase-addr=YOUR_ADDRESS \
  --threads=4
```
The dropdown populates `ALGO` and `RPC_HOST:RPC_PORT` per coin (values from the coin pages, not hardcoded here). Credentials remain as placeholders — link to the status page.

#### Tips
- Use `screen` or `tmux` to keep mining after SSH disconnect
- `--cpu-priority=2` keeps the machine responsive
- Check logs: look for `accepted` messages to confirm blocks found
- Monitor temps: `vcgencmd measure_temp` (RPi) or `sensors` (Linux)

---

### 6. `status.html` — Node Status Dashboard

**Purpose**: Live dashboard showing health of all three RPC nodes.

**URL**: `/status.html`

**Layout**:
- Page title: "Node Status" + last updated timestamp (auto-refreshes every 30s)
- Three node cards, one per coin

**Each node card shows**:
- Coin name + ticker + algorithm
- Status badge: 🟢 Online / 🔴 Offline
- Block Height
- Network Hashrate (if exposed by API — add Laravel endpoint if missing)
- Difficulty (if exposed by API — add Laravel endpoint if missing)
- Connected Peers (if exposed by API — add Laravel endpoint if missing)
- Response time (ms)

> ⚠️ **Obsolete below — see Errata §2 & §3.** Replace with API calls. `getmininginfo` fields are not yet on the public whitelist — add Laravel endpoints for hashrate/difficulty/peers before implementing, or scope the dashboard to what `/api/v1/{slug}/status` already returns and expand later.

**JS fetch logic** (correct pattern):
```javascript
// ✅ HTTPS API proxy only — no raw IPs, no secrets
const API = 'https://api.isekai-pool.com/api/v1';
const COINS = ['yenten', 'koto', 'tidecoin'];

async function fetchNodeStats(slug) {
  const start = Date.now();
  try {
    const res = await fetch(`${API}/${slug}/status`);
    const data = await res.json();
    return { ...data, online: true, responseMs: Date.now() - start };
  } catch {
    return { online: false, responseMs: null };
  }
}

// On load: fetch all, render cards. Auto-refresh every 30s.
async function refreshAll() {
  await Promise.all(COINS.map(fetchNodeStats));
}
setInterval(refreshAll, 30000);
refreshAll();
```

Auto-refresh every 30 seconds. Show a subtle pulse animation on the status badge when refreshing.

---

### 7. `about.html` — About

**Purpose**: What is isekai-pool.com, who runs it, why it exists.

**Content**:
- What: Public RPC node infrastructure for obscure CPU-mineable coins
- Why: These coins deserve public endpoints. CPU mining should remain accessible.
- Who: Independent operator (no need to dox — "a solo dev" is fine)
- Uptime commitment: best-effort, no SLA
- How to add a coin: open a GitHub issue or reach out (optional contact method)
- Link to isekai-pool.com GitHub if public

---

## File Structure

> ⚠️ All HTML files go under `web/` per Errata §1. Match the exact path convention in `_docs/CURSOR.md`.

```
web/
├── index.html          (exists — update nav/footer to match new pages)
├── coins.html          (new)
├── ytn.html            (new)
├── koto.html           (new)
├── tdc.html            (new)
├── guide.html          (new)
├── status.html         (new)
├── about.html          (new)
├── assets/
│   ├── css/
│   │   └── custom.css  (any overrides beyond Tailwind CDN)
│   └── js/
│       ├── api.js      (shared Laravel API fetch helpers — replaces rpc.js)
│       └── ui.js       (copy button, tabs, accordion)
├── img/
│   ├── og-default.png  (1200×630 OG social preview image)
│   └── coins/
│       ├── ytn-logo.png
│       ├── koto-logo.png
│       └── tdc-logo.png
├── sitemap.xml         # on disk: web/sitemap.xml → public URL /sitemap.xml (nginx root = web/)
└── robots.txt          # on disk: web/robots.txt → public URL /robots.txt
```

---

## Shared JS Utilities (`assets/js/api.js`)

> ⚠️ **Replaces the original `rpc.js` concept.** No raw IPs, no ports, no secrets. All data via HTTPS Laravel API.

```javascript
// api.js — shared across all pages
const API_BASE = 'https://api.isekai-pool.com/api/v1';

// Coin registry — slugs match Laravel API routes
const COINS = {
  YTN:  { slug: 'yenten',   name: 'Yenten',   algo: 'yespower',     rpcHost: '153.75.225.100', rpcPort: 9982 },
  KOTO: { slug: 'koto',     name: 'Koto',      algo: 'yescryptR16',  rpcHost: null, rpcPort: null },
  TDC:  { slug: 'tidecoin', name: 'Tidecoin',  algo: 'yespower-b2b', rpcHost: null, rpcPort: null },
};
// Note: rpcHost/rpcPort are for miner documentation display only — never used in browser fetch()

async function getCoinStatus(slug) {
  try {
    const res = await fetch(`${API_BASE}/${slug}/status`);
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}

async function getHealth() {
  try {
    const res = await fetch(`${API_BASE}/health`);
    return await res.json();
  } catch {
    return null;
  }
}
```

---

## SEO Requirements

Apply to every HTML page unless noted otherwise.

---

### Meta Tags (every page)

```html
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Primary SEO -->
  <title>PAGE_TITLE | isekai-pool.com</title>
  <meta name="description" content="PAGE_DESCRIPTION">
  <link rel="canonical" href="https://isekai-pool.com/PAGE.html">

  <!-- Open Graph (Discord, Slack, Facebook previews) -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://isekai-pool.com/PAGE.html">
  <meta property="og:title" content="PAGE_TITLE | isekai-pool.com">
  <meta property="og:description" content="PAGE_DESCRIPTION">
  <meta property="og:image" content="https://isekai-pool.com/img/og-default.png">
  <meta property="og:site_name" content="isekai-pool.com">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="PAGE_TITLE | isekai-pool.com">
  <meta name="twitter:description" content="PAGE_DESCRIPTION">
  <meta name="twitter:image" content="https://isekai-pool.com/img/og-default.png">
</head>
```

**Per-page title and description targets:**

| Page | Title | Description |
|------|-------|-------------|
| `index.html` | Public CPU Mining RPC Nodes | Free public RPC nodes for yespower and yescrypt CPU-mineable cryptocurrencies. Mine YTN, KOTO, and TDC with your CPU today. |
| `coins.html` | Supported Coins — YTN, KOTO, TDC | Browse all CPU-mineable coins available on isekai-pool.com. Public RPC endpoints for Yenten, Koto, and Tidecoin. |
| `ytn.html` | Mine Yenten (YTN) — yespower CPU Mining | Public RPC node for Yenten (YTN) CPU mining. Algorithm: yespower 1.0. Free endpoint, solo mining, instant setup with cpuminer-opt. |
| `koto.html` | Mine Koto (KOTO) — yescryptR16 CPU Mining | Public RPC node for Koto (KOTO) CPU mining. Algorithm: yescryptR16. Free endpoint for solo miners using cpuminer-opt. |
| `tdc.html` | Mine Tidecoin (TDC) — yespower-b2b CPU Mining | Public RPC node for Tidecoin (TDC) CPU mining. Algorithm: yespower-b2b. Free public endpoint, no registration required. |
| `guide.html` | CPU Mining Setup Guide — cpuminer-opt | Step-by-step guide to CPU mining with cpuminer-opt on Linux, Raspberry Pi, and Windows. Mine yespower and yescrypt coins today. |
| `status.html` | Node Status — Live RPC Dashboard | Live status of all isekai-pool.com RPC nodes. Check block height, network hashrate, difficulty, and uptime for YTN, KOTO, and TDC. |
| `about.html` | About isekai-pool.com | Public CPU mining infrastructure for obscure yespower and yescrypt cryptocurrencies. Free RPC nodes, no registration, no fees. |

---

### Structured Data / JSON-LD

#### `index.html` — WebSite schema
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "isekai-pool.com",
  "url": "https://isekai-pool.com",
  "description": "Public RPC node infrastructure for CPU-mineable yespower and yescrypt cryptocurrencies.",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://isekai-pool.com/coins.html?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
```

#### `guide.html` — HowTo schema
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "How to Mine CPU Coins with cpuminer-opt",
  "description": "Step-by-step guide to setting up cpuminer-opt and mining yespower/yescrypt coins on isekai-pool.com public RPC nodes.",
  "step": [
    { "@type": "HowToStep", "name": "Install dependencies", "text": "Install build tools and libraries required for cpuminer-opt." },
    { "@type": "HowToStep", "name": "Clone and compile cpuminer-opt", "text": "Download and compile cpuminer-opt from source with architecture-optimized flags." },
    { "@type": "HowToStep", "name": "Get a wallet address", "text": "Generate a wallet address for the coin you want to mine." },
    { "@type": "HowToStep", "name": "Start mining", "text": "Run cpuminer-opt pointed at the isekai-pool.com RPC endpoint." }
  ]
}
</script>
```

#### `ytn.html` / `koto.html` / `tdc.html` — FAQPage schema
Add a FAQ section at the bottom of each coin page with 4–5 Q&As (e.g. "What hardware do I need?", "Is this free?", "What is yespower?", "How long until I find a block?"). Wrap in FAQPage JSON-LD — this can trigger rich results in Google.

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What hardware do I need to mine Yenten (YTN)?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Any CPU can mine YTN. A Raspberry Pi 5 achieves ~3,000–4,000 H/s. A modern desktop i7 achieves ~10,000 H/s."
      }
    },
    {
      "@type": "Question",
      "name": "Is the isekai-pool.com RPC node free to use?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes. All RPC nodes on isekai-pool.com are free and public. No registration required."
      }
    },
    {
      "@type": "Question",
      "name": "What is the yespower mining algorithm?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "yespower is an ASIC-resistant proof-of-work algorithm designed specifically for CPU mining. It is used by coins like Yenten (YTN)."
      }
    },
    {
      "@type": "Question",
      "name": "How do I solo mine without a pool?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Point cpuminer-opt directly at our public RPC node using the --url flag. This is solo mining — you keep 100% of any block you find."
      }
    }
  ]
}
</script>
```

---

### `sitemap.xml`

Create `web/sitemap.xml` (nginx `root` is `.../web` — same pattern as `robots.txt`):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://isekai-pool.com/</loc><priority>1.0</priority><changefreq>weekly</changefreq></url>
  <url><loc>https://isekai-pool.com/coins.html</loc><priority>0.9</priority><changefreq>weekly</changefreq></url>
  <url><loc>https://isekai-pool.com/ytn.html</loc><priority>0.9</priority><changefreq>weekly</changefreq></url>
  <url><loc>https://isekai-pool.com/koto.html</loc><priority>0.8</priority><changefreq>weekly</changefreq></url>
  <url><loc>https://isekai-pool.com/tdc.html</loc><priority>0.8</priority><changefreq>weekly</changefreq></url>
  <url><loc>https://isekai-pool.com/guide.html</loc><priority>0.9</priority><changefreq>monthly</changefreq></url>
  <url><loc>https://isekai-pool.com/status.html</loc><priority>0.6</priority><changefreq>always</changefreq></url>
  <url><loc>https://isekai-pool.com/about.html</loc><priority>0.5</priority><changefreq>monthly</changefreq></url>
</urlset>
```

---

### `robots.txt`

```
User-agent: *
Allow: /

Sitemap: https://isekai-pool.com/sitemap.xml
```

---

### Heading Hierarchy (every page)

- One `<h1>` per page — contains the primary keyword (e.g. "Mine Yenten (YTN) with cpuminer-opt")
- `<h2>` for major sections (Quick Connect, Coin Info, Mining Guide, FAQ)
- `<h3>` for subsections
- Never skip levels

---

### Internal Linking Rules

Every page must link to at least 3 other pages contextually (not just nav). Examples:
- `ytn.html` → links to `guide.html` ("see our full mining guide"), `coins.html` ("browse other coins"), `status.html` ("check node status")
- `guide.html` → links to `ytn.html`, `koto.html`, `tdc.html` inline within the coin selection section
- `status.html` → links to each coin page from the node card

---

### Image SEO

- Every `<img>` must have a descriptive `alt` attribute
- OG image (`/img/og-default.png`): create a 1200×630px dark-themed banner with the isekai-pool.com logo and tagline. Used for all social previews.
- Coin logos: name them `ytn-logo.png`, `koto-logo.png`, `tdc-logo.png`

---

### Performance (Core Web Vitals)

- Load Tailwind from CDN — add `defer` to any non-critical scripts
- Inline critical CSS for above-the-fold content if Tailwind CDN is slow
- Lazy-load images below the fold: `<img loading="lazy">`
- All RPC fetch calls are async and non-blocking — never delay initial page render
- Target: 90+ PageSpeed score on mobile

---

### Submit After Launch

Once pages are live, submit to:
1. **Google Search Console** — add property, submit `sitemap.xml`
2. **Bing Webmaster Tools** — submit sitemap
3. **Post in coin communities** — YTN/KOTO/TDC Discord/Telegram with the site URL (this is the fastest way to get indexed and get backlinks)

---

## Notes for Cursor

- Do not install Node.js, npm, or any build tooling. Pure HTML + Tailwind CDN only.
- Keep all JS vanilla — no jQuery, no Alpine, no Vue.
- **Browser live data always goes through `https://api.isekai-pool.com/api/v1/`** — the Laravel app is the proxy. Never fetch raw node IPs from the browser.
- **Miner documentation** (cpuminer-opt commands) may reference `RPC_HOST:RPC_PORT` as display values for users to copy into their terminal — these are not browser `fetch()` calls and are fine as informational text with placeholders.
- KOTO and TDC are TBD — render their cards with "Coming Soon" state.
- Use `async/await` with proper try/catch on all API fetches — nodes may be slow or offline.
- Copy buttons use `navigator.clipboard.writeText()` with a visual "Copied!" confirmation.
- The isekai aesthetic: dark (`#0d0f14`), purple/violet accents (`#7c6af7`), subtle glow effects (`box-shadow` with violet), monospace for technical values. Pull exact classes from the existing `index.html` — do not invent a new palette.
