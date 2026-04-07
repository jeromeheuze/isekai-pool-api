<?php

namespace Tests\Feature;

use Tests\TestCase;

class EarnHubTest extends TestCase
{
    public function test_earn_hub_index_ok(): void
    {
        $this->get('/earn')
            ->assertOk()
            ->assertSee('Earn KOTO', false);
    }

    public function test_earn_activity_pages_ok(): void
    {
        $this->get('/earn/shrine')->assertOk()->assertSee('shrine_visit', false);
        $this->get('/earn/kanji')->assertOk()->assertSee('kanji_quiz', false);
        $this->get('/earn/retro')->assertOk()->assertSee('retro_trivia', false);
        $this->get('/earn/yokai-match')->assertOk()->assertSee('yokai_match', false);
        $this->get('/earn/yokai-quiz')->assertOk()->assertSee('yokai_quiz', false);
        $this->get('/earn/shrine-puzzle')->assertOk()->assertSee('shrine_puzzle', false);
        $this->get('/earn/map-explore')->assertOk()->assertSee('map_explore', false);
        $this->get('/earn/coffee-quiz')->assertOk()->assertSee('coffee_quiz', false);
        $this->get('/earn/daily-bonus')->assertOk()->assertSee('daily_bonus', false);
    }
}
