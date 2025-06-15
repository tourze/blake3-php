<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;

// Test a simple case that we know works
echo "=== Testing 2048 bytes (exactly 2 chunks) ===\n";
$input = '';
for ($i = 0; $i < 2048; $i++) {
    $input .= chr($i % 251);
}

$hasher = Blake3::newInstance();
$hasher->update($input);
$hash = bin2hex($hasher->finalize());

echo "Our hash:      $hash\n";
echo "Expected hash: e776b6028c7cd22a4d0ba182a8bf62205d2ef576467e838ed6f2529b85fba24a\n";
echo "Match: " . ($hash === 'e776b6028c7cd22a4d0ba182a8bf62205d2ef576467e838ed6f2529b85fba24a' ? 'YES' : 'NO') . "\n\n";

// Now test 2049 bytes (2 chunks + 1 byte) which fails
echo "=== Testing 2049 bytes (2 chunks + 1 byte) ===\n";
$input = '';
for ($i = 0; $i < 2049; $i++) {
    $input .= chr($i % 251);
}

$hasher = Blake3::newInstance();
$hasher->update($input);
$hash = bin2hex($hasher->finalize());

echo "Our hash:      $hash\n";
echo "Expected hash: 5f4d72f40d7a5f82b15ca2b2e44b1de3c2ef86c426c95c1af0b6879522563030\n";
echo "Match: " . ($hash === '5f4d72f40d7a5f82b15ca2b2e44b1de3c2ef86c426c95c1af0b6879522563030' ? 'YES' : 'NO') . "\n\n";

// Let's trace what happens with chunk counters
echo "=== Tracing chunk counter for 5121 bytes ===\n";

class DebugBlake3 extends Blake3 {
    public $chunk_counters = [];
    
    public function __construct() {
        parent::__construct(\Tourze\Blake3\Constants\Blake3Constants::IV, 0, false);
    }
    
    public function update(string $input): Blake3 {
        // Track chunk counters
        $reflection = new \ReflectionClass($this);
        $chunkStateProp = $reflection->getProperty('chunk_state');
        $chunkStateProp->setAccessible(true);
        
        parent::update($input);
        
        $chunk_state = $chunkStateProp->getValue($this);
        if (isset($chunk_state[0])) {
            $chunkReflection = new \ReflectionClass($chunk_state[0]);
            $getCounter = $chunkReflection->getMethod('getChunkCounter');
            $getCounter->setAccessible(true);
            $counter = $getCounter->invoke($chunk_state[0]);
            $this->chunk_counters[] = $counter;
        }
        
        return $this;
    }
}

$debug_hasher = new DebugBlake3();

// Process in 1024-byte chunks to see counters
for ($i = 0; $i < 5; $i++) {
    $chunk = substr($input, $i * 1024, 1024);
    if (strlen($chunk) > 0) {
        $debug_hasher->update($chunk);
        echo "After chunk $i: current counter = " . end($debug_hasher->chunk_counters) . "\n";
    }
}

// Process final byte
$debug_hasher->update(substr($input, 5120, 1));
echo "After final byte: current counter = " . end($debug_hasher->chunk_counters) . "\n";

// Check if the issue is that the counter increments one too many times
echo "\n=== Hypothesis: Counter off by one? ===\n";
echo "For 5121 bytes, we have:\n";
echo "- 5 complete chunks (0-4)\n";
echo "- 1 partial chunk\n";
echo "- Total chunks processed: 6\n";
echo "- Last chunk counter should be: 5\n";
echo "- Actual last counter: " . end($debug_hasher->chunk_counters) . "\n";