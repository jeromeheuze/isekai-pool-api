# KOTO Gamified Faucet — Cursor Spec
**koto.isekai-pool.com · Laravel + Livewire · KOTO Rewards Network**

---

## Vision

A gamified KOTO faucet that turns the Japan Empire Network into a **play-to-earn / learn-to-earn ecosystem**. Users earn real KOTO by playing mini-games, learning Japanese, visiting partner sites, and completing daily rituals. KOTO mined at `koto.isekai-pool.com` funds the reward pool — self-sustaining loop.

**The loop:**
```
Mine KOTO (pool) → Fund faucet → Players earn KOTO → 
Players discover the network → Some become miners → Pool grows
```

---

## Core Principles

- **No registration required** — wallet address is the identity
- **Daily rituals, not grind** — each activity has a 24h cooldown
- **Japanese aesthetic throughout** — Shizen Design System (csskitsune.com)
- **Cross-site earning** — activities span the entire Japan Empire Network
- **Transparent** — all payouts visible, faucet balance public
- **Anti-abuse first** — Cloudflare Turnstile + Redis rate limiting

---

## Tech Stack

| Layer | Tech |
|-------|------|
| Backend | Laravel 11 (existing isekai-pool-api repo) |
| Frontend | Livewire 3 + Alpine.js |
| CSS | Shizen Design System (csskitsune.com) + dark theme |
| Rate limiting | Redis (already installed for zny-nomp) |
| Captcha | Cloudflare Turnstile (free) |
| Payouts | KOTO RPC via `koto-cli sendtoaddress` |
| Cross-site auth | Signed URL tokens (no accounts needed) |

---

## Database Schema

### `faucet_claims` table
```php
Schema::create('faucet_claims', function (Blueprint $table) {
    $table->id();
    $table->string('wallet_address');      // KOTO k1... or jz... address
    $table->string('ip_address');
    $table->string('activity_slug');       // shrine_visit, kanji_quiz, etc.
    $table->string('source_site');         // isekai-pool, yokai-site, the725club, etc.
    $table->decimal('amount', 16, 8);      // KOTO amount paid
    $table->string('txid')->nullable();    // KOTO transaction ID
    $table->enum('status', ['pending', 'paid', 'failed']);
    $table->timestamps();
    
    $table->index(['wallet_address', 'activity_slug', 'created_at']);
    $table->index(['ip_address', 'activity_slug', 'created_at']);
});
```

### `faucet_balance` table (single row)
```php
Schema::create('faucet_balance', function (Blueprint $table) {
    $table->id();
    $table->decimal('balance', 16, 8);     // current faucet wallet balance
    $table->decimal('total_paid', 16, 8);  // all-time total paid out
    $table->integer('total_claims');        // all-time claim count
    $table->timestamp('last_sync');         // last RPC balance check
});
```

---

## Activities & Rewards

Each activity has a slug, reward amount, cooldown, and source site.

| Slug | Activity | Reward | Cooldown | Source |
|------|----------|--------|----------|--------|
| `shrine_visit` | Daily Shrine Visit | 0.5 KOTO | 24h | koto.isekai-pool.com |
| `kanji_quiz` | Kanji Quiz (5 questions) | 1.0 KOTO | 24h | koto.isekai-pool.com |
| `yokai_match` | Yokai Memory Match | 1.5 KOTO | 24h | japanesemythicalcreatures.com |
| `yokai_quiz` | Yokai Knowledge Quiz | 1.0 KOTO | 24h | japanesemythicalcreatures.com |
| `retro_trivia` | Japanese Retro Game Trivia | 1.0 KOTO | 24h | the725club.com |
| `shrine_puzzle` | Shrine Sudoku | 2.0 KOTO | 24h | shrinepuzzle.com |
| `map_explore` | JapanInPixels Map Challenge | 1.0 KOTO | 24h | japaninpixels.com |
| `coffee_quiz` | Japanese Coffee Quiz | 0.5 KOTO | 24h | kohibou.com |
| `daily_bonus` | All activities completed | 2.0 KOTO | 24h | any |

**Max daily earnings per wallet: ~10.5 KOTO** (if completing everything)
**At current price (~$0.000037): ~$0.0004/day** — trivial cost, meaningful engagement

---

## Pages & Routes

### `koto.isekai-pool.com/earn` — Earn Hub (main page)

**Layout:**
- Hero: "Earn KOTO — Private CPU Coin from Japan"
- Faucet wallet balance + today's stats (live via Livewire)
- Activity grid — card per activity showing:
  - Activity name + icon
  - Reward amount
  - Status: Available / Claimed (X hours remaining) / Locked (enter wallet first)
