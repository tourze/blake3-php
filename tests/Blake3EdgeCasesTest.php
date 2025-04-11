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
        // 生成大数据集 - 确保会形成合并树
        $data = str_repeat('z', 1024 * 10); // 10KB

        // 一次性更新
        $hasher1 = Blake3::newInstance();
        $hasher1->update($data);
        $hash1 = $hasher1->finalize();

        // 使用不同大小的分块方式，会产生不同的合并树结构
        $hasher2 = Blake3::newInstance();
        $chunks = [
            substr($data, 0, 1024 * 3),      // 3KB
            substr($data, 1024 * 3, 1024 * 5), // 5KB
            substr($data, 1024 * 8)           // 2KB
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

        // 生成一个特别长（1KB）的哈希
        $hash = $hasher->finalize(1024);

        $this->assertEquals(1024, strlen($hash), "应能生成指定长度的输出");

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
}
