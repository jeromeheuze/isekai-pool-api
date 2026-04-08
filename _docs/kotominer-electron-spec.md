# Kotominer — Electron App Spec
**by isekai-pool.com · Windows x64 primary · Electron + Vue 3 + Tailwind**

---

## Vision

The first English-language GUI miner for KOTO. NiceHash-style simplicity — download, enter wallet, click Start. Bundles the cpuminer binary, handles everything, looks beautiful. Doubles as the primary marketing surface for koto.isekai-pool.com, the faucet, the game, and the broader isekai ecosystem.

Primary target hardware: **Alienware Aurora R16** (Intel i9-14900F, Windows 11)  
Secondary: Any Windows x64 machine  
Tertiary: Linux x64 + ARM64 (RPi5)

---

## Tech Stack

| Layer | Tech |
|-------|------|
| Shell | Electron 28+ |
| Frontend | Vue 3 + Vite |
| Styling | Tailwind CSS + custom dark theme |
| Miner | cpuminer-koto binary (bundled, platform-specific) |
| Process | Node.js child_process.spawn |
| Storage | electron-store (JSON config, no DB needed) |
| Updates | electron-updater (auto-update from GitHub releases) |
| Build | electron-builder (NSIS installer for Windows, AppImage for Linux) |

---

## Bundled Binaries

Ship platform-specific cpuminer-koto binaries inside the app:

```
resources/
├── win32-x64/
│   └── cpuminer-koto.exe       (Windows x64 — Alienware, desktops)
├── linux-x64/
│   └── cpuminer-koto           (Linux x64 — VPS, desktops)
└── linux-arm64/
    └── cpuminer-koto           (ARM64 — RPi5)
```

Electron detects `process.platform` + `process.arch` and uses the correct binary automatically.

**Source binaries (ship as `cpuminer-koto` / `cpuminer-koto.exe` in `resources/`):**

