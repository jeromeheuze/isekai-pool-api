<?php

namespace App\Models;

use App\FaucetClaimStatus;
use App\FaucetPayoutBucket;
use Illuminate\Database\Eloquent\Model;

class FaucetClaim extends Model
{
    protected $fillable = [
        'idempotency_key',
        'wallet_address',
        'ip_address',
        'activity_slug',
        'source_site',
        'payout_bucket',
        'amount',
        'txid',
        'status',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'status' => FaucetClaimStatus::class,
            'payout_bucket' => FaucetPayoutBucket::class,
        ];
    }

    public function scopePaid($query)
    {
        return $query->where('status', FaucetClaimStatus::Paid);
    }

    public function scopeRoutine($query)
    {
        return $query->where('payout_bucket', FaucetPayoutBucket::Routine);
    }
}
