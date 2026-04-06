<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faucet_balance', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 16, 8)->default(0);
            $table->decimal('total_paid', 16, 8)->default(0);
            $table->unsignedInteger('total_claims')->default(0);
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();
        });

        Schema::create('faucet_claims', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->string('wallet_address');
            $table->string('ip_address', 45);
            $table->string('activity_slug', 64);
            $table->string('source_site', 64)->default('isekai-pool');
            $table->string('payout_bucket', 16)->default('routine');
            $table->decimal('amount', 16, 8);
            $table->string('txid')->nullable();
            $table->string('status', 16);
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['wallet_address', 'activity_slug', 'created_at']);
            $table->index(['ip_address', 'activity_slug', 'created_at']);
            $table->index(['wallet_address', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        DB::table('faucet_balance')->insert([
            'id' => 1,
            'balance' => 0,
            'total_paid' => 0,
            'total_claims' => 0,
            'last_sync' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faucet_claims');
        Schema::dropIfExists('faucet_balance');
    }
};
