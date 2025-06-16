<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\ChunkState\Blake3ChunkState;
use Tourze\Blake3\Output\Blake3Output;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

// Create a modified ChunkState that uses 0 for output counter
class FixedBlake3ChunkState extends Blake3ChunkState
{
    public function output(): Blake3Output
    {
        // Get the original implementation's values
        $reflection = new ReflectionClass(parent::class);
        
        $chainingValueProp = $reflection->getProperty('chaining_value');
        $chainingValueProp->setAccessible(true);
        $chaining_value = $chainingValueProp->getValue($this);
        
        $blockProp = $reflection->getProperty('block');
        $blockProp->setAccessible(true);
        $block = $blockProp->getValue($this);
        
        $blockLenProp = $reflection->getProperty('block_len');
        $blockLenProp->setAccessible(true);
        $block_len = $blockLenProp->getValue($this);
        
        $flagsProp = $reflection->getProperty('flags');
        $flagsProp->setAccessible(true);
        $flags = $flagsProp->getValue($this);
        
        $chunkCounterProp = $reflection->getProperty('chunk_counter');
        $chunkCounterProp->setAccessible(true);
        $chunk_counter = $chunkCounterProp->getValue($this);
        
        // Convert block to words
        $block_words = Blake3Util::words_from_little_endian_bytes($block);
        
        // Create output with counter = 0 instead of chunk_counter
        echo "Original would use counter=$chunk_counter, we're using counter=0\n";
        
        $startFlagMethod = $reflection->getMethod('start_flag');
        $startFlagMethod->setAccessible(true);
        $start_flag = $startFlagMethod->invoke($this);
        
        return new Blake3Output(
            $chaining_value,
            $block_words,
            0,  // Use 0 instead of $this->chunk_counter
            $block_len,
            $flags | $start_flag | Blake3Constants::CHUNK_END
        );
    }
}

// Test with the fixed version
echo "=== Testing with fixed chunk state ===\n\n";

// We need to create a custom Blake3 that uses our fixed chunk state
// This is complex, so let's try a different approach...

// Actually, let me first check what the blocks within a chunk do
echo "Let's trace what happens within a chunk:\n\n";

$key = Blake3Constants::IV;
$chunk = new Blake3ChunkState($key, 5, 0);

// Add 64 bytes (one block)
$chunk->update(str_repeat('A', 64));

// Check the internal state
$reflection = new ReflectionClass($chunk);
$blocksCompressedProp = $reflection->getProperty('blocks_compressed');
$blocksCompressedProp->setAccessible(true);
$blocks_compressed = $blocksCompressedProp->getValue($chunk);

echo "After 64 bytes: blocks_compressed = $blocks_compressed\n";

// The counter used in compress is the chunk_counter, not a block counter
echo "\nThis confirms that within a chunk, all blocks use the same chunk_counter.\n";
echo "So the issue must be elsewhere...\n";

// Wait, let me check the exact error value again
echo "\n=== Re-examining the error ===\n";
echo "For 5121 bytes:\n";
echo "Expected: 0xfa = 250\n";
echo "Actual:   0xff = 255\n";
echo "Error:    255 - 250 = 5\n";
echo "\nThe error is EXACTLY the chunk count (5).\n";
echo "This can't be a coincidence!\n";

// The bug must be that chunk 5's counter is affecting the output
echo "\n=== The bug must be ===\n";
echo "When we create the final output tree, chunk 5's output\n";
echo "has counter=5, and this value is somehow added to the result.\n";