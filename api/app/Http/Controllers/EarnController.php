<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class EarnController extends Controller
{
    public function index(): View
    {
        return view('earn.index', [
            'activities' => config('faucet.activities'),
            'apiBase' => config('earn.api_base'),
        ]);
    }

    public function shrine(): View
    {
        return view('earn.activity', [
            'title' => 'Daily shrine visit',
            'slug' => 'shrine_visit',
            'intro' => 'Torii animation + Turnstile + claim — full flow coming next.',
            'apiBase' => config('earn.api_base'),
        ]);
    }

    public function kanji(): View
    {
        return view('earn.activity', [
            'title' => 'Kanji quiz',
            'slug' => 'kanji_quiz',
            'intro' => 'Five JLPT-style questions — pass 4/5 to earn. Question bank TBD.',
            'apiBase' => config('earn.api_base'),
        ]);
    }

    public function retro(): View
    {
        return view('earn.activity', [
            'title' => 'Retro game trivia',
            'slug' => 'retro_trivia',
            'intro' => 'Japanese retro games — trivia + links to The 725 Club. Coming soon.',
            'apiBase' => config('earn.api_base'),
        ]);
    }
}
