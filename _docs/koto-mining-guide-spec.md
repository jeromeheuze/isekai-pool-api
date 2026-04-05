# KOTO Mining Guide — Content Update Spec
**Update two pages: isekai-pool.com/guide.html + koto.isekai-pool.com getting-started page**

---

## Page 1: isekai-pool.com/guide.html — Updates

### Critical Fix: Wrong Miner for KOTO

The current guide instructs users to compile cpuminer-opt and use `--algo=yescryptr8g`.
**This does not work.** cpuminer-opt rejects all shares on yescryptR8G due to a difficulty
calculation mismatch. The correct miner for KOTO is the official KotoDevelopers cpuminer-yescrypt.

### Changes to make in guide.html:

#### 1. Add a KOTO-specific warning under "Choose your coin" KOTO card:
```html
<p class="warning">
  ⚠️ KOTO requires a different miner — see the 
  <a href="https://koto.isekai-pool.com/getting-started">KOTO Mining Guide</a>
</p>
```

#### 2. Replace the ARM64 compile section with two tabs: "YTN / TDC" and "KOTO"

**YTN / TDC tab (cpuminer-opt — unchanged):**
```bash
sudo apt update && sudo apt install -y git build-essential automake autoconf \
  libcurl4-openssl-dev libssl-dev libjansson-dev libgmp-dev zlib1g-dev screen

git clone https://github.com/JayDDee/cpuminer-opt.git
cd cpuminer-opt
./autogen.sh
./configure CFLAGS="-O3 -march=armv8.2-a+crypto -mtune=cortex-a76" LIBS="-lcurl"
make -j4
```

Note: `LIBS="-lcurl"` is required on Raspberry Pi OS or the build will fail with undefined curl references.

**KOTO tab (cpuminer-yescrypt — new):**
```bash
sudo apt update && sudo apt install -y git build-essential automake autoconf \
  libcurl4-openssl-dev libssl-dev libjansson-dev libgmp-dev zlib1g-dev

git clone https://github.com/KotoDevelopers/cpuminer-yescrypt.git
cd cpuminer-yescrypt
./autogen.sh
./configure CFLAGS="-O3 -funroll-loops -fomit-frame-pointer"
make -j4
```

#### 3. Update the "Run the miner" section to split YTN/TDC vs KOTO:

**YTN (solo RPC):**
```bash
./cpuminer \
  --algo=yespowerr16 \
  --url=http://153.75.225.100:9982 \
  --user=isekai_ytn \
  --pass=YOUR_RPC_PASS \
  --coinbase-addr=YOUR_YTN_ADDRESS \
  --threads=4
```

**KOTO (pool — recommended):**
```bash
# Use the official KotoDevelopers miner — no -a flag needed
./minerd \
  -o stratum+tcp://koto.isekai-pool.com:3301 \
  -u YOUR_KOTO_ADDRESS.worker_name \
  -p x \
  -t 4
```

**KOTO (solo RPC — advanced):**
```bash
./minerd \
  -o http://153.75.225.100:8432 \
  -u isekai_koto \
  -p YOUR_RPC_PASS \
  --coinbase-addr=YOUR_KOTO_ADDRESS \
  -t 4
```

#### 4. Add prominent link to full KOTO guide:
```
→ Full KOTO RPi5 setup guide at koto.isekai-pool.com/getting-started
```

---

## Page 2: koto.isekai-pool.com/getting-started — Full Rewrite

This is the definitive KOTO RPi5 mining guide. Based on real tested setup from April 2026.
Replace the existing getting-started page content entirely.

---

### Page Title: Mine KOTO on Raspberry Pi 5

**Intro paragraph:**
The Raspberry Pi 5 is one of the best devices for KOTO mining. Low power (5–8W),
silent, runs 24/7, and yescryptR8G is memory-bandwidth limited so the RPi5's
fast LPDDR4X hits above its weight class. This guide takes you from zero to mining
in under 15 minutes.

---

### Section 1: What You Need

