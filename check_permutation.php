<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Constants\Blake3Constants;

echo "=== Checking MSG_PERMUTATION ===\n\n";

$perm = Blake3Constants::MSG_PERMUTATION;
echo "MSG_PERMUTATION: [" . implode(', ', $perm) . "]\n";
echo "Length: " . count($perm) . "\n\n";

// Check if it's a valid permutation (contains 0-15 exactly once)
$found = array_fill(0, 16, false);
foreach ($perm as $val) {
    if ($val < 0 || $val >= 16) {
        echo "ERROR: Invalid value $val\n";
    }
    if ($found[$val]) {
        echo "ERROR: Duplicate value $val\n";
    }
    $found[$val] = true;
}

$all_found = true;
for ($i = 0; $i < 16; $i++) {
    if (!$found[$i]) {
        echo "ERROR: Missing value $i\n";
        $all_found = false;
    }
}

if ($all_found) {
    echo "Permutation is valid (contains 0-15 exactly once)\n";
}

// Let's also check if the permutation matches the official one
$official_perm = [2, 6, 3, 10, 7, 0, 4, 13, 1, 11, 12, 5, 9, 14, 15, 8];
echo "\nComparing with official permutation:\n";
echo "Official: [" . implode(', ', $official_perm) . "]\n";
echo "Match: " . ($perm === $official_perm ? "YES" : "NO") . "\n";

// Check if there's an off-by-one somewhere
echo "\n=== Checking for off-by-one errors ===\n";
for ($i = 0; $i < 16; $i++) {
    if ($perm[$i] !== $official_perm[$i]) {
        echo "Position $i: our={$perm[$i]}, official={$official_perm[$i]}, diff=" . ($perm[$i] - $official_perm[$i]) . "\n";
    }
}

// Let's check if position 5 or 7 (related to word 8 which affects byte 31) has anything special
echo "\n=== Checking positions that might affect byte 31 ===\n";
echo "Position 7 maps to: " . $perm[7] . "\n";
echo "Position 15 maps to: " . $perm[15] . "\n";

// Word 8 (index 7) in little-endian gives us bytes 28-31
// So byte 31 is the most significant byte of word 8
echo "\nFor the compression output:\n";
echo "Word 8 (index 7) contains bytes 28-31 in little-endian\n";
echo "Byte 31 is the MSB of word 8\n";