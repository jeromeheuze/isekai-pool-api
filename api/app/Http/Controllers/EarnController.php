<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class EarnController extends Controller
{
    /**
     * @return array{title:string,slug:string,intro:string}
     */
    private function activityMeta(string $slug): array
    {
        return match ($slug) {
            'shrine_visit' => [
                'title' => 'Daily shrine visit',
                'slug' => 'shrine_visit',
                'intro' => 'Pause for a short shrine moment, then claim your daily reward.',
            ],
            'kanji_quiz' => [
                'title' => 'Kanji quiz',
                'slug' => 'kanji_quiz',
                'intro' => 'Answer 5 quick kanji questions. Score 4/5 or better to unlock claim.',
            ],
            'retro_trivia' => [
                'title' => 'Retro game trivia',
                'slug' => 'retro_trivia',
                'intro' => 'Classic JP retro trivia. Score 4/5 or better to unlock claim.',
            ],
            'yokai_match' => [
                'title' => 'Yokai match',
                'slug' => 'yokai_match',
                'intro' => 'Match all yokai pairs to unlock claim.',
            ],
            'yokai_quiz' => [
                'title' => 'Yokai quiz',
                'slug' => 'yokai_quiz',
                'intro' => 'Answer yokai folklore trivia. Score 4/5 or better.',
            ],
            'shrine_puzzle' => [
                'title' => 'Shrine puzzle',
                'slug' => 'shrine_puzzle',
                'intro' => 'Put shrine steps in the correct order to unlock claim.',
            ],
            'map_explore' => [
                'title' => 'Map explore',
                'slug' => 'map_explore',
                'intro' => 'Visit all map checkpoints in order to unlock claim.',
            ],
            'coffee_quiz' => [
                'title' => 'Coffee quiz',
                'slug' => 'coffee_quiz',
                'intro' => 'Quick coffee culture quiz. Score 4/5 or better.',
            ],
            'daily_bonus' => [
                'title' => 'Daily bonus',
                'slug' => 'daily_bonus',
                'intro' => 'One quick daily check-in action to claim bonus.',
            ],
            default => [
                'title' => 'Activity',
                'slug' => $slug,
                'intro' => 'Complete the activity, then claim your reward.',
            ],
        };
    }

    private function renderActivity(string $slug): View
    {
        return view('earn.activity', array_merge($this->activityMeta($slug), [
            'apiBase' => config('earn.api_base'),
            'turnstileSiteKey' => config('faucet.turnstile.site_key'),
        ]));
    }

    public function index(): View
    {
        return view('earn.index', [
            'activities' => config('faucet.activities'),
            'apiBase' => config('earn.api_base'),
        ]);
    }

    public function shrine(): View
    {
        $meta = $this->activityMeta('shrine_visit');

        return view('earn.shrine', array_merge($meta, [
            'apiBase' => config('earn.api_base'),
            'turnstileSiteKey' => config('faucet.turnstile.site_key'),
            'rewardKoto' => config('faucet.activities.shrine_visit.reward'),
            'explorerTxBase' => 'https://explorer.isekai-pool.com/tx/',
        ]));
    }

    /**
     * @return array<int, array{q: string, options: array<int, string>, answer: int}>
     */
    private function kanjiQuizQuestions(): array
    {
        return [
            ['q' => 'What does 水 mean?', 'options' => ['fire', 'water', 'tree'], 'answer' => 1],
            ['q' => 'What does 日 mean?', 'options' => ['sun/day', 'moon', 'mountain'], 'answer' => 0],
            ['q' => 'What does 山 mean?', 'options' => ['river', 'mountain', 'gold'], 'answer' => 1],
            ['q' => 'What does 人 mean?', 'options' => ['person', 'sword', 'rain'], 'answer' => 0],
            ['q' => 'What does 火 mean?', 'options' => ['water', 'fire', 'earth'], 'answer' => 1],
        ];
    }

    public function kanji(): View
    {
        $meta = $this->activityMeta('kanji_quiz');

        return view('earn.kanji', array_merge($meta, [
            'apiBase' => config('earn.api_base'),
            'turnstileSiteKey' => config('faucet.turnstile.site_key'),
            'rewardKoto' => config('faucet.activities.kanji_quiz.reward'),
            'explorerTxBase' => 'https://explorer.isekai-pool.com/tx/',
            'kanjiQuestions' => $this->kanjiQuizQuestions(),
        ]));
    }

    /**
     * @return array<int, array{q: string, options: array<int, string>, answer: int}>
     */
    private function retroQuizQuestions(): array
    {
        return [
            ['q' => 'Nintendo launched the Famicom in which year?', 'options' => ['1981', '1983', '1987'], 'answer' => 1],
            ['q' => 'Which company created the Mega Drive?', 'options' => ['SEGA', 'SNK', 'NEC'], 'answer' => 0],
            ['q' => 'Which game popularized side-scrolling platformers?', 'options' => ['Super Mario Bros.', 'Pac-Man', 'Tetris'], 'answer' => 0],
            ['q' => 'PC Engine was known as what in NA?', 'options' => ['Master System', 'TurboGrafx-16', 'Neo Geo'], 'answer' => 1],
            ['q' => 'Which studio made Street Fighter II?', 'options' => ['Konami', 'Taito', 'Capcom'], 'answer' => 2],
        ];
    }

    /**
     * Decorative watermark text per question (visual only; matches console/game theme).
     *
     * @return array<int, string>
     */
    private function retroQuizWatermarks(): array
    {
        return ['ファミコン', 'メガドライブ', 'マリオ', 'PCエンジン', 'ストリートファイター'];
    }

    public function retro(): View
    {
        $meta = $this->activityMeta('retro_trivia');

        return view('earn.retro', array_merge($meta, [
            'apiBase' => config('earn.api_base'),
            'turnstileSiteKey' => config('faucet.turnstile.site_key'),
            'rewardKoto' => config('faucet.activities.retro_trivia.reward'),
            'explorerTxBase' => 'https://explorer.isekai-pool.com/tx/',
            'retroQuestions' => $this->retroQuizQuestions(),
            'retroWatermarks' => $this->retroQuizWatermarks(),
        ]));
    }

    public function yokaiMatch(): View
    {
        $meta = $this->activityMeta('yokai_match');

        return view('earn.yokai-match', array_merge($meta, [
            'apiBase' => config('earn.api_base'),
            'turnstileSiteKey' => config('faucet.turnstile.site_key'),
            'rewardKoto' => config('faucet.activities.yokai_match.reward'),
            'explorerTxBase' => 'https://explorer.isekai-pool.com/tx/',
        ]));
    }

    /**
     * @return array<int, array{q: string, options: array<int, string>, answer: int}>
     */
    private function yokaiQuizQuestions(): array
    {
        return [
            ['q' => 'Which yokai is known for a long neck at night?', 'options' => ['Rokurokubi', 'Kappa', 'Tengu'], 'answer' => 0],
            ['q' => 'Kappa are commonly associated with what place?', 'options' => ['Mountains', 'Rivers', 'Deserts'], 'answer' => 1],
            ['q' => 'Tengu are often depicted with what?', 'options' => ['Long nose', 'Three eyes', 'Fish tail'], 'answer' => 0],
            ['q' => 'Zashiki-warashi are said to bring...', 'options' => ['Bad weather', 'Good fortune', 'Earthquakes'], 'answer' => 1],
            ['q' => 'Nurarihyon is often portrayed as a...', 'options' => ['Child spirit', 'Old man yokai', 'Fox yokai'], 'answer' => 1],
        ];
    }

    /**
     * Japanese watermark per question index (Q1–Q5).
     *
     * @return array<int, string>
     */
    private function yokaiQuizWatermarks(): array
    {
        return ['轆轤首', '河童', '天狗', '座敷童', 'ぬらりひょん'];
    }

    public function yokaiQuiz(): View
    {
        $meta = $this->activityMeta('yokai_quiz');

        return view('earn.yokai-quiz', array_merge($meta, [
            'apiBase' => config('earn.api_base'),
            'turnstileSiteKey' => config('faucet.turnstile.site_key'),
            'rewardKoto' => config('faucet.activities.yokai_quiz.reward'),
            'explorerTxBase' => 'https://explorer.isekai-pool.com/tx/',
            'yokaiQuestions' => $this->yokaiQuizQuestions(),
            'yokaiWatermarks' => $this->yokaiQuizWatermarks(),
        ]));
    }

    public function shrinePuzzle(): View
    {
        return $this->renderActivity('shrine_puzzle');
    }

    public function mapExplore(): View
    {
        return $this->renderActivity('map_explore');
    }

    public function coffeeQuiz(): View
    {
        return $this->renderActivity('coffee_quiz');
    }

    public function dailyBonus(): View
    {
        return $this->renderActivity('daily_bonus');
    }
}
