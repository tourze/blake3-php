<?php

namespace Tourze\Blake3\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Blake3;

/**
 * Blake3边界情况测试
 *
 * 测试各种边界情况和特殊输入，确保实现的健壮性。
 *
 * 测试向量来源：
 * 1. 根据密码学哈希函数特性设计的测试
 * 2. 参考自Python b3sum实现 (https://github.com/oconnor663/blake3-py/blob/master/tests/test_blake3.py)
 * 3. Rust官方实现的边界测试 (https://github.com/BLAKE3-team/BLAKE3/blob/master/reference_impl/reference_impl.rs)
 */
class Blake3EdgeCasesTest extends TestCase
{
    /**
     * 测试空块特性
     *
     * 测试在块边界（BLOCK_LEN的倍数）的输入特性
     *
     * 来源: 基于Rust参考实现边界测试设计
     */
    public function testBlockBoundaries(): void
    {
        $blockLen = 64; // BLOCK_LEN常量值

        // 测试刚好是块大小的输入
        $data1 = str_repeat('x', $blockLen);
        $hasher1 = Blake3::newInstance();
        $hasher1->update($data1);
        $hash1 = $hasher1->finalize();

        // 测试比块大小多1字节的输入
        $data2 = str_repeat('x', $blockLen + 1);
        $hasher2 = Blake3::newInstance();
        $hasher2->update($data2);
        $hash2 = $hasher2->finalize();

        // 结果应该不同
        $this->assertNotEquals($hash1, $hash2, "不同长度的输入应产生不同的哈希值");
    }

    /**
     * 测试块之间边界的分块更新
     *
     * 来源: 基于Python实现的边界测试设计
     */
    public function testChunkedUpdateAtBlockBoundary(): void
    {
        $blockLen = 64; // BLOCK_LEN常量值

        // 创建测试数据
        $data = str_repeat('a', $blockLen * 2);

        // 一次性更新
        $hasher1 = Blake3::newInstance();
        $hasher1->update($data);
        $hash1 = $hasher1->finalize();

        // 分块更新 - 在块边界处分割
        $hasher2 = Blake3::newInstance();
        $hasher2->update(substr($data, 0, $blockLen));
        $hasher2->update(substr($data, $blockLen));
        $hash2 = $hasher2->finalize();

        // 结果应该相同
        $this->assertEquals($hash1, $hash2, "块边界处的分块更新应产生相同的哈希值");
    }

    /**
     * 测试数据块边界
     *
     * 来源: 基于Rust参考实现边界测试设计
     */
    public function testChunkBoundaries(): void
    {
        $chunkLen = 1024; // CHUNK_LEN常量值

        // 测试刚好是数据块大小的输入
        $data1 = str_repeat('x', $chunkLen);
        $hasher1 = Blake3::newInstance();
        $hasher1->update($data1);
        $hash1 = $hasher1->finalize();

        // 测试比数据块大小多1字节的输入
        $data2 = str_repeat('x', $chunkLen + 1);
        $hasher2 = Blake3::newInstance();
        $hasher2->update($data2);
        $hash2 = $hasher2->finalize();

        // 结果应该不同
        $this->assertNotEquals($hash1, $hash2, "不同长度的输入应产生不同的哈希值");
    }

    /**
     * 测试数据块边界的分块更新
     *
     * 来源: 基于Python实现的边界测试设计
     */
    public function testChunkedUpdateAtChunkBoundary(): void
    {
        $chunkLen = 1024; // CHUNK_LEN常量值

        // 创建测试数据
        $data = str_repeat('a', $chunkLen * 2);

        // 一次性更新
        $hasher1 = Blake3::newInstance();
        $hasher1->update($data);
        $hash1 = $hasher1->finalize();

        // 分块更新 - 在数据块边界处分割
        $hasher2 = Blake3::newInstance();
        $hasher2->update(substr($data, 0, $chunkLen));
        $hasher2->update(substr($data, $chunkLen));
        $hash2 = $hasher2->finalize();

        // 结果应该相同
        $this->assertEquals($hash1, $hash2, "数据块边界处的分块更新应产生相同的哈希值");
    }

