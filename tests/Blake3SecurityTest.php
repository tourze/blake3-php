<?php

namespace Tourze\Blake3\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Blake3;

/**
 * Blake3算法的安全特性测试类
 *
 * 此测试类验证Blake3算法的各种安全特性，如抗碰撞能力、雪崩效应等
 */
class Blake3SecurityTest extends TestCase
{
    /**
     * 测试雪崩效应
     *
     * 改变输入的一个比特，应该导致输出的显著变化
     */
    public function testAvalancheEffect(): void
    {
        // 测试字符串
        $input = "The quick brown fox jumps over the lazy dog";

        // 基础哈希
        $hasher1 = Blake3::newInstance();
        $hasher1->update($input);
        $hash1 = $hasher1->finalize();

        // 修改输入的第一个字节的最低位
        $modifiedInput = chr(ord($input[0]) ^ 1) . substr($input, 1);

        $hasher2 = Blake3::newInstance();
        $hasher2->update($modifiedInput);
        $hash2 = $hasher2->finalize();

        // 计算汉明距离（不同比特的数量）
        $hammingDistance = 0;
        for ($i = 0; $i < strlen($hash1); $i++) {
            $xor = ord($hash1[$i]) ^ ord($hash2[$i]);
            for ($j = 0; $j < 8; $j++) {
                if (($xor >> $j) & 1) {
                    $hammingDistance++;
                }
            }
        }

        // 应该有约50%的位发生变化（理想的雪崩效应）
        // 256位输出中应该有约128位变化
        $bitsCount = strlen($hash1) * 8;
        $changePercentage = ($hammingDistance / $bitsCount) * 100;

        // 断言变化比例在40%-60%之间，表示良好的雪崩效应
        $this->assertGreaterThan(40, $changePercentage, "雪崩效应不足，只有{$changePercentage}%的位发生变化");
        $this->assertLessThan(60, $changePercentage, "雪崩效应过度，有{$changePercentage}%的位发生变化");

        echo "雪崩效应测试: 更改1位输入，导致 {$hammingDistance}/{$bitsCount} ({$changePercentage}%) 位输出变化\n";
    }

    /**
     * 测试密钥派生一致性
     *
     * 相同上下文和输入应生成相同的派生密钥
     */
    public function testKeyDerivationConsistency(): void
    {
        $input = "Secret input data";
        $context = "My application 1.0";

        // 第一次派生
        $hasher1 = Blake3::newKeyDerivationInstance($context);
        $hasher1->update($input);
        $derivedKey1 = $hasher1->finalize(32);

        // 第二次派生
        $hasher2 = Blake3::newKeyDerivationInstance($context);
        $hasher2->update($input);
        $derivedKey2 = $hasher2->finalize(32);

        // 不同上下文
        $differentContext = "My application 1.1";
        $hasher3 = Blake3::newKeyDerivationInstance($differentContext);
        $hasher3->update($input);
        $derivedKey3 = $hasher3->finalize(32);

        // 验证一致性
        $this->assertEquals($derivedKey1, $derivedKey2, "相同上下文和输入应生成相同的派生密钥");
        $this->assertNotEquals($derivedKey1, $derivedKey3, "不同上下文应生成不同的派生密钥");
    }

