<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

echo "=== Testing if chunk counter affects output ===\n\n";

// For the 5121 case, let's manually check the final compression
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

$hasher = Blake3::newInstance();
$hasher->update($input);

// Get the final output state
$reflection = new ReflectionClass($hasher);
$outputMethod = $reflection->getMethod('output');
$outputMethod->setAccessible(true);
$output = $outputMethod->invoke($hasher);

// Get properties
$outputReflection = new ReflectionClass($output);
$props = [];
foreach (['input_chaining_value', 'block_words', 'counter', 'block_len', 'flags'] as $prop) {
    $property = $outputReflection->getProperty($prop);
    $property->setAccessible(true);
    $props[$prop] = $property->getValue($output);
}

echo "Final output state:\n";
echo "Counter: {$props['counter']}\n";
echo "Block len: {$props['block_len']}\n";
echo "Flags: 0x" . sprintf("%x", $props['flags']) . "\n\n";

// Let's check if the counter value in the compression is affecting the result
echo "=== Testing compression with different counters ===\n";

// Normal compression (what we currently do)
$normal = Blake3Util::compress(
    $props['input_chaining_value'],
    $props['block_words'],
    $props['counter'],
    $props['block_len'],
    $props['flags'] | Blake3Constants::ROOT
);

// Try with counter = 0 (which should be correct for parent nodes)
$with_zero = Blake3Util::compress(
    $props['input_chaining_value'],
    $props['block_words'],
    0,
    $props['block_len'],
    $props['flags'] | Blake3Constants::ROOT
);

// Convert to hex for comparison
$normal_hex = '';
foreach ($normal as $word) {
    $normal_hex .= sprintf("%08x", $word);
}

$zero_hex = '';
foreach ($with_zero as $word) {
    $zero_hex .= sprintf("%08x", $word);
}

echo "With current counter ({$props['counter']}): " . substr($normal_hex, 0, 32) . "...\n";
echo "With counter = 0:                    " . substr($zero_hex, 0, 32) . "...\n";
echo "\nLast 8 chars:\n";
echo "Current: " . substr($normal_hex, -8) . "\n";
echo "Zero:    " . substr($zero_hex, -8) . "\n";

// Check the actual bytes
$normal_bytes = '';
foreach ($normal as $word) {
    $normal_bytes .= pack("V", $word);
}

$zero_bytes = '';
foreach ($with_zero as $word) {
    $zero_bytes .= pack("V", $word);
}

echo "\nByte 31:\n";
echo "Current: 0x" . sprintf("%02x", ord($normal_bytes[31])) . "\n";
echo "Zero:    0x" . sprintf("%02x", ord($zero_bytes[31])) . "\n";
echo "Expected: 0xfa\n";

// Let's also check what happens with counter = 5
$with_five = Blake3Util::compress(
    $props['input_chaining_value'],
    $props['block_words'],
    5,
    $props['block_len'],
    $props['flags'] | Blake3Constants::ROOT
);

$five_bytes = '';
foreach ($with_five as $word) {
    $five_bytes .= pack("V", $word);
}

echo "\nWith counter = 5: byte 31 = 0x" . sprintf("%02x", ord($five_bytes[31])) . "\n";

// Check if subtracting 5 from our result gives the expected value
$our_byte31 = ord($normal_bytes[31]);
$adjusted = ($our_byte31 - 5) & 0xFF;
echo "\nOur byte 31 (0x" . sprintf("%02x", $our_byte31) . ") - 5 = 0x" . sprintf("%02x", $adjusted) . "\n";
echo "Does this match expected (0xfa)? " . ($adjusted === 0xfa ? "YES!" : "NO") . "\n";