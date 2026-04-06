<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_coin_returns_404(): void
    {
        $this->getJson('/api/v1/network/NOTACOIN/current')
            ->assertStatus(404)
            ->assertJsonFragment(['error' => 'Coin not tracked']);
    }

    public function test_koto_current_returns_json_when_no_snapshots(): void
    {
        $this->getJson('/api/v1/network/KOTO/current')
            ->assertOk()
            ->assertJsonPath('coin', 'KOTO')
            ->assertJsonStructure(['coin', 'message', 'network', 'pools']);
    }

    public function test_koto_history_accepts_range(): void
    {
        $this->getJson('/api/v1/network/KOTO/history?range=24h')
            ->assertOk()
            ->assertJsonPath('coin', 'KOTO')
            ->assertJsonPath('range', '24h')
            ->assertJsonStructure(['coin', 'range', 'network', 'pools']);
    }
}
