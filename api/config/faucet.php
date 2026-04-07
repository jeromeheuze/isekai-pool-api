<?php

return [

    'enabled' => env('FAUCET_ENABLED', false),

    /**
     * Public faucet hot-wallet address (transparency / UI). Optional.
     *
     * @see env KOTO_FAUCET_WALLET
     */
    'faucet_wallet' => env('KOTO_FAUCET_WALLET'),

    /** Minimum on-book balance before paying routine claims (KOTO). FAUCET_MIN_BALANCE overrides KOTO_FAUCET_MIN_BALANCE. */
    'min_operator_balance' => is_numeric(env('FAUCET_MIN_BALANCE'))
        ? (float) env('FAUCET_MIN_BALANCE')
        : (float) env('KOTO_FAUCET_MIN_BALANCE', 10),

    /** Routine activity rewards (KOTO). Must sum with daily_bonus to cap at routine_max_per_day. */
    'activities' => [
        'shrine_visit' => ['reward' => 0.5, 'routine' => true],
        'kanji_quiz' => ['reward' => 1.0, 'routine' => true],
        'yokai_match' => ['reward' => 1.5, 'routine' => true],
        'yokai_quiz' => ['reward' => 1.0, 'routine' => true],
        'retro_trivia' => ['reward' => 1.0, 'routine' => true],
        'shrine_puzzle' => ['reward' => 2.0, 'routine' => true],
        'map_explore' => ['reward' => 1.0, 'routine' => true],
        'coffee_quiz' => ['reward' => 0.5, 'routine' => true],
        'daily_bonus' => ['reward' => 1.5, 'routine' => true],
    ],

    'routine_max_per_wallet_per_day' => 10.0,
    'hard_max_per_wallet_per_day' => (float) env('FAUCET_HARD_MAX_PER_WALLET_DAY', 25),
    'global_max_per_day' => (float) env('FAUCET_GLOBAL_MAX_PER_DAY', 100),

    'cooldown_hours' => 24,

    'timezone' => 'Asia/Tokyo',

    'turnstile' => [
        /** Server verify: FAUCET_TURNSTILE_SECRET or TURNSTILE_SECRET_KEY */
        'secret' => env('FAUCET_TURNSTILE_SECRET') ?: env('TURNSTILE_SECRET_KEY'),
        /** Browser widget (public). */
        'site_key' => env('TURNSTILE_SITE_KEY'),
    ],

    /**
     * Earn hub: require POST /faucet/activity-complete (Turnstile + proof) before /faucet/claim
     * when source_site is isekai-pool. Disable for local/tests via FAUCET_REQUIRE_COMPLETION_TOKEN=false.
     */
    'require_completion_token' => filter_var(env('FAUCET_REQUIRE_COMPLETION_TOKEN', true), FILTER_VALIDATE_BOOLEAN),

    /** HMAC secret for completion_token; falls back to hashed APP_KEY if unset. */
    'completion_token_secret' => env('FAUCET_COMPLETION_TOKEN_SECRET'),

    /** Signed completion token lifetime (minutes). */
    'completion_token_ttl_minutes' => max(1, min(120, (int) env('FAUCET_COMPLETION_TOKEN_TTL_MINUTES', 15))),

    /** GET /faucet/balance?sync_rpc=1 — refresh book balance from KOTO getbalance (trusted ops only). */
    'allow_balance_rpc_sync' => env('FAUCET_ALLOW_BALANCE_RPC_SYNC', false),

    /**
     * Periodically set faucet_balance.balance from getbalance (RPC wallet = hot wallet).
     * Skips while any claim is pending so reserved book balance is not overwritten.
     * Requires cron: * * * * * php /path/to/artisan schedule:run
     */
    'auto_sync_balance' => filter_var(env('FAUCET_AUTO_SYNC_BALANCE', 'true'), FILTER_VALIDATE_BOOLEAN),

    /** How often schedule:run triggers sync (1–59 minutes). */
    'sync_balance_interval_minutes' => max(1, min(59, (int) env('FAUCET_SYNC_BALANCE_INTERVAL_MINUTES', 5))),

];
