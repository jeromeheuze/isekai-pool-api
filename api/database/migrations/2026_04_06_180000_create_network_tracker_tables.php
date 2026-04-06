<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('coin', 10);
            $table->timestamp('captured_at');
            $table->unsignedBigInteger('block_height');
            $table->decimal('network_hashrate', 24, 4);
            $table->decimal('difficulty', 24, 8);
            $table->unsignedInteger('network_connections')->default(0);
            $table->timestamps();

            $table->index(['coin', 'captured_at']);
        });

        Schema::create('pool_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('coin', 10);
            $table->string('pool_slug', 64);
            $table->string('pool_name');
            $table->string('pool_url')->nullable();
            $table->timestamp('captured_at');
            $table->decimal('hashrate', 24, 4);
            $table->unsignedInteger('miners')->default(0);
            $table->unsignedInteger('workers')->default(0);
            $table->unsignedBigInteger('blocks_found')->default(0);
            $table->decimal('pool_fee', 7, 2)->nullable();
            $table->boolean('is_online')->default(true);
            $table->timestamps();

            $table->index(['coin', 'pool_slug', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_snapshots');
        Schema::dropIfExists('network_snapshots');
    }
};
