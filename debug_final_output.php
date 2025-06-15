<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

// Generate 5121 bytes
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

$hasher = Blake3::newInstance();
$hasher->update($input);

// Use reflection to get the final output state
$reflection = new ReflectionClass($hasher);
$outputMethod = $reflection->getMethod('output');
$outputMethod->setAccessible(true);
$output = $outputMethod->invoke($hasher);

// Get internal state of the output
$outputReflection = new ReflectionClass($output);
$props = [];
foreach (['input_chaining_value', 'block_words', 'counter', 'block_len', 'flags'] as $prop) {
    $property = $outputReflection->getProperty($prop);
    $property->setAccessible(true);
    $props[$prop] = $property->getValue($output);
}

echo "=== Final Blake3Output state ===\n";
echo "Counter: " . $props['counter'] . "\n";
echo "Block length: " . $props['block_len'] . "\n";
echo "Flags: " . $props['flags'] . " (0x" . sprintf("%x", $props['flags']) . ")\n\n";

// Check what flags are set
echo "Flag analysis:\n";
if ($props['flags'] & Blake3Constants::CHUNK_START) echo "- CHUNK_START is set\n";
if ($props['flags'] & Blake3Constants::CHUNK_END) echo "- CHUNK_END is set\n";
if ($props['flags'] & Blake3Constants::PARENT) echo "- PARENT is set\n";
if ($props['flags'] & Blake3Constants::ROOT) echo "- ROOT is set\n";
if ($props['flags'] & Blake3Constants::KEYED_HASH) echo "- KEYED_HASH is set\n";
if ($props['flags'] & Blake3Constants::DERIVE_KEY_CONTEXT) echo "- DERIVE_KEY_CONTEXT is set\n";
if ($props['flags'] & Blake3Constants::DERIVE_KEY_MATERIAL) echo "- DERIVE_KEY_MATERIAL is set\n";

echo "\n=== Comparing compression with and without ROOT flag ===\n";

// Compress without ROOT flag
$result_no_root = Blake3Util::compress(
    $props['input_chaining_value'],
    $props['block_words'],
    $props['counter'],
    $props['block_len'],
    $props['flags']
);

// Compress with ROOT flag
$result_with_root = Blake3Util::compress(
    $props['input_chaining_value'],
    $props['block_words'],
    $props['counter'],
    $props['block_len'],
    $props['flags'] | Blake3Constants::ROOT
);

// Check the 8th word (which affects byte 31)
echo "Word 8 without ROOT: 0x" . sprintf("%08x", $result_no_root[7]) . "\n";
echo "Word 8 with ROOT:    0x" . sprintf("%08x", $result_with_root[7]) . "\n";

// Convert to bytes and check byte 31
$bytes_no_root = '';
foreach ($result_no_root as $word) {
    $bytes_no_root .= pack("V", $word);
}

$bytes_with_root = '';
foreach ($result_with_root as $word) {
    $bytes_with_root .= pack("V", $word);
}

echo "\nByte 31 without ROOT: 0x" . sprintf("%02x", ord($bytes_no_root[31])) . "\n";
echo "Byte 31 with ROOT:    0x" . sprintf("%02x", ord($bytes_with_root[31])) . "\n";

// Let's also check if the issue is in the flags passed to the final output
echo "\n=== Checking if PARENT flag affects result ===\n";

// The final output might have PARENT flag set
if ($props['flags'] & Blake3Constants::PARENT) {
    // Try without PARENT flag
    $result_no_parent = Blake3Util::compress(
        $props['input_chaining_value'],
        $props['block_words'],
        $props['counter'],
        $props['block_len'],
        ($props['flags'] & ~Blake3Constants::PARENT) | Blake3Constants::ROOT
    );
    
    echo "Word 8 without PARENT: 0x" . sprintf("%08x", $result_no_parent[7]) . "\n";
    
    $bytes_no_parent = '';
    foreach ($result_no_parent as $word) {
        $bytes_no_parent .= pack("V", $word);
    }
    echo "Byte 31 without PARENT: 0x" . sprintf("%02x", ord($bytes_no_parent[31])) . "\n";
}

// Check the expected value
echo "\n=== Expected value ===\n";
$expected = hex2bin('628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa');
echo "Expected byte 31: 0x" . sprintf("%02x", ord($expected[31])) . "\n";