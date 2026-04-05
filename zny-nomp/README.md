# zny-nomp — Koto pool templates (isekai)

Tracked files here are **examples only**. Live secrets stay on the VPS (`config.json`, `pool_configs/koto.json`, RPC passwords).

**Spec:** [_docs/koto-pool-specs.md](../_docs/koto-pool-specs.md) · **Runbook:** [_docs/koto-pool-spec.md](../_docs/koto-pool-spec.md)

## Bootstrap on the server

1. Clone the pool software (upstream ships `config_example.json` and coin samples):

   ```bash
   cd /home/crypto
   git clone https://github.com/ROZ-MOFUMOFU-ME/zny-nomp
   cd zny-nomp
   npm install
   cp config_example.json config.json
   ```

2. Merge **isekai** settings from [CONFIG_MERGE.md](./CONFIG_MERGE.md) into `config.json` (website host, stratumHost, redis, admin password).

3. Copy coin + pool definitions from this repo into the clone:

   ```bash
   cp /var/www/isekai-pool-api/zny-nomp/coins/koto.json.example ./coins/koto.json
   cp /var/www/isekai-pool-api/zny-nomp/pool_configs/koto.json.example ./pool_configs/koto.json
   ```

4. Edit `coins/koto.json`: confirm **`peerMagic`** against [Koto `chainparams.cpp`](https://github.com/KotoProject/Koto/blob/master/src/chainparams.cpp) (`pchMessageStart` mainnet).

5. Edit `pool_configs/koto.json`: set real **t/z addresses**, **RPC password**, **paymentProcessing** / **rewardRecipients** to taste.

6. Add **blocknotify** to `koto.conf` (path to `scripts/cli.js` in this `zny-nomp` tree). Restart `kotod`.

7. Open **UFW** stratum ports **3301–3304** (or your chosen set). Do **not** expose zny-nomp website port **8080** publicly.

8. Install [systemd/zny-nomp.service.example](./systemd/zny-nomp.service.example) and [nginx-koto-pool.example.conf](./nginx-koto-pool.example.conf); issue TLS for `koto.isekai-pool.com`.

## Files in this directory

| File | Purpose |
|------|---------|
| `coins/koto.json.example` | Coin definition → copy to `coins/koto.json` in the clone |
| `pool_configs/koto.json.example` | Pool + stratum ports → copy to `pool_configs/koto.json` |
| `CONFIG_MERGE.md` | JSON keys to merge into upstream `config.json` |
| `systemd/zny-nomp.service.example` | systemd unit |
| `nginx-koto-pool.example.conf` | reverse proxy to localhost:8080 |
