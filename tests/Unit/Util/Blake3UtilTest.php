<?php

declare(strict_types=1);

namespace Tourze\Blake3\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Util\Blake3Util;

class Blake3UtilTest extends TestCase
{
    public function testMask32(): void
    {
        $this->assertEquals(0xFFFFFFFF, Blake3Util::mask32(0xFFFFFFFF));
        $this->assertEquals(0x12345678, Blake3Util::mask32(0x12345678));
        $this->assertEquals(0x0, Blake3Util::mask32(0x100000000));
        $this->assertEquals(0x1, Blake3Util::mask32(0x100000001));
    }

    public function testAdd32(): void
    {
        $this->assertEquals(0x2, Blake3Util::add32(0x1, 0x1));
        $this->assertEquals(0x0, Blake3Util::add32(0xFFFFFFFF, 0x1));
        $this->assertEquals(0xFFFFFFFE, Blake3Util::add32(0xFFFFFFFF, 0xFFFFFFFF));
    }

    public function testRightrotate32(): void
    {
        // 测试基本旋转
        $this->assertEquals(0x80000000, Blake3Util::rightrotate32(0x1, 1));
        $this->assertEquals(0x1, Blake3Util::rightrotate32(0x1, 0));
        $this->assertEquals(0x1, Blake3Util::rightrotate32(0x1, 32));
        
        // 测试已知值
        $this->assertEquals(0x12345678, Blake3Util::rightrotate32(0x12345678, 0));
        $this->assertEquals(0x091A2B3C, Blake3Util::rightrotate32(0x12345678, 1));
    }

    public function testWordsFromLittleEndianBytes(): void
    {
        $bytes = "\x78\x56\x34\x12\x21\x43\x65\x87";
        $words = Blake3Util::words_from_little_endian_bytes($bytes);
        
        $this->assertEquals([0x12345678, 0x87654321], $words);
    }

    public function testWordsFromLittleEndianBytesEmpty(): void
    {
        $words = Blake3Util::words_from_little_endian_bytes("");
        $this->assertEquals([], $words);
    }

    public function testWordsFromLittleEndianBytesLongInput(): void
    {
        // Test with multiple 4-byte aligned input
        $bytes = "\x78\x56\x34\x12\x21\x43\x65\x87\xAB\xCD\xEF\x01";
        $words = Blake3Util::words_from_little_endian_bytes($bytes);
        
        $this->assertEquals([0x12345678, 0x87654321, 0x01EFCDAB], $words);
    }

    public function testG(): void
    {
        $state = array_fill(0, 16, 0);
        $state[0] = 0x12345678;
        $state[1] = 0x87654321;
        $state[2] = 0xABCDEF01;
        $state[3] = 0x23456789;

        // 调用g函数
        Blake3Util::g($state, 0, 1, 2, 3, 0x11111111, 0x22222222);

        // 验证状态被修改
        $this->assertNotEquals(0x12345678, $state[0]);
        $this->assertNotEquals(0x87654321, $state[1]);
        $this->assertNotEquals(0xABCDEF01, $state[2]);
        $this->assertNotEquals(0x23456789, $state[3]);
    }

    public function testCompress(): void
    {
        $chainingValue = array_fill(0, 8, 0x12345678);
        $blockWords = array_fill(0, 16, 0x87654321);
        $counter = 0;
        $blockLen = 64;
        $flags = 0;

        $result = Blake3Util::compress($chainingValue, $blockWords, $counter, $blockLen, $flags);

        $this->assertCount(16, $result);
        
        // 验证结果是32位整数
        foreach ($result as $word) {
            $this->assertIsInt($word);
            $this->assertGreaterThanOrEqual(0, $word);
            $this->assertLessThanOrEqual(0xFFFFFFFF, $word);
        }
    }

    public function testParentOutput(): void
    {
        $leftChildCV = array_fill(0, 8, 0x12345678);
        $rightChildCV = array_fill(0, 8, 0x87654321);
        $keyWords = array_fill(0, 8, 0xABCDEF01);
        $flags = 0;

        $output = Blake3Util::parent_output($leftChildCV, $rightChildCV, $keyWords, $flags);

        $this->assertInstanceOf(\Tourze\Blake3\Output\Blake3Output::class, $output);
    }
}