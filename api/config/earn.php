<?php

return [

    /*
    | Faucet JSON API base for fetch() from the earn hub (no trailing slash).
    | Default is same-origin /api/v1 (works when the hub is served via isekai-pool.com → proxy to Laravel).
    | Override only if the hub must call a different API origin.
    */
    'api_base' => env('EARN_API_BASE')
        ? rtrim((string) env('EARN_API_BASE'), '/')
        : '/api/v1',

];
