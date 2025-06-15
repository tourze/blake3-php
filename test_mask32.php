<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Util\Blake3Util;

echo "=== Testing mask32 function ===\n\n";

// Test some values around the problematic area
$test_values = [
    0xfffffffa,  // -6 in signed 32-bit
    0xfffffffb,  // -5 
    0xfffffffc,  // -4
    0xfffffffd,  // -3
    0xfffffffe,  // -2
    0xffffffff,  // -1
    0x100000000, // 2^32
    0x100000005, // 2^32 + 5
];

foreach ($test_values as $val) {
    $masked = Blake3Util::mask32($val);
    echo sprintf("mask32(0x%x) = 0x%08x\n", $val, $masked);
}

// Test if PHP's integer handling might be causing issues
echo "\n=== Testing PHP integer behavior ===\n";

// In compression, we XOR values. Let's see if that's causing issues
$a = 0xfffffffa;
$b = 0x00000005;
$xor = $a ^ $b;
$masked_xor = Blake3Util::mask32($xor);

echo sprintf("0x%08x ^ 0x%08x = 0x%08x\n", $a, $b, $xor);
echo sprintf("mask32(0x%08x) = 0x%08x\n", $xor, $masked_xor);

// Test specific case that might be happening
echo "\n=== Testing potential overflow scenario ===\n";

// If we have a value that should be 0xfa but becomes 0xff
$correct = 0xfa;
$wrong = 0xff;
$diff = $wrong - $correct;

echo "Correct value: 0x" . sprintf("%02x", $correct) . "\n";
echo "Wrong value:   0x" . sprintf("%02x", $wrong) . "\n";
echo "Difference:    $diff\n";

// Check if the rightrotate32 function might be involved
echo "\n=== Testing rightrotate32 ===\n";

$test_rotate = 0xfffffffa;
for ($n = 0; $n <= 8; $n++) {
    $rotated = Blake3Util::rightrotate32($test_rotate, $n);
    echo sprintf("rightrotate32(0x%08x, %d) = 0x%08x\n", $test_rotate, $n, $rotated);
}

// Test if the issue is in the g function
echo "\n=== Testing potential arithmetic overflow ===\n";

// The g function does: state[a] = mask32(state[a] + state[b] + mx)
// If any of these additions overflow, it might cause issues

$state_a = 0xfffffffa;  // Close to max
$state_b = 0x00000003;
$mx = 0x00000002;

$sum1 = $state_a + $state_b;
$sum2 = $sum1 + $mx;
$masked = Blake3Util::mask32($sum2);

echo sprintf("state[a] = 0x%08x\n", $state_a);
echo sprintf("state[b] = 0x%08x\n", $state_b);
echo sprintf("mx       = 0x%08x\n", $mx);
echo sprintf("sum1     = 0x%016x (before mask)\n", $sum1);
echo sprintf("sum2     = 0x%016x (before mask)\n", $sum2);
echo sprintf("masked   = 0x%08x\n", $masked);

// Check if we're accidentally using signed arithmetic somewhere
echo "\n=== Checking signed vs unsigned ===\n";
$byte_fa = 0xfa;
$byte_ff = 0xff;

echo "0xfa as signed byte: " . unpack('c', chr($byte_fa))[1] . "\n";
echo "0xff as signed byte: " . unpack('c', chr($byte_ff))[1] . "\n";
echo "Difference: " . (unpack('c', chr($byte_ff))[1] - unpack('c', chr($byte_fa))[1]) . "\n";