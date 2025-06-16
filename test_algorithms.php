<?php
echo "=== Comparing C vs Rust tree merge algorithms ===\n\n";

// C algorithm: use popcnt
function c_algorithm($chunk_counter) {
    echo "C algorithm for chunk $chunk_counter:\n";
    
    // Count bits
    $popcnt = 0;
    $temp = $chunk_counter;
    while ($temp > 0) {
        $popcnt += ($temp & 1);
        $temp >>= 1;
    }
    
    echo "  popcnt($chunk_counter) = $popcnt\n";
    echo "  Target stack size after merge: $popcnt\n";
    
    return $popcnt;
}

// Rust algorithm: count trailing zeros
function rust_algorithm($total_chunks) {
    echo "Rust algorithm for total_chunks $total_chunks:\n";
    
    $merges = 0;
    $temp = $total_chunks;
    while (($temp & 1) == 0) {
        $merges++;
        $temp >>= 1;
    }
    
    echo "  Number of merges: $merges\n";
    echo "  Binary: " . decbin($total_chunks) . "\n";
    
    return $merges;
}

// Test both algorithms
for ($i = 0; $i <= 8; $i++) {
    echo "\n--- After chunk $i ---\n";
    
    // C passes chunk_counter (the index)
    $c_target = c_algorithm($i);
    
    // Rust passes total_chunks (counter + 1)
    $rust_merges = rust_algorithm($i + 1);
    
    // Simulate the stack
    echo "Stack simulation:\n";
    if ($i == 0) {
        echo "  Initial: stack = []\n";
        echo "  After push: stack = [CV0]\n";
    } else {
        // This is simplified, but shows the pattern
        echo "  C: merge to size $c_target, then push\n";
        echo "  Rust: do $rust_merges merges, then push\n";
    }
}

echo "\n=== Key insight ===\n";
echo "The algorithms are different!\n";
echo "- C: merge BEFORE push based on chunk_counter\n";
echo "- Rust: merge AFTER push based on total_chunks\n";
echo "\nBut wait, that's not right either...\n";