1. **Official — [KotoDevelopers/cpuminer-yescrypt releases](https://github.com/KotoDevelopers/cpuminer-yescrypt/releases)** — download the Windows x64 zip (e.g. `KotoMiner_Win_x64.zip` on some tags), extract the miner, **rename** to `cpuminer-koto.exe` if the release uses `minerd.exe` or another name.
2. **Optional — [crypto-jeronimo/cpuminer-koto](https://github.com/crypto-jeronimo/cpuminer-koto)** — optimized builds; align filename with Kotominer’s expected name.

---

## App Structure

```
kotominer/
├── src/
│   ├── main/
│   │   ├── index.js            (Electron main process)
│   │   ├── miner.js            (spawn/kill cpuminer, parse stdout)
│   │   ├── hardware.js         (CPU detection, core count, temp)
│   │   └── updater.js          (electron-updater)
│   └── renderer/
│       ├── App.vue
│       ├── views/
│       │   ├── Mining.vue      (main mining dashboard)
│       │   ├── Pools.vue       (pool directory)
│       │   ├── AboutKoto.vue   (what is KOTO)
│       │   ├── Guide.vue       (setup + tips)
│       │   ├── Faucet.vue      (embedded earn hub)
│       │   └── Games.vue       (isekai game + GameGlass promo)
│       └── components/
│           ├── HashrateMeter.vue
│           ├── ShareCounter.vue
│           ├── EarningsEstimate.vue
│           ├── PoolCard.vue
│           ├── NewsPanel.vue
│           └── StatusBar.vue
├── resources/                  (bundled binaries)
├── electron-builder.yml
└── package.json
```

---

## Screens

---

### 1. Mining Dashboard (default screen)

The main screen. Everything a miner needs at a glance.

```
┌────────────────────────────────────────────────────────┐
│  ⛩  Kotominer v1.0          by isekai-pool.com    [−][□][×] │
├──────────┬─────────────────────────────────────────────┤
│          │                                             │
│  ⛩ Mine  │   KOTO Wallet Address                      │
│  🏊 Pools │   [k1_______________________________] [✓]  │
│  📖 About │                                             │
│  🗺 Guide │   Pool                                      │
│  💧 Faucet│   [koto.isekai-pool.com:3301 ▼]            │
│  🎮 Games │                                             │
│          │   CPU Threads                               │
│          │   [●●●●●●●●○○○○○○○○○○○○]  8 / 24          │
│          │   Recommended: 16 for i9-14900F             │
│          │                                             │
│          │   ┌─────────────────────────────────────┐  │
│          │   │      ▶  START MINING                 │  │
│          │   └─────────────────────────────────────┘  │
│          │                                             │
│          ├─────────────────────────────────────────────┤
│          │  ● Mining · 14,823 H/s · 48°C              │
│          │                                             │
│          │  Shares    Pool Hashrate    Network         │
│          │  47 / 0    31.2 kH/s       19.1 kH/s       │
│          │                                             │
│          │  Est. earnings: ~8.4 KOTO/day (~$0.00031)  │
│          │  Your share of network: 77.5%               │
│          │                                             │
│          │  ──────────────── Hashrate History ──────── │
│          │  [sparkline chart last 10 minutes]          │
│          │                                             │
│          │  Recent shares:                             │
│          │  16:54:23 · accepted · diff 0.42            │
│          │  16:54:18 · accepted · diff 0.38            │
│          │  16:54:11 · accepted · diff 0.41            │
└──────────┴─────────────────────────────────────────────┘
```

**Key elements:**
- Wallet address validated on blur (regex k1... or jz...)
- Pool dropdown pre-populated with all known pools
- Thread slider with CPU-specific recommendation
- Estimated earnings updates live based on current hashrate + network diff
- Hashrate sparkline (last 10 minutes, canvas/SVG)
- Share log (last 20, color-coded accepted/rejected)
- Temperature from hardware.js (Windows: wmic, Linux: sensors)
- Stop button replaces Start when mining

---

### 2. Pools Directory

All known KOTO pools, always up to date.

```
┌─────────────────────────────────────────────────────┐
│  KOTO Mining Pools                    [Refresh Status]│
├─────────────────────────────────────────────────────┤
│                                                     │
│  ⭐ RECOMMENDED                                      │
│  ┌───────────────────────────────────────────────┐  │
│  │ 🏊 koto.isekai-pool.com          ● Online     │  │
│  │ Fee: 1% · Min Pay: 0.5 KOTO · Language: EN   │  │
│  │ Ports: 3301 (low) · 3302 (mid) · 3303 (high) │  │
│  │ stratum+tcp://koto.isekai-pool.com:3301       │  │
│  │ [Use This Pool ▶]  [Visit Website]            │  │
│  └───────────────────────────────────────────────┘  │
│                                                     │
│  OTHER POOLS                                        │
│  ┌───────────────────────────────────────────────┐  │
│  │ 🏊 mofumofu.me                   ● Online     │  │
│  │ Fee: 0.5% · Min Pay: 0.1 KOTO · Language: JP │  │
│  │ Ports: 3301-3304                              │  │
│  │ stratum+tcp://koto.mofumofu.me:3301           │  │
│  │ [Use This Pool ▶]  [Visit Website]            │  │
│  └───────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────┐  │
│  │ 🏊 leywapool.com                 ● Online     │  │
│  │ Fee: 0.9% · Min Pay: varies · Language: EN   │  │
│  │ [Use This Pool ▶]  [Visit Website]            │  │
│  └───────────────────────────────────────────────┘  │
│                                                     │
│  SOLO MINING (Advanced)                             │
│  ┌───────────────────────────────────────────────┐  │
│  │ ⚡ isekai-pool.com RPC Node                   │  │
│  │ Direct RPC · No fees · Keep 100% of blocks   │  │
│  │ url: http://153.75.225.100:8432               │  │
│  │ Requires: KOTO wallet address for coinbase    │  │
│  │ [Use Solo Mining ▶]  [Learn More]             │  │
│  └───────────────────────────────────────────────┘  │
│                                                     │
│  Pool status fetched from api.isekai-pool.com       │
│  Last updated: 16:54:23                             │
└─────────────────────────────────────────────────────┘
```

**Clicking "Use This Pool"** auto-fills the Mining screen with correct stratum URL and port.

Pool status (online/offline) polled from `api.isekai-pool.com/api/v1/koto/pools` every 5 minutes.

---

### 3. About KOTO

The Western world's best KOTO explainer. Doubles as marketing.

**Sections:**
- **What is KOTO?** — Japanese CPU privacy coin, Zcash fork, yescryptR8G, born 2018
- **Why CPU mining?** — ASIC resistant by design, your hardware = fair share
- **Privacy features** — zk-SNARKs explained simply, transparent vs shielded addresses
- **The community** — small, Japanese, alive, growing
- **Roadmap** — link to ko-to.org
- **Get a wallet** — download links for Core Client + Electrum
- **Block explorer** — link to insight explorer
- **Where to trade** — known exchanges
- **Resources** — Discord, BitcoinTalk, GitHub

Design: dark ink background, KOTO logo prominent, 琴 kanji as decorative element, Shizen aesthetic.

---

### 4. Mining Guide

Step by step — from zero to mining in 5 minutes.

**Sections:**

#### Quick Start (3 steps)
```
1. Get a KOTO wallet → download Core Client
2. Enter your k1... address above
3. Click Start Mining
```

#### Understanding Your Stats
- What is hashrate? (H/s explained simply)
- What are shares? (pool shares vs solo blocks)
- How are earnings calculated?
- Why does TTF vary so much?

#### Hardware Tips (Alienware R16 specific + general)
| CPU | Recommended Threads | Expected H/s |
|-----|--------------------|----|
| Intel i9-14900F (yours) | 16–20 | ~15,000–20,000 H/s |
| Intel i7-13700K | 12–16 | ~10,000–14,000 H/s |
| AMD Ryzen 9 5900X | 20–24 | ~18,000–25,000 H/s |
| Raspberry Pi 5 | 2–4 | ~400–500 H/s |
| Raspberry Pi 4 | 2 | ~150–200 H/s |

#### Pool vs Solo Mining
- Pool: steady micro-payouts, good for smaller hashrate
- Solo: all-or-nothing, good when you have significant % of network
- At 15,000 H/s you're ~78% of KOTO network → solo is very viable

#### Temperature & Safety
- Recommended max CPU temp: 85°C
- Use --cpu-priority=2 to keep system responsive
- Kotominer auto-warns if temp exceeds 80°C

#### RPi5 Setup Guide
- Link to full guide at isekai-pool.com/guide.html
- Quick ARM64 compile instructions inline

---

### 5. Faucet (WebView)

Embedded WebView pointing to `koto.isekai-pool.com/earn`.

- Full earn hub inside the app
- Wallet address pre-filled from Mining screen settings
- Users can play kanji quiz, shrine visit, etc. without leaving the app
- "Open in Browser" button for full experience

---

### 6. Games

Promotional screen for Isekai Adventure + GameGlass.

```
┌─────────────────────────────────────────────────────┐
│  🎮 Games & Rewards                                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │     ⛩  ISEKAI ADVENTURE                     │   │
│  │     Earn KOTO while you play                │   │
│  │     An RPG world powered by CPU mining      │   │
│  │                                             │   │
│  │     [COMING SOON]  [Join Waitlist]          │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  ┌─────────────────────────────────────────────┐   │
│  │     💎 GameGlass.Live                       │   │
│  │     Crypto payments for every game engine   │   │
│  │     Spend your KOTO in games. Direct.       │   │
│  │                                             │   │
│  │     [Learn More]  [Join Waitlist]           │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  Other 10k Game Studio games:                       │
│  [CrystalMines] [GemStrike] [More coming...]        │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## Miner Process Manager (`miner.js`)

```javascript
const { spawn } = require('child_process');
const path = require('path');
const os = require('os');

class MinerProcess {
  constructor() {
    this.process = null;
    this.stats = {
      hashrate: 0,
      shares: { accepted: 0, rejected: 0 },
      temperature: 0,
      uptime: 0,
    };
  }

  getBinaryPath() {
    const platform = process.platform;  // win32, linux
    const arch = process.arch;          // x64, arm64
    const binary = platform === 'win32' ? 'cpuminer-koto.exe' : 'cpuminer-koto';
    return path.join(process.resourcesPath, `${platform}-${arch}`, binary);
  }

  start(config) {
    const args = [
      '--algo=yescryptr8g',
      `--url=${config.pool_url}`,
      `--user=${config.wallet_address}`,
      '--pass=x',
      `--threads=${config.threads}`,
      '--cpu-priority=2',
      '--no-color',
    ];

    // Solo mining mode
    if (config.solo) {
      args.push(`--coinbase-addr=${config.wallet_address}`);
    }

    this.process = spawn(this.getBinaryPath(), args);
    this.process.stdout.on('data', (data) => this.parseOutput(data.toString()));
    this.process.stderr.on('data', (data) => this.parseOutput(data.toString()));
  }

  parseOutput(line) {
    // Parse hashrate: "CPU #0: 1,823.4 H/s"
    const hashrateMatch = line.match(/Total:\s+([\d,.]+)\s+([KMG]?[Hh]\/s)/);
    if (hashrateMatch) {
      this.stats.hashrate = parseFloat(hashrateMatch[1].replace(',', ''));
    }

    // Parse accepted shares
    const shareMatch = line.match(/accepted:\s+(\d+)\/(\d+)/);
    if (shareMatch) {
      this.stats.shares.accepted = parseInt(shareMatch[1]);
    }

    // Parse TTF
    const ttfMatch = line.match(/Miner TTF @ ([\d.]+) ([hHmMsS\/]+) ([\d.]+)/);
    if (ttfMatch) {
      // emit TTF update
    }

    this.emit('stats', this.stats);
    this.emit('log', line.trim());
  }

  stop() {
    if (this.process) {
      this.process.kill('SIGTERM');
      this.process = null;
    }
  }
}
```

---

## Hardware Detection (`hardware.js`)

```javascript
const os = require('os');

function getCPUInfo() {
  const cpus = os.cpus();
  return {
    model: cpus[0].model,
    cores: cpus.length,
    recommended_threads: Math.floor(cpus.length * 0.75), // 75% of cores
  };
}

async function getCPUTemp() {
  // Windows: wmic path Win32_PerfFormattedData_Counters_ThermalZoneInformation
  // Linux: cat /sys/class/thermal/thermal_zone*/temp
  // Return degrees C or null if unavailable
}
```

---

## Earnings Estimator

Live calculation shown on Mining screen:

```javascript
function estimateEarnings(myHashrate, networkHashrate, blockReward = 11.25) {
  const myShare = myHashrate / networkHashrate;
  const blocksPerDay = (24 * 60 * 60) / 60; // 1440 blocks/day at 60s block time
  const dailyKoto = myShare * blocksPerDay * blockReward;
  return {
    daily: dailyKoto,
    weekly: dailyKoto * 7,
    monthly: dailyKoto * 30,
    networkShare: (myShare * 100).toFixed(2) + '%',
  };
}
```

Network hashrate polled from `api.isekai-pool.com/api/v1/koto/status` every 60s.

---

## News Panel

Small panel at the bottom of Mining screen. Pulls from:
- `api.isekai-pool.com/api/v1/news` (simple JSON feed you control)
- Shows: pool announcements, game launch news, KOTO network updates
- Max 3 items, links open in system browser

This is your direct marketing channel inside the miner.

```json
[
  {
    "title": "Isekai Adventure — Alpha coming soon",
    "body": "Play and earn KOTO. Join the waitlist.",
    "url": "https://isekai-pool.com/game",
    "date": "2026-04-04"
  },
  {
    "title": "KOTO Faucet now live",
    "body": "Earn KOTO daily at koto.isekai-pool.com/earn",
    "url": "https://koto.isekai-pool.com/earn",
    "date": "2026-04-04"
  }
]
```

---

## Settings (persisted via electron-store)

```javascript
{
  wallet_address: '',
  pool_url: 'stratum+tcp://koto.isekai-pool.com:3301',
  threads: auto-detected,
  cpu_priority: 2,
  auto_start: false,        // start mining on app launch
  minimize_to_tray: true,   // keep mining when window closed
  temp_warning: 80,         // warn if CPU exceeds this °C
  theme: 'dark',            // dark only for now
  language: 'en',
}
```

---

## System Tray

When minimized to tray:
- Icon: KOTO logo (torii gate pixel icon)
- Tooltip: "Kotominer · 14,823 H/s · 47 accepted"
- Right-click menu:
  - Show Window
  - Pause Mining
  - Resume Mining
  - Quit

---

## Auto-Update

Via `electron-updater` pointing to GitHub releases:
```
https://github.com/jeromeheuze/kotominer/releases/latest
```

Check on startup. If update available: "New version available — update now?" banner.

---

## Installer (electron-builder)

**Windows:**
- NSIS installer: `Kotominer-Setup-1.0.0.exe`
- Installs to `%APPDATA%\Kotominer`
- Desktop shortcut + Start menu entry
- Uninstaller included

**Linux:**
- AppImage: `Kotominer-1.0.0.AppImage` (no install needed)
- `.deb` package for Debian/Ubuntu

**Distribution:**
- GitHub releases (primary)
- Download page at `isekai-pool.com/kotominer`
- Listed in KOTO community channels

---

## Design System

Follow isekai-pool.com dark theme:

```css
--bg-primary: #0d0f14;
--bg-card: #13161e;
--bg-elevated: #1a1d28;
--accent-violet: #7c6af7;
--accent-gold: #f0c040;      /* KOTO amounts */
--accent-green: #22c55e;     /* accepted shares */
--accent-red: #ef4444;       /* rejected shares / warnings */
--text-primary: #e2e8f0;
--text-muted: #64748b;
--font-mono: 'JetBrains Mono', monospace;  /* hashrates, addresses */
```

Torii gate SVG as the app icon and splash screen centerpiece.  
Subtle particle/firefly animation on splash screen only (not while mining — performance).

---

## Version Roadmap

### v1.0 — KOTO Only
- Mining dashboard
- All known pools
- About KOTO
- Guide
- Faucet webview
- Windows x64 + Linux x64

### v1.1 — Multi-coin
- Add YTN (yespowerR16) tab
- Add TDC (yespower-b2b) tab
- Coin switcher in nav

### v1.2 — Games Integration
- Isekai Adventure webview/link
- GameGlass promo screen
- In-app KOTO spending via GameGlass widget

### v2.0 — Full Isekai Miner
- Mine any isekai-pool.com coin
- Unified earnings dashboard across all coins
- Live leaderboard vs other Kotominer users

---

## Notes for Cursor

- **Scaffold in repo:** `kotominer/` at the workspace root (Electron main + Vue/Vite renderer + `miner-restore` hook). Run `npm install` and `npm run dev` inside that folder.
- Primary dev target is Windows x64 (Alienware Aurora R16, i9-14900F, 24 cores)
- Use Electron 28+ with contextIsolation enabled
- IPC between main/renderer via ipcMain/ipcRenderer with preload script
- Never expose Node.js APIs directly to renderer
- cpuminer binary spawned from main process only, never renderer
- Hashrate parsing must handle both H/s and kH/s output formats
- electron-store for settings persistence, no SQLite needed
- News feed is a simple JSON endpoint you control — no CMS needed
- Pool status checks go through api.isekai-pool.com, never direct to pool IPs from the app
- All external links open via shell.openExternal(), never in-app navigation
- Faucet and Games screens use BrowserView/WebContentsView for embedded web content
