<?php

namespace Tourze\Blake3\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Blake3;
use Tourze\Blake3\Util\BufferSizeManager;

/**
 * Blake3缓冲区优化测试类
 *
 * 此测试类验证Blake3在不同缓冲区大小配置下的行为与性能
 */
class Blake3BufferOptimizationTest extends TestCase
{
    /**
     * 测试前的准备工作
     */
    protected function setUp(): void
    {
        // 创建临时测试目录
        if (!is_dir(__DIR__ . '/tmp')) {
            mkdir(__DIR__ . '/tmp', 0777, true);
        }
    }

    /**
     * 清理测试文件
     */
    protected function tearDown(): void
    {
        // 清理所有测试生成的临时文件
        $files = glob(__DIR__ . '/tmp/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * 测试根据文件大小自动选择缓冲区大小
     */
    public function testBufferSizeByFileSize(): void
    {
        // 创建测试文件
        $smallFile = __DIR__ . '/tmp/small_file.dat';
        $mediumFile = __DIR__ . '/tmp/medium_file.dat';
        $largeFile = __DIR__ . '/tmp/large_file.dat';

        file_put_contents($smallFile, str_repeat('a', 500 * 1024)); // 500KB
        file_put_contents($mediumFile, str_repeat('b', 5 * 1024 * 1024)); // 5MB
        file_put_contents($largeFile, str_repeat('c', 10 * 1024 * 1024)); // 10MB

        // 使用反射获取自动选择的缓冲区大小
        $reflectionClass = new \ReflectionClass(BufferSizeManager::class);

        // 验证小文件使用较小的缓冲区
        $smallFileSize = filesize($smallFile);
        $smallBuffer = $reflectionClass->getMethod('getOptimalBufferSize')->invoke(null, $smallFileSize);
        $this->assertEquals(BufferSizeManager::BUFFER_SMALL, $smallBuffer, "小文件应使用小缓冲区");

        // 验证中等文件使用默认缓冲区
        $mediumFileSize = filesize($mediumFile);
        $mediumBuffer = $reflectionClass->getMethod('getOptimalBufferSize')->invoke(null, $mediumFileSize);
        $this->assertEquals(BufferSizeManager::BUFFER_DEFAULT, $mediumBuffer, "中等文件应使用默认缓冲区");

        // 验证大文件使用较大的缓冲区
        $largeFileSize = filesize($largeFile);
        $largeBuffer = $reflectionClass->getMethod('getOptimalBufferSize')->invoke(null, $largeFileSize);
        $this->assertEquals(BufferSizeManager::BUFFER_LARGE, $largeBuffer, "大文件应使用大缓冲区");
    }

    /**
     * 测试低内存模式强制使用小缓冲区
     */
    public function testLowMemoryMode(): void
    {
        // 无论文件多大，低内存模式都应使用小缓冲区
        $reflectionClass = new \ReflectionClass(BufferSizeManager::class);

        // 即使是大文件
        $largeFileSize = 100 * 1024 * 1024; // 100MB
        $buffer = $reflectionClass->getMethod('getOptimalBufferSize')->invoke(null, $largeFileSize, true);

        $this->assertEquals(BufferSizeManager::BUFFER_SMALL, $buffer, "低内存模式应始终使用小缓冲区");
    }

    /**
     * 测试不同缓冲区大小下的性能比较
     *
     * 注意：这是一个性能测试，可能需要较长时间运行
     * @group performance
     */
    public function testBufferSizePerformance(): void
    {
        // 创建测试文件 - 使用中等大小以便测试
        $testFile = __DIR__ . '/tmp/performance_test.dat';
        $fileSize = 2 * 1024 * 1024; // 2MB
        file_put_contents($testFile, str_repeat('x', $fileSize));

        // 测试不同的缓冲区大小
        $bufferSizes = [
            'tiny' => BufferSizeManager::BUFFER_TINY,
            'small' => BufferSizeManager::BUFFER_SMALL,
            'default' => BufferSizeManager::BUFFER_DEFAULT,
            'large' => BufferSizeManager::BUFFER_LARGE,
            'huge' => BufferSizeManager::BUFFER_HUGE,
            'auto' => null // 自动选择
        ];

        $results = [];

        foreach ($bufferSizes as $name => $size) {
            // 清除缓存
            clearstatcache();

            $startMemory = memory_get_usage(true);
            $startTime = microtime(true);

            // 执行哈希
            $hash = Blake3::hashFile($testFile, 32, $size);

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $results[$name] = [
                'time' => $endTime - $startTime,
                'memory' => $endMemory - $startMemory,
                'buffer_size' => $size === null ? 'auto' : $size,
                'hash' => $hash
            ];
        }

        // 输出结果比较
        echo "缓冲区大小性能比较：\n";
        echo "--------------------------------------------------------\n";
        echo "缓冲区     | 大小        | 耗时 (秒)   | 内存使用 (KB)\n";
        echo "--------------------------------------------------------\n";

        foreach ($results as $name => $data) {
            echo sprintf(
                "%-12s|%-12s|%-12.4f|%-12.2f\n",
                $name,
                $data['buffer_size'] === 'auto' ? 'auto' : ($data['buffer_size'] / 1024) . 'KB',
                $data['time'],
                $data['memory'] / 1024
            );
        }

        echo "--------------------------------------------------------\n";

        // 不严格断言性能特性，但至少确保所有测试都完成并产生相同的哈希
        $firstHash = null;
        foreach ($results as $result) {
            if ($firstHash === null) {
                $firstHash = $result['hash'];
            } else {
                $this->assertEquals($firstHash, $result['hash'], "不同缓冲区大小应产生相同的哈希值");
            }
        }

        $this->addToAssertionCount(1); // 添加虚拟断言，确保测试被计数
    }

    /**
     * 测试动态缓冲区调整功能
     *
     * @group performance
     */
    public function testDynamicBufferSizeAdjustment(): void
    {
        // 创建一个非常大的测试文件，以便触发动态调整
        $testFile = __DIR__ . '/tmp/dynamic_buffer_test.dat';
        $fileSize = 5 * 1024 * 1024; // 5MB

        $stream = fopen($testFile, 'wb');
        for ($i = 0; $i < $fileSize / (1024 * 1024); $i++) {
            fwrite($stream, str_repeat(chr($i % 256), 1024 * 1024));
        }
        fclose($stream);

        // 使用自动缓冲区大小执行哈希计算
        $hasher = Blake3::newInstance();

        // 记录开始使用的内存和时间
        $startMemory = memory_get_usage(true);
        $startTime = microtime(true);

        // 执行哈希计算
        $hasher->updateFile($testFile);
        $hash = $hasher->finalize();

        // 记录结束时的内存和时间
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        // 输出结果
        echo "动态缓冲区调整测试结果：\n";
        echo "处理时间: " . round($endTime - $startTime, 2) . " 秒\n";
        echo "内存使用: " . round(($endMemory - $startMemory) / (1024 * 1024), 2) . " MB\n";
        echo "平均处理速度: " . round($fileSize / ($endTime - $startTime) / (1024 * 1024), 2) . " MB/s\n";

        // 确保测试完成
        $this->assertEquals(32, strlen($hash), "哈希值长度应为32字节");
    }

    /**
     * 测试内存限制检测功能
     */
    public function testMemoryLimitDetection(): void
    {
        // 获取当前PHP内存限制
        $reflectionClass = new \ReflectionClass(BufferSizeManager::class);
        $memoryLimit = $reflectionClass->getMethod('getMemoryLimitBytes')->invoke(null);

        // 验证内存限制解析正确
        $iniMemoryLimit = ini_get('memory_limit');
        if ($iniMemoryLimit === '-1') {
            $this->assertEquals(-1, $memoryLimit, "无限内存限制应返回-1");
        } else {
            $this->assertGreaterThan(0, $memoryLimit, "内存限制应解析为大于0的值");
        }

        // 测试记忆限制感知的缓冲区大小
        $bufferSize = $reflectionClass->getMethod('getMemoryAwareBufferSize')->invoke(null);
        $this->assertIsInt($bufferSize, "记忆感知缓冲区大小应返回整数");
        $this->assertGreaterThan(0, $bufferSize, "记忆感知缓冲区大小应大于0");
    }
}