    /**
     * 测试密钥哈希安全性
     *
     * 验证密钥哈希模式的正确性和安全性
     */
    public function testKeyedHashSecurity(): void
    {
        $input = "Secret message";

        // 不同密钥应产生不同哈希
        $key1 = str_repeat("\x00", 32); // 全零密钥
        $key2 = str_repeat("\xff", 32); // 全一密钥
        $key3 = random_bytes(32);       // 随机密钥

        $hasher1 = Blake3::newKeyedInstance($key1);
        $hasher1->update($input);
        $hash1 = $hasher1->finalize();

        $hasher2 = Blake3::newKeyedInstance($key2);
        $hasher2->update($input);
        $hash2 = $hasher2->finalize();

        $hasher3 = Blake3::newKeyedInstance($key3);
        $hasher3->update($input);
        $hash3 = $hasher3->finalize();

        // 普通哈希
        $hasher4 = Blake3::newInstance();
        $hasher4->update($input);
        $hash4 = $hasher4->finalize();

        // 验证所有哈希都不同
        $this->assertNotEquals($hash1, $hash2, "不同密钥应产生不同哈希");
        $this->assertNotEquals($hash1, $hash3, "不同密钥应产生不同哈希");
        $this->assertNotEquals($hash2, $hash3, "不同密钥应产生不同哈希");
        $this->assertNotEquals($hash1, $hash4, "密钥哈希应与普通哈希不同");

        // 已知密钥和输入无法推导出其他密钥的输出
        $known_key = $key1;
        $known_hash = $hash1;

        // 测试知道一个密钥的哈希后，是否可以推断出其他密钥的哈希
        // 这基本不可能，但我们至少验证输出差异很大
        $diffs = [
            self::calculateHammingDistance($hash1, $hash2),
            self::calculateHammingDistance($hash1, $hash3),
            self::calculateHammingDistance($hash2, $hash3),
        ];

        foreach ($diffs as $index => $diff) {
            $percentage = ($diff / (strlen($hash1) * 8)) * 100;
            $this->assertGreaterThan(30, $percentage, "密钥导致的哈希差异不足 (差异对 {$index}: {$percentage}%)");
        }
    }

    /**
     * 测试时序一致性
     *
     * 哈希算法不应该有明显的时间侧信道
     */
    public function testTimingConsistency(): void
    {
        // 注意：这个测试可能会受到系统负载的影响，因此只是一个粗略的检查

        $iterations = 100;
        $data1 = str_repeat("a", 1000);
        $data2 = str_repeat("b", 1000);

        $times1 = [];
        $times2 = [];

        // 交替测量两种输入的哈希时间
        for ($i = 0; $i < $iterations; $i++) {
            // 测量第一个数据
            $start = hrtime(true);
            $hasher = Blake3::newInstance();
            $hasher->update($data1);
            $hash = $hasher->finalize();
            $end = hrtime(true);
            $times1[] = $end - $start;

            // 测量第二个数据
            $start = hrtime(true);
            $hasher = Blake3::newInstance();
            $hasher->update($data2);
            $hash = $hasher->finalize();
            $end = hrtime(true);
            $times2[] = $end - $start;
        }

        // 计算平均时间和标准差
        $avg1 = array_sum($times1) / count($times1);
        $avg2 = array_sum($times2) / count($times2);

        $variance1 = 0;
        $variance2 = 0;

        foreach ($times1 as $time) {
            $variance1 += pow($time - $avg1, 2);
        }

        foreach ($times2 as $time) {
            $variance2 += pow($time - $avg2, 2);
        }

        $stdDev1 = sqrt($variance1 / count($times1));
        $stdDev2 = sqrt($variance2 / count($times2));

        // 计算平均时间的差异百分比
        $diff = abs($avg1 - $avg2) / max($avg1, $avg2) * 100;

        // 时间差异不应超过10%
        $this->assertLessThan(10, $diff, "时间差异超过10%，可能存在时序侧信道");

        echo "时序一致性测试: 时间差异 {$diff}%\n";
        echo "数据1: " . ($avg1 / 1000000) . "ms (±" . ($stdDev1 / 1000000) . "ms)\n";
        echo "数据2: " . ($avg2 / 1000000) . "ms (±" . ($stdDev2 / 1000000) . "ms)\n";
    }

    /**
     * 辅助方法：计算汉明距离
     */
    private static function calculateHammingDistance(string $str1, string $str2): int
    {
        if (strlen($str1) !== strlen($str2)) {
            throw new \InvalidArgumentException("字符串长度不一致");
        }

        $distance = 0;
        $length = strlen($str1);

        for ($i = 0; $i < $length; $i++) {
            $xor = ord($str1[$i]) ^ ord($str2[$i]);

            // 计算设置的位数
            for ($j = 0; $j < 8; $j++) {
                $distance += ($xor >> $j) & 1;
            }
        }

        return $distance;
    }
}
