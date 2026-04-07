# KOTO Pool Launch — Community Posts

---

## 1. BitcoinTalk Post
**Thread:** https://bitcointalk.org/index.php?topic=2728195.0
**Post as a reply to the existing thread**

---

**[NEW POOL] koto.isekai-pool.com — English-language KOTO pool with explorer + faucet**

Hello KOTO community,

I launched a new English-language KOTO mining pool and wanted to share it here.

**Pool:** https://koto.isekai-pool.com
**Algorithm:** yescryptR8G
**Fee:** 1%
**Min payout:** 0.1 KOTO
**No registration required** — your KOTO address is your identity

**Stratum ports:**
- 3301 — low difficulty (Raspberry Pi, mobile miners)
- 3302 — medium difficulty (desktop CPUs)
- 3303 — high difficulty (multi-core, high-end CPUs)

**Miner command (Linux/RPi):**
```
./minerd -o stratum+tcp://koto.isekai-pool.com:3301 -u YOUR_KOTO_ADDRESS.worker -p x -t 4
```

**Windows:**
Download KotoMiner from https://github.com/KotoDevelopers/cpuminer-yescrypt/releases
Use minerd-avx2.exe for modern Intel/AMD CPUs.

---

**What else I built:**

🔍 **Block Explorer** — https://explorer.isekai-pool.com
Both official KOTO explorers appear to be down. I built a lightweight RPC-backed explorer running on a fully synced node. Blocks, transactions, and address lookup all working.

📊 **Network Tracker** — https://isekai-pool.com/koto-network.html
Historical hashrate and pool distribution chart updated every 15 minutes. Tracks isekai-pool.com, mofumofu.me, leywapool.com, and unknown hashrate over time.

💧 **Faucet** (coming soon) — https://isekai-pool.com/faucet.html
Gamified KOTO faucet funded by pool mining. Daily shrine visit, kanji quiz, retro game trivia. Earn KOTO by learning Japanese.

---

**About isekai-pool.com**

I run a public CPU coin infrastructure hub at isekai-pool.com — RPC nodes for KOTO, Yenten, and Tidecoin. I'm a French developer based in San Francisco with a passion for Japanese culture and obscure CPU coins.

I want to grow the Western KOTO community and provide reliable English-language infrastructure. The pool, explorer, and faucet are all free to use.

Thank you to wo01 and the KotoDevelopers team for maintaining KOTO. The pool software is based on zny-nomp by ROZ (mofumofu.me) — full credits at https://koto.isekai-pool.com/credits.

Happy mining! ⛩

— Jerome
https://isekai-pool.com

---

## 2. Reddit Post
**Subreddits:** r/cpumining · r/cryptomining · r/privacy

**Title:** I built the first English-language KOTO mining pool + block explorer (Japanese CPU privacy coin)

---

**Body:**

KOTO is a Japanese CPU privacy coin from 2018 — a Zcash fork running on yescryptR8G. It's maintained by a single developer in Japan, has ~3,000 community members, and until last week had zero English-language mining infrastructure.

I spent a Sunday building out the full stack:

**🏊 Mining Pool** — koto.isekai-pool.com
1% fee, no registration, three difficulty ports for RPi through desktop. The pool currently holds 20%+ of the total KOTO network hashrate with a single Raspberry Pi 5 running 24/7.

**🔍 Block Explorer** — explorer.isekai-pool.com
Both official KOTO explorers went offline. Built a lightweight RPC-backed replacement — blocks, transactions, address lookup, all working.

**📊 Network Tracker** — isekai-pool.com/koto-network.html
Historical pool distribution chart updated every 15 minutes. First public record of KOTO pool data at this granularity.

**💧 Faucet** (in progress) — isekai-pool.com/faucet.html
Gamified — earn KOTO through daily shrine visits, kanji quizzes, and Japanese retro game trivia.

---

**Why KOTO?**

- Genuine CPU privacy coin — yescryptR8G is ASIC resistant by design
- Network is tiny (~20 kH/s total) — a single RPi5 is ~5% of network
- Japanese aesthetic + Zcash privacy tech = underrated combination
- Almost no Western presence — huge gap to fill

**Miner setup (Linux/RPi5):**
```bash
git clone https://github.com/KotoDevelopers/cpuminer-yescrypt.git
cd cpuminer-yescrypt && ./autogen.sh
./configure CFLAGS="-O3 -funroll-loops -fomit-frame-pointer"
make -j4
./minerd -o stratum+tcp://koto.isekai-pool.com:3301 -u YOUR_KOTO_ADDRESS.worker -p x -t 4
```

Note: cpuminer-opt doesn't work for KOTO (difficulty bug). Use the official KotoDevelopers miner above.

Happy to answer questions. The KOTO community is small but the coin is technically solid.

---

