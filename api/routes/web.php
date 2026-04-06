<?php

use App\Http\Controllers\ExplorerController;
use Illuminate\Support\Facades\Route;

/*
| Explorer: register the explorer domain BEFORE the catch-all "/", so
| explorer.isekai-pool.com/ resolves to ExplorerController (not welcome).
*/
$explorerInner = function () {
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
    Route::domain($domain)->get('/', [ExplorerController::class, 'index'])->name('explorer.home');
    Route::domain($domain)->group($explorerInner);
} else {
    Route::prefix('explorer')->group(function () use ($explorerInner) {
        Route::get('/', [ExplorerController::class, 'index'])->name('explorer.home');
        $explorerInner();
    });
}

Route::get('/', function () {
    return view('welcome');
});
