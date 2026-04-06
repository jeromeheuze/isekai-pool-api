<?php

return [

    /*
    | When set (e.g. explorer.isekai-pool.com), explorer routes are registered at / on that host.
    | Leave empty to mount under /explorer on the default app host (local dev).
    */
    'domain' => env('EXPLORER_DOMAIN') ?: null,

    'coin' => 'koto',

];
