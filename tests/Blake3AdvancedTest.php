<?php

namespace Tourze\Blake3\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Blake3;

/**
 * Blake3高级测试类
 *
 * 此测试包含更全面的测试场景，用于深入测试Blake3库的性能和正确性
 */
class Blake3AdvancedTest extends TestCase
{
    /**
     * 测试不同数据大小的哈希计算
     *
     * 测试在各种不同数据大小下的哈希计算正确性，包括空数据、小数据和中等大小数据
     */
    public function testVariousDataSizes(): void
    {
        $sizes = [0, 1, 2, 3, 4, 5, 8, 16, 31, 32, 63, 64, 65, 127, 128, 129,
            255, 256, 257, 511, 512, 513, 1023, 1024, 1025, 2048, 4096];

        $hasher = Blake3::newInstance();

        foreach ($sizes as $size) {
            // 使用递增模式生成测试数据
            $data = '';
            for ($i = 0; $i < $size; $i++) {
                $data .= chr($i % 256);
            }

            // 重置哈希器并计算当前大小的哈希
            $hasher = Blake3::newInstance();
            $hasher->update($data);
            $hash = $hasher->finalize();

            // 验证哈希长度
            $this->assertEquals(32, strlen($hash), "输出长度应为32字节（对于{$size}字节输入）");

            // 对于某些特定大小，与预期的哈希值进行比较
            if ($size === 0) {
                $expected = hex2bin('af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262');
                $this->assertEquals($expected, $hash, "空输入的哈希值不符合预期");
            }

            if ($size === 64) {
                // 生成64字节的递增序列数据的哈希
                $expected64 = hex2bin('4eed7141ea4a5cd4b788606bd23f46e212af9cacebacdc7d1f4c6dc7f2511b98');
                $this->assertEquals($expected64, $hash, "64字节输入的哈希值不符合预期");
            }
        }
    }

    /**
     * 测试连续更新的正确性和一致性
     *
     * 验证多次小批量更新与一次性大批量更新是否产生相同的哈希值
     */
    public function testConsecutiveUpdates(): void
    {
        // 测试数据 - 一段较长的字符串
        $fullData = str_repeat("Blake3 is a cryptographic hash function", 100);
        $dataLen = strlen($fullData);

        // 一次性更新
        $hasher1 = Blake3::newInstance();
        $hasher1->update($fullData);
        $hash1 = $hasher1->finalize();

        // 小批量更新 - 5字节一次
        $hasher2 = Blake3::newInstance();
        for ($i = 0; $i < $dataLen; $i += 5) {
            $chunk = substr($fullData, $i, min(5, $dataLen - $i));
            $hasher2->update($chunk);
        }
        $hash2 = $hasher2->finalize();

        // 中等批量更新 - 100字节一次
        $hasher3 = Blake3::newInstance();
        for ($i = 0; $i < $dataLen; $i += 100) {
            $chunk = substr($fullData, $i, min(100, $dataLen - $i));
            $hasher3->update($chunk);
        }
        $hash3 = $hasher3->finalize();

        // 不规则批量更新 - 使用斐波那契数列作为块大小
        $hasher4 = Blake3::newInstance();
        $fib1 = 1;
        $fib2 = 1;
        $offset = 0;
        while ($offset < $dataLen) {
            $chunk = substr($fullData, $offset, min($fib1, $dataLen - $offset));
            $hasher4->update($chunk);
            $offset += $fib1;

            // 计算下一个斐波那契数
            $nextFib = $fib1 + $fib2;
            $fib1 = $fib2;
            $fib2 = $nextFib;
        }
        $hash4 = $hasher4->finalize();

        // 所有更新方式应产生相同的哈希值
        $this->assertEquals($hash1, $hash2, "小批量更新应产生与一次性更新相同的哈希值");
        $this->assertEquals($hash1, $hash3, "中等批量更新应产生与一次性更新相同的哈希值");
        $this->assertEquals($hash1, $hash4, "不规则批量更新应产生与一次性更新相同的哈希值");
    }

