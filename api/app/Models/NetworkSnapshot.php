<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkSnapshot extends Model
{
    protected $fillable = [
        'coin',
        'captured_at',
        'block_height',
        'network_hashrate',
        'difficulty',
        'network_connections',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'block_height' => 'integer',
            'network_hashrate' => 'decimal:4',
            'difficulty' => 'decimal:8',
            'network_connections' => 'integer',
        ];
    }
}
