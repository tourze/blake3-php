<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\ChunkState\Blake3ChunkState;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

echo "=== Testing block padding for partial chunks ===\n\n";

// Test with a 1-byte chunk (like the last chunk in 5121 case)
$key = Blake3Constants::IV;
$chunk_counter = 5;
$flags = 0;

$chunkState = new Blake3ChunkState($key, $chunk_counter, $flags);
$chunkState->update(chr(100)); // Single byte with value 100

$output = $chunkState->output();

// Get the internal state
$outputReflection = new ReflectionClass($output);
$blockWordsProp = $outputReflection->getProperty('block_words');
$blockWordsProp->setAccessible(true);
$block_words = $blockWordsProp->getValue($output);

$blockLenProp = $outputReflection->getProperty('block_len');
$blockLenProp->setAccessible(true);
$block_len = $blockLenProp->getValue($output);

echo "Block length: $block_len\n";
echo "Block words (16 words):\n";
for ($i = 0; $i < 16; $i++) {
    echo "  Word $i: 0x" . sprintf("%08x", $block_words[$i]) . "\n";
}

// The first word should contain our byte (100 = 0x64) in little-endian format
echo "\nFirst word bytes: ";
$first_word_bytes = pack("V", $block_words[0]);
for ($i = 0; $i < 4; $i++) {
    echo sprintf("0x%02x ", ord($first_word_bytes[$i]));
}
echo "\n";

// Test compression with correct block_len
echo "\n=== Testing compression with different block_len values ===\n";

$cv = $output->chaining_value();
$flagsWithEnd = $flags | Blake3Constants::CHUNK_START | Blake3Constants::CHUNK_END;

// Test with block_len = 1 (correct for 1 byte)
$result1 = Blake3Util::compress($key, $block_words, $chunk_counter, 1, $flagsWithEnd);
echo "With block_len=1:  Word 8 = 0x" . sprintf("%08x", $result1[7]) . "\n";

// Test with block_len = 64 (incorrect, but what might be happening)
$result64 = Blake3Util::compress($key, $block_words, $chunk_counter, 64, $flagsWithEnd);
echo "With block_len=64: Word 8 = 0x" . sprintf("%08x", $result64[7]) . "\n";

// Check what block_len the output is actually using
$flagsProp = $outputReflection->getProperty('flags');
$flagsProp->setAccessible(true);
$output_flags = $flagsProp->getValue($output);

echo "\nOutput block_len: $block_len\n";
echo "Output flags: 0x" . sprintf("%x", $output_flags) . "\n";

// Now let's check the chaining value
$cv = $output->chaining_value();
echo "\nChaining value (first 4 words):\n";
for ($i = 0; $i < 4; $i++) {
    echo "  CV[$i]: 0x" . sprintf("%08x", $cv[$i]) . "\n";
}

// Compare with expected chaining value from our debug output
echo "\nExpected CV[0]: 0xc8100f55 (from debug output)\n";
echo "Actual CV[0]:   0x" . sprintf("%08x", $cv[0]) . "\n";