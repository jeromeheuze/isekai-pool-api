<?php

namespace Tests\Unit;

use App\Support\KotoAddress;
use PHPUnit\Framework\TestCase;

class KotoAddressTest extends TestCase
{
    public function test_accepts_typical_k1_transparent_length(): void
    {
        $addr = 'k1D5M6eWUdZTpRRqdgUnMBHyqDmBjuNT8Ni';
        $this->assertTrue(KotoAddress::isValid($addr));
    }

    public function test_rejects_too_short(): void
    {
        $this->assertFalse(KotoAddress::isValid('k1'.str_repeat('a', 28)));
    }

    public function test_rejects_wrong_prefix(): void
    {
        $this->assertFalse(KotoAddress::isValid('t1'.str_repeat('a', 32)));
    }

    public function test_accepts_long_jz_style_prefix(): void
    {
        $payload = str_repeat('a', 90);
        $this->assertTrue(KotoAddress::isValid('jz'.$payload));
    }
}
