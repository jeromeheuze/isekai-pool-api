# isekai-pool.com — Hashrate Rental System Spec
**Phase: Future · Stack: Laravel + Livewire + GameGlass · Status: Parked**

---

## Vision

A lightweight MiningRigRentals clone focused exclusively on CPU-mineable yespower/yescrypt coins. You own the hardware, renters pay for hashrate time, payouts go directly to their wallet. No third-party rig owners to manage initially — just your own machines pointed at your pool.

**Tagline:** "Rent CPU hashrate. Mine KOTO tonight."

---

## Why Build This

- MRR doesn't support yescryptR8G natively
- No existing rental market for KOTO specifically
- You own the hardware (EliteDesk + RPi5 + future units)
- KOTO network is tiny — even 1 rented unit is meaningful hashrate
- GameGlass handles KOTO payments natively — zero friction checkout
- Laravel backend already exists at api.isekai-pool.com

---

## How It Works

```
Renter visits isekai-pool.com/rent
        ↓
Picks rig, duration, threads
        ↓
Pays in KOTO via GameGlass widget
        ↓
Agent on physical rig detects rental start
        ↓
Miner restarts pointed at renter's pool/wallet
        ↓
Renter mines KOTO to their own wallet
        ↓
Rental ends → miner returns to default pool
        ↓
Renter can verify blocks on explorer
```

---

## Database Schema

### `rigs` table
```php
Schema::create('rigs', function (Blueprint $table) {
    $table->id();
    $table->string('name');                    // "EliteDesk-01", "RPi5-01"
    $table->string('cpu');                     // "Intel i5-8500T"
    $table->integer('max_threads');            // 6
    $table->integer('hashrate_hs');            // 7000 (estimated H/s at max threads)
    $table->string('algo');                    // "yescryptR8G"
    $table->decimal('price_per_hour', 10, 4); // in KOTO
    $table->boolean('available');
    $table->string('agent_token');             // secret token for agent auth
    $table->timestamp('last_heartbeat');       // agent pings every 60s
    $table->timestamps();
});
```

### `rentals` table
```php
Schema::create('rentals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rig_id');
    $table->string('renter_wallet');           // KOTO k1... address
    $table->string('renter_pool_url');         // stratum+tcp://their-pool:port
    $table->string('renter_worker');           // their worker name
    $table->integer('threads');                // how many threads rented
    $table->integer('duration_hours');
    $table->timestamp('starts_at');
    $table->timestamp('ends_at');
    $table->decimal('total_koto', 16, 8);     // amount paid
    $table->string('payment_txid')->nullable();
    $table->enum('status', ['pending_payment', 'scheduled', 'active', 'completed', 'cancelled']);
    $table->timestamps();
});
```

---

## Pages & Routes

### `isekai-pool.com/rent` — Rental Marketplace

**Layout:**
- Hero: "Rent KOTO Hashrate — No hardware. No setup. Start mining in minutes."
- Available rigs grid

**Each rig card:**
```
┌─────────────────────────────────┐
│  EliteDesk-01                   │
│  Intel i5-8500T · 6 cores       │
│  ~7,000 H/s · yescryptR8G      │
│                                 │
│  ● Available                    │
│                                 │
│  Duration:  [1h] [4h] [8h] [24h]│
│  Threads:   [●●●●●●] 6/6       │
│                                 │
│  Price: 12.5 KOTO / hour        │
│  Total: 12.5 KOTO               │
│                                 │
│  [Rent Now →]                   │
└─────────────────────────────────┘
```

### `isekai-pool.com/rent/checkout` — Checkout

**Fields:**
- Your KOTO wallet address (where stats are visible)
- Your pool stratum URL (where your miner will connect)
- Worker name
- Start time (now or scheduled)
- GameGlass payment widget

**Flow:**
1. Fill in wallet + pool URL
2. GameGlass widget opens for KOTO payment
3. Payment confirmed → rental scheduled
4. Confirmation page with rental ID + countdown

### `isekai-pool.com/rent/dashboard` — Renter Dashboard

- Active rental status
- Live hashrate (polled from agent)
- Time remaining
- Estimated KOTO earned (based on network stats)
- Block explorer link
- Extend rental button

### `isekai-pool.com/rent/admin` — Operator Dashboard (you only)

- All rigs status + heartbeat
- Active/upcoming/completed rentals
- Revenue summary
- Manual override controls

---

## The Agent (runs on each physical rig)

Small Node.js or Python script. Runs as a systemd service on each mining machine.

**Responsibilities:**
- Poll `api.isekai-pool.com/api/v1/rentals/active?rig=EliteDesk-01` every 60 seconds
- If active rental found: restart miner with renter's stratum URL + threads
- If no active rental: restart miner with default pool (koto.isekai-pool.com)
- Send heartbeat to API every 60 seconds with current hashrate
- Never expose the API token or miner credentials

