<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\ChunkState\Blake3ChunkState;
use Tourze\Blake3\Constants\Blake3Constants;

// Generate 5121 bytes of test data
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

echo "=== Testing 5121 bytes (5 chunks + 1 byte) ===\n";
echo "Total chunks: " . ceil(5121 / 1024) . "\n";
echo "Last chunk size: " . (5121 % 1024) . " bytes\n\n";

// Test chunk processing manually
$hasher = Blake3::newInstance();

// Use reflection to access internal state
$reflection = new ReflectionClass($hasher);
$keyProp = $reflection->getProperty('key');
$keyProp->setAccessible(true);
$key = $keyProp->getValue($hasher);

$flagsProp = $reflection->getProperty('flags');
$flagsProp->setAccessible(true);
$flags = $flagsProp->getValue($hasher);

// Process each chunk manually to see what's happening
$chunkStates = [];
for ($chunk_idx = 0; $chunk_idx < 5; $chunk_idx++) {
    $chunk_start = $chunk_idx * 1024;
    $chunk_data = substr($input, $chunk_start, 1024);
    
    $chunkState = new Blake3ChunkState($key, $chunk_idx, $flags);
    $chunkState->update($chunk_data);
    $cv = $chunkState->output()->chaining_value();
    
    echo "Chunk $chunk_idx CV: ";
    for ($i = 0; $i < 4; $i++) {
        echo sprintf("0x%08x ", $cv[$i]);
    }
    echo "...\n";
    
    $chunkStates[] = $cv;
}

// Process the last partial chunk (1 byte)
$lastChunkData = substr($input, 5120, 1);
echo "\nLast chunk data: 1 byte, value = " . ord($lastChunkData[0]) . " (0x" . sprintf("%02x", ord($lastChunkData[0])) . ")\n";

$lastChunkState = new Blake3ChunkState($key, 5, $flags);
$lastChunkState->update($lastChunkData);
$lastOutput = $lastChunkState->output();
$lastCV = $lastOutput->chaining_value();

echo "Last chunk CV: ";
for ($i = 0; $i < 4; $i++) {
    echo sprintf("0x%08x ", $lastCV[$i]);
}
echo "...\n";

// Now let's see what the final output should be
echo "\n=== Final output generation ===\n";

// Create hasher and process all data
$hasher2 = Blake3::newInstance();
$hasher2->update($input);

// Get the output using reflection
$outputMethod = $reflection->getMethod('output');
$outputMethod->setAccessible(true);
$output = $outputMethod->invoke($hasher2);

// Get the root output bytes
$rootBytes = $output->root_output_bytes(32);
$hash = bin2hex($rootBytes);

echo "Our hash:      $hash\n";
echo "Expected hash: 628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cfa\n";

// Check the specific bytes around position 31
echo "\nBytes around position 31:\n";
for ($i = 28; $i < 32; $i++) {
    echo "Byte $i: 0x" . sprintf("%02x", ord($rootBytes[$i])) . "\n";
}

// Let's check if the issue is related to the counter value
echo "\n=== Checking counter values ===\n";

// The last chunk should have counter = 5
$lastChunkReflection = new ReflectionClass($lastChunkState);
$getChunkCounter = $lastChunkReflection->getMethod('getChunkCounter');
$getChunkCounter->setAccessible(true);
$counter = $getChunkCounter->invoke($lastChunkState);
echo "Last chunk counter: $counter\n";

// Check the stack merging
echo "\n=== Checking tree structure ===\n";
$stackProp = $reflection->getProperty('stack');
$stackProp->setAccessible(true);
$stack = $stackProp->getValue($hasher2);

$stackSizeProp = $reflection->getProperty('stack_size');
$stackSizeProp->setAccessible(true);
$stackSize = $stackSizeProp->getValue($hasher2);

echo "Stack size after processing: $stackSize\n";

// Binary representation of chunk count
$chunkCount = 5; // 0-4 processed chunks
echo "Chunk count (5) in binary: " . decbin($chunkCount) . "\n";
echo "Population count (number of 1s): " . substr_count(decbin($chunkCount), '1') . "\n";