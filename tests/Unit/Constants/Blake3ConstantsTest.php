<?php

declare(strict_types=1);

namespace Tourze\Blake3\Tests\Unit\Constants;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Constants\Blake3Constants;

class Blake3ConstantsTest extends TestCase
{
    public function testOutLen(): void
    {
        $this->assertEquals(32, Blake3Constants::OUT_LEN);
    }

    public function testKeyLen(): void
    {
        $this->assertEquals(32, Blake3Constants::KEY_LEN);
    }

    public function testBlockLen(): void
    {
        $this->assertEquals(64, Blake3Constants::BLOCK_LEN);
    }

    public function testChunkLen(): void
    {
        $this->assertEquals(1024, Blake3Constants::CHUNK_LEN);
    }

    public function testFlags(): void
    {
        $this->assertEquals(1, Blake3Constants::CHUNK_START);
        $this->assertEquals(2, Blake3Constants::CHUNK_END);
        $this->assertEquals(4, Blake3Constants::PARENT);
        $this->assertEquals(8, Blake3Constants::ROOT);
        $this->assertEquals(16, Blake3Constants::KEYED_HASH);
        $this->assertEquals(32, Blake3Constants::DERIVE_KEY_CONTEXT);
        $this->assertEquals(64, Blake3Constants::DERIVE_KEY_MATERIAL);
    }

    public function testInitialVector(): void
    {
        $expectedIV = [
            0x6A09E667,
            0xBB67AE85,
            0x3C6EF372,
            0xA54FF53A,
            0x510E527F,
            0x9B05688C,
            0x1F83D9AB,
            0x5BE0CD19
        ];

        $this->assertEquals($expectedIV, Blake3Constants::IV);
        $this->assertCount(8, Blake3Constants::IV);
    }

    public function testMessagePermutation(): void
    {
        $expectedPermutation = [2, 6, 3, 10, 7, 0, 4, 13, 1, 11, 12, 5, 9, 14, 15, 8];

        $this->assertEquals($expectedPermutation, Blake3Constants::MSG_PERMUTATION);
        $this->assertCount(16, Blake3Constants::MSG_PERMUTATION);
    }

    public function testFlagsAreUnique(): void
    {
        $flags = [
            Blake3Constants::CHUNK_START,
            Blake3Constants::CHUNK_END,
            Blake3Constants::PARENT,
            Blake3Constants::ROOT,
            Blake3Constants::KEYED_HASH,
            Blake3Constants::DERIVE_KEY_CONTEXT,
            Blake3Constants::DERIVE_KEY_MATERIAL,
        ];

        $this->assertEquals(count($flags), count(array_unique($flags)));
    }

    public function testFlagsArePowersOfTwo(): void
    {
        $flags = [
            Blake3Constants::CHUNK_START,
            Blake3Constants::CHUNK_END,
            Blake3Constants::PARENT,
            Blake3Constants::ROOT,
            Blake3Constants::KEYED_HASH,
            Blake3Constants::DERIVE_KEY_CONTEXT,
            Blake3Constants::DERIVE_KEY_MATERIAL,
        ];

        foreach ($flags as $flag) {
            $this->assertTrue(($flag & ($flag - 1)) === 0, "Flag $flag is not a power of 2");
        }
    }
}