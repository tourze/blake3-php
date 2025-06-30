<?php

declare(strict_types=1);

namespace Tourze\Blake3\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Util\BufferSizeManager;

class BufferSizeManagerTest extends TestCase
{
    public function testBufferConstants(): void
    {
        $this->assertEquals(4 * 1024, BufferSizeManager::BUFFER_TINY);
        $this->assertEquals(16 * 1024, BufferSizeManager::BUFFER_SMALL);
        $this->assertEquals(64 * 1024, BufferSizeManager::BUFFER_DEFAULT);
        $this->assertEquals(256 * 1024, BufferSizeManager::BUFFER_LARGE);
        $this->assertEquals(1024 * 1024, BufferSizeManager::BUFFER_HUGE);
    }

    public function testGetOptimalBufferSizeWithLowMemory(): void
    {
        $size = BufferSizeManager::getOptimalBufferSize(null, true);
        $this->assertEquals(BufferSizeManager::BUFFER_SMALL, $size);

        $size = BufferSizeManager::getOptimalBufferSize(100 * 1024 * 1024, true);
        $this->assertEquals(BufferSizeManager::BUFFER_SMALL, $size);
    }

    public function testGetOptimalBufferSizeWithUnknownFileSize(): void
    {
        $size = BufferSizeManager::getOptimalBufferSize(null, false);
        $this->assertEquals(BufferSizeManager::BUFFER_DEFAULT, $size);
    }

    public function testGetOptimalBufferSizeWithSmallFile(): void
    {
        $size = BufferSizeManager::getOptimalBufferSize(500 * 1024, false); // 500KB
        $this->assertEquals(BufferSizeManager::BUFFER_SMALL, $size);
    }

    public function testGetOptimalBufferSizeWithMediumFile(): void
    {
        $size = BufferSizeManager::getOptimalBufferSize(5 * 1024 * 1024, false); // 5MB
        $this->assertEquals(BufferSizeManager::BUFFER_DEFAULT, $size);
    }

    public function testGetOptimalBufferSizeWithLargeFile(): void
    {
        $size = BufferSizeManager::getOptimalBufferSize(50 * 1024 * 1024, false); // 50MB
        $this->assertEquals(BufferSizeManager::BUFFER_LARGE, $size);
    }

    public function testGetOptimalBufferSizeWithHugeFile(): void
    {
        $size = BufferSizeManager::getOptimalBufferSize(200 * 1024 * 1024, false); // 200MB
        $this->assertEquals(BufferSizeManager::BUFFER_HUGE, $size);
    }

    public function testGetMemoryAwareBufferSize(): void
    {
        $size = BufferSizeManager::getMemoryAwareBufferSize();
        
        $this->assertGreaterThanOrEqual(BufferSizeManager::BUFFER_TINY, $size);
        $this->assertLessThanOrEqual(BufferSizeManager::BUFFER_LARGE, $size);
    }

    public function testGetDynamicBufferSizeWithZeroTime(): void
    {
        $initialSize = BufferSizeManager::BUFFER_DEFAULT;
        $size = BufferSizeManager::getDynamicBufferSize($initialSize, 1000, 0);
        
        $this->assertEquals($initialSize, $size);
    }

    public function testGetDynamicBufferSizeWithNormalProcessing(): void
    {
        $initialSize = BufferSizeManager::BUFFER_DEFAULT;
        $processedBytes = 100 * 1024; // 100KB
        $processingTime = 1.0; // 1 second
        
        $size = BufferSizeManager::getDynamicBufferSize($initialSize, $processedBytes, $processingTime);
        
        $this->assertGreaterThanOrEqual(BufferSizeManager::BUFFER_TINY, $size);
        $this->assertLessThanOrEqual(BufferSizeManager::BUFFER_HUGE, $size);
    }

    public function testGetDynamicBufferSizeWithSlowProcessing(): void
    {
        $initialSize = BufferSizeManager::BUFFER_DEFAULT;
        $processedBytes = 1024; // 1KB
        $processingTime = 1.0; // 1 second (very slow)
        
        $size = BufferSizeManager::getDynamicBufferSize($initialSize, $processedBytes, $processingTime);
        
        // Should use minimum buffer size for slow processing
        $this->assertEquals(BufferSizeManager::BUFFER_TINY, $size);
    }

    public function testGetDynamicBufferSizeWithFastProcessing(): void
    {
        $initialSize = BufferSizeManager::BUFFER_DEFAULT;
        $processedBytes = 10 * 1024 * 1024; // 10MB
        $processingTime = 0.1; // 0.1 second (very fast)
        
        $size = BufferSizeManager::getDynamicBufferSize($initialSize, $processedBytes, $processingTime);
        
        // Should use larger buffer size for fast processing
        $this->assertGreaterThan(BufferSizeManager::BUFFER_DEFAULT, $size);
    }

    public function testGetDynamicBufferSizeStability(): void
    {
        $initialSize = BufferSizeManager::BUFFER_DEFAULT;
        $processedBytes = 60 * 1024; // About what would be processed to maintain same buffer size
        $processingTime = 0.1;
        
        $size = BufferSizeManager::getDynamicBufferSize($initialSize, $processedBytes, $processingTime);
        
        // Should maintain initial size if change is not significant
        $this->assertEquals($initialSize, $size);
    }
}