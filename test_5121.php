<?php
require_once "vendor/autoload.php";

use Tourze\Blake3\Blake3;

// Test case for 5121 bytes
$data = "";
for ($i = 0; $i < 5121; $i++) {
    $data .= chr($i % 251);
}

$hash = Blake3::hash($data);
$hex = bin2hex($hash);

echo "5121 bytes hash: $hex\n";

// Expected from official implementation
$expected = "ce5c8112f9df87e022e511ef3f936d11258985b320c8e17a31684de35555f41f";

echo "Expected:        $expected\n";

// Find the difference
for ($i = 0; $i < 32; $i++) {
    $got = ord($hash[$i]);
    $exp = hexdec(substr($expected, $i * 2, 2));
    if ($got !== $exp) {
        echo "Byte $i: got $got, expected $exp, diff = " . ($got - $exp) . "\n";
    }
}