**Agent pseudocode:**
```javascript
const AGENT_TOKEN = process.env.AGENT_TOKEN;
const RIG_ID = process.env.RIG_ID;
const API = 'https://api.isekai-pool.com/api/v1';
const DEFAULT_POOL = 'stratum+tcp://koto.isekai-pool.com:3302';
const DEFAULT_WORKER = 'k1YourAddress.elitedesk01';

let currentRentalId = null;
let minerProcess = null;

async function checkRental() {
    const res = await fetch(`${API}/rentals/active?rig=${RIG_ID}`, {
        headers: { 'X-Agent-Token': AGENT_TOKEN }
    });
    const data = await res.json();

    if (data.rental && data.rental.id !== currentRentalId) {
        // New rental started — switch miner
        currentRentalId = data.rental.id;
        restartMiner({
            url: data.rental.renter_pool_url,
            user: data.rental.renter_worker,
            threads: data.rental.threads
        });
    } else if (!data.rental && currentRentalId !== null) {
        // Rental ended — return to default
        currentRentalId = null;
        restartMiner({
            url: DEFAULT_POOL,
            user: DEFAULT_WORKER,
            threads: MAX_THREADS
        });
    }

    // Send heartbeat with current hashrate
    await fetch(`${API}/rigs/${RIG_ID}/heartbeat`, {
        method: 'POST',
        headers: { 'X-Agent-Token': AGENT_TOKEN },
        body: JSON.stringify({ hashrate: getCurrentHashrate() })
    });
}

setInterval(checkRental, 60000);
```

---

## API Endpoints (Laravel)

```
GET  /api/v1/rigs                          → list available rigs + pricing
GET  /api/v1/rigs/{id}                     → single rig details
POST /api/v1/rentals                        → create rental (after payment)
GET  /api/v1/rentals/{id}                  → rental status
GET  /api/v1/rentals/active?rig={id}       → agent polls this (auth required)
POST /api/v1/rigs/{id}/heartbeat           → agent reports hashrate (auth required)
GET  /api/v1/rentals/{id}/stats            → live hashrate for renter dashboard
```

---

## Pricing Model

**Base rate:** KOTO per hour per thread

| Rig | CPU | H/s per thread | Price per thread/hr |
|-----|-----|----------------|---------------------|
| EliteDesk-01 | i5-8500T | ~1,200 H/s | 2 KOTO |
| RPi5-01 | Cortex-A76 | ~250 H/s | 0.5 KOTO |

**Bundle pricing:**
- 1 hour: base rate
- 4 hours: 10% discount
- 8 hours: 15% discount
- 24 hours: 20% discount

**At current KOTO price (~$0.000037):**
- Full EliteDesk (6 threads) for 1 hour = 12 KOTO = ~$0.0004
- Basically free in USD terms — priced for the KOTO community, not USD speculators

As KOTO price grows (1 YTN = 1 USD roadmap style), pricing becomes meaningful.
Adjust pricing via admin panel without code changes.

---

## Payment Flow (GameGlass)

```javascript
// On checkout confirmation
function openPayment(rental) {
    const widget = `https://gameglass.live/widget
        ?coin=KOTO
        &amount=${rental.total_koto}
        &wallet=${OPERATOR_WALLET}
        &ref=${rental.id}`;

    openGameGlassOverlay(widget);
}

// On payment confirmed
function onPaymentConfirmed(txid, rentalId) {
    // Call API to activate rental
    fetch(`/api/v1/rentals/${rentalId}/confirm`, {
        method: 'POST',
        body: JSON.stringify({ txid })
    });
}
```

---

## Hashrate Verification

Since the KOTO network is tiny and public:

1. Agent reports hashrate every 60s to API
2. Renter dashboard shows live H/s from agent heartbeat
3. Block explorer shows which address mined each block
4. If renter's pool finds blocks during rental period — fully verifiable

No trust required — blockchain is the receipt.

---

## Anti-Abuse Rules

- Maximum 1 active rental per wallet address
- Minimum rental: 1 hour
- Maximum rental: 7 days
- Renter must provide valid KOTO address and stratum URL
- Stratum URL validated on checkout (must be a real stratum endpoint)
- Refund policy: if rig goes offline during rental → pro-rated KOTO refund

---

## Future: Third-Party Rig Owners

Once the platform is proven with your own hardware, open it to external rig owners:

- Rig owners register, install agent, list their rigs
- Platform takes 10% of rental fee
- Rig owner keeps 90%
- Same agent software, same API
- This is the full MRR clone vision

---

## Livewire Components

```
app/Livewire/Rental/
├── RigListing.php         // marketplace grid
├── CheckoutForm.php       // booking form + GameGlass trigger
├── RentalDashboard.php    // renter live stats
├── AdminDashboard.php     // operator controls
└── RigStatus.php          // real-time rig heartbeat display
```

---

## Development Phases

### Phase 1 — MVP (your hardware only)
- Single rig (EliteDesk-01) listed
- Manual payment confirmation (you verify KOTO txid)
- Basic checkout form
- Agent script running on EliteDesk
- Renter gets confirmation email with rental details

### Phase 2 — GameGlass Payments
- Automated KOTO payment via GameGlass widget
- Auto-activation on payment confirmation
- Renter dashboard with live stats

### Phase 3 — Multi-rig
- Add all your physical rigs
- Rig availability calendar
- Scheduling (rent starting at future time)

### Phase 4 — Third-party rigs
- External rig owner registration
- Revenue split (90/10)
- Trust scoring for rig owners
- Full MRR feature parity

---

## Notes

- This spec is **parked** — build after pool + faucet + game are live
- The agent is the most interesting piece — keep it simple, make it bulletproof
- GameGlass payment integration makes this unique vs MRR (KOTO-native payments)
- Start with manual payment verification in Phase 1 to validate demand before automating
- The EliteDesk arriving this week becomes the first listed rig once Phase 1 is built
- Physical constraint: need dedicated space for rigs before scaling beyond 2-3 units
