<?php
// Let's verify that our test input generation is correct
echo "=== Verifying test input generation ===\n\n";

// Check modulo 251 pattern
echo "First 20 bytes with modulo 251:\n";
for ($i = 0; $i < 20; $i++) {
    $val = $i % 251;
    echo "i=$i: " . sprintf("%3d (0x%02x)", $val, $val) . "\n";
}

echo "\nBytes around 250-255:\n";
for ($i = 248; $i < 256; $i++) {
    $val = $i % 251;
    echo "i=$i: " . sprintf("%3d (0x%02x)", $val, $val) . "\n";
}

echo "\n=== Checking if issue is modulo-related ===\n";

// The difference between our result (0xff) and expected (0xfa) is 5
// Let's see what happens with different modulo values

// Check byte at position 5120 (the last byte of input)
$pos = 5120;
echo "\nByte at position $pos:\n";
echo "  With mod 251: " . ($pos % 251) . " (0x" . sprintf("%02x", $pos % 251) . ")\n";
echo "  With mod 256: " . ($pos % 256) . " (0x" . sprintf("%02x", $pos % 256) . ")\n";

// Check if there's a pattern
echo "\n=== Checking failing test positions ===\n";
$failing_sizes = [2049, 3073, 4097, 5121, 6145, 7169, 8193];
foreach ($failing_sizes as $size) {
    $last_pos = $size - 1;
    $mod251 = $last_pos % 251;
    $mod256 = $last_pos % 256;
    echo "Size $size (last pos $last_pos): mod251=$mod251, mod256=$mod256, diff=" . abs($mod251 - $mod256) . "\n";
}

// Let's also verify with a different approach
echo "\n=== Double-checking input generation ===\n";
$input1 = '';
for ($i = 0; $i < 5121; $i++) {
    $input1 .= chr($i % 251);
}

// Alternative way using range and array_map
$input2 = implode('', array_map(function($i) { return chr($i % 251); }, range(0, 5120)));

echo "Input methods match: " . ($input1 === $input2 ? "YES" : "NO") . "\n";
echo "Input length: " . strlen($input1) . "\n";
echo "Last byte value: " . ord($input1[5120]) . " (0x" . sprintf("%02x", ord($input1[5120])) . ")\n";