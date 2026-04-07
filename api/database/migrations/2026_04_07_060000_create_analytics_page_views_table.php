<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_page_views', function (Blueprint $table) {
            $table->id();
            $table->date('event_date');
            $table->string('host', 120);
            $table->string('path', 255);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['event_date', 'host', 'path'], 'analytics_page_views_daily_unique');
            $table->index(['event_date', 'views']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_page_views');
    }
};
