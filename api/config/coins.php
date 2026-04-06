<?php

return [

    'yenten' => [
        'name' => 'Yenten',
        'symbol' => 'YTN',
        'algo' => 'YespowerR16',
        'description' => 'CPU-only Bitcoin fork. GPU slower than CPU.',
        'supply' => '80,000,000',
        'block_time' => '2 min',
        'explorer' => 'https://explorer.yentencoin.info',
        'website' => 'https://yentencoin.info',
        'status' => 'active',
        'rpc' => [
            'host' => '127.0.0.1',
            'port' => env('YTN_RPC_PORT', 9982),
            'user' => env('YTN_RPC_USER', 'isekai_ytn'),
            'pass' => env('YTN_RPC_PASS'),
        ],
    ],

    'koto' => [
        'name' => 'Koto',
        'symbol' => 'KOTO',
        'algo' => 'yescryptR8G',
        'description' => 'Japanese privacy coin. Zcash fork; miners use yescryptR8G.',
        'supply' => '210,000,000',
        'block_time' => '~60 sec',
        'explorer' => 'https://explorer.isekai-pool.com',
        'website' => 'https://ko-to.org',
        'exchange' => 'TradeOgre',
        'status' => 'active',
        'rpc' => [
            'host' => env('KOTO_RPC_HOST', '127.0.0.1'),
            'port' => env('KOTO_RPC_PORT', 8432),
            'user' => env('KOTO_RPC_USER', 'isekai_koto'),
            'pass' => env('KOTO_RPC_PASS'),
        ],
    ],

    'tidecoin' => [
        'name' => 'Tidecoin',
        'symbol' => 'TDC',
        'algo' => 'YespowerTIDE',
        'description' => 'Post-quantum secure Bitcoin. Uses FALCON-512 — NIST-certified quantum-resistant signatures.',
        'supply' => '21,000,000',
        'block_time' => '60 sec',
        'explorer' => 'https://explorer.tidecoin.org',
        'website' => 'https://tidecoin.org',
        'exchange' => 'tidecoin.exchange',
        'status' => 'active',
        'rpc' => [
            'host' => '127.0.0.1',
            'port' => env('TDC_RPC_PORT', 9368),
            'user' => env('TDC_RPC_USER', 'isekai_tdc'),
            'pass' => env('TDC_RPC_PASS'),
        ],
    ],

    'sugarchain' => [
        'name' => 'Sugarchain',
        'symbol' => 'SUGAR',
        'algo' => 'YespowerSugar',
        'description' => 'World\'s fastest PoW blockchain. 5-second blocks, Native SegWit.',
        'supply' => '1,073,741,824',
        'block_time' => '5 sec',
        'explorer' => 'https://1explorer.sugarchain.org',
        'website' => 'https://sugarchain.org',
        'exchange' => 'XeggeX',
        'status' => 'planned',
        'rpc' => [
            'host' => '127.0.0.1',
            'port' => env('SUGAR_RPC_PORT', 34229),
            'user' => env('SUGAR_RPC_USER', 'isekai_sugar'),
            'pass' => env('SUGAR_RPC_PASS'),
        ],
    ],

];
