<?php
echo "=== Binary pattern analysis ===\n\n";

$chunk_counts = [1, 2, 3, 4, 5, 6, 7, 8];
$results = [
    1 => 'PASS',
    2 => 'FAIL', 
    3 => 'PASS',
    4 => 'FAIL',
    5 => 'FAIL',
    6 => 'FAIL', 
    7 => 'PASS',
    8 => 'FAIL'
];

echo "Chunks | Binary | Popcnt | Result\n";
echo "-------|--------|--------|-------\n";

foreach ($chunk_counts as $n) {
    $binary = decbin($n);
    $popcnt = substr_count($binary, '1');
    printf("%6d | %6s | %6d | %s\n", $n, $binary, $popcnt, $results[$n]);
}

echo "\nPattern observation:\n";
echo "- 1 (001): popcnt=1, PASS\n";
echo "- 2 (010): popcnt=1, FAIL\n";
echo "- 3 (011): popcnt=2, PASS\n";
echo "- 4 (100): popcnt=1, FAIL\n";
echo "- 5 (101): popcnt=2, FAIL\n";
echo "- 6 (110): popcnt=2, FAIL\n";
echo "- 7 (111): popcnt=3, PASS\n";
echo "- 8 (1000): popcnt=1, FAIL\n";

echo "\nLet me check if it's related to powers of 2...\n";
foreach ($chunk_counts as $n) {
    $is_power_of_2 = ($n & ($n - 1)) === 0;
    $is_one_less_than_power_of_2 = (($n + 1) & $n) === 0;
    echo "$n: power_of_2=" . ($is_power_of_2 ? 'Y' : 'N');
    echo ", one_less=" . ($is_one_less_than_power_of_2 ? 'Y' : 'N');
    echo ", result=" . $results[$n] . "\n";
}

// Check Mersenne numbers (2^n - 1)
echo "\n=== Checking Mersenne numbers (2^n - 1) ===\n";
for ($i = 0; $i <= 4; $i++) {
    $mersenne = (1 << $i) - 1;
    if ($mersenne > 0 && $mersenne <= 8) {
        echo "2^$i - 1 = $mersenne: " . $results[$mersenne] . "\n";
    }
}

// The pattern seems to be: numbers of form 2^n - 1 pass!
echo "\n=== PATTERN FOUND! ===\n";
echo "Numbers of the form 2^n - 1 (Mersenne numbers) PASS:\n";
echo "- 1 = 2^1 - 1 ✓\n";
echo "- 3 = 2^2 - 1 ✓\n"; 
echo "- 7 = 2^3 - 1 ✓\n";
echo "\nAll others FAIL.\n";

// This relates to the tree structure!
echo "\n=== Tree structure insight ===\n";
echo "When chunk_counter = 2^n - 1, all bits are 1s\n";
echo "This means the tree is perfectly balanced!\n";
echo "Example: 7 = 0b111 means 3 nodes in stack\n";

// So the bug might be in how we handle non-perfect trees
echo "\n=== Hypothesis ===\n";
echo "The bug occurs when the tree is NOT perfectly balanced\n";
echo "i.e., when chunk_counter is NOT of form 2^n - 1\n";