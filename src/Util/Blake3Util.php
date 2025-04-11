<?php

namespace Tourze\Blake3\Util;

use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Output\Blake3Output;

/**
 * Blake3算法工具类
 */
class Blake3Util
{
    /**
     * 将32位整数截断
     */
    public static function mask32(int $x): int
    {
        return $x & 0xFFFFFFFF;
    }

    /**
     * 32位加法
     */
    public static function add32(int $x, int $y): int
    {
        return self::mask32($x + $y);
    }

    /**
     * 32位右旋转
     * 修复旋转方向：右旋转是向右移位，需要改正逻辑
     */
    public static function rightrotate32(int $x, int $n): int
    {
        // 确保n在0-31范围内
        $n = $n & 31;
        // 正确的右旋转：右移n位并将溢出位放到左边
        return self::mask32(($x >> $n) | ($x << (32 - $n)));
    }

    /**
     * 混合函数，混合列或对角线
     */
    public static function g(array &$state, int $a, int $b, int $c, int $d, int $mx, int $my): void
    {
        $state[$a] = self::add32($state[$a], self::add32($state[$b], $mx));
        $state[$d] = self::rightrotate32($state[$d] ^ $state[$a], 16);
        $state[$c] = self::add32($state[$c], $state[$d]);
        $state[$b] = self::rightrotate32($state[$b] ^ $state[$c], 12);
        $state[$a] = self::add32($state[$a], self::add32($state[$b], $my));
        $state[$d] = self::rightrotate32($state[$d] ^ $state[$a], 8);
        $state[$c] = self::add32($state[$c], $state[$d]);
        $state[$b] = self::rightrotate32($state[$b] ^ $state[$c], 7);
    }

    /**
     * 执行一轮压缩
     */
    public static function round(array &$state, array $m): void 
    {
        // 混合列
        self::g($state, 0, 4, 8, 12, $m[0], $m[1]);
        self::g($state, 1, 5, 9, 13, $m[2], $m[3]);
        self::g($state, 2, 6, 10, 14, $m[4], $m[5]);
        self::g($state, 3, 7, 11, 15, $m[6], $m[7]);
        // 混合对角线
        self::g($state, 0, 5, 10, 15, $m[8], $m[9]);
        self::g($state, 1, 6, 11, 12, $m[10], $m[11]);
        self::g($state, 2, 7, 8, 13, $m[12], $m[13]);
        self::g($state, 3, 4, 9, 14, $m[14], $m[15]);
    }

    /**
     * 置换消息块
     * 修复排列逻辑，确保正确使用MSG_PERMUTATION
     */
    public static function permute(array &$m): void
    {
        $original = $m;
        for ($i = 0; $i < 16; $i++) {
            $m[$i] = $original[Blake3Constants::MSG_PERMUTATION[$i]];
        }
    }

    /**
     * 压缩函数
     * 修复压缩状态的初始化和计算逻辑
     */
    public static function compress(
        array $chaining_value,
        array $block_words,
        int $counter,
        int $block_len,
        int $flags
    ): array {
        // 确保chaining_value和block_words有正确的长度
        assert(count($chaining_value) === 8, 'Chaining value must be 8 words');
        assert(count($block_words) === 16, 'Block words must be 16 words');

        // 注意：低32位和高32位需要分别处理
        $counter_low = self::mask32($counter);
        $counter_high = self::mask32($counter >> 32);

        // 正确初始化状态
        $state = [
            $chaining_value[0],
            $chaining_value[1],
            $chaining_value[2],
            $chaining_value[3],
            $chaining_value[4],
            $chaining_value[5],
            $chaining_value[6],
            $chaining_value[7],
            Blake3Constants::IV[0],
            Blake3Constants::IV[1],
            Blake3Constants::IV[2],
            Blake3Constants::IV[3],
            $counter_low,
            $counter_high,
            $block_len,
            $flags
        ];

        $block = $block_words; // 复制，不直接修改输入

        // 运行7轮压缩
        self::round($state, $block); // 第1轮
        self::permute($block);
        self::round($state, $block); // 第2轮
        self::permute($block);
        self::round($state, $block); // 第3轮
        self::permute($block);
        self::round($state, $block); // 第4轮
        self::permute($block);
        self::round($state, $block); // 第5轮
        self::permute($block);
        self::round($state, $block); // 第6轮
        self::permute($block);
        self::round($state, $block); // 第7轮

        // 正确计算最终状态
        for ($i = 0; $i < 8; $i++) {
            $state[$i] ^= $state[$i + 8];
            $state[$i + 8] ^= $chaining_value[$i];
        }

        return $state;
    }

    /**
     * 从小端字节转换为字
     * 确保正确处理字节序转换
     */
    public static function words_from_little_endian_bytes(string $b): array
    {
        $len = strlen($b);
        assert($len % 4 === 0, 'Input length must be a multiple of 4 bytes');

        $result = [];
        for ($i = 0; $i < $len; $i += 4) {
            // 使用unpack确保正确处理字节序
            $result[] = unpack('V', substr($b, $i, 4))[1];
        }
        return $result;
    }

    /**
     * 创建父节点输出
     */
    public static function parent_output(array $left_child_cv, array $right_child_cv, array $key_words, int $flags): Blake3Output 
    {
        $combined_child_cv = array_merge($left_child_cv, $right_child_cv);
        return new Blake3Output(
            $key_words,
            $combined_child_cv,
            0,
            Blake3Constants::BLOCK_LEN,
            Blake3Constants::PARENT | $flags
        );
    }

    /**
     * 计算父节点的链接值
     */
    public static function parent_cv(array $left_child_cv, array $right_child_cv, array $key_words, int $flags): array
    {
        $output = self::parent_output($left_child_cv, $right_child_cv, $key_words, $flags);
        return $output->chaining_value();
    }
}
