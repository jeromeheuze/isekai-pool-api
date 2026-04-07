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
        return $this->renderActivity('shrine_visit');
    }

    public function kanji(): View
    {
        return $this->renderActivity('kanji_quiz');
    }

    public function retro(): View
    {
        return $this->renderActivity('retro_trivia');
    }

    public function yokaiMatch(): View
    {
        return $this->renderActivity('yokai_match');
    }

    public function yokaiQuiz(): View
    {
        return $this->renderActivity('yokai_quiz');
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
