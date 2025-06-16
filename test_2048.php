<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Tourze\Blake3\Blake3;

echo "=== Testing 2048 bytes (2 chunks) ===\n\n";

// Generate test data
$data = "";
for ($i = 0; $i < 2048; $i++) {
    $data .= chr($i % 251);
}

$hash = Blake3::hash($data);
$hex = bin2hex($hash);

echo "Got:      $hex\n";
echo "Expected: e776b6028c7cd22a4d0ba182a8bf62205d2ef576467e838ed6f2529b85fba24a\n";

if ($hex === "e776b6028c7cd22a4d0ba182a8bf62205d2ef576467e838ed6f2529b85fba24a") {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    
    // Let's debug the tree merging
    echo "\nDebugging tree merge for 2 chunks:\n";
    echo "- Chunk 0: counter = 0, popcnt(0) = 0\n";
    echo "  Before merge: stack_len = 0\n";
    echo "  After merge: stack_len = 0\n";
    echo "  After push: stack_len = 1, stack = [CV[0]]\n";
    echo "\n- Chunk 1: counter = 1, popcnt(1) = 1\n";
    echo "  Before merge: stack_len = 1\n";
    echo "  After merge: stack_len = 1 (no merge needed)\n";
    echo "  After push: stack_len = 2, stack = [CV[0], CV[1]]\n";
    echo "\nBut wait! We should merge CV[0] and CV[1] after chunk 1!\n";
    
    echo "\nLet me check the reference implementation again...\n";
}