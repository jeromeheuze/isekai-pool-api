<?php

return [

    /*
    | When set (e.g. explorer.isekai-pool.com), explorer routes are registered at / on that host.
    | Leave empty to mount under /explorer on the default app host (local dev).
    */
    'domain' => env('EXPLORER_DOMAIN') ?: null,

    'coin' => 'koto',

    /*
    | Without txindex=1 on the node, getrawtransaction only works for mempool txs unless a
    | blockhash is passed. Scan this many recent blocks (tip downward) to find the tx.
    | Set to 0 to disable (only direct getrawtransaction; enable txindex on the node instead).
    */
    'tx_lookup_block_scan_depth' => (int) env('EXPLORER_TX_LOOKUP_BLOCK_SCAN_DEPTH', 2500),

];
