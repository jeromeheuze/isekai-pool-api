# Cursor Task 1 — Fix API 404 + Build Homepage

## Context
Read `_docs/CURSOR.md` first for full project context.

All Laravel scaffold files exist and are correct:
- `api/app/Http/Controllers/Api/RpcController.php` ✅
- `api/app/Services/RpcService.php` ✅
- `api/config/coins.php` ✅
- `api/routes/api.php` ✅

**The only bug:** `api/bootstrap/app.php` is not registering the API routes.

---

## Task 1 — Fix bootstrap/app.php (5 min)

Open `api/bootstrap/app.php`. It currently looks like:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

Add the `api:` line so it becomes:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

That's it. This is why `https://api.isekai-pool.com/api/v1/health` returns 404.

---

## Task 2 — Fix .env RPC passwords (already done on VPS, just keep local clean)

The `api/.env.example` has these placeholders — they are filled in on the VPS
automatically by `vps-init.sh`. Do NOT commit real passwords to git.
The `.env` file is in `.gitignore` already.

---

## Task 3 — Build the homepage (web/index.html)

The file `web/index.html` exists but needs to be a proper page.

**Design spec:**
- Dark theme — background `#0d0f14`, accent color `#7c6af7` (purple)
- Font: JetBrains Mono (Google Fonts CDN)
- Tailwind CSS via CDN only — no build step
- Mobile responsive

**Sections to build:**

### Nav
- Logo: `▲ isekai` + subtitle `異世界` (Japanese for "another world")
- Links: Coins | API | GitHub

### Hero
- Headline: "CPU Coin Infrastructure / for the coins nobody hosts"
- Subtext: "Public RPC nodes for obscure yespower/yescrypt CPU coins. Mine with a Raspberry Pi. No GPU required."
- Two buttons: "View API Docs" and "See Coins"

### Live Node Status (3 cards, auto-refreshing)
Fetch from `https://api.isekai-pool.com/api/v1/{coin}/status` for each coin.
Show for each coin:
- Coin name + symbol
- Online/offline indicator (green pulse dot or red dot)
- Block height (formatted with commas)
- Sync progress bar (percentage)
- Algorithm name

Coins to show: yenten, koto, tidecoin

Refresh every 30 seconds automatically.

### Why CPU Coins (3 feature cards)
1. 🍓 "Raspberry Pi Ready" — Yespower designed for CPUs, $35 Pi can mine
2. 🔒 "No GPU, No ASIC" — GPU miners are actually slower, truly decentralized
3. 🌐 "Free Public RPC" — No API key, no auth, just call it

### API Preview (code block)
Dark code block showing example curl commands:
```bash
# Health check
curl https://api.isekai-pool.com/api/v1/health

# Yenten block count
curl -X POST https://api.isekai-pool.com/api/v1/yenten/rpc \
  -H "Content-Type: application/json" \
  -d '{"method":"getblockcount","params":[]}'
```

### Footer
- Left: `isekai-pool.com — 異世界インフラ`
- Right: GitHub link | API link

---

## Task 4 — After building, push and verify

```bash
git add .
git commit -m "fix: api routing + build homepage"
git push
```

GitHub Actions will auto-deploy to the VPS in ~9 seconds.

Then verify:
- `https://api.isekai-pool.com/api/v1/health` → returns JSON
- `https://isekai-pool.com` → shows the homepage

---

## Important rules (from CURSOR.md)

- HTML + Tailwind CDN only — no React, no Vue, no build step
- Fetch from `api.isekai-pool.com` — never hardcode node IPs in frontend
- Dark theme only
- Monospace font (JetBrains Mono)
- Each HTML page is self-contained — no shared templates
