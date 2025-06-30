<?php

declare(strict_types=1);

namespace Tourze\Blake3\Tests\Unit\Output;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Output\Blake3Output;

class Blake3OutputTest extends TestCase
{
    private function createTestOutput(): Blake3Output
    {
        $inputChainingValue = array_fill(0, 8, 0x12345678);
        $blockWords = array_fill(0, 16, 0x87654321);
        $counter = 0;
        $blockLen = 32;
        $flags = Blake3Constants::ROOT;

        return new Blake3Output($inputChainingValue, $blockWords, $counter, $blockLen, $flags);
    }

    public function testConstructor(): void
    {
        $output = $this->createTestOutput();
        $this->assertInstanceOf(Blake3Output::class, $output);
    }

    public function testChainingValue(): void
    {
        $output = $this->createTestOutput();
        $chainingValue = $output->chaining_value();

        $this->assertCount(8, $chainingValue);
    }

    public function testRootOutputBytes(): void
    {
        $output = $this->createTestOutput();
        
        // 测试默认长度
        $bytes = $output->root_output_bytes(32);
        $this->assertEquals(32, strlen($bytes));

        // 测试自定义长度
        $customLength = 64;
        $bytes = $output->root_output_bytes($customLength);
        $this->assertEquals($customLength, strlen($bytes));
    }

    public function testRootOutputBytesWithZeroLength(): void
    {
        $output = $this->createTestOutput();
        $bytes = $output->root_output_bytes(0);
        $this->assertEquals('', $bytes);
    }

    public function testWriteToStream(): void
    {
        $output = $this->createTestOutput();
        $stream = fopen('php://memory', 'w+');

        $bytesWritten = $output->writeToStream($stream, 32);
        $this->assertEquals(32, $bytesWritten);

        rewind($stream);
        $content = stream_get_contents($stream);
        $this->assertEquals(32, strlen($content));

        fclose($stream);
    }

    public function testWriteToStreamWithZeroLength(): void
    {
        $output = $this->createTestOutput();
        $stream = fopen('php://memory', 'w+');

        $bytesWritten = $output->writeToStream($stream, 0);
        $this->assertEquals(0, $bytesWritten);

        fclose($stream);
    }

    public function testWriteToFile(): void
    {
        $output = $this->createTestOutput();
        $tempFile = tempnam(sys_get_temp_dir(), 'blake3_test_');

        try {
            $bytesWritten = $output->writeToFile($tempFile, 32);
            $this->assertEquals(32, $bytesWritten);

            $content = file_get_contents($tempFile);
            $this->assertEquals(32, strlen($content));
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testWriteToStreamInvalidResource(): void
    {
        $this->expectException(\Tourze\Blake3\Exception\Blake3InvalidArgumentException::class);
        
        $output = $this->createTestOutput();
        $invalidResource = "not a resource";
        // @phpstan-ignore-next-line
        $output->writeToStream($invalidResource, 32);
    }

    public function testWriteToFileInvalidPath(): void
    {
        $this->expectException(\Tourze\Blake3\Exception\Blake3RuntimeException::class);
        
        $output = $this->createTestOutput();
        $output->writeToFile('/invalid/path/that/does/not/exist/file.txt', 32);
    }
}