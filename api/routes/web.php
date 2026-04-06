<?php

use App\Http\Controllers\ExplorerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

$explorerRoutes = function () {
    Route::get('/', [ExplorerController::class, 'index'])->name('explorer.home');
    Route::get('/block/{heightOrHash}', [ExplorerController::class, 'block'])
        ->where('heightOrHash', '(\d+|[a-fA-F0-9]{64})')
        ->name('explorer.block');
    Route::get('/tx/{txid}', [ExplorerController::class, 'tx'])
        ->where('txid', '[a-fA-F0-9]{64}')
        ->name('explorer.tx');
    Route::get('/address/{address}', [ExplorerController::class, 'address'])->name('explorer.address');
    Route::get('/search', [ExplorerController::class, 'search'])->name('explorer.search');
};

if ($domain = config('explorer.domain')) {
    Route::domain($domain)->group($explorerRoutes);
} else {
    Route::prefix('explorer')->group($explorerRoutes);
}
