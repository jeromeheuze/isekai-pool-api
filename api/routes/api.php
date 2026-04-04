<?php

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

    Route::prefix('{coin}')
        ->where(['coin' => 'yenten|koto|tidecoin|sugarchain'])
        ->group(function () {
            Route::get('/status', [RpcController::class, 'status']);

            Route::middleware('throttle:60,1')
                ->post('/rpc', [RpcController::class, 'proxy']);
        });

});
