<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Tourze\Blake3\Blake3;

echo "=== Minimal test case ===\n\n";

// Test the simplest case: empty string
echo "Test 1: Empty string\n";
$hash = Blake3::hash("");
$hex = bin2hex($hash);
echo "Got:      $hex\n";
echo "Expected: af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262\n";
echo $hex === "af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262" ? "✓ PASS\n" : "✗ FAIL\n";

echo "\nTest 2: Single byte 'a'\n";
$hash = Blake3::hash("a");
$hex = bin2hex($hash);
echo "Got:      $hex\n";
echo "Expected: 17762fddd969a453925d65717ac3eea21320b66b54342fde15128d6caf21215f\n";
echo $hex === "17762fddd969a453925d65717ac3eea21320b66b54342fde15128d6caf21215f" ? "✓ PASS\n" : "✗ FAIL\n";

echo "\nTest 3: 'abc'\n";
$hash = Blake3::hash("abc");
$hex = bin2hex($hash);
echo "Got:      $hex\n";
echo "Expected: 6437b3ac38465133ffb63b75273a8db548c558465d79db03fd359c6cd5bd9d85\n";
echo $hex === "6437b3ac38465133ffb63b75273a8db548c558465d79db03fd359c6cd5bd9d85" ? "✓ PASS\n" : "✗ FAIL\n";

echo "\nTest 4: 64 bytes (one block)\n";
$data = str_repeat("a", 64);
$hash = Blake3::hash($data);
$hex = bin2hex($hash);
echo "Got:      $hex\n";
// This expected value might be wrong, but let's see what we get
echo "Result for 64 'a's: $hex\n";

echo "\nTest 5: 1024 bytes (one chunk)\n";
$data = "";
for ($i = 0; $i < 1024; $i++) {
    $data .= chr($i % 251);
}
$hash = Blake3::hash($data);
$hex = bin2hex($hash);
echo "Got:      $hex\n";
echo "Expected: 42214739f095a406f3fc83deb889744ac00df831c10daa55189b5d121c855af7\n";
echo $hex === "42214739f095a406f3fc83deb889744ac00df831c10daa55189b5d121c855af7" ? "✓ PASS\n" : "✗ FAIL\n";