- Wallet address input (sticky, saved to localStorage)
- Recent payouts feed (last 20 transactions, public)

### `koto.isekai-pool.com/earn/shrine` — Daily Shrine Visit

Simple animated Shinto shrine. One button: "Visit the Shrine". 
- Torii gate animation on click
- Turnstile verification
- Payout triggered
- Haiku displayed as reward flavor text (rotate through 10 haiku about KOTO/privacy/Japan)

### `koto.isekai-pool.com/earn/kanji` — Kanji Quiz

5 kanji questions per session. Multiple choice, 4 options each.

Question format:
```
What does this kanji mean?
琴
A) Shrine  B) Mountain  C) Koto (string instrument)  D) River
```

Question bank: 50+ kanji drawn from JLPT N5-N4 level.  
Pass threshold: 4/5 correct → earn reward.  
Wrong answers show explanation — educational, not punishing.

### `koto.isekai-pool.com/earn/retro` — Japanese Retro Game Trivia

5 questions about Japanese-exclusive retro games — SFC, Game Boy, PC-Engine.  
Question bank sourced from The 725 Club's game library (71 SNES games + GBA list).

Example:
```
Which 1994 SFC game featured a tanuki hero?
A) Super Mario World  B) Pocky & Rocky  C) The 725 Club Special  D) ActRaiser
```

Pass threshold: 3/5 → earn reward.  
Links to the725club.com for "learn more" on each answer.

### Cross-site earning (partner widgets)

Other Japan Empire Network sites embed a lightweight earn widget:

```html
<!-- Drop this on any partner site -->
<script src="https://koto.isekai-pool.com/earn/widget.js"></script>
<div id="koto-earn-widget" data-activity="yokai_match" data-site="yokai"></div>
```

The widget renders a mini game inline. On completion it POSTs to the KOTO faucet API with a signed token. User claims their KOTO without leaving the partner site.

---

## API Endpoints (Laravel)

Add to existing `isekai-pool-api`:

```
POST /api/v1/faucet/claim
Body: { wallet_address, activity_slug, turnstile_token, score, site_token }
Returns: { success, txid, amount, next_claim_at }

GET  /api/v1/faucet/status?wallet=k1xxx
Returns: { activities: [{ slug, available, next_claim_at, reward }], total_earned }

GET  /api/v1/faucet/balance
Returns: { balance, total_paid, total_claims, daily_paid }

GET  /api/v1/faucet/recent
Returns: [ { wallet_short, activity, amount, txid, time } ] // last 20, wallet truncated
```

---

## Claim Flow

```
1. User enters KOTO wallet address (validated: must start with k1 or jz)
2. User completes activity (game/quiz/visit)
3. Turnstile captcha verification
4. API checks:
   a. Valid wallet address format
   b. IP cooldown (Redis: ip:{ip}:{activity} → TTL 24h)
   c. Wallet cooldown (Redis: wallet:{address}:{activity} → TTL 24h)
   d. Faucet balance > reward amount
   e. Daily cap not exceeded (Redis: daily:total → TTL resets at midnight JST)
5. All checks pass → queue payout job
6. Laravel job calls: koto-cli sendtoaddress {wallet} {amount}
7. Store txid + mark claim paid
8. Return success response with txid
```

---

## Payout Job (Laravel Queue)

```php
class ProcessFaucetPayout implements ShouldQueue
{
    public function handle(): void
    {
        // Call KOTO RPC via HTTP
        $response = Http::post('http://127.0.0.1:' . config('koto.rpc_port'), [
            'jsonrpc' => '1.0',
            'method'  => 'sendtoaddress',
            'params'  => [$this->claim->wallet_address, $this->claim->amount],
        ])->withBasicAuth(config('koto.rpc_user'), config('koto.rpc_pass'));

        $txid = $response->json('result');
        
        $this->claim->update(['txid' => $txid, 'status' => 'paid']);
        
        // Update faucet balance cache
        Cache::decrement('faucet:balance', $this->claim->amount * 1e8);
    }
}
```

Use Laravel's database queue driver (already have MySQL). Worker runs as systemd service.

---

## Anti-Abuse Rules

| Rule | Implementation |
|------|----------------|
| Per-IP per-activity 24h cooldown | Redis key: `ip:{hash}:{activity}` TTL 86400 |
| Per-wallet per-activity 24h cooldown | Redis key: `wallet:{address}:{activity}` TTL 86400 |
| Max 10 KOTO per wallet per day | Redis key: `daily:wallet:{address}` TTL resets midnight JST |
| Max 100 KOTO total per day | Redis key: `daily:total` TTL resets midnight JST |
| Cloudflare Turnstile on every claim | Server-side verification before payout |
| Valid KOTO address format | Regex: `^(k1|jz)[a-zA-Z0-9]{38,}$` |
| Minimum faucet balance | Refuse claims if balance < 10 KOTO |
| VPN/proxy detection | Cloudflare handles at edge |

