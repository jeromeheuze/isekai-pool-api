/**
 * electron-updater — GitHub releases (see electron-builder.yml publish).
 * @param {import('electron').App} app
 * @param {import('electron').BrowserWindow | null} mainWindow
 */
export function initUpdater(app, mainWindow) {
  if (!app.isPackaged) return;

  import('electron-updater')
    .then(({ autoUpdater }) => {
      autoUpdater.autoDownload = true;
      autoUpdater.autoInstallOnAppQuit = true;

      autoUpdater.on('update-available', (info) => {
        mainWindow?.webContents.send('app:update-available', {
          version: info?.version ?? '',
        });
      });

      autoUpdater.on('update-downloaded', () => {
        mainWindow?.webContents.send('app:update-downloaded', {});
      });

      autoUpdater.on('error', () => {});

      autoUpdater.checkForUpdates().catch(() => {});
    })
    .catch(() => {});
}