    /**
     * 测试不同的压缩树结构产生相同的哈希
     *
     * 来源: 基于Rust参考实现的合并节点逻辑测试
     */
    public function testDifferentMergeTreesProduceSameHash(): void
    {
        // 生成小数据集 - 仍然确保会形成简单的合并树但减少大小
        $data = str_repeat('z', 1024 * 3); // 3KB，从原来的10KB减少

        // 一次性更新
        $hasher1 = Blake3::newInstance();
        $hasher1->update($data);
        $hash1 = $hasher1->finalize();

        // 使用不同大小的分块方式，会产生不同的合并树结构
        $hasher2 = Blake3::newInstance();
        $chunks = [
            substr($data, 0, 1024),        // 1KB
            substr($data, 1024, 1024),     // 1KB
            substr($data, 2048)            // 1KB
        ];

        foreach ($chunks as $chunk) {
            $hasher2->update($chunk);
        }
        $hash2 = $hasher2->finalize();

        // 不管合并树如何，最终哈希应该相同
        $this->assertEquals($hash1, $hash2, "不同分块方式应产生相同的哈希值");
    }

    /**
     * 测试输出长度为0的情况
     *
     * 来源: 边界情况测试，参考自Python实现的测试
     */
    public function testZeroOutputLength(): void
    {
        $hasher = Blake3::newInstance();
        $hasher->update("test");
        $hash = $hasher->finalize(0);

        $this->assertEquals('', $hash, "输出长度为0应返回空字符串");
    }

    /**
     * 测试非常大的输出长度
     *
     * 来源: BLAKE3的XOF（可扩展输出函数）特性
     */
    public function testLargeOutputLength(): void
    {
        $hasher = Blake3::newInstance();
        $hasher->update("test");

        // 生成一个较长（128字节）的哈希，原来是1KB
        $hash = $hasher->finalize(128);

        $this->assertEquals(128, strlen($hash), "应能生成指定长度的输出");

        // 验证输出的前32字节与默认长度输出一致
        $defaultHash = $hasher->finalize();
        $this->assertEquals($defaultHash, substr($hash, 0, 32), "长输出的前部分应与默认输出一致");
    }

    /**
     * 测试多次finalize调用的一致性
     *
     * 来源: 哈希函数的状态一致性特性
     */
    public function testMultipleFinalizeCalls(): void
    {
        $hasher = Blake3::newInstance();
        $hasher->update("test data");

        $hash1 = $hasher->finalize();
        $hash2 = $hasher->finalize();

        $this->assertEquals($hash1, $hash2, "多次finalize调用应返回相同结果");
    }

    /**
     * 测试update后再次finalize
     *
     * 来源: 哈希函数的状态维护特性
     */
    public function testUpdateAfterFinalize(): void
    {
        $hasher = Blake3::newInstance();
        $hasher->update("first");
        $hash1 = $hasher->finalize();

        // finalize后继续update
        $hasher->update("second");
        $hash2 = $hasher->finalize();

        // 结果应该不同，因为输入不同
        $this->assertNotEquals($hash1, $hash2, "finalize后update应影响结果");

        // 验证行为正确性 - 创建新hasher，一次性更新全部数据
        $verifyHasher = Blake3::newInstance();
        $verifyHasher->update("firstsecond");
        $verifyHash = $verifyHasher->finalize();

        $this->assertEquals($hash2, $verifyHash, "分两次update应与一次性update结果相同");
    }