| Item | Notes |
|------|-------|
| Raspberry Pi 5 (4GB or 8GB) | 4GB is sufficient |
| MicroSD card 32GB+ or USB SSD | SSD preferred for longevity |
| Power supply (5V 5A USB-C) | Official RPi PSU recommended |
| Ethernet cable | More reliable than WiFi for mining |
| KOTO wallet address | Get one from the KOTO Core Client |

---

### Section 2: Get a KOTO Wallet Address

Download the KOTO Core Client wallet from the official site:
**https://ko-to.org**

Available for Windows, macOS, and Linux. Wait for it to sync (this takes a few hours
the first time — you can continue with miner setup while it syncs).

Once synced, go to **File → Receiving Addresses** and copy your `k1...` address.
This is where your mining rewards will be sent.

---

### Section 3: Install Ubuntu on RPi5

KOTO's official miner (cpuminer-yescrypt) compiles and runs best on Ubuntu.

```bash
# Flash Ubuntu 24.04 LTS (64-bit) for Raspberry Pi using Raspberry Pi Imager
# https://www.raspberrypi.com/software/
# Choose: Other general-purpose OS → Ubuntu → Ubuntu 24.04 LTS (64-bit)
```

Boot, complete setup, connect via SSH or directly.

---

### Section 4: Install Dependencies

```bash
sudo apt update && sudo apt upgrade -y

sudo apt install -y \
  git build-essential automake autoconf \
  libcurl4-openssl-dev libssl-dev \
  libjansson-dev libgmp-dev \
  zlib1g-dev screen
```

---

### Section 5: Set CPU Governor to Performance

By default the RPi5 uses `ondemand` governor which throttles the CPU.
Switch to `performance` for maximum hashrate:

```bash
echo performance | sudo tee /sys/devices/system/cpu/cpu*/cpufreq/scaling_governor
```

Make it permanent:
```bash
sudo apt install -y cpufrequtils
echo 'GOVERNOR="performance"' | sudo tee /etc/default/cpufrequtils
```

Verify it worked:
```bash
cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_governor
# Should output: performance
```

---

### Section 6: Clone and Compile cpuminer-yescrypt

> ⚠️ **Do not use cpuminer-opt for KOTO.** cpuminer-opt's yescryptR8G implementation
> has a difficulty calculation bug that causes all shares to be rejected.
> Use the official KotoDevelopers miner below.

```bash
cd ~
git clone https://github.com/KotoDevelopers/cpuminer-yescrypt.git
cd cpuminer-yescrypt
./autogen.sh
./configure CFLAGS="-O3 -funroll-loops -fomit-frame-pointer"
make -j4
```

Compilation takes 3–5 minutes. When complete verify it built:
```bash
./minerd --version
```

---

### Section 7: Start Mining

Connect to the isekai-pool.com KOTO pool. No registration required —
your KOTO wallet address is your username.

```bash
./minerd \
  -o stratum+tcp://koto.isekai-pool.com:3301 \
  -u YOUR_KOTO_ADDRESS.rpi5 \
  -p x \
  -t 4
```

**Replace `YOUR_KOTO_ADDRESS`** with your `k1...` address from the KOTO wallet.
The `.rpi5` part is your worker name — use anything you like.

**Port guide:**
| Port | Difficulty | Use for |
|------|-----------|---------|
| 3301 | Low (0.01) | RPi5, low hashrate devices |
| 3302 | Medium (0.5) | Desktop CPUs |
| 3303 | High (1.0) | High-end CPUs, multi-core |

**Expected output:**
```
[2026-04-05 12:00:00] Starting Stratum on stratum+tcp://koto.isekai-pool.com:3301
[2026-04-05 12:00:01] 4 miner threads started, using 'yespower' algorithm.
[2026-04-05 12:00:02] Stratum connection established
[2026-04-05 12:00:03] accepted: 1/1 (100.00%), 1.05 khash/s (yay!!!)
```

`accepted` shares with 100% means everything is working correctly.

---

