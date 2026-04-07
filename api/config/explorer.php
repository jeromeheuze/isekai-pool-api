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
    | 0 disables scanning — then confirmed txs need txindex=1 on the node or they 404.
    | Older txs need a larger depth (or txindex). Default is high so typical payouts resolve.
    */
    'tx_lookup_block_scan_depth' => (int) env('EXPLORER_TX_LOOKUP_BLOCK_SCAN_DEPTH', 50000),

    /*
    | Max PHP execution time (seconds) for one tx lookup when block scanning runs. Prevents
    | default 30s limits from killing long scans (misses scan the full depth).
    */
    'tx_lookup_max_seconds' => (int) env('EXPLORER_TX_LOOKUP_MAX_SECONDS', 300),

];
