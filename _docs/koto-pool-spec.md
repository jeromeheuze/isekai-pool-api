# KOTO Pool — zny-nomp Setup Spec
**koto.isekai-pool.com · VPS 153.75.225.100**

---

## Overview

Set up a KOTO (yescryptR8G) mining pool using zny-nomp on the existing VPS.
The pool runs as a separate service alongside the existing Yenten/TDC/KOTO RPC nodes.
Frontend served at `koto.isekai-pool.com` via nginx reverse proxy.

---

## Prerequisites (verify before starting)

```bash
# KOTO node must be fully synced
koto-cli -datadir=/home/crypto/.koto getblockcount
# Compare to https://explorer.koto.cash — must match

# Check Node.js version
node --version  # needs v16.11+

# Check Redis
redis-cli ping  # should return PONG
```

---

## Step 1 — Install Dependencies

```bash
# Node.js (stable via n)
sudo apt install -y nodejs npm
sudo npm install -g n
sudo n stable
sudo apt purge nodejs npm -y
hash -r

# Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Build tools
sudo apt install -y build-essential libsodium-dev libboost-all-dev libgmp3-dev node-gyp libssl-dev
```

---

## Step 2 — Clone zny-nomp

```bash
cd /home/crypto
git clone https://github.com/ROZ-MOFUMOFU-ME/zny-nomp
cd zny-nomp
npm install
```

---

## Step 3 — Generate KOTO Wallet Addresses

KOTO uses Zcash-style shielded transactions for payouts. You need two addresses:

```bash
# As crypto user
# Transparent address (tAddress) — receives block rewards
koto-cli -datadir=/home/crypto/.koto getnewaddress "pool-payout"

# Shielded address (zAddress) — intermediate for shielded payouts
koto-cli -datadir=/home/crypto/.koto z_getnewaddress
```

Save both addresses — they go into `pool_configs/koto.json`.

Fund the tAddress with a small amount of KOTO to cover initial payout fees.

---

## Step 4 — Coin Config

Create `coins/koto.json`:

```json
{
    "name": "Koto",
    "symbol": "KOTO",
    "algorithm": "yescryptR8G",
    "coinbase": "isekai-pool",
    "peerMagic": "5a6f6f54",
    "txMessages": false
}
```

> Note: Verify `peerMagic` value from KOTO source:
> https://github.com/KotoProject/Koto/blob/master/src/chainparams.cpp
> Look for `pchMessageStart` in mainnet params.

---

## Step 5 — Pool Config

Create `pool_configs/koto.json`:

```json
{
    "enabled": true,
    "coin": "koto.json",
    "blockIdentifier": "isekai-pool.com",

    "address": "KOTO_T_ADDRESS",
    "BTCover17": false,

    "zAddress": "KOTO_Z_ADDRESS",
    "tAddress": "KOTO_T_ADDRESS_2",
    "walletInterval": 2.5,

    "rewardRecipients": {
        "KOTO_T_ADDRESS": 1.0
    },

    "paymentProcessing": {
        "minConf": 10,
        "enabled": true,
        "paymentMode": "prop",
        "paymentInterval": 120,
        "minimumPayment": 0.5,
        "maxBlocksPerPayment": 3,
        "daemon": {
            "host": "127.0.0.1",
            "port": 8432,
            "user": "isekai_koto",
            "password": "KOTO_RPC_PASSWORD"
        }
    },

    "tlsOptions": {
        "enabled": false,
        "serverKey": "",
        "serverCert": "",
        "ca": ""
    },

    "ports": {
        "3301": {
            "diff": 0.01,
            "tls": false,
            "varDiff": {
                "minDiff": 0.001,
                "maxDiff": 1,
                "targetTime": 15,
                "retargetTime": 60,
                "variancePercent": 30
            }
        },
        "3302": {
            "diff": 0.5,
            "tls": false,
            "varDiff": {
                "minDiff": 0.01,
                "maxDiff": 16,
                "targetTime": 15,
                "retargetTime": 60,
                "variancePercent": 30
            }
        },
        "3303": {
            "diff": 1,
            "tls": false,
            "varDiff": {
                "minDiff": 0.1,
                "maxDiff": 32,
                "targetTime": 15,
                "retargetTime": 60,
                "variancePercent": 30
            }
        }
    },

    "poolId": "main",

    "daemons": [
        {
            "host": "127.0.0.1",
            "port": 8432,
            "user": "isekai_koto",
            "password": "KOTO_RPC_PASSWORD"
        }
    ],

    "p2p": {
        "enabled": true,
        "host": "127.0.0.1",
        "port": 8433,
        "disableTransactions": true
    },

    "mposMode": {
        "enabled": false
    }
}
```

> Fill in:
> - `KOTO_T_ADDRESS` — transparent address from Step 3
> - `KOTO_Z_ADDRESS` — shielded address from Step 3
> - `KOTO_RPC_PASSWORD` — from `/home/crypto/.koto/koto.conf`
> - `port 8432` — verify KOTO RPC port from `koto.conf`

---

## Step 6 — Portal Config

Create `config.json` from `config_example.json`:

```bash
cp config_example.json config.json
nano config.json
```

Key fields to update:

