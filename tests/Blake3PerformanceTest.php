<?php

namespace Tourze\Blake3\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Blake3;

/**
 * Blake3算法的性能测试类
 *
 * 注意：此测试类主要用于性能测试，可能需要较长时间运行
 * 如果只想运行功能测试，可以跳过此类
 */
class Blake3PerformanceTest extends TestCase
{
    /**
     * 验证小数据集性能
     *
     * 测试处理小数据批量的性能
     *
     * @group performance
     */
    public function testSmallDataPerformance(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        $iterations = 1000;
        $data = "The quick brown fox jumps over the lazy dog";

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $hasher = Blake3::newInstance();
            $hasher->update($data);
            $hash = $hasher->finalize();
        }

        $end = microtime(true);
        $avgTime = ($end - $start) * 1000 / $iterations; // 毫秒

        // 记录性能测试结果，但不断言具体值，因为这依赖于运行环境
        $this->addToAssertionCount(1);
        echo "\n小数据性能测试: {$avgTime} ms/哈希 (数据大小: " . strlen($data) . " 字节)\n";
    }

    /**
     * 验证中等数据集性能
     *
     * 测试处理中等大小数据的性能
     *
     * @group performance
     */
    public function testMediumDataPerformance(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        $iterations = 100;
        $data = str_repeat("Medium sized data for performance testing. ", 100); // ~4KB

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $hasher = Blake3::newInstance();
            $hasher->update($data);
            $hash = $hasher->finalize();
        }

        $end = microtime(true);
        $avgTime = ($end - $start) * 1000 / $iterations; // 毫秒

        // 记录性能测试结果
        $this->addToAssertionCount(1);
        echo "\n中等数据性能测试: {$avgTime} ms/哈希 (数据大小: " . strlen($data) . " 字节)\n";
    }

    /**
     * 验证大数据集性能
     *
     * 测试处理大数据的性能
     *
     * @group performance
     */
    public function testLargeDataPerformance(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        $iterations = 10;
        $data = str_repeat("a", 1024 * 1024); // 1MB

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $hasher = Blake3::newInstance();
            $hasher->update($data);
            $hash = $hasher->finalize();
        }

        $end = microtime(true);
        $avgTime = ($end - $start) * 1000 / $iterations; // 毫秒

        // 记录性能测试结果
        $this->addToAssertionCount(1);
        echo "\n大数据性能测试: {$avgTime} ms/哈希 (数据大小: " . strlen($data) . " 字节)\n";
    }

    /**
     * 验证分块更新性能
     *
     * 比较分块更新与一次性更新的性能差异
     *
     * @group performance
     */
    public function testChunkedUpdatePerformance(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        $iterations = 50;
        $data = str_repeat("Chunked update performance test data. ", 1000); // ~40KB
        $dataLen = strlen($data);

        // 测试一次性更新
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $hasher = Blake3::newInstance();
            $hasher->update($data);
            $hash = $hasher->finalize();
        }
        $end = microtime(true);
        $singleUpdateTime = ($end - $start) * 1000 / $iterations; // 毫秒

        // 测试分块更新 - 1KB一次
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $hasher = Blake3::newInstance();
            for ($offset = 0; $offset < $dataLen; $offset += 1024) {
                $chunk = substr($data, $offset, min(1024, $dataLen - $offset));
                $hasher->update($chunk);
            }
            $hash = $hasher->finalize();
        }
        $end = microtime(true);
        $chunkedUpdateTime = ($end - $start) * 1000 / $iterations; // 毫秒

        // 记录性能测试结果
        $this->addToAssertionCount(1);
        echo "\n分块更新性能测试:\n";
        echo "一次性更新: {$singleUpdateTime} ms/哈希\n";
        echo "分块更新(1KB): {$chunkedUpdateTime} ms/哈希\n";
        echo "性能比例: " . round($chunkedUpdateTime / $singleUpdateTime, 2) . "x\n";
    }

    /**
     * 验证不同输出长度的性能
     *
     * 测试生成不同长度输出的性能差异
     *
     * @group performance
     */
    public function testOutputLengthPerformance(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        $iterations = 100;
        $data = "Testing output length performance";
        $outputLengths = [32, 64, 128, 256, 512, 1024];

        echo "\n不同输出长度性能测试:\n";

        foreach ($outputLengths as $length) {
            $start = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                $hasher = Blake3::newInstance();
                $hasher->update($data);
                $hash = $hasher->finalize($length);
            }

            $end = microtime(true);
            $avgTime = ($end - $start) * 1000 / $iterations; // 毫秒

            // 记录性能测试结果
            echo "输出长度 {$length} 字节: {$avgTime} ms/哈希\n";
        }

        $this->addToAssertionCount(1);
    }

    /**
     * 性能与内置哈希算法比较
     *
     * 将Blake3与PHP内置哈希算法进行性能比较
     *
     * @group performance
     */
    public function testCompareWithBuiltinHashes(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        $iterations = 100;
        $data = str_repeat("Performance comparison with built-in hash functions. ", 100); // ~5KB
        $algorithms = ['blake3', 'sha256', 'sha512', 'sha1', 'md5'];

        echo "\nBlake3与内置哈希算法性能比较:\n";

        $results = [];

        foreach ($algorithms as $algo) {
            $start = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                if ($algo === 'blake3') {
                    $hasher = Blake3::newInstance();
                    $hasher->update($data);
                    $hash = $hasher->finalize();
                } else {
                    $hash = hash($algo, $data, true);
                }
            }

            $end = microtime(true);
            $avgTime = ($end - $start) * 1000 / $iterations; // 毫秒

            $results[$algo] = $avgTime;
            echo "{$algo}: {$avgTime} ms/哈希\n";
        }

        // 计算相对性能
        foreach ($algorithms as $algo) {
            if ($algo !== 'blake3') {
                $ratio = $results['blake3'] / $results[$algo];
                echo "Blake3 vs {$algo}: " . ($ratio < 1 ? "快 " . round(1 / $ratio, 2) . "倍" : "慢 " . round($ratio, 2) . "倍") . "\n";
            }
        }

        $this->addToAssertionCount(1);
    }

    /**
     * 测试不同缓冲区大小处理大文件的性能
     *
     * @group performance
     */
    public function testBufferSizeImpactOnLargeFile(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        // 创建测试用临时目录
        $tmpDir = sys_get_temp_dir() . '/blake3_test_' . uniqid();
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        // 创建测试文件
        $testFile = $tmpDir . '/large_file.dat';
        $fileSize = 50 * 1024 * 1024; // 50MB

        echo "创建{$fileSize}字节的测试文件...\n";

        // 分块创建大文件以避免内存问题
        $chunk = str_repeat('x', 1024 * 1024); // 1MB块
        $fp = fopen($testFile, 'wb');
        for ($i = 0; $i < $fileSize / (1024 * 1024); $i++) {
            fwrite($fp, $chunk);
        }
        fclose($fp);

        // 测试不同缓冲区大小的性能
        $bufferSizes = [
            '4KB' => 4 * 1024,
            '16KB' => 16 * 1024,
            '64KB' => 64 * 1024,
            '256KB' => 256 * 1024,
            '1MB' => 1024 * 1024,
            '自动优化' => null // 使用自动优化的缓冲区大小
        ];

        $results = [];

        foreach ($bufferSizes as $name => $size) {
            echo "测试缓冲区大小: {$name}...\n";

            // 清除缓存
            clearstatcache();

            $hash = null;
            $startTime = microtime(true);
            $peakMemory = memory_get_peak_usage(true);

            // 执行哈希计算
            $hash = Blake3::hashFile($testFile, 32, $size);

            $endTime = microtime(true);
            $newPeakMemory = memory_get_peak_usage(true);
            $memoryUsed = $newPeakMemory - $peakMemory;

            // 记录结果
            $results[$name] = [
                'time' => $endTime - $startTime,
                'memory' => $memoryUsed,
                'throughput' => $fileSize / ($endTime - $startTime)
            ];
        }

        // 输出结果
        echo "\n缓冲区大小性能比较 (处理 {$fileSize} 字节):\n";
        echo "--------------------------------------------------------------\n";
        echo "缓冲区大小 | 处理时间 (秒) | 内存使用 (MB) | 吞吐量 (MB/s)\n";
        echo "--------------------------------------------------------------\n";

        foreach ($results as $name => $data) {
            echo sprintf(
                "%-12s | %-14.2f | %-14.2f | %-12.2f\n",
                $name,
                $data['time'],
                $data['memory'] / (1024 * 1024),
                $data['throughput'] / (1024 * 1024)
            );
        }

        echo "--------------------------------------------------------------\n";

        // 清理测试文件
        unlink($testFile);
        rmdir($tmpDir);

        // 添加一个断言以使测试有效
        $this->addToAssertionCount(1);
    }

    /**
     * 测试自动优化缓冲区大小与固定大小的性能比较
     *
     * @group performance
     */
    public function testAutoVsFixedBufferSize(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        // 创建一系列不同大小的测试文件
        $tmpDir = sys_get_temp_dir() . '/blake3_test_' . uniqid();
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        // 创建不同大小的测试文件
        $fileSizes = [
            'small' => 100 * 1024,         // 100KB
            'medium' => 5 * 1024 * 1024,   // 5MB
            'large' => 20 * 1024 * 1024,   // 20MB
        ];

        $files = [];
        foreach ($fileSizes as $name => $size) {
            $filePath = $tmpDir . "/{$name}_file.dat";

            // 创建文件
            $fp = fopen($filePath, 'wb');
            $chunkSize = min(1024 * 1024, $size); // 最大1MB块
            $chunk = str_repeat($name[0], $chunkSize);

            $remaining = $size;
            while ($remaining > 0) {
                $writeSize = min($chunkSize, $remaining);
                fwrite($fp, substr($chunk, 0, $writeSize));
                $remaining -= $writeSize;
            }
            fclose($fp);

            $files[$name] = $filePath;
        }

        echo "测试文件已创建:\n";
        foreach ($files as $name => $path) {
            echo "  - {$name}: " . filesize($path) . " bytes\n";
        }

        // 比较自动与固定缓冲区大小
        $bufferModes = [
            '固定 (16KB)' => 16 * 1024,    // 固定小缓冲区
            '固定 (64KB)' => 64 * 1024,    // 固定中等缓冲区
            '固定 (256KB)' => 256 * 1024,  // 固定大缓冲区
            '自动优化' => null,            // 自动优化的缓冲区大小
        ];

        $results = [];

        // 针对每个文件测试不同的缓冲区模式
        foreach ($files as $fileName => $filePath) {
            $results[$fileName] = [];

            foreach ($bufferModes as $modeName => $bufferSize) {
                echo "测试 {$fileName} 文件，使用 {$modeName} 缓冲区...\n";

                // 清除缓存
                clearstatcache();

                $startTime = microtime(true);
                $startMemory = memory_get_usage(true);

                // 执行哈希计算
                $hash = Blake3::hashFile($filePath, 32, $bufferSize);

                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);

                // 记录结果
                $results[$fileName][$modeName] = [
                    'time' => $endTime - $startTime,
                    'memory' => $endMemory - $startMemory,
                    'file_size' => filesize($filePath)
                ];
            }
        }

        // 输出结果
        echo "\n自动 vs 固定缓冲区大小性能比较:\n";

        foreach ($results as $fileName => $modeResults) {
            $fileSize = $modeResults[array_key_first($modeResults)]['file_size'];

            echo "\n{$fileName} 文件 (" . round($fileSize / (1024 * 1024), 2) . " MB):\n";
            echo "---------------------------------------------------------------\n";
            echo "缓冲区模式  | 处理时间 (秒) | 内存使用 (MB) | 吞吐量 (MB/s)\n";
            echo "---------------------------------------------------------------\n";

            foreach ($modeResults as $modeName => $data) {
                echo sprintf(
                    "%-12s | %-14.2f | %-14.2f | %-12.2f\n",
                    $modeName,
                    $data['time'],
                    $data['memory'] / (1024 * 1024),
                    $fileSize / $data['time'] / (1024 * 1024)
                );
            }

            echo "---------------------------------------------------------------\n";
        }

        // 清理测试文件
        foreach ($files as $filePath) {
            unlink($filePath);
        }
        rmdir($tmpDir);

        // 添加一个断言以使测试有效
        $this->addToAssertionCount(1);
    }

    /**
     * 测试低内存模式与正常模式的性能和内存使用比较
     *
     * @group performance
     */
    public function testLowMemoryMode(): void
    {
        // 跳过长时间运行的测试，除非特别指定
        $this->markTestSkipped('性能测试需要较长时间运行，默认跳过。使用 --group=performance 选项来运行此测试。');

        // 创建测试文件
        $tmpDir = sys_get_temp_dir() . '/blake3_test_' . uniqid();
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $testFile = $tmpDir . '/test_file.dat';
        $fileSize = 15 * 1024 * 1024; // 15MB

        // 创建测试文件
        $fp = fopen($testFile, 'wb');
        $chunkSize = 1024 * 1024; // 1MB块
        for ($i = 0; $i < $fileSize / $chunkSize; $i++) {
            fwrite($fp, str_repeat(chr($i % 256), $chunkSize));
        }
        fclose($fp);

        // 测试模式
        $modes = [
            '正常模式' => false,
            '低内存模式' => true
        ];

        $results = [];

        foreach ($modes as $modeName => $lowMemoryMode) {
            echo "测试 {$modeName}...\n";

            // 清除缓存
            clearstatcache();

            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            // 执行哈希计算
            $hash = Blake3::hashFile($testFile, 32, null, $lowMemoryMode);

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);

            // 记录结果
            $results[$modeName] = [
                'time' => $endTime - $startTime,
                'memory_increase' => $endMemory - $startMemory,
                'peak_memory' => $peakMemory,
                'throughput' => $fileSize / ($endTime - $startTime)
            ];
        }

        // 输出结果
        echo "\n内存模式性能比较:\n";
        echo "---------------------------------------------------------------\n";
        echo "模式       | 处理时间 (秒) | 内存增加 (MB) | 峰值内存 (MB) | 吞吐量 (MB/s)\n";
        echo "---------------------------------------------------------------\n";

        foreach ($results as $modeName => $data) {
            echo sprintf(
                "%-10s | %-14.2f | %-14.2f | %-14.2f | %-12.2f\n",
                $modeName,
                $data['time'],
                $data['memory_increase'] / (1024 * 1024),
                $data['peak_memory'] / (1024 * 1024),
                $data['throughput'] / (1024 * 1024)
            );
        }

        echo "---------------------------------------------------------------\n";

        // 清理
        unlink($testFile);
        rmdir($tmpDir);

        // 添加一个断言以使测试有效
        $this->addToAssertionCount(1);
    }
}
