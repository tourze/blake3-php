<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;

echo "=== Analyzing the exact difference pattern ===\n\n";

// Test cases with their expected hashes
$test_cases = [
    5121 => '628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa',
    2049 => '5f4d72f40d7a5f82b15ca2b2e44b1de3c2ef86c426c95c1af0b6879522563030',
    3073 => '7124b49501012f81cc7f11ca069ec9226cecb8a2c850cfe644e327d22d3e1cd3',
    4097 => '9b4052b38f1c5fc8b1f9ff7ac7b27cd242487b3d890d15c96a1c25b8aa0fb995',
];

foreach ($test_cases as $size => $expected) {
    echo "Testing $size bytes:\n";
    
    // Generate input
    $input = '';
    for ($i = 0; $i < $size; $i++) {
        $input .= chr($i % 251);
    }
    
    // Calculate hash
    $hasher = Blake3::newInstance();
    $hasher->update($input);
    $actual = bin2hex($hasher->finalize());
    
    echo "Expected: $expected\n";
    echo "Actual:   $actual\n";
    
    // Find differences
    $diffs = [];
    for ($i = 0; $i < strlen($expected); $i += 2) {
        $exp_byte = hexdec(substr($expected, $i, 2));
        $act_byte = hexdec(substr($actual, $i, 2));
        if ($exp_byte !== $act_byte) {
            $diff = $act_byte - $exp_byte;
            $diffs[] = [
                'pos' => $i / 2,
                'expected' => sprintf('%02x', $exp_byte),
                'actual' => sprintf('%02x', $act_byte),
                'diff' => $diff
            ];
        }
    }
    
    if (empty($diffs)) {
        echo "MATCH!\n";
    } else {
        echo "Differences found:\n";
        foreach ($diffs as $d) {
            echo "  Byte {$d['pos']}: expected {$d['expected']}, got {$d['actual']}, diff = {$d['diff']}\n";
        }
    }
    
    echo "\n";
}

// Let's also check the relationship between chunk count and the difference
echo "=== Checking chunk count vs difference pattern ===\n";
foreach ($test_cases as $size => $expected) {
    $chunks = floor($size / 1024);
    $remainder = $size % 1024;
    echo "Size $size: $chunks chunks + $remainder bytes\n";
}

// One more test - let's see if the issue happens with other "just over chunk boundary" sizes
echo "\n=== Testing other boundary cases ===\n";
$boundary_tests = [1025, 1026, 2050, 3074];

foreach ($boundary_tests as $size) {
    $input = '';
    for ($i = 0; $i < $size; $i++) {
        $input .= chr($i % 251);
    }
    
    $hasher = Blake3::newInstance();
    $hasher->update($input);
    $hash = bin2hex($hasher->finalize());
    
    echo "Size $size: last 4 chars = " . substr($hash, -4) . "\n";
}