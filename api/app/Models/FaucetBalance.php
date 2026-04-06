<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (id = 1) tracking faucet bookkeeping. Reconcile with on-chain getbalance periodically.
 */
class FaucetBalance extends Model
{
    protected $table = 'faucet_balance';

    protected $fillable = [
        'balance',
        'total_paid',
        'total_claims',
        'last_sync',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:8',
            'total_paid' => 'decimal:8',
            'last_sync' => 'datetime',
        ];
    }

    /**
     * Single bookkeeping row (id = 1). Auto-creates if missing so the API does not 500 after deploy.
     */
    public static function singleton(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'balance' => 0,
                'total_paid' => 0,
                'total_claims' => 0,
            ]
        );
    }
}
