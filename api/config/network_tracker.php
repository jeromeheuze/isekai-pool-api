<?php

return [

    'enabled' => filter_var(env('NETWORK_TRACKER_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),

    /**
     * Tracked coins: key = uppercase symbol, value = config key under coins.php for RPC + pool list.
     */
    'coins' => [
        'KOTO' => [
            'rpc_config_key' => 'koto',
            'pools' => [
                'isekai' => [
                    'name' => 'isekai-pool.com',
                    'stats_url' => 'https://koto.isekai-pool.com/api/stats',
                    'parser' => 'nomp',
                    'pool_fee' => 1.0,
                ],
                'mofumofu' => [
                    'name' => 'mofumofu.me',
                    'stats_url' => 'https://koto.mofumofu.me/api/stats',
                    'parser' => 'nomp',
                    'pool_fee' => 0.5,
                ],
                'leywapool' => [
                    'name' => 'leywapool.com',
                    'stats_url' => 'https://leywapool.com/site/api',
                    'parser' => 'leywapool',
                    'pool_fee' => null,
                ],
            ],
        ],
    ],

];