```json
{
    "logLevel": "warning",
    "logColors": false,

    "clustering": {
        "enabled": true,
        "forks": 2
    },

    "defaultPoolConfigs": {
        "blockRefreshInterval": 1000,
        "jobRebroadcastTimeout": 55,
        "connectionTimeout": 600,
        "validateWorkerUsername": true,
        "redis": {
            "host": "127.0.0.1",
            "port": 6379
        }
    },

    "website": {
        "enabled": true,
        "host": "127.0.0.1",
        "port": 8080,
        "stratumHost": "koto.isekai-pool.com",
        "stats": {
            "updateInterval": 15,
            "historicalRetention": 43200,
            "hashrateWindow": 300
        },
        "adminCenter": {
            "enabled": true,
            "password": "CHANGE_THIS_ADMIN_PASSWORD"
        }
    },

    "redis": {
        "host": "127.0.0.1",
        "port": 6379
    }
}
```

> Note: `host: "127.0.0.1"` on website — nginx proxies to port 8080, not exposed directly.

---

## Step 7 — Block Notify

Add to `/home/crypto/.koto/koto.conf`:

```ini
blocknotify=node /home/crypto/zny-nomp/scripts/cli.js blocknotify koto %s
```

Restart KOTO node:
```bash
koto-cli -datadir=/home/crypto/.koto stop
sleep 5
kotod -datadir=/home/crypto/.koto -conf=/home/crypto/.koto/koto.conf -daemon
```

---

## Step 8 — Firewall

```bash
# Stratum ports
ufw allow 3301/tcp comment "KOTO pool low diff"
ufw allow 3302/tcp comment "KOTO pool mid diff"
ufw allow 3303/tcp comment "KOTO pool high diff"
ufw reload
```

Do NOT expose port 8080 publicly — nginx handles that.

---

## Step 9 — Systemd Service

Create `/etc/systemd/system/zny-nomp.service`:

```ini
[Unit]
Description=zny-nomp KOTO Mining Pool
After=network.target redis-server.service

[Service]
Type=simple
User=crypto
WorkingDirectory=/home/crypto/zny-nomp
ExecStart=/usr/local/bin/node init.js
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable zny-nomp
sudo systemctl start zny-nomp
sudo journalctl -u zny-nomp -f
```

---

## Step 10 — Nginx + Subdomain

### DNS
Add A record in your DNS provider:
```
koto.isekai-pool.com  →  153.75.225.100
```

### Nginx config

Create `/etc/nginx/sites-available/koto.isekai-pool.com`:

```nginx
server {
    listen 80;
    server_name koto.isekai-pool.com;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_cache_bypass $http_upgrade;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/koto.isekai-pool.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### SSL (Let's Encrypt)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d koto.isekai-pool.com
```

Auto-renewal is handled by certbot's systemd timer.

---

## Step 11 — Test Mining

Once pool is running, test with the RPi5:

```bash
# Stop YTN miner temporarily
sudo systemctl stop cpuminer.service

# Test KOTO pool connection
/home/rpi/cpuminer-opt/cpuminer \
  --algo=yescryptr8g \
  --url=stratum+tcp://koto.isekai-pool.com:3301 \
  --user=YOUR_KOTO_ADDRESS.rpi5 \
  --pass=x \
  --threads=4
```

Miners use their own KOTO address as username — no pool account needed.

---

## Miner Connection Info (for koto.isekai-pool.com page)

| Port | Difficulty | Use case |
|------|-----------|----------|
| 3301 | 0.01 | CPU / RPi / low hashrate |
| 3302 | 0.5 | Mid-range CPU |
| 3303 | 1.0 | Multi-core / high hashrate |

**Stratum URL**: `stratum+tcp://koto.isekai-pool.com:PORT`
**Username**: `YOUR_KOTO_ADDRESS.WORKER_NAME`
**Password**: `x` (anything)
**Algorithm**: `yescryptR8G` / `--algo=yescryptr8g`

---

## KOTO conf requirements

The existing `/home/crypto/.koto/koto.conf` needs these lines confirmed/added:

```ini
server=1
daemon=1
rpcallowip=127.0.0.1
rpcbind=127.0.0.1
```

Note: KOTO RPC only needs localhost access — zny-nomp runs on the same machine.
Do NOT open KOTO RPC publicly like YTN — pool handles the stratum interface.

---

## Verify checklist

- [ ] KOTO node 100% synced
- [ ] Redis running (`redis-cli ping`)
- [ ] Node.js v16.11+ (`node --version`)
- [ ] `coins/koto.json` created
- [ ] `pool_configs/koto.json` created with real addresses/passwords
- [ ] `config.json` updated with `stratumHost: koto.isekai-pool.com`
- [ ] blocknotify added to `koto.conf`
- [ ] Firewall ports 3301-3303 open
- [ ] zny-nomp systemd service running
- [ ] DNS A record pointing to VPS
- [ ] Nginx reverse proxy configured
- [ ] SSL cert issued
- [ ] Test miner connects and submits shares

---

## Notes for Cursor / implementation

- KOTO RPC port — verify from `koto.conf` before filling into pool config
- The `peerMagic` value in `coins/koto.json` must be verified from KOTO source — wrong value breaks P2P block notify
- `tAddress` and `zAddress` must be different addresses
- Fund `tAddress` with ~10 KOTO before enabling payouts
- zny-nomp website runs on port 8080 — nginx proxies it, never expose 8080 directly
- Once running, add `koto.isekai-pool.com` link to the main `isekai-pool.com` nav and the KOTO coin page
