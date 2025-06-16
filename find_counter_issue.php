<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\ChunkState\Blake3ChunkState;

// Let's examine what happens when we have 5 chunks
echo "=== Examining the 5-chunk case in detail ===\n\n";

// Create a hasher
$key = Blake3Constants::IV;
$flags = 0;

// Manually simulate processing 5 chunks
$stack = [];
$stack_size = 0;

// Helper function to simulate add_chunk_chaining_value
function simulate_add_chunk_cv(&$stack, &$stack_size, $new_cv, $chunk_counter, $key, $flags) {
    echo "Adding chunk $chunk_counter:\n";
    
    // Calculate target stack length using popcnt
    $post_merge_stack_len = 0;
    $temp = $chunk_counter;
    while ($temp > 0) {
        $post_merge_stack_len += ($temp & 1);
        $temp >>= 1;
    }
    
    echo "  Binary: " . str_pad(decbin($chunk_counter), 8, '0', STR_PAD_LEFT) . "\n";
    echo "  Target stack length: $post_merge_stack_len\n";
    echo "  Current stack size: $stack_size\n";
    
    // Merge until we reach target length
    while ($stack_size > $post_merge_stack_len) {
        echo "  Merging: stack_size $stack_size -> ";
        
        // Merge top two elements
        $right = $stack[$stack_size - 1];
        $left = $stack[$stack_size - 2];
        
        // Parent CV would be computed here
        $parent = ['merged', $left[0], $right[0]]; // Simplified
        
        $stack[$stack_size - 2] = $parent;
        $stack_size--;
        echo "$stack_size\n";
    }
    
    // Add new CV
    $stack[$stack_size] = $new_cv;
    $stack_size++;
    echo "  Final stack size: $stack_size\n\n";
}

// Simulate adding chunks 0-4
for ($i = 0; $i < 5; $i++) {
    $cv = ['chunk', $i];
    simulate_add_chunk_cv($stack, $stack_size, $cv, $i, $key, $flags);
}

echo "Final stack structure:\n";
for ($i = 0; $i < $stack_size; $i++) {
    echo "  Stack[$i]: " . json_encode($stack[$i]) . "\n";
}

// Now check what happens when we process this tree
echo "\n=== Tree structure after 5 chunks ===\n";
echo "We have processed chunks 0-4\n";
echo "Stack has $stack_size nodes\n";
echo "Binary of 4: " . decbin(4) . " (popcnt=1)\n";
echo "This means we merged chunks as follows:\n";
echo "  0+1 → parent1\n";
echo "  2+3 → parent2\n"; 
echo "  parent1+parent2 → parent3\n";
echo "  Stack contains: [parent3, chunk4]\n";

// The issue might be when we create the final output
echo "\n=== When creating final output ===\n";
echo "We process chunk 5 (the partial chunk with 1 byte)\n";
echo "Then we need to merge:\n";
echo "  1. chunk5 output\n";
echo "  2. Stack[1] (chunk4)\n";
echo "  3. Stack[0] (parent3)\n";

// Let's check if the issue is in the Blake3Output construction
echo "\n=== Checking Blake3Output counter usage ===\n";

$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

$hasher = Blake3::newInstance();
$hasher->update($input);

// Use reflection to get the output
$reflection = new ReflectionClass($hasher);
$outputMethod = $reflection->getMethod('output');
$outputMethod->setAccessible(true);
$output = $outputMethod->invoke($hasher);

// Get output properties
$outputReflection = new ReflectionClass($output);
$counterProp = $outputReflection->getProperty('counter');
$counterProp->setAccessible(true);
$counter = $counterProp->getValue($output);

echo "Final Blake3Output counter: $counter\n";
echo "This counter is used in compression when generating output\n";

// The bug might be that we're somehow using chunk count instead of 0
echo "\nHypothesis: The counter should always be 0 for final output\n";
echo "but somehow the chunk count (5) is affecting the result.\n";