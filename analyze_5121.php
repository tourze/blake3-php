<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;

// 测试 5121 字节的情况
echo "=== Analyzing 5121 bytes case ===\n\n";

// 生成正确的输入（0-250 循环）
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

// 计算哈希
$hasher = Blake3::newInstance();
$hasher->update($input);
$hash = bin2hex($hasher->finalize());

echo "Input length: 5121 bytes\n";
echo "Our hash:      $hash\n";
echo "Expected hash: 628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa\n\n";

// 比较差异
$expected = '628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa';
$diff_pos = -1;
for ($i = 0; $i < strlen($expected); $i++) {
    if ($hash[$i] !== $expected[$i]) {
        $diff_pos = $i;
        break;
    }
}

if ($diff_pos >= 0) {
    echo "First difference at position $diff_pos (byte " . ($diff_pos / 2) . ")\n";
    echo "Expected: " . substr($expected, $diff_pos, 8) . "\n";
    echo "Actual:   " . substr($hash, $diff_pos, 8) . "\n";
}

// 测试不同的输出长度
echo "\nTesting different output lengths:\n";
$hasher2 = Blake3::newInstance();
$hasher2->update($input);

for ($len = 30; $len <= 34; $len++) {
    $out = bin2hex($hasher2->finalize($len));
    echo "Length $len: $out\n";
}