### Section 8: Run 24/7 with systemd

Create a startup script:
```bash
sudo nano /etc/cpuminer/config-koto.json
```

```json
{
  "url": "stratum+tcp://koto.isekai-pool.com:3301",
  "user": "YOUR_KOTO_ADDRESS.rpi5",
  "pass": "x",
  "threads": 4
}
```

Create the systemd service:
```bash
sudo nano /etc/systemd/system/cpuminer-koto.service
```

```ini
[Unit]
Description=CPUMiner KOTO
After=network.target

[Service]
ExecStart=/home/rpi/cpuminer-yescrypt/minerd --config /etc/cpuminer/config-koto.json
Restart=always
RestartSec=10
User=rpi

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable cpuminer-koto.service
sudo systemctl start cpuminer-koto.service
```

Check it's running:
```bash
journalctl -u cpuminer-koto.service -f
```

---

### Section 9: Expected Performance

| Hardware | Threads | Hashrate | Pool Share |
|----------|---------|----------|------------|
| Raspberry Pi 5 (4GB) | 4 | ~1,000–1,100 H/s | ~5% of network |
| Raspberry Pi 4 | 4 | ~400–600 H/s | ~2% of network |
| Intel N100 mini PC | 4 | ~2,000–3,000 H/s | ~10–15% |
| Intel i5-8500T | 6 | ~6,000–8,000 H/s | ~30–40% |
| Intel i9-14900F | 16 | ~12,000–15,000 H/s | ~60–75% |

Network total hashrate is ~19 kH/s — KOTO is a small network where even modest
hardware makes a meaningful contribution.

---

### Section 10: Troubleshooting

| Problem | Fix |
|---------|-----|
| `make` fails with curl errors | Run `./configure LIBS="-lcurl"` instead |
| All shares rejected | You're using cpuminer-opt — use cpuminer-yescrypt instead |
| `unknown algorithm` error | Don't use `-a` flag — minerd defaults to correct algo |
| Low hashrate (~100 H/s) | CPU governor is `ondemand` — switch to `performance` |
| Connection refused | Check pool status at koto.isekai-pool.com |
| Wallet not syncing | KOTO chain is ~4.3M blocks, sync takes 2–4 hours |

---

### Section 11: Monitor Your Mining

- **Pool dashboard**: https://koto.isekai-pool.com/stats
- **Your worker stats**: https://koto.isekai-pool.com/workers/YOUR_KOTO_ADDRESS
- **Block explorer**: https://insight.kotocoin.info
- **Network stats**: https://miningpoolstats.stream/koto

---

### Section 12: Windows Setup (Alienware / Desktop)

For Windows machines download the official KotoMiner:
**https://github.com/KotoDevelopers/cpuminer-yescrypt/releases**

Extract `KotoMiner_Win_x64.zip`. Edit `start.bat`:

```bat
minerd-avx2.exe -o stratum+tcp://koto.isekai-pool.com:3302 -u YOUR_KOTO_ADDRESS.desktop -p x -t 16
```

Use `minerd-avx2.exe` for Intel 8th gen+ and AMD Ryzen.
Use port 3302 for desktop CPUs.
Double-click `start.bat` to start mining.

---

## Notes for Cursor

### isekai-pool.com/guide.html
- File location: `web/guide.html`
- Add LIBS="-lcurl" to the ARM64 compile command
- Add KOTO warning with link to koto.isekai-pool.com/getting-started
- Split miner section into YTN/TDC (cpuminer-opt) vs KOTO (cpuminer-yescrypt)
- Keep existing page structure and styling — content updates only

### koto.isekai-pool.com getting-started page
- File location: `/home/crypto/zny-nomp/website/pages/getting_started.html`
- Full content replacement with the guide above
- Match existing dark theme styling from index.html
- All code blocks need copy buttons (existing JS handles this)
- The troubleshooting table is critical — every item is a real gotcha we hit during setup
- The Windows section is important — minerd-avx2.exe + no -a flag is the key insight