## 3. poolbay.io Submission
**URL:** https://poolbay.io/submit

**Fields:**
- Coin: KOTO
- Pool name: isekai-pool.com
- Pool URL: https://koto.isekai-pool.com
- API URL: https://koto.isekai-pool.com/api/stats
- Fee: 1%
- Min payout: 0.1 KOTO
- Algorithm: yescryptR8G
- Contact: (your email)

---

## 4. cryptunit.com Submission
**URL:** https://www.cryptunit.com

Search for KOTO → find the pool submission form → submit:
- Pool URL: https://koto.isekai-pool.com
- Stratum: stratum+tcp://koto.isekai-pool.com:3302
- Fee: 1%

---

## 5. LinkedIn Post
**Short version for LinkedIn feed (not the full article)**

---

Built something this weekend:

→ Launched koto.isekai-pool.com — the first English-language mining pool for KOTO, a Japanese CPU privacy coin
→ Built explorer.isekai-pool.com — the only working KOTO block explorer (both official ones went offline)
→ The pool hit #1 on the KOTO network within hours, finding 2 out of every 3 blocks globally

KOTO runs on yescryptR8G — an algorithm designed so that regular CPUs (including a $80 Raspberry Pi) can mine competitively. No ASICs, no GPU farms.

This is the infrastructure layer for GameGlass.live — a payment widget letting game developers accept CPU-mined coins directly, without app store cuts.

Mine → Earn → Spend in games. The loop is real.

koto.isekai-pool.com | explorer.isekai-pool.com | gameglass.live

#BuildInPublic #CPUMining #IndieGameDev #Privacy #Japan #GameGlass

---

## 6. X/Twitter Thread
**Post as a thread**

---

**Tweet 1:**
Built a KOTO mining pool this weekend.

Within hours it was finding 2 out of every 3 blocks on the entire network.

Here's what I built and why it matters for @GameGlassLive 🧵

---

**Tweet 2:**
KOTO is a Japanese CPU privacy coin — a Zcash fork running on yescryptR8G.

Small network (~20 kH/s), maintained by one developer in Japan, almost zero Western presence.

A Raspberry Pi 5 is enough to hold 5% of the network solo.

---

**Tweet 3:**
In one day I built:

⛩ koto.isekai-pool.com — mining pool
🔍 explorer.isekai-pool.com — block explorer (official ones went offline)
📊 isekai-pool.com/koto-network.html — historical network tracker
💧 faucet coming soon — earn KOTO through kanji quizzes and shrine visits

---

**Tweet 4:**
The GameGlass connection:

CPU miners earn KOTO with nowhere to spend it.
Indie game devs need payments without app store cuts.

GameGlass.live is the widget that connects them.
Isekai Adventure (Godot 4 RPG) will be the first game to prove it.

Mine → Earn → Spend in games.

---

**Tweet 5:**
If you want to mine KOTO:

Linux/RPi:
```
git clone github.com/KotoDevelopers/cpuminer-yescrypt
./minerd -o stratum+tcp://koto.isekai-pool.com:3301 -u YOUR_ADDRESS.worker -p x
```

Windows: download KotoMiner from the KotoDevelopers GitHub releases.

1% fee. No registration. English support.

---

**Tweet 6:**
The faucet will let anyone earn KOTO through:
- Daily shrine visits ⛩
- Kanji quizzes 🈳
- Japanese retro game trivia 🎮

Funded entirely by pool mining. Self-sustaining loop.

Follow for updates on the faucet launch and Isekai Adventure.

#KOTO #CPUMining #BuildInPublic #GameDev #Privacy #Japan

---

## 7. KOTO Discord Message
**Once you get access — post in #general or #mining channel**

---

こんにちは！Hello KOTO community!

I'm Jerome, a developer from San Francisco. I just launched English-language KOTO infrastructure:

⛩ **Mining Pool** — koto.isekai-pool.com (1% fee, no registration)
🔍 **Block Explorer** — explorer.isekai-pool.com (RPC-backed, both official explorers appear offline)
📊 **Network Tracker** — isekai-pool.com/koto-network.html

I'm a fan of Japanese culture and CPU privacy coins. I want to help KOTO grow in the Western community.

Miner guide in English: koto.isekai-pool.com/getting-started

Thank you wo01 and the dev team for keeping KOTO alive! 🙏

Pool software based on zny-nomp by ROZ — full credits at koto.isekai-pool.com/credits

---

## Posting Order (recommended)

1. **BitcoinTalk** — highest priority, wo01 monitors this
2. **Reddit r/cpumining** — largest CPU mining audience
3. **poolbay.io** — directory listing, permanent backlink
4. **LinkedIn** — connects GameGlass angle to professional audience
5. **X/Twitter thread** — real-time community discovery
6. **Discord** — once access sorted
7. **cryptunit.com** — directory listing
