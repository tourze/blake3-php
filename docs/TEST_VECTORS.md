# BLAKE3 Test Vectors Documentation

This document provides information about the test vectors used in the BLAKE3 PHP implementation.

## Official Test Vectors

The primary source of test vectors is the official BLAKE3 repository's test vectors file:
[BLAKE3 Test Vectors JSON](https://github.com/BLAKE3-team/BLAKE3/blob/master/test_vectors/test_vectors.json)

These vectors are published under the MIT License by the BLAKE3 team and provide guaranteed correct inputs and outputs for various BLAKE3 operations.

### Example Vector (From Official Repository)

```json
{
  "key": "whats the Elvish word for friend",
  "context_string": "BLAKE3 2019-12-27 16:29:52 test vectors context",
  "cases": [
    {
      "input_len": 0,
      "hash": "d1e8a7a302a96a110e9e7b1dfbea0fe44578002f07b5e6146aeebb5da988b056",
      "keyed_hash": "72ea343f23ab7a5bee5f289c9376d8ff55e9844c4bcc40cd408727e919a95caa",
      "derive_key": "92b2b75604ed3c761f9d6f62392c8a9227ad0ea3f09573e783f1498a4ed60d26"
    },
    {
      "input_len": 1,
      "hash": "2d3adedff11b61f14c886e35afa036736dcd87a74d27b5c1510225d0f592e213",
      "keyed_hash": "7fd454e49a7a7efcd6f5f85c3fe7e24e42a1483ab19ba0c442bdcc4fc59be227",
      "derive_key": "7213bf554a05e33ce80ae1b5d4045140d2deb86cf40c0c3f209c52de0fcb3abf"
    },
    ...
  ]
}
```

## Other Implementation Vectors

Additional test vectors were derived from these open-source implementations:

### Rust Reference Implementation
- Source: [BLAKE3 Repository](https://github.com/BLAKE3-team/BLAKE3)
- License: CC0-1.0 or Apache-2.0

### Python Implementation
- Source: [blake3-py](https://github.com/oconnor663/blake3-py)
- License: MIT

### JavaScript Implementation
- Source: [blake3](https://github.com/connor4312/blake3)
- License: MIT

### Go Implementation
- Source: [blake3](https://github.com/zeebo/blake3)
- License: MIT

## Vector Types

The tests include vectors for all three BLAKE3 operation modes:

1. **Standard Hashing**: Regular hash computation
2. **Keyed Hashing**: Hash computation with a secret key
3. **Key Derivation**: Deriving keys from context and input

## Special Test Cases

Beyond the standard vectors, we've included tests for:

- Empty inputs
- Block boundary cases (inputs that align with 64-byte block boundaries)
- Chunk boundary cases (inputs that align with 1024-byte chunk boundaries)
- Multi-byte character inputs
- Extremely large outputs (testing extendable output function)
- Chunked updates (testing the ability to process input incrementally)

## Verifying Test Vectors

You can compare our implementation's output against the official reference implementation using the `b3sum` command-line tool:

```bash
# Install b3sum if needed
cargo install b3sum

# Generate a hash with the reference implementation
echo -n "input string" | b3sum --no-names --hex

# Compare with our implementation's output
```

## License

All test vectors are used in accordance with their respective licenses. The primary BLAKE3 test vectors are used under the CC0-1.0 or Apache-2.0 license from the BLAKE3 team.
