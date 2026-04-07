<?php

namespace App\Services\Faucet;

use App\Support\KotoAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class FaucetActivityCompletionService
{
    private const CACHE_PREFIX = 'faucet:activity_session:';

    private const KANJI_QUIZ = [
        ['answer' => 1],
        ['answer' => 0],
        ['answer' => 1],
        ['answer' => 0],
        ['answer' => 1],
    ];

    private const RETRO_QUIZ = [
        ['answer' => 1],
        ['answer' => 0],
        ['answer' => 0],
        ['answer' => 1],
        ['answer' => 2],
    ];

    private const YOKAI_QUIZ = [
        ['answer' => 0],
        ['answer' => 1],
        ['answer' => 0],
        ['answer' => 1],
        ['answer' => 1],
    ];

    private const COFFEE_QUIZ = [
        ['answer' => 0],
        ['answer' => 1],
        ['answer' => 1],
        ['answer' => 0],
        ['answer' => 1],
    ];

    private const SHRINE_STEPS = ['Bow', 'Cleanse hands', 'Offer prayer', 'Final bow'];

    private const QUIZ_PASS_MIN = 4;

    public function __construct(
        private TurnstileVerifier $turnstile
    ) {}

    /**
     * Start a timed server-side session (shrine timer, daily check-in).
     */
    public function createSession(string $activitySlug): string
    {
        if (! in_array($activitySlug, ['shrine_visit', 'daily_bonus'], true)) {
            throw new RuntimeException('Session not used for this activity');
        }

        $id = (string) Str::uuid();
        Cache::put(
            self::CACHE_PREFIX.$id,
            ['slug' => $activitySlug, 'started_at' => now()->timestamp],
            now()->addMinutes(20)
        );

        return $id;
    }

    /**
     * Verify Turnstile + proof, return a short-lived signed completion token bound to wallet + slug.
     *
     * @param  array<string, mixed>  $proof
     */
    public function completeActivity(Request $request, string $wallet, string $activitySlug, array $proof): string
    {
        if (! config('faucet.enabled')) {
            throw new RuntimeException('Faucet is disabled');
        }

        if (! KotoAddress::isValid($wallet)) {
            throw new RuntimeException('Invalid KOTO address format');
        }

        $activities = config('faucet.activities');
        if (! isset($activities[$activitySlug])) {
            throw new RuntimeException('Unknown activity');
        }

        $ip = $request->ip() ?? '0.0.0.0';
        if (! $this->turnstile->verify($request->input('turnstile_token'), $ip)) {
            throw new RuntimeException('Captcha verification failed');
        }

        $this->assertProofValid($activitySlug, $proof);

        return $this->issueToken($wallet, $activitySlug);
    }

    public function verifyToken(?string $token, string $wallet, string $activitySlug): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $secret = $this->signingSecret();
        $raw = $this->base64UrlDecode($token);
        if ($raw === null || ! str_contains($raw, '|')) {
            return false;
        }

        [$json, $sig] = explode('|', $raw, 2);
        $expected = hash_hmac('sha256', $json, $secret);
        if (! hash_equals($expected, $sig)) {
            return false;
        }

        $data = json_decode($json, true);
        if (! is_array($data)) {
            return false;
        }

        $exp = (int) ($data['exp'] ?? 0);
        if ($exp < $this->now()->timestamp) {
            return false;
        }

        $w = (string) ($data['w'] ?? '');
        $a = (string) ($data['a'] ?? '');
        if ($w !== $wallet || $a !== $activitySlug) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $proof
     */
    private function assertProofValid(string $activitySlug, array $proof): void
    {
        match ($activitySlug) {
            'kanji_quiz' => $this->assertQuizProof(self::KANJI_QUIZ, $proof),
            'retro_trivia' => $this->assertQuizProof(self::RETRO_QUIZ, $proof),
            'yokai_quiz' => $this->assertQuizProof(self::YOKAI_QUIZ, $proof),
            'coffee_quiz' => $this->assertQuizProof(self::COFFEE_QUIZ, $proof),
            'yokai_match' => $this->assertYokaiMatchProof($proof),
            'shrine_puzzle' => $this->assertShrinePuzzleProof($proof),
            'map_explore' => $this->assertMapExploreProof($proof),
            'shrine_visit' => $this->assertSessionProof('shrine_visit', $proof, 8),
            'daily_bonus' => $this->assertSessionProof('daily_bonus', $proof, 1),
            default => throw new RuntimeException('Activity proof not configured'),
        };
    }

    /**
     * @param  array<int, array{answer: int}>  $questions
     * @param  array<string, mixed>  $proof
     */
    private function assertQuizProof(array $questions, array $proof): void
    {
        $answers = $proof['answers'] ?? null;
        if (! is_array($answers) || count($answers) !== count($questions)) {
            throw new RuntimeException('Invalid quiz proof');
        }

        $score = 0;
        foreach ($questions as $i => $q) {
            if (! isset($answers[$i]) || ! is_numeric($answers[$i])) {
                throw new RuntimeException('Invalid quiz proof');
            }
            if ((int) $answers[$i] === (int) $q['answer']) {
                $score++;
            }
        }

        if ($score < self::QUIZ_PASS_MIN) {
            throw new RuntimeException('Quiz score too low');
        }
    }

    /**
     * @param  array<string, mixed>  $proof
     */
    private function assertYokaiMatchProof(array $proof): void
    {
        $matches = $proof['matches'] ?? null;
        if (! is_array($matches) || count($matches) !== 4) {
            throw new RuntimeException('Invalid match proof');
        }

        $norm = [];
        foreach ($matches as $m) {
            if (! is_array($m) || count($m) !== 2) {
                throw new RuntimeException('Invalid match proof');
            }
            $a = (int) $m[0];
            $b = (int) $m[1];
            if ($a !== $b || $a < 0 || $a > 3) {
                throw new RuntimeException('Invalid match proof');
            }
            $norm[] = [$a, $a];
        }

        usort($norm, fn ($x, $y) => $x[0] <=> $y[0]);

        $expected = [[0, 0], [1, 1], [2, 2], [3, 3]];
        if ($norm !== $expected) {
            throw new RuntimeException('Invalid match proof');
        }
    }

    /**
     * @param  array<string, mixed>  $proof
     */
    private function assertShrinePuzzleProof(array $proof): void
    {
        $order = $proof['order'] ?? null;
        if (! is_array($order) || $order !== self::SHRINE_STEPS) {
            throw new RuntimeException('Invalid puzzle proof');
        }
    }

    /**
     * @param  array<string, mixed>  $proof
     */
    private function assertMapExploreProof(array $proof): void
    {
        $seq = $proof['sequence'] ?? null;
        if (! is_array($seq) || count($seq) !== 4) {
            throw new RuntimeException('Invalid map proof');
        }
        $ints = array_map(static fn ($v) => (int) $v, $seq);
        if ($ints !== [1, 2, 3, 4]) {
            throw new RuntimeException('Invalid map proof');
        }
    }

    /**
     * @param  array<string, mixed>  $proof
     */
    private function assertSessionProof(string $expectedSlug, array $proof, int $minSeconds): void
    {
        $sessionId = $proof['session_id'] ?? null;
        if (! is_string($sessionId) || $sessionId === '') {
            throw new RuntimeException('Missing session');
        }

        $row = Cache::get(self::CACHE_PREFIX.$sessionId);
        if (! is_array($row)) {
            throw new RuntimeException('Invalid or expired session');
        }

        $slug = (string) ($row['slug'] ?? '');
        $startedAt = (int) ($row['started_at'] ?? 0);
        if ($slug !== $expectedSlug || $startedAt <= 0) {
            throw new RuntimeException('Invalid session');
        }

        if ($this->now()->timestamp - $startedAt < $minSeconds) {
            throw new RuntimeException('Activity not finished yet');
        }

        Cache::forget(self::CACHE_PREFIX.$sessionId);
    }

    private function issueToken(string $wallet, string $activitySlug): string
    {
        $ttlMinutes = max(1, (int) config('faucet.completion_token_ttl_minutes', 15));
        $exp = $this->now()->addMinutes($ttlMinutes)->timestamp;

        $payload = [
            'w' => $wallet,
            'a' => $activitySlug,
            'exp' => $exp,
            'iat' => $this->now()->timestamp,
            'jti' => Str::random(16),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Could not issue token');
        }

        $sig = hash_hmac('sha256', $json, $this->signingSecret());

        return $this->base64UrlEncode($json.'|'.$sig);
    }

    private function signingSecret(): string
    {
        $secret = config('faucet.completion_token_secret');
        if (is_string($secret) && $secret !== '') {
            return $secret;
        }

        return hash('sha256', (string) config('app.key'), true);
    }

    private function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $encoded): ?string
    {
        $b64 = strtr($encoded, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $out = base64_decode($b64, true);

        return $out === false ? null : $out;
    }

    private function now(): Carbon
    {
        return now();
    }
}
