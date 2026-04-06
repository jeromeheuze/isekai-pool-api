# Koto mining pool — implementation specs (isekai)

**Target:** Stratum pool for **Koto (KOTO)** using **yescryptR8G**, co-located with existing infra.  
**Pool hostname (planned):** `koto.isekai-pool.com`  
**VPS:** same host as [isekai-pool.com](https://isekai-pool.com) (see deploy / README).  
**Reference stack:** [zny-nomp](https://github.com/ROZ-MOFUMOFU-ME/zny-nomp) (MOFUMOFU fork lineage; algorithm `yescryptR8G`).

> **Do not commit** real `rpcpassword`, zny-nomp `adminCenter` passwords, or payout addresses. Use placeholders below and inject secrets only on the server.

---

## 1. Goals

| Goal | Detail |
|------|--------|
| Stratum | Miners connect with `YOUR_KOTO_ADDRESS.workername` — no pool accounts. |
| Coexist | Pool sits beside existing `kotod` + Laravel API; RPC stays **127.0.0.1** only. |
| Web UI | Pool portal behind nginx + TLS; bind portal to localhost, proxy from nginx. |
| Payouts | PROP (or as configured); respect Koto transparent + shielded address requirements for zny-nomp. |

## 2. Non-goals (for v1)

- Public JSON-RPC for Koto from the internet (unlike YTN-style public RPC if you expose it elsewhere).
- Replacing [koto.mofumofu.me](https://koto.mofumofu.me/) — treat as **reference** for ports/algo UX.

## 3. Architecture

```
Miners (stratum) ──TCP 3301–3304──► VPS : zny-nomp
                                        │
                                        ├──► Redis (127.0.0.1)
                                        ├──► kotod RPC :8432 (127.0.0.1 only)
                                        └──► kotod P2P :8433 (p2p listener for pool)

Browsers ──HTTPS──► nginx :443 ──proxy──► zny-nomp website :8080 (127.0.0.1 only)
```

## 4. Prerequisites

- `kotod` fully synced; block height matches a public explorer (e.g. [explorer.isekai-pool.com](https://explorer.isekai-pool.com)).
- Node.js **≥ 16.11** (verify zny-nomp README if stricter).
- Redis running (`redis-cli ping` → `PONG`).
- OS user (e.g. `crypto`) owning `/home/crypto/.koto` and pool install dir.

## 5. Ports

| Port | Service | Public? |
|------|---------|---------|
| 3301 | Stratum (low diff) | Yes (ufw) |
| 3302 | Stratum (mid) | Yes |
| 3303 | Stratum (high) | Yes |
| 3304 | Stratum (cloud / high diff) | Yes (optional; match mofumofu-style tiers) |
| 8080 | zny-nomp website | **No** — localhost + nginx only |
| 8432 | Koto RPC | **No** — 127.0.0.1 |
| 8433 | Koto P2P | Pool `p2p` to local node |

## 6. Coin definition (`coins/koto.json`)

- `algorithm`: **`yescryptR8G`**
- `peerMagic`: must match **mainnet** `pchMessageStart` from Koto `chainparams.cpp` (wrong magic breaks P2P / block notify).

```json
{
  "name": "Koto",
  "symbol": "KOTO",
  "algorithm": "yescryptR8G",
  "coinbase": "isekai-pool",
  "peerMagic": "VERIFY_FROM_KOTO_SOURCE",
  "txMessages": false
}
```

## 7. Pool config (`pool_configs/koto.json`) — shape only

Replace all caps placeholders on the server.

- **`address`**: pool fee / reward recipient transparent address (as required by zny-nomp).
- **`zAddress`** / **`tAddress`**: from `koto-cli` / `z_getnewaddress` — **do not reuse** example addresses from drafts.
- **`daemons`[] / `paymentProcessing.daemon`**: `127.0.0.1:8432`, rpcuser + **env-sourced password** (same as `koto.conf`).

```json
{
  "enabled": true,
  "coin": "koto.json",
  "address": "KOTO_T_ADDRESS_POOL_FEE",
  "zAddress": "KOTO_Z_ADDRESS",
  "tAddress": "KOTO_T_ADDRESS_PAYOUT",
  "daemons": [
    {
      "host": "127.0.0.1",
      "port": 8432,
      "user": "isekai_koto",
      "password": "KOTO_RPC_PASSWORD"
    }
  ],
  "ports": {
    "3301": { "diff": 0.01, "tls": false, "varDiff": { "minDiff": 0.001, "maxDiff": 1, "targetTime": 15, "retargetTime": 60, "variancePercent": 30 } },
    "3302": { "diff": 0.5, "tls": false, "varDiff": { "minDiff": 0.01, "maxDiff": 16, "targetTime": 15, "retargetTime": 60, "variancePercent": 30 } },
    "3303": { "diff": 1, "tls": false, "varDiff": { "minDiff": 0.1, "maxDiff": 32, "targetTime": 15, "retargetTime": 60, "variancePercent": 30 } },
    "3304": { "diff": 5, "tls": false, "varDiff": { "minDiff": 0.5, "maxDiff": 128, "targetTime": 15, "retargetTime": 60, "variancePercent": 30 } }
  },
  "p2p": { "enabled": true, "host": "127.0.0.1", "port": 8433, "disableTransactions": true }
}
```

Tune `paymentProcessing`, `rewardRecipients`, and `minimumPayment` to your policy; align with funded t-address for fees.

## 8. Global config (`config.json`)

- `website.stratumHost`: **`koto.isekai-pool.com`**
- `website.host` / `port`: **`127.0.0.1`** + **`8080`** (nginx terminates TLS).
- `website.adminCenter.password`: strong random; **never** commit.
- `redis`: `127.0.0.1:6379` unless you isolate Redis.

## 9. `koto.conf` — block notify

```ini
blocknotify=node /home/crypto/zny-nomp/scripts/cli.js blocknotify koto %s
```

Adjust path if install dir differs. Restart `kotod` after change.

Required baseline:

```ini
server=1
daemon=1
rpcallowip=127.0.0.1
rpcbind=127.0.0.1
```

## 10. Process management

- **systemd** unit for zny-nomp: `WorkingDirectory=/home/crypto/zny-nomp`, `ExecStart` = `node init.js` (use full path to node if needed), user `crypto`, `After=redis-server.service`.

## 11. Nginx + TLS

- Server name `koto.isekai-pool.com` → `proxy_pass http://127.0.0.1:8080`.
- Obtain cert with **certbot** (`--nginx`).
- Do **not** expose `:8080` on the firewall.

## 12. Miner-facing summary (for `koto.html` / docs)

| Field | Value |
|-------|--------|
| Algorithm | **yescryptR8G** (some builds show `--algo=yescryptr8g` — check `cpuminer --help`) |
| URL | `stratum+tcp://koto.isekai-pool.com:PORT` |
| Username | `KOTO_ADDRESS.workername` |
| Password | conventionally `x` |

## 13. isekai-pool.com integration

When the pool is live:

- Add **Koto pool** link on [web/koto.html](https://isekai-pool.com/koto.html) (alongside or instead of third-party pool only — your choice).
- Optional: short banner on [web/index.html](../web/index.html) or [web/coins.html](../web/coins.html).

## 14. Verification checklist

- [ ] Explorer height == `getblockcount`
- [ ] Redis up; zny-nomp starts without RPC errors
- [ ] Stratum ports reachable from outside; share submission works
- [ ] Website loads via HTTPS; not via raw `:8080`
- [ ] Block notify fires on new blocks (watch pool logs)
- [ ] Payout path tested on test amount before high balance

## 15. Related files in this repo

| Path | Purpose |
|------|---------|
| [zny-nomp/README.md](../zny-nomp/README.md) | Bootstrap: clone upstream, copy `*.json.example` → live `*.json` on VPS |
| [zny-nomp/coins/koto.json.example](../zny-nomp/coins/koto.json.example) | Coin definition template |
| [zny-nomp/pool_configs/koto.json.example](../zny-nomp/pool_configs/koto.json.example) | Pool + stratum ports template |
| [zny-nomp/CONFIG_MERGE.md](../zny-nomp/CONFIG_MERGE.md) | Keys to merge into zny-nomp `config.json` |
| [zny-nomp/systemd/zny-nomp.service.example](../zny-nomp/systemd/zny-nomp.service.example) | systemd unit |
| [zny-nomp/nginx-koto-pool.example.conf](../zny-nomp/nginx-koto-pool.example.conf) | nginx reverse proxy for pool UI |
| [_docs/koto-pool-spec.md](./koto-pool-spec.md) | Hands-on runbook |
| [_docs/koto-faucet-spec.md](./koto-faucet-spec.md) | Separate faucet idea |
| [scripts/build-koto.sh](../scripts/build-koto.sh) | `kotod` build |

---

*Version: 0.1 — starter specs; extend with runbook and monitoring as you operationalize.*
