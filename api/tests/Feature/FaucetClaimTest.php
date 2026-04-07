<?php

namespace Tests\Feature;

use App\Models\FaucetBalance;
use App\Services\Faucet\KotoFaucetWalletService;
use App\Services\Faucet\TurnstileVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FaucetClaimTest extends TestCase
{
    use RefreshDatabase;

    private const WALLET = 'k1D5M6eWUdZTpRRqdgUnMBHyqDmBjuNT8Ni';

    protected function setUp(): void
    {
        parent::setUp();
        config(['faucet.enabled' => true]);
    }

    public function test_disabled_returns_503(): void
    {
        config(['faucet.enabled' => false]);

        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'shrine_visit',
            'turnstile_token' => 'test-token',
        ])
            ->assertStatus(503)
            ->assertJsonFragment(['error' => 'Faucet is disabled']);
    }

    public function test_validation_error_when_missing_wallet(): void
    {
        $this->postJson('/api/v1/faucet/claim', [
            'activity_slug' => 'shrine_visit',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['wallet_address']);
    }

    public function test_invalid_address_returns_422(): void
    {
        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => 'not-a-koto-address',
            'activity_slug' => 'shrine_visit',
            'turnstile_token' => 'x',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Invalid KOTO address format']);
    }

    public function test_unknown_activity_returns_422(): void
    {
        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'no_such_activity',
            'turnstile_token' => 'x',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Unknown activity']);
    }

    public function test_captcha_failed_returns_422(): void
    {
        $this->mock(TurnstileVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(false);
        });

        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'shrine_visit',
            'turnstile_token' => 'bad',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'Captcha verification failed']);
    }

    public function test_success_returns_pending_when_payout_job_not_run(): void
    {
        Queue::fake();

        $this->mock(TurnstileVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        FaucetBalance::query()->whereKey(1)->update(['balance' => 100]);

        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'shrine_visit',
            'turnstile_token' => 'ok',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pending', true)
            ->assertJsonPath('txid', null);

        Queue::assertPushed(\App\Jobs\ProcessFaucetPayout::class);
    }

    public function test_success_paid_when_rpc_succeeds(): void
    {
        $txid = '7ed1cd5f545b44531266ef2a07b2a2e0cfdc53d60d5df88e091e603c4f365951';

        $this->mock(TurnstileVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->once()->andReturn(true);
        });

        $this->mock(KotoFaucetWalletService::class, function ($mock) use ($txid) {
            $mock->shouldReceive('sendToAddress')->once()->andReturn($txid);
        });

        FaucetBalance::query()->whereKey(1)->update(['balance' => 100]);

        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'shrine_visit',
            'turnstile_token' => 'ok',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('txid', $txid)
            ->assertJsonPath('amount', '0.50000000')
            ->assertJsonMissingPath('pending');
    }
}
