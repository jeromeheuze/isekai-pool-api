import { spawn } from 'child_process';
import { EventEmitter } from 'events';
import fs from 'fs';
import path from 'path';
import { platformArchDir, getBundledResourcesDir } from './miner-paths.js';
import { resolveMinerExecutablePath } from './miner-resolve.js';
import { restoreMinerFromManifest, minerBinaryMissingMessage } from './miner-restore.js';

export class MinerProcess extends EventEmitter {
  constructor() {
    super();
    this.process = null;
    this.stats = {
      hashrate: 0,
      hashrate_unit: 'H/s',
      shares: { accepted: 0, rejected: 0 },
      temperature: null,
      uptime: 0,
    };
    this._startTime = null;
  }

  getExecutablePath() {
    return resolveMinerExecutablePath();
  }

  bundledPath() {
    return getBundledResourcesDir();
  }

  isBinaryPresent() {
    const p = this.getExecutablePath();
    return !!p && fs.existsSync(p);
  }

  /**
   * @param {{ pool_url: string, wallet_address: string, threads: number, solo?: boolean }} config
   */
  /**
   * @returns {{ ok: true } | { ok: false, error: string }}
   */
  start(config) {
    if (this.process) {
      const err = 'Miner already running';
      this.emit('error', new Error(err));
      return { ok: false, error: err };
    }

    const exe = this.getExecutablePath();
    if (!exe || !fs.existsSync(exe)) {
      const msg = minerBinaryMissingMessage();
      this.emit('error', new Error(msg));
      return { ok: false, error: msg };
    }

    const args = [
      '--algo=yescryptr8g',
      `--url=${config.pool_url}`,
      `--user=${config.wallet_address}`,
      '--pass=x',
      `--threads=${config.threads}`,
      '--cpu-priority=2',
      '--no-color',
    ];

    if (config.solo) {
      args.push(`--coinbase-addr=${config.wallet_address}`);
    }

    this._startTime = Date.now();
    const cwd = path.dirname(exe);
    this.process = spawn(exe, args, {
      cwd,
      windowsHide: true,
      stdio: ['ignore', 'pipe', 'pipe'],
    });

    this.process.stdout.on('data', (data) => this.parseOutput(data.toString()));
    this.process.stderr.on('data', (data) => this.parseOutput(data.toString()));

    this.process.on('error', (err) => {
      this.emit('error', err);
      this.process = null;
    });

    this.process.on('close', (code) => {
      this.emit('close', code);
      this.process = null;
    });

    this.emit('started');
    return { ok: true };
  }

  parseOutput(chunk) {
    const lines = chunk.split(/\r?\n/);
    for (const line of lines) {
      if (!line.trim()) continue;

      const totalHash = line.match(/Total:\s+([\d,.]+)\s+([KMG]?)[Hh]\/s/i);
      if (totalHash) {
        let v = parseFloat(totalHash[1].replace(/,/g, ''));
        const prefix = (totalHash[2] || '').toUpperCase();
        let unit = 'H/s';
        if (prefix === 'K') {
          v *= 1e3;
          unit = 'H/s';
        } else if (prefix === 'M') {
          v *= 1e6;
          unit = 'H/s';
        } else if (prefix === 'G') {
          v *= 1e9;
          unit = 'H/s';
        }
        this.stats.hashrate = v;
        this.stats.hashrate_unit = unit;
      }

      const share = line.match(/accepted:\s*(\d+)\s*\/\s*(\d+)/i);
      if (share) {
        this.stats.shares.accepted = parseInt(share[1], 10);
        this.stats.shares.rejected = parseInt(share[2], 10);
      }

      if (this._startTime) {
        this.stats.uptime = Math.floor((Date.now() - this._startTime) / 1000);
      }

      this.emit('stats', { ...this.stats });
      this.emit('log', line.trim());
    }
  }

  stop() {
    if (this.process) {
      this.process.kill('SIGTERM');
      this.process = null;
    }
    this._startTime = null;
    this.emit('stopped');
  }
}

export async function tryRestoreMiner() {
  const key = platformArchDir();
  return restoreMinerFromManifest(key);
}