---

## Faucet Funding Strategy

Self-sustaining via pool mining:

1. KOTO pool at `koto.isekai-pool.com` mines blocks
2. A percentage of block rewards (e.g. 10% via `rewardRecipients`) goes to faucet wallet
3. Rest goes to operator wallet
4. Faucet balance shown publicly — transparency builds trust

Initial seed: mine enough solo KOTO before launch to seed ~500 KOTO in the faucet.

---

## UI Design Notes

Follow Shizen Design System (csskitsune.com) dark theme:

- Background: deep ink `#0d0f14`
- Primary: violet `#7c6af7`
- Accent: gold `#f0c040` for KOTO amounts and rewards
- Typography: monospace for wallet addresses and amounts
- Torii gate SVG as the visual centerpiece
- Subtle particle/firefly animation on the shrine page
- Activity cards: dark glass morphism style
- Cooldown timers: animated countdown rings

---

## Gamification Layer

### Streak System
- Visit shrine 7 days in a row → **7-day streak bonus: +3 KOTO**
- Stored in Redis: `streak:{wallet}` with daily update
- Show streak counter on earn hub

### Milestones (one-time)
| Milestone | Reward |
|-----------|--------|
| First claim ever | +0.5 KOTO welcome bonus |
| Complete all activities in one day | +2 KOTO daily master bonus |
| 30-day streak | +10 KOTO loyalty bonus |
| Refer a new wallet (1st claim) | +1 KOTO referral bonus |

### Leaderboard
- Top 10 earners this week (wallet truncated: `k1abc...xyz`)
- Resets every Monday JST
- Winner gets +5 KOTO bonus
- Shown publicly on earn hub — social proof + competition

---

## Cross-Site Integration Plan

| Site | Activity | Widget Type |
|------|----------|-------------|
| japanesemythicalcreatures.com | Yokai Memory Match | Embedded JS widget |
| japanesemythicalcreatures.com | Yokai Knowledge Quiz | Embedded JS widget |
| the725club.com | Japanese Retro Trivia | Embedded JS widget |
| shrinepuzzle.com | Shrine Sudoku completion | API webhook on solve |
| japaninpixels.com | Map exploration challenge | Embedded JS widget |
| kohibou.com | Japanese Coffee Quiz | Embedded JS widget |

**Widget token system:**
Each partner site gets a signed site token. Claims from partner sites include this token. Prevents spoofing — only legitimate widget completions trigger payouts.

```javascript
// Widget generates a signed completion token on the client
// Server verifies: site_token + activity_hash + timestamp + wallet
// Valid for 5 minutes only
```

---

## Livewire Components

```
app/Livewire/Faucet/
├── EarnHub.php          // main earn page, activity grid, wallet input
├── ShrineVisit.php      // shrine animation + claim
├── KanjiQuiz.php        // quiz engine, question rotation
├── RetroTrivia.php      // retro game quiz
├── FaucetBalance.php    // live balance display (polls every 60s)
├── RecentPayouts.php    // live feed of recent claims
└── Leaderboard.php      // weekly top earners
```

---

## Partner Onboarding

Once live, reach out to KOTO community to list `koto.isekai-pool.com/earn` as:
- The only English-language KOTO faucet
- The only gamified KOTO earn platform
- Gateway for new Western users to get their first KOTO

Post in:
- KOTO Discord (once access sorted)
- KOTO BitcoinTalk thread
- Monero/privacy coin subreddits (CPU mining angle)
- Retro gaming communities (The 725 Club crossover)

---

## Launch Checklist

- [ ] KOTO pool live and mining (zny-nomp setup complete)
- [ ] Faucet wallet funded with 200+ KOTO seed
- [ ] Laravel faucet API endpoints built and tested
- [ ] Livewire earn hub live at koto.isekai-pool.com/earn
- [ ] Shrine visit activity working end-to-end
- [ ] Kanji quiz (50 question bank written)
- [ ] Retro trivia (30 question bank from 725 Club library)
- [ ] Cloudflare Turnstile integrated
- [ ] Redis rate limiting tested
- [ ] Cross-site widget tested on japanesemythicalcreatures.com
- [ ] Streak system working
- [ ] Leaderboard working
- [ ] Faucet balance public and updating
- [ ] Mobile responsive (Shizen Design System handles this)
- [ ] Announce in KOTO community channels
