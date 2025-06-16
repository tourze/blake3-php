<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;

echo "=== Testing various input sizes to find pattern ===\n\n";

// Test cases from the official vectors
$test_cases = [
    1025 => 'd00278ae47eb27b34faecf67b4fe263f82d5412916c1ffd97c8cb7fb814b8444',
    2049 => '5f4d72f40d7a5f82b15ca2b2e44b1de3c2ef86c426c95c1af0b6879522563030',
    3073 => '7124b49501012f81cc7f11ca069ec9226cecb8a2c850cfe644e327d22d3e1cd3',
    4097 => '9b4052b38f1c5fc8b1f9ff7ac7b27cd242487b3d890d15c96a1c25b8aa0fb995',
    5121 => '628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa',
    6145 => 'f1323a8631446cc50536a9f705ee5cb619424d46887f3c376c695b70e0f0507f',
    7169 => 'a003fc7a51754a9b3c7fae0367ab3d782dccf28855a03d435f8cfe74605e7817',
    8193 => 'bab6c09cb8ce8cf459261398d2e7aef35700bf488116ceb94a36d0f5f1b7bc3b'
];

$results = [];

foreach ($test_cases as $size => $expected) {
    $input = '';
    for ($i = 0; $i < $size; $i++) {
        $input .= chr($i % 251);
    }
    
    $hasher = Blake3::newInstance();
    $hasher->update($input);
    $actual = bin2hex($hasher->finalize());
    
    $match = ($actual === $expected);
    $chunks = floor($size / 1024);
    $remainder = $size % 1024;
    
    // Calculate the difference if they don't match
    $diff = 'N/A';
    if (!$match && strlen($actual) === strlen($expected)) {
        // Find the last differing byte
        for ($i = strlen($expected) - 2; $i >= 0; $i -= 2) {
            $exp_byte = hexdec(substr($expected, $i, 2));
            $act_byte = hexdec(substr($actual, $i, 2));
            if ($exp_byte !== $act_byte) {
                $diff = $act_byte - $exp_byte;
                break;
            }
        }
    }
    
    $results[] = [
        'size' => $size,
        'chunks' => $chunks,
        'remainder' => $remainder,
        'match' => $match,
        'diff' => $diff,
        'last_actual' => substr($actual, -2),
        'last_expected' => substr($expected, -2)
    ];
}

// Display results
echo "Size  | Chunks | +Bytes | Match | LastByte | Diff\n";
echo "------|--------|--------|-------|----------|------\n";
foreach ($results as $r) {
    printf("%5d | %6d | %6d | %-5s | %s→%s | %s\n",
        $r['size'],
        $r['chunks'],
        $r['remainder'],
        $r['match'] ? 'YES' : 'NO',
        $r['last_expected'],
        $r['last_actual'],
        $r['diff']
    );
}

// Look for patterns
echo "\n=== Analysis ===\n";
$passing = array_filter($results, fn($r) => $r['match']);
$failing = array_filter($results, fn($r) => !$r['match']);

echo "Passing: " . count($passing) . " tests\n";
echo "Failing: " . count($failing) . " tests\n";

// Check if there's a pattern in chunk counts
echo "\nChunk counts for failing tests:\n";
foreach ($failing as $f) {
    echo "  {$f['size']} bytes = {$f['chunks']} chunks (binary: " . decbin($f['chunks']) . ")\n";
}

// Let's also test a few more specific sizes to understand the pattern
echo "\n=== Testing additional sizes ===\n";
$additional_tests = [2048, 3072, 4096, 5120, 6144, 7168, 8192];

foreach ($additional_tests as $size) {
    $input = '';
    for ($i = 0; $i < $size; $i++) {
        $input .= chr($i % 251);
    }
    
    $hasher = Blake3::newInstance();
    $hasher->update($input);
    $hash = bin2hex($hasher->finalize());
    
    echo "$size bytes: last 2 chars = " . substr($hash, -2) . "\n";
}

// Check the specific difference pattern
echo "\n=== Checking if difference equals chunk count ===\n";
foreach ($failing as $f) {
    if (is_numeric($f['diff'])) {
        echo "Size {$f['size']}: chunks={$f['chunks']}, diff={$f['diff']}";
        if ($f['diff'] == $f['chunks']) {
            echo " ← MATCHES CHUNK COUNT!";
        }
        echo "\n";
    }
}