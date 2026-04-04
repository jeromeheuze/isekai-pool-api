<?php

use App\Http\Controllers\Api\RpcController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| isekai-pool.com — Public RPC API v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // All nodes health check
    Route::get('/health', [RpcController::class, 'health']);

    // Per-coin routes
    Route::prefix('{coin}')
        ->where(['coin' => 'yenten|koto|tidecoin|sugarchain|cpuchain'])
        ->group(function () {

            // Node status + blockchain info
            Route::get('/status', [RpcController::class, 'status']);

            // Public RPC proxy — 60 req/min
            Route::middleware('throttle:60,1')
                ->post('/rpc', [RpcController::class, 'proxy']);
        });

});
