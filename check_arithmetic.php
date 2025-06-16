<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

echo "=== Checking for arithmetic issues ===\n\n";

// The error pattern:
// - 5121 bytes: error = 5 (number of complete chunks)
// - Other sizes show different errors

// Let's check if there's an issue with the mask32 function
echo "Testing mask32 with values near the error:\n";
$test_values = [
    0xfa => '0xfa (250)',
    0xff => '0xff (255)', 
    0xfa + 5 => '0xfa + 5',
    0xfffffffa => '0xfffffffa',
    0xffffffff => '0xffffffff'
];

foreach ($test_values as $val => $desc) {
    $masked = Blake3Util::mask32($val);
    echo sprintf("%s: 0x%08x → 0x%08x\n", $desc, $val, $masked);
}

// Let's also check the rightrotate32 function
echo "\nTesting rightrotate32:\n";
$val = 0xfa;
for ($n = 0; $n <= 32; $n += 8) {
    $rotated = Blake3Util::rightrotate32($val, $n);
    echo sprintf("rightrotate32(0x%02x, %2d) = 0x%08x\n", $val, $n, $rotated);
}

// Check if there's an issue with how we handle the state array
echo "\n=== Checking state array handling ===\n";
echo "In compress(), state is initialized as:\n";
echo "state[0-7] = chaining_value[0-7]\n";
echo "state[8-11] = IV[0-3]\n";
echo "state[12] = counter_low\n";
echo "state[13] = counter_high\n";
echo "state[14] = block_len\n";
echo "state[15] = flags\n";

// For chunk 5 with 1 byte:
echo "\nFor chunk 5 (1 byte):\n";
echo "state[12] = 5 (counter_low)\n";
echo "state[13] = 0 (counter_high)\n";
echo "state[14] = 1 (block_len)\n";
echo "state[15] = 3 (CHUNK_START | CHUNK_END)\n";

// The value 5 is in state[12]
// After 7 rounds of compression, this affects the output
echo "\nThe counter value (5) is mixed into the state during compression.\n";
echo "This is correct behavior, but somehow it's causing the wrong result.\n";

// Let me check if there's an off-by-one error somewhere
echo "\n=== Checking for off-by-one errors ===\n";
echo "Chunk indices: 0, 1, 2, 3, 4, 5 (for 5121 bytes)\n";
echo "That's 6 chunks total (5 complete + 1 partial)\n";
echo "But we process chunks 0-4 first, then chunk 5\n";

// Wait, let me re-examine the exact error values
echo "\n=== Re-examining error values ===\n";
$errors = [
    2049 => ['actual' => 0x79, 'expected' => 0x30, 'chunks' => 2],
    4097 => ['actual' => 0x8b, 'expected' => 0x95, 'chunks' => 4],
    5121 => ['actual' => 0xff, 'expected' => 0xfa, 'chunks' => 5],
    6145 => ['actual' => 0xe2, 'expected' => 0x7f, 'chunks' => 6],
    8193 => ['actual' => 0x7f, 'expected' => 0x3b, 'chunks' => 8]
];

foreach ($errors as $size => $info) {
    $diff = $info['actual'] - $info['expected'];
    if ($diff < 0) $diff += 256;
    echo sprintf(
        "%d bytes: actual=0x%02x, expected=0x%02x, diff=%3d, chunks=%d\n",
        $size,
        $info['actual'],
        $info['expected'],
        $diff,
        $info['chunks']
    );
}

echo "\nOnly 5121 has diff=5 matching chunk count.\n";
echo "Other sizes have different error patterns.\n";
echo "This suggests the bug is more complex than a simple addition.\n";