    /**
     * 测试可扩展输出功能 (XOF)
     *
     * 测试Blake3可扩展输出功能的正确性和一致性
     */
    public function testExtendableOutput(): void
    {
        $input = "BLAKE3 supports arbitrary output length via extendable output function (XOF)";
        $hasher = Blake3::newInstance();
        $hasher->update($input);

        // 测试不同输出长度
        $outputSizes = [1, 2, 3, 4, 8, 16, 20, 32, 64, 128, 256, 512, 1024];

        $prevHash = '';
        foreach ($outputSizes as $size) {
            $hash = $hasher->finalize($size);

            // 验证输出长度
            $this->assertEquals($size, strlen($hash), "输出长度应为{$size}字节");

            // 验证扩展哈希的一致性 - 较短的哈希应该是较长哈希的前缀
            if ($prevHash !== '') {
                $this->assertEquals($prevHash, substr($hash, 0, strlen($prevHash)),
                    "扩展哈希的一致性失败 - 较短哈希应是较长哈希的前缀");
            }

            $prevHash = $hash;
        }
    }

    /**
     * 测试对相似输入的抗冲突能力
     *
     * 验证对于相似但不同的输入，哈希值是否有足够的差异
     */
    public function testSimilarInputs(): void
    {
        $testCases = [
            // 相差一个字符
            ["The quick brown fox jumps over the lazy dog",
                "The quick brown fox jumps over the lazy dot"],
            // 大小写不同
            ["Blake3 is a cryptographic hash function",
                "blake3 is a cryptographic hash function"],
            // 尾部有空格
            ["No trailing spaces",
                "No trailing spaces "],
            // 首部有空格
            ["No leading spaces",
                " No leading spaces"],
            // 一个比特的差异（第一个字节的最低位）
            [chr(0) . "Same content",
                chr(1) . "Same content"],
        ];

        foreach ($testCases as $index => [$input1, $input2]) {
            $hasher1 = Blake3::newInstance();
            $hasher1->update($input1);
            $hash1 = $hasher1->finalize();

            $hasher2 = Blake3::newInstance();
            $hasher2->update($input2);
            $hash2 = $hasher2->finalize();

            // 验证不同输入产生不同哈希值
            $this->assertNotEquals($hash1, $hash2,
                "测试用例 #$index: 相似输入应产生不同的哈希值");

            // 计算汉明距离（不同位的数量）
            $diff = 0;
            for ($i = 0; $i < strlen($hash1); $i++) {
                $xor = ord($hash1[$i]) ^ ord($hash2[$i]);
                // 统计置位的数量
                for ($j = 0; $j < 8; $j++) {
                    $diff += ($xor >> $j) & 1;
                }
            }

            // 汉明距离应该相对较大以表明良好的雪崩效应
            // 理想情况下，应接近128位（50%的位翻转）
            $this->assertGreaterThan(80, $diff,
                "测试用例 #$index: 汉明距离应足够大以表明良好的雪崩效应");
        }
    }

    /**
     * 测试不同标志位组合对哈希结果的影响
     *
     * 验证不同标志位组合下的哈希计算是否正确
     */
    public function testFlagCombinations(): void
    {
        $input = "Test data for flag combinations";

        // 标准哈希
        $hasher1 = Blake3::newInstance();
        $hasher1->update($input);
        $hash1 = $hasher1->finalize();

        // 密钥哈希
        $key = str_repeat("\xff", 32); // 32字节的0xFF
        $hasher2 = Blake3::newKeyedInstance($key);
        $hasher2->update($input);
        $hash2 = $hasher2->finalize();

        // 密钥派生
        $context = "BLAKE3 2019-12-27 14:13:42 KEY DERIVATION TEST";
        $hasher3 = Blake3::newKeyDerivationInstance($context);
        $hasher3->update($input);
        $hash3 = $hasher3->finalize();

        // 验证不同模式产生不同哈希值
        $this->assertNotEquals($hash1, $hash2, "标准哈希和密钥哈希应产生不同的结果");
        $this->assertNotEquals($hash1, $hash3, "标准哈希和密钥派生应产生不同的结果");
        $this->assertNotEquals($hash2, $hash3, "密钥哈希和密钥派生应产生不同的结果");
    }
}
