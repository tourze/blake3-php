<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Blake3;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

// Override compress to trace all calls
class TracingBlake3Util extends Blake3Util
{
    public static $trace = [];
    
    public static function compress(
        array $chaining_value,
        array $block_words,
        int $counter,
        int $block_len,
        int $flags
    ): array {
        $trace_entry = [
            'counter' => $counter,
            'block_len' => $block_len,
            'flags' => $flags,
            'cv_first' => sprintf('0x%08x', $chaining_value[0]),
            'flags_str' => self::flagsToString($flags)
        ];
        
        $result = parent::compress($chaining_value, $block_words, $counter, $block_len, $flags);
        
        // Add result info
        $trace_entry['result_word7'] = sprintf('0x%08x', $result[7]);
        $trace_entry['result_byte31'] = sprintf('0x%02x', ($result[7] >> 24) & 0xFF);
        
        self::$trace[] = $trace_entry;
        
        return $result;
    }
    
    private static function flagsToString($flags): string
    {
        $parts = [];
        if ($flags & Blake3Constants::CHUNK_START) $parts[] = 'START';
        if ($flags & Blake3Constants::CHUNK_END) $parts[] = 'END';
        if ($flags & Blake3Constants::PARENT) $parts[] = 'PARENT';
        if ($flags & Blake3Constants::ROOT) $parts[] = 'ROOT';
        return implode('|', $parts) ?: '0';
    }
}

// Monkey patch the compress method
$rc = new ReflectionClass('Tourze\Blake3\Util\Blake3Util');
$filename = $rc->getFileName();

// Read the file
$content = file_get_contents($filename);

// Replace the compress method to call our tracing version
$modified = str_replace(
    'public static function compress(',
    'public static function compress_original(',
    $content
);
$modified = str_replace(
    'class Blake3Util',
    'class Blake3Util
{
    public static function compress(
        array $chaining_value,
        array $block_words,
        int $counter,
        int $block_len,
        int $flags
    ): array {
        return TracingBlake3Util::compress($chaining_value, $block_words, $counter, $block_len, $flags);
    }
    
    public static function compress_original',
    $modified
);

// This approach won't work at runtime, let's try a different method
echo "=== Tracing compression calls for 5121 bytes ===\n\n";

// Let's manually trace the final output generation
$input = '';
for ($i = 0; $i < 5121; $i++) {
    $input .= chr($i % 251);
}

$hasher = Blake3::newInstance();
$hasher->update($input);

// Get the output object
$reflection = new ReflectionClass($hasher);
$outputMethod = $reflection->getMethod('output');
$outputMethod->setAccessible(true);
$output = $outputMethod->invoke($hasher);

// Now trace what happens when we call root_output_bytes
$outputReflection = new ReflectionClass($output);
$props = [];
foreach (['input_chaining_value', 'block_words', 'counter', 'block_len', 'flags'] as $prop) {
    $property = $outputReflection->getProperty($prop);
    $property->setAccessible(true);
    $props[$prop] = $property->getValue($output);
}

echo "Final Blake3Output state:\n";
echo "- counter: {$props['counter']}\n";
echo "- block_len: {$props['block_len']}\n";
echo "- flags: {$props['flags']} (";
if ($props['flags'] & Blake3Constants::PARENT) echo "PARENT ";
if ($props['flags'] & Blake3Constants::ROOT) echo "ROOT ";
echo ")\n\n";

// Manually call compress with ROOT flag
echo "When root_output_bytes is called, it does:\n";
echo "compress(cv, block_words, 0, block_len, flags | ROOT)\n\n";

$result = Blake3Util::compress(
    $props['input_chaining_value'],
    $props['block_words'],
    0, // output_block_counter starts at 0
    $props['block_len'],
    $props['flags'] | Blake3Constants::ROOT
);

echo "Result word 7: " . sprintf('0x%08x', $result[7]) . "\n";
$bytes = pack("V", $result[7]);
echo "Byte 31: " . sprintf('0x%02x', ord($bytes[3])) . "\n";

// Now let's check what happens if we use a different counter
echo "\n=== Testing different counters ===\n";
for ($test_counter = 0; $test_counter <= 5; $test_counter++) {
    $result = Blake3Util::compress(
        $props['input_chaining_value'],
        $props['block_words'],
        $test_counter,
        $props['block_len'],
        $props['flags'] | Blake3Constants::ROOT
    );
    $bytes = pack("V", $result[7]);
    echo "Counter $test_counter: byte 31 = " . sprintf('0x%02x', ord($bytes[3])) . "\n";
}

echo "\nExpected byte 31: 0xfa\n";

// The pattern is clear - the counter value affects the output
// But the final output uses counter=0, so where does the 5 come from?