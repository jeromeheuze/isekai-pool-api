<?php

namespace Tests\Feature;

use App\Jobs\ProcessFaucetPayout;
use App\Models\FaucetBalance;
use App\Services\Faucet\TurnstileVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FaucetActivityCompletionTest extends TestCase
{
    use RefreshDatabase;

    private const WALLET = 'k1D5M6eWUdZTpRRqdgUnMBHyqDmBjuNT8Ni';

    protected function setUp(): void
    {
        parent::setUp();
        config(['faucet.enabled' => true]);
        config(['faucet.require_completion_token' => true]);
    }

    public function test_activity_session_rejects_unsupported_slug(): void
    {
        $this->postJson('/api/v1/faucet/activity-session', [
            'activity_slug' => 'kanji_quiz',
        ])->assertStatus(422);
    }

    public function test_activity_complete_issues_token_for_kanji_quiz(): void
    {
        $this->mock(TurnstileVerifier::class, fn ($m) => $m->shouldReceive('verify')->once()->andReturn(true));

        $answers = [1, 0, 1, 0, 1];

        $this->postJson('/api/v1/faucet/activity-complete', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'kanji_quiz',
            'turnstile_token' => 'ok',
            'proof' => ['answers' => $answers],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['completion_token']);
    }

    public function test_activity_complete_rejects_low_quiz_score(): void
    {
        $this->mock(TurnstileVerifier::class, fn ($m) => $m->shouldReceive('verify')->once()->andReturn(true));

        $this->postJson('/api/v1/faucet/activity-complete', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'kanji_quiz',
            'turnstile_token' => 'ok',
            'proof' => ['answers' => [0, 0, 0, 0, 0]],
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Quiz score too low');
    }

    public function test_shrine_visit_complete_after_session_age(): void
    {
        $this->mock(TurnstileVerifier::class, fn ($m) => $m->shouldReceive('verify')->once()->andReturn(true));

        $sessionId = $this->postJson('/api/v1/faucet/activity-session', [
            'activity_slug' => 'shrine_visit',
        ])->assertOk()->json('session_id');

        $this->assertNotEmpty($sessionId);

        Cache::put('faucet:activity_session:'.$sessionId, [
            'slug' => 'shrine_visit',
            'started_at' => now()->subSeconds(10)->timestamp,
        ], now()->addMinutes(20));

        $this->postJson('/api/v1/faucet/activity-complete', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'shrine_visit',
            'turnstile_token' => 'ok',
            'proof' => ['session_id' => $sessionId],
        ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_claim_accepts_valid_completion_token_without_turnstile(): void
    {
        Queue::fake();

        $this->mock(TurnstileVerifier::class, fn ($m) => $m->shouldReceive('verify')->once()->andReturn(true));

        $answers = [1, 0, 1, 0, 1];

        $token = $this->postJson('/api/v1/faucet/activity-complete', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'kanji_quiz',
            'turnstile_token' => 'ok',
            'proof' => ['answers' => $answers],
        ])->assertOk()->json('completion_token');

        $this->mock(TurnstileVerifier::class, fn ($m) => $m->shouldReceive('verify')->never());

        FaucetBalance::query()->whereKey(1)->update(['balance' => 100]);

        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'kanji_quiz',
            'completion_token' => $token,
            'source_site' => 'isekai-pool',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pending', true);

        Queue::assertPushed(ProcessFaucetPayout::class);
    }

    public function test_claim_rejects_wrong_wallet_for_token(): void
    {
        $this->mock(TurnstileVerifier::class, fn ($m) => $m->shouldReceive('verify')->once()->andReturn(true));

        $token = $this->postJson('/api/v1/faucet/activity-complete', [
            'wallet_address' => self::WALLET,
            'activity_slug' => 'kanji_quiz',
            'turnstile_token' => 'ok',
            'proof' => ['answers' => [1, 0, 1, 0, 1]],
        ])->assertOk()->json('completion_token');

        $this->mock(TurnstileVerifier::class, fn ($m) => $m->shouldReceive('verify')->never());

        $this->postJson('/api/v1/faucet/claim', [
            'wallet_address' => 'k1bbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
            'activity_slug' => 'kanji_quiz',
            'completion_token' => $token,
            'source_site' => 'isekai-pool',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Invalid or expired activity completion');
    }
}
