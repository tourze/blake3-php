<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\ChunkState\Blake3ChunkState;

echo "=== Checking partial chunk handling ===\n\n";

// Create a chunk with only 1 byte
$key = Blake3Constants::IV;
$chunk5 = new Blake3ChunkState($key, 5, 0);
$chunk5->update(chr(100)); // Single byte

// Get its output
$output5 = $chunk5->output();

// Check the block_len
$outputReflection = new ReflectionClass($output5);
$blockLenProp = $outputReflection->getProperty('block_len');
$blockLenProp->setAccessible(true);
$block_len = $blockLenProp->getValue($output5);

$counterProp = $outputReflection->getProperty('counter');
$counterProp->setAccessible(true);
$counter = $counterProp->getValue($output5);

echo "Chunk 5 (1 byte) output:\n";
echo "- counter: $counter\n";
echo "- block_len: $block_len\n";

// The key insight: block_len is 1, not 64!
echo "\nIMPORTANT: block_len = $block_len (not 64!)\n";

// Let's check if this affects the compression
$cv = $output5->chaining_value();
echo "\nChaining value (first 4 words):\n";
for ($i = 0; $i < 4; $i++) {
    echo sprintf("  CV[%d]: 0x%08x\n", $i, $cv[$i]);
}

// Now let's manually check what happens in the final tree
echo "\n=== Final tree construction ===\n";
echo "After processing 5120 bytes (5 complete chunks):\n";
echo "- Stack has 2 nodes: [P0123, chunk4]\n";
echo "- Current chunk is chunk5 (1 byte)\n";
echo "\nThe output() method will:\n";
echo "1. Get output from chunk5 (counter=5, block_len=1)\n";
echo "2. Merge with stack[1] (chunk4)\n";
echo "3. Merge with stack[0] (P0123)\n";

// The issue might be that chunk5's counter (5) is somehow affecting the result
echo "\n=== Hypothesis about the bug ===\n";
echo "The partial chunk (chunk 5) has:\n";
echo "- counter = 5\n";
echo "- block_len = 1\n";
echo "\nWhen this is used in the final output tree, the counter value\n";
echo "might be incorrectly influencing the computation.\n";

// Let's also check a working case (3073 bytes)
echo "\n=== Comparing with 3073 bytes (works) ===\n";
$chunk3 = new Blake3ChunkState($key, 3, 0);
$chunk3->update(chr(60)); // Single byte at position 3072
$output3 = $chunk3->output();

$counter3 = $counterProp->getValue($output3);
$block_len3 = $blockLenProp->getValue($output3);

echo "Chunk 3 (1 byte) output:\n";
echo "- counter: $counter3\n";
echo "- block_len: $block_len3\n";

// Both have their chunk counter set, so why does one work and not the other?
echo "\nBoth partial chunks have their chunk counter set.\n";
echo "The difference must be in the tree structure!\n";

// For 3 chunks: binary 0b11 → balanced at that level
// For 5 chunks: binary 0b101 → unbalanced
echo "\n=== The real issue ===\n";
echo "For 3 chunks (0b11): the tree is balanced at the chunk level\n";
echo "For 5 chunks (0b101): chunk 4 is at a different level\n";
echo "\nThe bug might be in how we handle chunks at different tree levels.\n";