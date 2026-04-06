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
}
