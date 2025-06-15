<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

// Generate different input sizes to see pattern
$test_sizes = [1024, 1025, 2048, 2049, 3072, 3073, 4096, 4097, 5120, 5121];

echo "=== Testing counter values for different input sizes ===\n\n";

foreach ($test_sizes as $size) {
    $input = '';
    for ($i = 0; $i < $size; $i++) {
        $input .= chr($i % 251);
    }
    
    $hasher = Blake3::newInstance();
    $hasher->update($input);
    
    // Get the output object
    $reflection = new ReflectionClass($hasher);
    $outputMethod = $reflection->getMethod('output');
    $outputMethod->setAccessible(true);
    $output = $outputMethod->invoke($hasher);
    
    // Get counter from output
    $outputReflection = new ReflectionClass($output);
    $counterProp = $outputReflection->getProperty('counter');
    $counterProp->setAccessible(true);
    $counter = $counterProp->getValue($output);
    
    $blockLenProp = $outputReflection->getProperty('block_len');
    $blockLenProp->setAccessible(true);
    $block_len = $blockLenProp->getValue($output);
    
    $flagsProp = $outputReflection->getProperty('flags');
    $flagsProp->setAccessible(true);
    $flags = $flagsProp->getValue($output);
    
    // Get the hash
    $hash = bin2hex($hasher->finalize());
    $last_byte = substr($hash, -2);
    
    echo "Size: $size bytes\n";
    echo "  Chunks: " . floor($size / 1024) . " complete + " . ($size % 1024) . " bytes\n";
    echo "  Counter: $counter\n";
    echo "  Block length: $block_len\n";
    echo "  Flags: 0x" . sprintf("%x", $flags) . " (";
    if ($flags & Blake3Constants::PARENT) echo "PARENT ";
    if ($flags & Blake3Constants::CHUNK_START) echo "CHUNK_START ";
    if ($flags & Blake3Constants::CHUNK_END) echo "CHUNK_END ";
    echo ")\n";
    echo "  Last byte of hash: 0x$last_byte\n";
    echo "\n";
}

// Let's specifically check the 5121 case with different counter values
echo "=== Testing 5121 bytes with modified counter ===\n";

$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

$hasher = Blake3::newInstance();
$hasher->update($input);

// Get the output state
$reflection = new ReflectionClass($hasher);
$outputMethod = $reflection->getMethod('output');
$outputMethod->setAccessible(true);
$output = $outputMethod->invoke($hasher);

$outputReflection = new ReflectionClass($output);
$props = [];
foreach (['input_chaining_value', 'block_words', 'counter', 'block_len', 'flags'] as $prop) {
    $property = $outputReflection->getProperty($prop);
    $property->setAccessible(true);
    $props[$prop] = $property->getValue($output);
}

// Try with different counter values
for ($test_counter = 0; $test_counter <= 5; $test_counter++) {
    $result = Blake3Util::compress(
        $props['input_chaining_value'],
        $props['block_words'],
        $test_counter,  // Try different counter values
        $props['block_len'],
        $props['flags'] | Blake3Constants::ROOT
    );
    
    $bytes = '';
    foreach ($result as $word) {
        $bytes .= pack("V", $word);
    }
    
    echo "Counter $test_counter: byte 31 = 0x" . sprintf("%02x", ord($bytes[31])) . "\n";
}

echo "\nExpected: byte 31 = 0xfa\n";