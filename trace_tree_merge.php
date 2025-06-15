<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

// Custom Blake3 class to trace tree merging
class TracingBlake3 extends Blake3
{
    private $trace = [];
    
    public function __construct()
    {
        parent::__construct(Blake3Constants::IV, 0);
    }
    
    protected function add_chunk_chaining_value(array $new_cv, int $chunk_counter): void
    {
        echo "\n=== add_chunk_chaining_value called ===\n";
        echo "Chunk counter: $chunk_counter (binary: " . decbin($chunk_counter) . ")\n";
        echo "New CV (first 4): ";
        for ($i = 0; $i < 4; $i++) {
            echo sprintf("0x%08x ", $new_cv[$i]);
        }
        echo "\n";
        
        // Get current stack state
        $reflection = new ReflectionClass($this);
        $stackProp = $reflection->getProperty('stack');
        $stackProp->setAccessible(true);
        $stack = $stackProp->getValue($this);
        
        $stackSizeProp = $reflection->getProperty('stack_size');
        $stackSizeProp->setAccessible(true);
        $stack_size = $stackSizeProp->getValue($this);
        
        echo "Stack size before: $stack_size\n";
        
        // Calculate target stack length
        $post_merge_stack_len = 0;
        $temp = $chunk_counter;
        while ($temp > 0) {
            $post_merge_stack_len += ($temp & 1);
            $temp >>= 1;
        }
        echo "Target stack length: $post_merge_stack_len\n";
        
        // Call parent method
        parent::add_chunk_chaining_value($new_cv, $chunk_counter);
        
        // Get new stack state
        $stack = $stackProp->getValue($this);
        $stack_size = $stackSizeProp->getValue($this);
        
        echo "Stack size after: $stack_size\n";
        for ($i = 0; $i < $stack_size; $i++) {
            echo "  Stack[$i] (first 2): " . sprintf("0x%08x 0x%08x", $stack[$i][0], $stack[$i][1]) . "\n";
        }
    }
}

// Generate test input
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

echo "=== Tracing tree merge for 5121 bytes ===\n";

// Use custom tracing hasher
$hasher = new TracingBlake3();

// Process in chunks to see the tree building
for ($chunk = 0; $chunk < 5; $chunk++) {
    $chunk_data = substr($input, $chunk * 1024, 1024);
    $hasher->update($chunk_data);
}

// Process last partial chunk
$last_chunk = substr($input, 5120, 1);
echo "\n=== Processing final partial chunk (1 byte) ===\n";
$hasher->update($last_chunk);

// Get final hash
$hash = bin2hex($hasher->finalize());
echo "\n=== Final result ===\n";
echo "Our hash:      $hash\n";
echo "Expected hash: 628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa\n";
echo "Last byte: 0x" . substr($hash, -2) . " (expected: 0xfa)\n";