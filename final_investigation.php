<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;

echo "=== Final investigation ===\n\n";

// Let's trace the exact tree merging for a simple failing case
echo "Tracing tree merging for 2049 bytes (2 chunks + 1 byte):\n\n";

// Manually trace what should happen
echo "Chunk 0: counter=0 (0b00), popcnt=0, stack=[chunk0]\n";
echo "Chunk 1: counter=1 (0b01), popcnt=1, stack=[chunk0,chunk1]\n";
echo "  No merge needed (stack_size=2, target=1)\n";
echo "  After adding: stack=[chunk0,chunk1]\n";
echo "Chunk 2: (partial, 1 byte)\n";
echo "\nFinal tree:\n";
echo "    root\n";
echo "   /    \\\n";
echo "  P01    2\n";
echo " /  \\\n";
echo "0    1\n";

// Now let's think about the add_chunk_chaining_value logic
echo "\n=== Analyzing add_chunk_chaining_value ===\n";
echo "For chunk 1 (counter=1):\n";
echo "- Binary: 0b01\n";
echo "- Popcnt: 1\n";
echo "- Current stack_size: 1\n";
echo "- Target stack length: 1\n";
echo "- No merging needed!\n";

echo "\nWait... if no merging happens for chunk 1,\n";
echo "then the stack has [chunk0, chunk1].\n";
echo "This seems wrong!\n";

// Let me re-read the algorithm
echo "\n=== Re-examining the algorithm ===\n";

for ($i = 0; $i <= 8; $i++) {
    $binary = decbin($i);
    $popcnt = substr_count($binary, '1');
    echo sprintf("Counter %d (0b%s): popcnt=%d\n", $i, str_pad($binary, 4, '0', STR_PAD_LEFT), $popcnt);
}

echo "\nThe popcnt represents how many nodes should remain in the stack.\n";
echo "For counter=1, popcnt=1, so we should have 1 node in stack.\n";
echo "But we have 2 nodes after adding chunk 1!\n";

echo "\n=== THE BUG ===\n";
echo "When counter=1, we have stack=[chunk0, chunk1]\n";
echo "But popcnt(1)=1, so we should merge to have only 1 node!\n";
echo "\nLet me check our add_chunk_chaining_value implementation...\n";

// Check what happens step by step
$stack = [];
$stack_size = 0;

// Add chunk 0
echo "\nAdding chunk 0:\n";
$counter = 0;
$popcnt = 0;
$stack[$stack_size] = "chunk0";
$stack_size++;
echo "  Stack: [" . implode(", ", array_slice($stack, 0, $stack_size)) . "]\n";

// Add chunk 1
echo "\nAdding chunk 1:\n";
$counter = 1;
$popcnt = 1;
echo "  Before: stack_size=$stack_size, target=$popcnt\n";
echo "  Need to merge? " . ($stack_size > $popcnt ? "YES" : "NO") . "\n";

// We should merge!
if ($stack_size > $popcnt) {
    echo "  Merging chunks 0 and 1...\n";
    $stack_size = 1;
    $stack[0] = "P01";
}
$stack[$stack_size] = "chunk1";
$stack_size++;
echo "  Stack: [" . implode(", ", array_slice($stack, 0, $stack_size)) . "]\n";

echo "\nOops! We added chunk1 AFTER merging, but the merge already created P01!\n";
echo "This is the bug - we're adding the new chunk after merging,\n";
echo "but the merge might have already included it.\n";