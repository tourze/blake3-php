<?php
echo "=== Verifying test data generation ===\n\n";

// The official test vectors use a repeating pattern 0-250
echo "Official pattern: 0, 1, 2, ..., 249, 250, 0, 1, 2, ...\n";
echo "That's a 251-byte cycle.\n\n";

// Generate test data for 5121 bytes
$data = '';
for ($i = 0; $i < 5121; $i++) {
    $data .= chr($i % 251);
}

echo "First 20 bytes: ";
for ($i = 0; $i < 20; $i++) {
    echo ord($data[$i]) . " ";
}
echo "\n";

echo "Bytes 249-252: ";
for ($i = 249; $i < 253; $i++) {
    echo ord($data[$i]) . " ";
}
echo "\n";

echo "Last 5 bytes (5116-5120): ";
for ($i = 5116; $i < 5121; $i++) {
    echo ord($data[$i]) . " ";
}
echo "\n";

// Check byte at position 5120
echo "\nByte at position 5120: " . ord($data[5120]) . "\n";
echo "5120 % 251 = " . (5120 % 251) . "\n";

// Let me also verify the exact test vectors
echo "\n=== Checking against known test vectors ===\n";

// These are from the official test suite
$known_tests = [
    0 => 'af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262',
    1 => '2d3adedff11b61f14c886e35afa036736dcd87a74d27b5c1510225d0f592e213',
    64 => '4eed7141ea4a5cd4b788606bd23f46e212af9cacebacdc7d1f4c6dc7f2511b98',
    1024 => '42214739f095a406f3fc83deb889744ac00df831c10daa55189b5d121c855af7'
];

foreach ($known_tests as $size => $expected) {
    $input = '';
    for ($i = 0; $i < $size; $i++) {
        $input .= chr($i % 251);
    }
    
    // Use the official BLAKE3 implementation if available
    if (function_exists('blake3')) {
        $hash = bin2hex(blake3($input));
        echo "Size $size: " . ($hash === $expected ? "PASS" : "FAIL") . "\n";
    } else {
        echo "Size $size: (blake3 function not available)\n";
    }
}

// Let's also check if there's a pattern in the failing sizes
echo "\n=== Pattern in failing sizes ===\n";
$failing = [2049, 4097, 5121, 6145, 8193];
foreach ($failing as $size) {
    $chunks = floor($size / 1024);
    $binary = decbin($chunks);
    $is_power_of_2 = ($chunks & ($chunks - 1)) === 0;
    $is_mersenne = (($chunks + 1) & $chunks) === 0;
    
    echo sprintf(
        "Size %5d: %d chunks (0b%s), power_of_2=%s, mersenne=%s\n",
        $size,
        $chunks,
        $binary,
        $is_power_of_2 ? 'Y' : 'N',
        $is_mersenne ? 'Y' : 'N'
    );
}

echo "\nAll failing cases have non-Mersenne chunk counts!\n";