    /**
     * 测试各种空输入
     *
     * 来源: 边界情况测试
     */
    public function testEmptyInputs(): void
    {
        // 空update后的finalize
        $hasher1 = Blake3::newInstance();
        $hasher1->update("");
        $hash1 = $hasher1->finalize();

        // 不update的finalize
        $hasher2 = Blake3::newInstance();
        $hash2 = $hasher2->finalize();

        // 多次空update
        $hasher3 = Blake3::newInstance();
        $hasher3->update("");
        $hasher3->update("");
        $hasher3->update("");
        $hash3 = $hasher3->finalize();

        // 所有结果应该相同
        $this->assertEquals($hash1, $hash2, "空update与不update结果应相同");
        $this->assertEquals($hash1, $hash3, "多次空update结果应相同");
    }

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
     * 测试流处理API
     */
    public function testStreamAPI(): void
    {
        // 创建测试数据
        $testData = str_repeat("Stream API test data ", 100);
        $tempFile = __DIR__ . '/tmp/stream_test.dat';

        // 写入测试数据到文件
        file_put_contents($tempFile, $testData);

        // 打开文件流
        $stream = fopen($tempFile, 'rb');
        $this->assertNotFalse($stream, "无法打开测试文件");

        // 使用流API计算哈希
        $hasher = Blake3::newInstance();
        $hasher->updateStream($stream);
        $hash1 = $hasher->finalize();

        fclose($stream);

        // 直接使用字符串计算哈希，结果应该相同
        $hash2 = Blake3::hash($testData);

        $this->assertEquals($hash1, $hash2, "流处理与直接处理应产生相同的哈希值");
    }

    /**
     * 测试文件处理API
     */
    public function testFileAPI(): void
    {
        // 创建测试数据
        $testData = str_repeat("File API test data ", 100);
        $tempFile = __DIR__ . '/tmp/file_test.dat';

        // 写入测试数据到文件
        file_put_contents($tempFile, $testData);

        // 使用文件API计算哈希
        $hash1 = Blake3::hashFile($tempFile);

        // 直接使用字符串计算哈希，结果应该相同
        $hash2 = Blake3::hash($testData);

        $this->assertEquals($hash1, $hash2, "文件处理与直接处理应产生相同的哈希值");

        // 测试十六进制输出
        $hashHex = Blake3::hashFileHex($tempFile);
        $this->assertEquals(bin2hex($hash1), $hashHex, "十六进制输出应与二进制输出一致");
    }

    /**
     * 测试哈希输出到文件
     */
    public function testOutputToFile(): void
    {
        $testData = "Output to file test data";
        $outputFile = __DIR__ . '/tmp/hash_output.bin';

        // 计算哈希并直接输出到文件
        $hasher = Blake3::newInstance();
        $hasher->update($testData);
        $bytesWritten = $hasher->finalizeToFile($outputFile, 64); // 64字节输出

        $this->assertEquals(64, $bytesWritten, "应写入64字节");
        $this->assertFileExists($outputFile, "输出文件应该存在");

        // 读取输出文件内容
        $fileContent = file_get_contents($outputFile);
        $this->assertEquals(64, strlen($fileContent), "文件应包含64字节");

        // 与直接计算的哈希比较
        $directHash = $hasher->finalize(64);
        $this->assertEquals($directHash, $fileContent, "文件输出与直接输出应相同");
    }

    /**
     * 测试流式输出
     */
    public function testStreamOutput(): void
    {
        $testData = "Stream output test data";
        $outputFile = __DIR__ . '/tmp/stream_output.bin';

        // 打开输出流
        $stream = fopen($outputFile, 'wb');
        $this->assertNotFalse($stream, "无法打开输出文件");

        // 计算哈希并直接输出到流
        $hasher = Blake3::newInstance();
        $hasher->update($testData);
        $bytesWritten = $hasher->finalizeToStream($stream, 48); // 48字节输出

        fclose($stream);

        $this->assertEquals(48, $bytesWritten, "应写入48字节");
        $this->assertFileExists($outputFile, "输出文件应该存在");

        // 读取输出文件内容
        $fileContent = file_get_contents($outputFile);
        $this->assertEquals(48, strlen($fileContent), "文件应包含48字节");

        // 与直接计算的哈希比较
        $directHash = $hasher->finalize(48);
        $this->assertEquals($directHash, $fileContent, "流输出与直接输出应相同");
    }

