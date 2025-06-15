<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Util\Blake3Util;
use Tourze\Blake3\Constants\Blake3Constants;

// Test with a simple 32-byte output to see if the issue is in compression
echo "=== Testing basic compression ===\n";

// First test with empty input
$hasher = Blake3::newInstance();
$hash = bin2hex($hasher->finalize());
echo "Empty input hash: $hash\n";
echo "Expected:         af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262\n";
echo "Match: " . ($hash === 'af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262' ? 'YES' : 'NO') . "\n\n";

// Test with single byte
$hasher = Blake3::newInstance();
$hasher->update(chr(0));
$hash = bin2hex($hasher->finalize());
echo "Single byte (0x00) hash: $hash\n";
echo "Expected:                2d3adedff11b61f14c886e35afa036736dcd87a74d27b5c1510225d0f592e213\n";
echo "Match: " . ($hash === '2d3adedff11b61f14c886e35afa036736dcd87a74d27b5c1510225d0f592e213' ? 'YES' : 'NO') . "\n\n";

// Let's test the compression function directly
echo "=== Testing compression function directly ===\n";

// Test compression with all zeros
$cv = Blake3Constants::IV;
$block = array_fill(0, 16, 0);
$result = Blake3Util::compress($cv, $block, 0, 64, 0);

echo "Compression with zero block:\n";
echo "First 4 words: ";
for ($i = 0; $i < 4; $i++) {
    echo sprintf("0x%08x ", $result[$i]);
}
echo "\n\n";

// Now let's check what happens with the ROOT flag
$result_root = Blake3Util::compress($cv, $block, 0, 64, Blake3Constants::ROOT);
echo "Compression with ROOT flag:\n";
echo "First 4 words: ";
for ($i = 0; $i < 4; $i++) {
    echo sprintf("0x%08x ", $result_root[$i]);
}
echo "\n";

// Check if the ROOT flag is affecting the result correctly
echo "ROOT flag value: " . Blake3Constants::ROOT . " (0x" . dechex(Blake3Constants::ROOT) . ")\n\n";

// Let's specifically check the 32nd byte generation
echo "=== Checking 32nd byte generation ===\n";
$words = $result_root;
$bytes = '';
foreach ($words as $word) {
    $bytes .= pack("V", $word);
}
echo "32nd byte (index 31): 0x" . sprintf("%02x", ord($bytes[31])) . "\n";

// Check if the issue is in the pack function
echo "\n=== Testing pack function ===\n";
$test_word = 0xff4c15fe;
$packed = pack("V", $test_word);
echo "Word 0xff4c15fe packed: ";
for ($i = 0; $i < 4; $i++) {
    echo sprintf("0x%02x ", ord($packed[$i]));
}
echo "\n";
echo "Expected: 0xfe 0x15 0x4c 0xff\n";