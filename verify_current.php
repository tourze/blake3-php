<?php
require_once __DIR__ . '/../../vendor/autoload.php';

echo "=== Verifying current implementation ===\n\n";

// Create a custom test to trace the tree merging
class DebugBlake3 extends \Tourze\Blake3\Blake3 {
    public function debugUpdate(string $input): void {
        $inputLength = strlen($input);
        $chunks = intval($inputLength / 1024);
        $remainder = $inputLength % 1024;
        
        echo "Input: $inputLength bytes = $chunks chunks";
        if ($remainder > 0) echo " + $remainder bytes";
        echo "\n\n";
        
        // Just call the parent update
        $this->update($input);
    }
}

// Test cases that are failing
$test_sizes = [3072, 5120];

foreach ($test_sizes as $size) {
    echo "Testing $size bytes:\n";
    
    $data = "";
    for ($i = 0; $i < $size; $i++) {
        $data .= chr($i % 251);
    }
    
    $hasher = new DebugBlake3(\Tourze\Blake3\Constants\Blake3Constants::IV, 0);
    $hasher->debugUpdate($data);
    $hash = bin2hex($hasher->finalize());
    
    echo "Got: $hash\n\n";
}

// Let's also check the pattern
echo "=== Pattern check ===\n";
echo "Working cases have popcnt(chunks) = 1 (powers of 2)\n";
echo "Failing cases have popcnt(chunks) > 1\n";
echo "\nThis suggests our tree merging is correct for balanced trees\n";
echo "but wrong for unbalanced trees.\n";