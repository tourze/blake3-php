<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Constants\Blake3Constants;

echo "=== Checking MSG_PERMUTATION usage ===\n\n";

// The official permutation
$perm = Blake3Constants::MSG_PERMUTATION;
echo "MSG_PERMUTATION: [" . implode(', ', $perm) . "]\n";

// This permutation is used in the permute function
echo "\nIn Blake3Util::permute():\n";
echo "for (\$i = 0; \$i < 16; \$i++) {\n";
echo "    \$m[\$i] = \$original[Blake3Constants::MSG_PERMUTATION[\$i]];\n";
echo "}\n";

// Let's check if this is implemented correctly
echo "\n=== Testing permutation ===\n";
$test_input = range(0, 15);
echo "Input: [" . implode(', ', $test_input) . "]\n";

$output = [];
for ($i = 0; $i < 16; $i++) {
    $output[$i] = $test_input[$perm[$i]];
}
echo "Output: [" . implode(', ', $output) . "]\n";

// Check if it's a valid permutation
echo "\nExpected output: [2, 6, 3, 10, 7, 0, 4, 13, 1, 11, 12, 5, 9, 14, 15, 8]\n";
echo "Match: " . ($output === [2, 6, 3, 10, 7, 0, 4, 13, 1, 11, 12, 5, 9, 14, 15, 8] ? "YES" : "NO") . "\n";

// The issue might be that we're somehow modifying the state incorrectly
echo "\n=== Hypothesis about the bug ===\n";
echo "Since the error is exactly equal to the chunk count for 5121 bytes,\n";
echo "and tests pass only for perfectly balanced trees (2^n-1 chunks),\n";
echo "the issue might be:\n";
echo "\n1. When the tree is unbalanced, we have nodes at different levels\n";
echo "2. During the final output generation, we might be using the wrong counter\n";
echo "3. Or there's an off-by-one error in the tree merging\n";

// Let's check the specific value that's wrong
echo "\n=== Analyzing the specific error ===\n";
echo "For 5121 bytes:\n";
echo "Expected last byte: 0xfa\n";
echo "Actual last byte: 0xff\n";
echo "Difference: 0xff - 0xfa = 5\n";
echo "Number of complete chunks: 5\n";
echo "\nThis is too specific to be a coincidence!\n";

// The bug might be in how we count chunks or merge the tree
echo "\n=== Possible locations for the bug ===\n";
echo "1. In add_chunk_chaining_value - when we calculate merges\n";
echo "2. In the output() method - when we create the final tree\n";
echo "3. In the compression function - when we set flags or counters\n";

// Let me check if the issue could be with the chunk counter increment
echo "\n=== Chunk counter increment check ===\n";
echo "In Blake3::update(), after a chunk is full:\n";
echo "1. We call add_chunk_chaining_value with current chunk counter\n";
echo "2. We create new chunk with counter + 1\n";
echo "\nFor 5 chunks (0-4), the last chunk has counter=4\n";
echo "When we create chunk 5, it has counter=5\n";
echo "But wait... could the value 5 be leaking into the computation somehow?\n";