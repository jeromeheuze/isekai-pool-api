<?php

namespace Tests\Unit;

use App\Support\ExplorerMiner;
use PHPUnit\Framework\TestCase;

class ExplorerMinerTest extends TestCase
{
    public function test_detects_isekai_in_coinbase(): void
    {
        $hex = bin2hex('...isekai-pool.com...');

        $this->assertSame('isekai-pool.com', ExplorerMiner::labelFromCoinbaseHex($hex));
    }

    public function test_unknown_when_empty(): void
    {
        $this->assertSame('Unknown', ExplorerMiner::labelFromCoinbaseHex(''));
    }

    public function test_detects_mofumofu_case_insensitive(): void
    {
        $hex = bin2hex('POOL MofuMofu stratum');

        $this->assertSame('mofumofu.me', ExplorerMiner::labelFromCoinbaseHex($hex));
    }

    public function test_detects_isekai_case_insensitive(): void
    {
        $hex = bin2hex('ISEKAI-POOL.COM worker');

        $this->assertSame('isekai-pool.com', ExplorerMiner::labelFromCoinbaseHex($hex));
    }

    public function test_label_from_script_sig_hex_when_coinbase_key_missing(): void
    {
        $payload = bin2hex('...isekai-pool.com...');
        $tx = [
            'vin' => [
                [
                    'scriptSig' => ['hex' => $payload],
                ],
            ],
        ];

        $this->assertSame('isekai-pool.com', ExplorerMiner::labelFromTransaction($tx));
    }
}
