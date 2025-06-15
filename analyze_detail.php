<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Output\Blake3Output;

// 生成5121字节的测试输入
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

// 创建hasher并更新
$hasher = Blake3::newInstance();
$hasher->update($input);

// 使用反射来访问私有方法和属性
$reflection = new ReflectionClass($hasher);
$outputMethod = $reflection->getMethod('output');
$outputMethod->setAccessible(true);

// 获取Blake3Output对象
$output = $outputMethod->invoke($hasher);

// 再次使用反射来访问Blake3Output的内部状态
$outputReflection = new ReflectionClass($output);

// 获取所有属性
$props = [];
foreach (['input_chaining_value', 'block_words', 'counter', 'block_len', 'flags'] as $prop) {
    $property = $outputReflection->getProperty($prop);
    $property->setAccessible(true);
    $props[$prop] = $property->getValue($output);
}

echo "=== Blake3Output Internal State ===\n";
echo "Counter: " . $props['counter'] . "\n";
echo "Block length: " . $props['block_len'] . "\n";
echo "Flags: 0x" . dechex($props['flags']) . "\n\n";

echo "Input chaining value (first 4): ";
for ($i = 0; $i < 4; $i++) {
    echo sprintf("0x%08x ", $props['input_chaining_value'][$i]);
}
echo "\n\n";

// 测试不同的输出长度
echo "=== Testing output generation ===\n";
for ($len = 30; $len <= 34; $len++) {
    $bytes = $output->root_output_bytes($len);
    $hex = bin2hex($bytes);
    echo "Length $len: $hex\n";
    
    // 特别检查第32个字节
    if ($len >= 32) {
        $byte32 = ord($bytes[31]);
        echo "  Byte 32 (index 31): 0x" . sprintf("%02x", $byte32) . " (decimal: $byte32)\n";
    }
}

// 分析第32个字节的生成过程
echo "\n=== Analyzing byte 32 generation ===\n";

// 获取压缩函数的输出
use Tourze\Blake3\Util\Blake3Util;
use Tourze\Blake3\Constants\Blake3Constants;

// 第32个字节来自第一个输出块的最后一个字节
$words = Blake3Util::compress(
    $props['input_chaining_value'],
    $props['block_words'],
    0, // output_block_counter
    $props['block_len'],
    $props['flags'] | Blake3Constants::ROOT
);

echo "Compression output words (first 8):\n";
for ($i = 0; $i < 8; $i++) {
    echo sprintf("  Word %d: 0x%08x\n", $i, $words[$i]);
}

// 第32个字节来自第8个字（索引7）的最高字节
$word8 = $words[7];
echo "\nWord 8 (index 7): 0x" . sprintf("%08x", $word8) . "\n";
echo "Word 8 bytes (little-endian): ";
$bytes = pack("V", $word8);
for ($i = 0; $i < 4; $i++) {
    echo sprintf("0x%02x ", ord($bytes[$i]));
}
echo "\n";

// 期望值分析
echo "\n=== Expected value analysis ===\n";
$expected = '628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa';
$expectedBytes = hex2bin($expected);
echo "Expected byte 32: 0x" . sprintf("%02x", ord($expectedBytes[31])) . "\n";

// 尝试找出差异来源
echo "\n=== Checking if issue is in word generation ===\n";
// 期望的第8个字应该产生 0xfa 作为第4个字节
// 我们得到的是 0xff
$expectedWord8Byte4 = 0xfa;
$actualWord8Byte4 = ($word8 >> 24) & 0xff;
echo "Expected word 8 byte 4: 0x" . sprintf("%02x", $expectedWord8Byte4) . "\n";
echo "Actual word 8 byte 4: 0x" . sprintf("%02x", $actualWord8Byte4) . "\n";
echo "Difference: " . ($actualWord8Byte4 - $expectedWord8Byte4) . "\n";