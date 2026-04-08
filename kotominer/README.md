# Kotominer

Electron + Vue 3 GUI for mining **KOTO** (cpuminer-koto). Spec: [`../_docs/kotominer-electron-spec.md`](../_docs/kotominer-electron-spec.md).

## Prerequisites

- Node.js 20+
- A **cpuminer-koto** binary per platform under `resources/` (see `resources/README.md`). Official builds: **[KotoDevelopers/cpuminer-yescrypt releases](https://github.com/KotoDevelopers/cpuminer-yescrypt/releases)** — extract and rename to `cpuminer-koto.exe` (Windows) as documented there.

## Develop

```bash
cd kotominer
npm install
npm run dev
```

Vite serves the renderer on port **5173**; Electron loads it via `VITE_DEV_SERVER_URL`.

## Build

```bash
npm run build
```

Artifacts go to `release/` (NSIS / AppImage / deb per `electron-builder.yml`).

## Security model

- **Context isolation** + **preload** IPC only (`src/preload/preload.cjs`).
- Miner is **spawned only from the main process** (`src/main/miner.js`).
- External links use **`shell.openExternal`**.

## Restore after antivirus

If the bundled miner is quarantined, you can:

1. Copy a verified `cpuminer-koto` into `%APPDATA%/Kotominer/bin/` (Windows) or the app’s userData `bin/` on Linux.
2. Set **`KOTOMINER_MINER_MANIFEST_URL`** to a JSON manifest with `sha256`-verified download URLs (see `src/main/miner-restore.js`).
