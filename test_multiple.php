<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Tourze\Blake3\Blake3;

echo "=== Testing multiple sizes ===\n\n";

// Test vectors
$tests = [
    1023 => '10108970eeda3eb932baac1428c7a2163b0e924c9a9e25b35bba72b28f70bd11',
    1024 => '42214739f095a406f3fc83deb889744ac00df831c10daa55189b5d121c855af7',
    2048 => 'e776b6028c7cd22a4d0ba182a8bf62205d2ef576467e838ed6f2529b85fba24a',
    3072 => 'b98cb0ff3623be03326b373de6b9095218513e64f1ee2edd2525c7ad1e5cffd2',
    3073 => '7124b49501012f81cc7f11ca069ec9226cecb8a2c850cfe644e327d22d3e1cd3',
    4096 => '015094013f57a5277b59d8475c0501042c0b642e531b0a1c8f58d2163229e969',
    5120 => '9cadc15fed8b5d854562b26a9536d9707cadeda9b143978f319ab34230535833',
    5121 => '628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa',
];

$passed = 0;
$failed = 0;

foreach ($tests as $size => $expected) {
    // Generate test data
    $data = "";
    for ($i = 0; $i < $size; $i++) {
        $data .= chr($i % 251);
    }
    
    $hash = Blake3::hash($data);
    $hex = bin2hex($hash);
    
    echo "Size $size: ";
    if ($hex === $expected) {
        echo "✓ PASS\n";
        $passed++;
    } else {
        echo "✗ FAIL\n";
        echo "  Expected: $expected\n";
        echo "  Got:      $hex\n";
        $failed++;
        
        // Special check for last-byte-only difference
        if (strlen($hex) === strlen($expected) && substr($hex, 0, -2) === substr($expected, 0, -2)) {
            $last_got = hexdec(substr($hex, -2));
            $last_exp = hexdec(substr($expected, -2));
            echo "  NOTE: Only last byte differs by " . ($last_got - $last_exp) . "\n";
        }
    }
}

echo "\nSummary: $passed passed, $failed failed\n";

// Now let's check what's special about the passing cases
echo "\n=== Analyzing passing cases ===\n";
if ($passed > 0) {
    echo "Cases that passed: ";
    foreach ($tests as $size => $expected) {
        $data = "";
        for ($i = 0; $i < $size; $i++) {
            $data .= chr($i % 251);
        }
        $hash = Blake3::hash($data);
        $hex = bin2hex($hash);
        if ($hex === $expected) {
            echo "$size ";
        }
    }
    echo "\n";
}