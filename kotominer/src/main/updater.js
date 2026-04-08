/**
 * electron-updater — wire when GitHub releases are configured.
 * See _docs/kotominer-electron-spec.md
 * @param {import('electron').App} app
 */
export function initUpdater(app) {
  if (!app.isPackaged) return;
  // import('electron-updater').then(({ autoUpdater }) => {
  //   autoUpdater.checkForUpdatesAndNotify();
  // });
}
