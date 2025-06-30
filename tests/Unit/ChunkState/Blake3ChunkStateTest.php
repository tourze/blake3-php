<?php

declare(strict_types=1);

namespace Tourze\Blake3\Tests\Unit\ChunkState;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\ChunkState\Blake3ChunkState;
use Tourze\Blake3\Constants\Blake3Constants;

class Blake3ChunkStateTest extends TestCase
{
    public function testConstructor(): void
    {
        $keyWords = array_fill(0, 8, 0x12345678);
        $chunkCounter = 0;
        $flags = Blake3Constants::KEYED_HASH;

        $chunkState = new Blake3ChunkState($keyWords, $chunkCounter, $flags);

        $this->assertEquals(0, $chunkState->len());
        $this->assertEquals($chunkCounter, $chunkState->getChunkCounter());
        $this->assertEquals(Blake3Constants::CHUNK_START, $chunkState->start_flag());
    }

    public function testUpdateAndLen(): void
    {
        $keyWords = array_fill(0, 8, 0);
        $chunkState = new Blake3ChunkState($keyWords, 0, 0);

        $data = "Hello, World!";
        $chunkState->update($data);

        $this->assertEquals(strlen($data), $chunkState->len());
    }

    public function testUpdateWithOffset(): void
    {
        $keyWords = array_fill(0, 8, 0);
        $chunkState = new Blake3ChunkState($keyWords, 0, 0);

        $data = "Hello, World!";
        $offset = 7;
        $length = 5; // "World"
        
        $chunkState->updateWithOffset($data, $offset, $length);

        $this->assertEquals($length, $chunkState->len());
    }

    public function testStartFlag(): void
    {
        $keyWords = array_fill(0, 8, 0);
        $chunkState = new Blake3ChunkState($keyWords, 0, 0);

        // 初始状态应该有CHUNK_START标志
        $this->assertEquals(Blake3Constants::CHUNK_START, $chunkState->start_flag());

        // 更新一些数据但不足一个块
        $chunkState->update(str_repeat('a', 30));
        $this->assertEquals(Blake3Constants::CHUNK_START, $chunkState->start_flag());

        // 更新一个完整块后，start_flag应该为0
        $chunkState->update(str_repeat('b', Blake3Constants::BLOCK_LEN));
        $this->assertEquals(0, $chunkState->start_flag());
    }

    public function testOutput(): void
    {
        $keyWords = array_fill(0, 8, 0);
        $chunkState = new Blake3ChunkState($keyWords, 0, 0);

        $data = "test data";
        $chunkState->update($data);

        $output = $chunkState->output();
        $this->assertInstanceOf(\Tourze\Blake3\Output\Blake3Output::class, $output);
    }

    public function testEmptyUpdate(): void
    {
        $keyWords = array_fill(0, 8, 0);
        $chunkState = new Blake3ChunkState($keyWords, 0, 0);

        $initialLen = $chunkState->len();
        $chunkState->update("");

        $this->assertEquals($initialLen, $chunkState->len());
    }

    public function testUpdateWithOffsetZeroLength(): void
    {
        $keyWords = array_fill(0, 8, 0);
        $chunkState = new Blake3ChunkState($keyWords, 0, 0);

        $initialLen = $chunkState->len();
        $chunkState->updateWithOffset("some data", 0, 0);

        $this->assertEquals($initialLen, $chunkState->len());
    }
}