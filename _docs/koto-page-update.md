# Koto coin page update (isekai-pool.com)

Target file: **`web/koto.html`**.

Promote **koto.isekai-pool.com** as the primary pool; list **koto.mofumofu.me** only as an alternative. Use **KotoDevelopers `minerd`** (cpuminer-yescrypt), not cpuminer-opt, for KOTO.

---

## Pooled mining (copy for page body)

Mine KOTO at our public pool — no registration, 1% fee, direct payouts.

- `stratum+tcp://koto.isekai-pool.com:3301` — low diff — RPi, mobile  
- `stratum+tcp://koto.isekai-pool.com:3302` — mid diff — desktop  
- `stratum+tcp://koto.isekai-pool.com:3303` — high diff — multi-core  

⚠️ Use the official KotoDevelopers miner — **cpuminer-opt does not work for KOTO.**

**Download:** https://github.com/KotoDevelopers/cpuminer-yescrypt/releases  

**Windows:**

```text
minerd-avx2.exe -o stratum+tcp://koto.isekai-pool.com:3302 -u YOUR_KOTO_ADDRESS.worker1 -p x
```

**Linux / RPi5:**

```text
./minerd -o stratum+tcp://koto.isekai-pool.com:3301 -u YOUR_KOTO_ADDRESS.rpi5 -p x -t 4
```

→ Full RPi5 setup guide: **https://koto.isekai-pool.com/getting-started**  
→ Pool dashboard: **https://koto.isekai-pool.com**  

**Alternative pool:** [koto.mofumofu.me](https://koto.mofumofu.me) (Japanese)

---

## Coin info table

| Field | Value |
|-------|--------|
| Block time | ~60 seconds |
| Mining pool | koto.isekai-pool.com |

---

## Checklist

- [ ] Meta descriptions do not prioritize mofumofu over isekai  
- [ ] Solo RPC example uses `minerd`, not `cpuminer --algo=...`  
- [ ] Links section leads with koto.isekai-pool.com  
- [ ] FAQ “pool vs solo” recommends isekai pool first  
