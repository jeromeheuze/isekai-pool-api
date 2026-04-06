<?php

use App\Http\Controllers\Api\FaucetController;
use App\Http\Controllers\Api\NetworkTrackerController;
use App\Http\Controllers\Api\RpcController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| isekai-pool.com — Public RPC API v1
|--------------------------------------------------------------------------
| Registered from bootstrap/app.php. Laravel adds the /api prefix, so these
| routes are served at /api/v1/...
*/

Route::prefix('v1')->group(function () {

    Route::get('/health', [RpcController::class, 'health']);

    Route::prefix('network')
        ->middleware('throttle:120,1')
        ->group(function () {
            Route::get('{coin}/current', [NetworkTrackerController::class, 'current'])
                ->where('coin', '[A-Za-z0-9]+');
            Route::get('{coin}/history', [NetworkTrackerController::class, 'history'])
                ->where('coin', '[A-Za-z0-9]+');
            Route::get('{coin}/pools', [NetworkTrackerController::class, 'pools'])
                ->where('coin', '[A-Za-z0-9]+');
        });

    Route::prefix('faucet')->group(function () {
        Route::post('/claim', [FaucetController::class, 'claim'])
            ->middleware('throttle:12,1');
        Route::get('/status', [FaucetController::class, 'status'])
            ->middleware('throttle:60,1');
        Route::get('/balance', [FaucetController::class, 'balance'])
            ->middleware('throttle:60,1');
        Route::get('/recent', [FaucetController::class, 'recent'])
            ->middleware('throttle:60,1');
    });

    Route::prefix('{coin}')
        ->where(['coin' => 'yenten|koto|tidecoin|sugarchain'])
        ->group(function () {
            Route::get('/status', [RpcController::class, 'status']);

            Route::middleware('throttle:60,1')
                ->post('/rpc', [RpcController::class, 'proxy']);
        });

});
