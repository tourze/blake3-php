<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;

echo "=== Final debugging attempt ===\n\n";

// Let's trace exactly what happens with the last chunk for 5121 bytes
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

// Process up to the last chunk
$hasher = Blake3::newInstance();
$hasher->update(substr($input, 0, 5120)); // First 5 complete chunks

// Check internal state
$reflection = new ReflectionClass($hasher);

$stackProp = $reflection->getProperty('stack');
$stackProp->setAccessible(true);
$stack = $stackProp->getValue($hasher);

$stackSizeProp = $reflection->getProperty('stack_size'); 
$stackSizeProp->setAccessible(true);
$stack_size = $stackSizeProp->getValue($hasher);

$chunkStateProp = $reflection->getProperty('chunk_state');
$chunkStateProp->setAccessible(true);
$chunk_state = $chunkStateProp->getValue($hasher);

// Get chunk counter
$chunkReflection = new ReflectionClass($chunk_state[0]);
$getCounter = $chunkReflection->getMethod('getChunkCounter');
$getCounter->setAccessible(true);
$chunk_counter = $getCounter->invoke($chunk_state[0]);

echo "After processing 5120 bytes:\n";
echo "Stack size: $stack_size\n";
echo "Current chunk counter: $chunk_counter\n";
echo "Chunks processed: 5 (0-4)\n\n";

// Now process the last byte
$hasher->update(substr($input, 5120, 1));

echo "After processing final byte:\n";
$chunk_counter = $getCounter->invoke($chunk_state[0]);
echo "Current chunk counter: $chunk_counter\n";

// Get the final hash
$hash = bin2hex($hasher->finalize());
echo "\nFinal hash: $hash\n";
echo "Expected:   628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa\n";

// Check if the issue might be with the stack array
echo "\n=== Checking stack array handling ===\n";

// In add_chunk_chaining_value, we do:
// $this->stack[$this->stack_size] = $new_cv;
// $this->stack_size++;

// Let's check if there's an off-by-one error
$test_stack = [];
$test_stack_size = 0;

// Simulate adding 5 chunks
for ($i = 0; $i < 5; $i++) {
    echo "Adding chunk $i: stack_size before = $test_stack_size\n";
    $test_stack[$test_stack_size] = "chunk_$i";
    $test_stack_size++;
    echo "  stack_size after = $test_stack_size\n";
}

echo "\nFinal test stack: " . json_encode($test_stack) . "\n";
echo "Final test stack size: $test_stack_size\n";

// One more check - let's see if the chunk_state array might be the issue
echo "\n=== Checking chunk_state array ===\n";
echo "chunk_state is an array with " . count($chunk_state) . " elements\n";
echo "We always use chunk_state[0]\n";

// Maybe the issue is that we're using chunk_state as an array when it should be a single object?
// Let's check the initialization in the constructor
$keyProp = $reflection->getProperty('key');
$keyProp->setAccessible(true);
$key = $keyProp->getValue($hasher);

$flagsProp = $reflection->getProperty('flags');
$flagsProp->setAccessible(true);
$flags = $flagsProp->getValue($hasher);

echo "\nIn constructor, chunk_state is initialized as:\n";
echo '$this->chunk_state = [new Blake3ChunkState($key, 0, $flags)];' . "\n";
echo "This creates an array with one element at index 0\n";