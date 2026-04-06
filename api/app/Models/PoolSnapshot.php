<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolSnapshot extends Model
{
    protected $fillable = [
        'coin',
        'pool_slug',
        'pool_name',
        'pool_url',
        'captured_at',
        'hashrate',
        'miners',
        'workers',
        'blocks_found',
        'pool_fee',
        'is_online',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'hashrate' => 'decimal:4',
            'miners' => 'integer',
            'workers' => 'integer',
            'blocks_found' => 'integer',
            'pool_fee' => 'decimal:2',
            'is_online' => 'boolean',
        ];
    }
}
