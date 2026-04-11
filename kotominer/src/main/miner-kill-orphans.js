import { execFile } from 'child_process';
import { promisify } from 'util';

const execFileAsync = promisify(execFile);

/**
 * Best-effort: terminate stray cpuminer/minerd processes not tracked by Kotominer
 * (e.g. after a crash or an external launch). Windows + Linux only.
 */
export async function killOrphanMinerProcesses() {
  try {
    if (process.platform === 'win32') {
      await execFileAsync(
        'powershell.exe',
        [
          '-NoProfile',
          '-ExecutionPolicy',
          'Bypass',
          '-Command',
          "Get-Process -ErrorAction SilentlyContinue | Where-Object { $_.ProcessName -match '^minerd' -or $_.ProcessName -match '^cpuminer-koto' } | ForEach-Object { Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue }",
        ],
        { windowsHide: true, timeout: 20_000 }
      );
      return;
    }
    if (process.platform === 'linux' || process.platform === 'darwin') {
      await execFileAsync('pkill', ['-9', '-f', 'minerd'], { timeout: 5000 }).catch(() => {});
      await execFileAsync('pkill', ['-9', '-x', 'cpuminer-koto'], { timeout: 5000 }).catch(() => {});
    }
  } catch {
    /* ignore */
  }
}