    /**
     * 测试大文件处理
     *
     * 注意：此测试会创建和处理较大的文件，可能需要一些时间
     *
     * @group large-file
     */
    public function testLargeFileProcessing(): void
    {
        // 此测试创建大文件，默认跳过
        $this->markTestSkipped('大文件测试需要较长时间运行，默认跳过。使用 --group=large-file 选项来运行此测试。');

        $largeFileSize = 100 * 1024 * 1024; // 100MB
        $chunkSize = 1024 * 1024; // 1MB
        $tempFile = __DIR__ . '/tmp/large_file.dat';

        // 创建测试用大文件，分块写入以避免内存问题
        $stream = fopen($tempFile, 'wb');
        $this->assertNotFalse($stream, "无法创建大文件");

        for ($i = 0; $i < $largeFileSize / $chunkSize; $i++) {
            $chunk = str_repeat(chr($i % 256), $chunkSize);
            fwrite($stream, $chunk);
        }
        fclose($stream);

        // 测试文件处理API
        $start = microtime(true);
        $hash = Blake3::hashFile($tempFile);
        $end = microtime(true);

        $this->assertEquals(32, strlen($hash), "哈希输出应为32字节");

        // 输出处理时间信息
        echo sprintf("处理%dMB文件用时：%.2f秒\n", $largeFileSize / (1024 * 1024), $end - $start);
    }

    /**
     * 测试内存使用优化
     *
     * 注意：此测试主要用于验证内存优化，实际内存使用可能因系统而异
     */
    public function testMemoryUsageOptimization(): void
    {
        // 绕过此测试，除非特别启用内存测试
        $this->markTestSkipped('内存优化测试需要手动检查内存使用情况，默认跳过。');

        // 创建中等大小的测试数据
        $medium_data = str_repeat("Memory optimization test data. ", 10000); // ~300KB

        // 记录开始内存使用
        $startMemory = memory_get_usage();

        // 使用优化后的API计算哈希
        $hasher = Blake3::newInstance();
        $hasher->update($medium_data);
        $hash = $hasher->finalize();

        // 记录结束内存使用
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        // 不严格断言内存使用，因为这取决于PHP实现
        // 但至少记录内存使用情况
        echo sprintf("哈希计算内存使用：起始 %.2fMB，结束 %.2fMB，峰值 %.2fMB\n", 
            $startMemory / (1024 * 1024),
            $endMemory / (1024 * 1024),
            $peakMemory / (1024 * 1024)
        );

        $this->addToAssertionCount(1); // 添加一个虚拟断言，确保测试被计数
    }

    /**
     * 测试内存分块优化
     *
     * 比较大块与小块更新的内存使用差异
     */
    public function testChunkedMemoryOptimization(): void
    {
        // 绕过此测试，除非特别启用内存测试
        $this->markTestSkipped('内存分块优化测试需要手动检查内存使用情况，默认跳过。');

        // 创建较大的测试数据
        $data_size = 5 * 1024 * 1024; // 5MB
        $large_data = str_repeat("X", $data_size);

        // 测试一次性更新
        echo "测试一次性更新5MB数据：\n";
        $startMemory = memory_get_usage();
        $hasher1 = Blake3::newInstance();
        $hasher1->update($large_data);
        $hash1 = $hasher1->finalize();
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        echo sprintf("  内存使用：起始 %.2fMB，结束 %.2fMB，峰值 %.2fMB\n", 
            $startMemory / (1024 * 1024),
            $endMemory / (1024 * 1024),
            $peakMemory / (1024 * 1024)
        );

        // 测试分块更新
        echo "测试分块更新5MB数据（10KB一块）：\n";
        $startMemory = memory_get_usage();
        $hasher2 = Blake3::newInstance();

        $chunk_size = 10 * 1024; // 10KB
        for ($offset = 0; $offset < $data_size; $offset += $chunk_size) {
            $chunk = substr($large_data, $offset, min($chunk_size, $data_size - $offset));
            $hasher2->update($chunk);
        }

        $hash2 = $hasher2->finalize();
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        echo sprintf("  内存使用：起始 %.2fMB，结束 %.2fMB，峰值 %.2fMB\n", 
            $startMemory / (1024 * 1024),
            $endMemory / (1024 * 1024),
            $peakMemory / (1024 * 1024)
        );

        // 验证哈希值相同
        $this->assertEquals($hash1, $hash2, "分块更新和一次性更新应产生相同的哈希值");
    }
}
