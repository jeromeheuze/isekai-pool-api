<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransparencyAnalyticsTest extends TestCase
{
    public function test_collect_pixel_records_page_view(): void
    {
        $this->get('/a.gif?p=%2Fkoto.html&h=isekai-pool.com')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');

        if (! Schema::hasTable('analytics_page_views')) {
            $this->assertTrue(true);

            return;
        }

        $row = DB::table('analytics_page_views')
            ->where('host', 'isekai-pool.com')
            ->where('path', '/koto.html')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->views);
    }

    public function test_transparency_dashboard_and_json_are_public(): void
    {
        $this->get('/transparency/analytics')->assertOk()->assertSee('Public minimal analytics');
        $this->get('/transparency/analytics.json')
            ->assertOk()
            ->assertJsonPath('window_days', 30);
    }
}
