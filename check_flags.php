<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Constants\Blake3Constants;

echo "=== Checking Blake3 Flag Values ===\n\n";

// Our flag values
$our_flags = [
    'CHUNK_START' => Blake3Constants::CHUNK_START,
    'CHUNK_END' => Blake3Constants::CHUNK_END,
    'PARENT' => Blake3Constants::PARENT,
    'ROOT' => Blake3Constants::ROOT,
    'KEYED_HASH' => Blake3Constants::KEYED_HASH,
    'DERIVE_KEY_CONTEXT' => Blake3Constants::DERIVE_KEY_CONTEXT,
    'DERIVE_KEY_MATERIAL' => Blake3Constants::DERIVE_KEY_MATERIAL,
];

// Official flag values from BLAKE3 spec
$official_flags = [
    'CHUNK_START' => 1 << 0,  // 1
    'CHUNK_END' => 1 << 1,    // 2
    'PARENT' => 1 << 2,       // 4
    'ROOT' => 1 << 3,         // 8
    'KEYED_HASH' => 1 << 4,   // 16
    'DERIVE_KEY_CONTEXT' => 1 << 5,  // 32
    'DERIVE_KEY_MATERIAL' => 1 << 6, // 64
];

echo "Flag comparison:\n";
foreach ($our_flags as $name => $value) {
    $official = $official_flags[$name];
    $match = $value === $official ? "✓" : "✗";
    echo sprintf("%-20s: our=%3d (0x%02x), official=%3d (0x%02x) %s\n", 
        $name, $value, $value, $official, $official, $match);
}

// Check if any combination of flags could produce 5
echo "\n=== Checking if any flag combination produces 5 ===\n";
$target = 5;
echo "Target value: $target\n";
echo "Binary: " . decbin($target) . " = ";

$components = [];
foreach ($our_flags as $name => $value) {
    if ($target & $value) {
        $components[] = $name;
    }
}
echo implode(' | ', $components) . "\n";

// 5 = 0b101 = CHUNK_START | PARENT
echo "\n5 = CHUNK_START (1) | PARENT (4)\n";

// Let's check what flags are typically used together
echo "\n=== Common flag combinations ===\n";
echo "First block:    CHUNK_START = " . Blake3Constants::CHUNK_START . "\n";
echo "Middle block:   0\n";
echo "Last block:     CHUNK_END = " . Blake3Constants::CHUNK_END . "\n";
echo "Single block:   CHUNK_START | CHUNK_END = " . (Blake3Constants::CHUNK_START | Blake3Constants::CHUNK_END) . "\n";
echo "Parent node:    PARENT = " . Blake3Constants::PARENT . "\n";
echo "Root output:    ROOT = " . Blake3Constants::ROOT . "\n";
echo "Parent + Root:  PARENT | ROOT = " . (Blake3Constants::PARENT | Blake3Constants::ROOT) . "\n";

// The specific case for 5121 bytes final output
echo "\n=== For 5121 bytes final output ===\n";
echo "Should have flags: PARENT | ROOT = " . (Blake3Constants::PARENT | Blake3Constants::ROOT) . " (when generating output)\n";
echo "That's: 4 | 8 = 12\n";

// Check if somehow we're using 5 as a flag
echo "\n=== Is 5 being used as a flag somewhere? ===\n";
echo "5 in binary: 0b" . str_pad(decbin(5), 8, '0', STR_PAD_LEFT) . "\n";
echo "This would mean CHUNK_START | PARENT are set together\n";
echo "This combination should not normally occur!\n";