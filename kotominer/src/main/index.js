import { app, BrowserWindow, ipcMain, shell } from 'electron';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import Store from 'electron-store';
import { MinerProcess, tryRestoreMiner } from './miner.js';
import { getCPUInfo, getCPUTemp } from './hardware.js';
import { getMinerSelectionMeta, clearMinerSelectionCache } from './miner-resolve.js';
import { initUpdater } from './updater.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
/** Repo root (kotominer/) in dev; inside asar the packaged app may not include build/ — window icon still set by installer on Windows. */
const projectRoot = path.join(__dirname, '../..');

function resolveWindowIcon() {
  const ico = path.join(projectRoot, 'build', 'icon.ico');
  const png = path.join(projectRoot, 'build', 'icon.png');
  if (process.platform === 'win32' && fs.existsSync(ico)) return ico;
  if (fs.existsSync(png)) return png;
  if (fs.existsSync(ico)) return ico;
  return undefined;
}

const store = new Store({
  defaults: {
    wallet_address: '',
    pool_url: 'stratum+tcp://koto.isekai-pool.com:3301',
    threads: null,
    cpu_priority: 2,
    auto_start: false,
    minimize_to_tray: true,
    temp_warning: 80,
    theme: 'dark',
    language: 'en',
  },
});

let mainWindow = null;
const miner = new MinerProcess();

function createWindow() {
  const preloadPath = path.join(__dirname, '../preload/preload.cjs');

  const iconPath = resolveWindowIcon();
  mainWindow = new BrowserWindow({
    ...(iconPath ? { icon: iconPath } : {}),
    width: 1080,
    height: 720,
    minWidth: 880,
    minHeight: 600,
    backgroundColor: '#0d0f14',
    webPreferences: {
      preload: preloadPath,
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: false,
    },
    title: 'Kotominer',
    show: false,
  });

  mainWindow.once('ready-to-show', () => mainWindow?.show());

  if (process.env.VITE_DEV_SERVER_URL) {
    mainWindow.loadURL(process.env.VITE_DEV_SERVER_URL);
    mainWindow.webContents.openDevTools({ mode: 'detach' });
  } else {
    mainWindow.loadFile(path.join(__dirname, '../../dist/index.html'));
  }

  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: 'deny' };
  });

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

function wireMinerEvents() {
  miner.on('stats', (s) => {
    mainWindow?.webContents.send('miner:stats', s);
  });
  miner.on('log', (line) => {
    mainWindow?.webContents.send('miner:log', line);
  });
  miner.on('error', (err) => {
    mainWindow?.webContents.send('miner:error', err.message || String(err));
  });
  miner.on('close', (code) => {
    mainWindow?.webContents.send('miner:close', code);
  });
  miner.on('started', () => {
    mainWindow?.webContents.send('miner:started');
  });
  miner.on('stopped', () => {
    mainWindow?.webContents.send('miner:stopped');
  });
}

function registerIpc() {
  ipcMain.handle('settings:get', () => store.store);

  ipcMain.handle('settings:set', (_e, partial) => {
    for (const [k, v] of Object.entries(partial || {})) {
      store.set(k, v);
    }
    return store.store;
  });

  ipcMain.handle('hardware:cpu', () => getCPUInfo());

  ipcMain.handle('hardware:temp', () => getCPUTemp());

  ipcMain.handle('miner:paths', () => getMinerSelectionMeta());

  ipcMain.handle('miner:clearSelectionCache', () => {
    clearMinerSelectionCache();
    return { ok: true };
  });

  ipcMain.handle('miner:restore', async () => tryRestoreMiner());

  ipcMain.handle('miner:start', (_e, cfg) => {
    return miner.start(cfg);
  });

  ipcMain.handle('miner:stop', () => {
    miner.stop();
    return { ok: true };
  });

  ipcMain.handle('shell:openExternal', (_e, url) => {
    shell.openExternal(url);
  });
}

app.whenReady().then(() => {
  wireMinerEvents();
  registerIpc();
  initUpdater(app);

  const cpu = getCPUInfo();
  if (store.get('threads') == null) {
    store.set('threads', cpu.recommended_threads);
  }

  createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', () => {
  miner.stop();
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('before-quit', () => {
  miner.stop();
});
