# BLAKE3 PHP Implementation

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/blake3-php.svg?style=flat-square)](https://packagist.org/packages/tourze/blake3-php)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/blake3-php.svg?style=flat-square)](https://packagist.org/packages/tourze/blake3-php)
[![License](https://img.shields.io/packagist/l/tourze/blake3-php.svg?style=flat-square)](https://packagist.org/packages/tourze/blake3-php)

A PHP implementation of the BLAKE3 cryptographic hash function, providing high-performance, secure hashing capabilities.

## Features

- Pure PHP implementation following the BLAKE3 specification
- Support for standard hashing, keyed hashing, and key derivation
- Simple and easy-to-use API
- Compliant with PSR-4 and PSR-12 standards

## Installation

Install the package via Composer:

```bash
composer require tourze/blake3-php
```

## Requirements

- PHP 8.1 or higher
- Composer for dependency management

## Performance Considerations

Note that this pure PHP implementation of BLAKE3 does not currently achieve the performance advantages that BLAKE3 is known for in other languages with native implementations. Based on our benchmark tests:

- The PHP BLAKE3 implementation is significantly slower than PHP's built-in hash functions (SHA256, SHA1, MD5)
- For a 100KB input, BLAKE3 takes approximately 300ms while SHA256 only takes 0.42ms
- Performance gap increases with larger input sizes

This performance difference is due to:

1. Lack of hardware acceleration and SIMD instructions that BLAKE3 benefits from in native implementations
2. PHP's interpreter overhead for complex operations
3. This being a pure PHP implementation prioritizing compatibility over performance

If performance is critical for your application, consider using PHP's built-in hash functions or a PHP extension with native BLAKE3 implementation. This library is best suited for applications where BLAKE3's algorithm properties are required, but raw performance is not the primary concern.

See the [benchmark results](benchmark/benchmark_results.md) for detailed performance data.

## Quick Start

### Basic Hashing

```php
use Tourze\Blake3\Blake3;

// Create a new hasher instance
$hasher = Blake3::newInstance();

// Update the hash state
$hasher->update('hello ');
$hasher->update('world');

// Finalize and get the 32-byte (default) output
$hash = $hasher->finalize();
echo bin2hex($hash); // Output the hash as a hex string
```

### Keyed Hashing

```php
use Tourze\Blake3\Blake3;

// Create a 32-byte key
$key = str_repeat('k', 32);

// Create a keyed hasher instance
$hasher = Blake3::newKeyedInstance($key);
$hasher->update('message');
$hash = $hasher->finalize();
```

### Key Derivation

```php
use Tourze\Blake3\Blake3;

// Derive a key from a context
$hasher = Blake3::newKeyDerivationInstance('application context string');
$hasher->update('input');
$derived_key = $hasher->finalize(32); // Derive a 32-byte key
```

## Testing

The package includes comprehensive test suites based on test vectors from the official BLAKE3 implementation and other mainstream language implementations:

```bash
composer test
```

## Directory Structure

```shell
src/
  ├── Blake3.php                 # Main class
  ├── ChunkState/                # Chunk state handling
  │   └── Blake3ChunkState.php
  ├── Constants/                 # Constants definitions
  │   └── Blake3Constants.php
  ├── Output/                    # Output handling
  │   └── Blake3Output.php
  └── Util/                      # Utility functions
      └── Blake3Util.php
```

## References

- [BLAKE3 Official Specification](https://github.com/BLAKE3-team/BLAKE3-specs/blob/master/blake3.pdf)
- [BLAKE3 Official Repository](https://github.com/BLAKE3-team/BLAKE3)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
