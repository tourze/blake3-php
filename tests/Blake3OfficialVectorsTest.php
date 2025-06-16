<?php

namespace Tourze\Blake3\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Blake3\Blake3;

/**
 * BLAKE3 官方测试向量测试类
 *
 * 测试向量来源：
 * https://github.com/BLAKE3-team/BLAKE3/blob/master/test_vectors/test_vectors.json
 *
 * 这些测试向量由 BLAKE3 团队创建并维护，用于验证 BLAKE3 实现的正确性。
 * 测试数据包含不同长度的输入以及对应的期望哈希值。
 */
class Blake3OfficialVectorsTest extends TestCase
{
    /**
     * 官方测试向量数据
     *
     * 每个测试向量包含：
     * - input_len: 输入数据的字节长度
     * - hash: 期望的哈希值（32字节，64个十六进制字符）
     * - keyed_hash: 使用测试密钥的哈希值（本实现暂不支持）
     *
     * 输入数据是从 0x00 开始的递增字节序列
     */
    private const TEST_VECTORS = [
        // 0 字节
        ['input_len' => 0, 'hash' => 'af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262'],
        // 1 字节: 00
        ['input_len' => 1, 'hash' => '2d3adedff11b61f14c886e35afa036736dcd87a74d27b5c1510225d0f592e213'],
        // 2 字节: 00 01
        ['input_len' => 2, 'hash' => '7b7015bb92cf0b318037702a6cdd81dee41224f734684c2c122cd6359cb1ee63'],
        // 3 字节: 00 01 02
        ['input_len' => 3, 'hash' => 'e1be4d7a8ab5560aa4199eea339849ba8e293d55ca0a81006726d184519e647f'],
        // 4 字节: 00 01 02 03
        ['input_len' => 4, 'hash' => 'f30f5ab28fe047904037f77b6da4fea1e27241c5d132638d8bedce9d40494f32'],
        // 5 字节
        ['input_len' => 5, 'hash' => 'b40b44dfd97e7a84a996a91af8b85188c66c126940ba7aad2e7ae6b385402aa2'],
        // 6 字节
        ['input_len' => 6, 'hash' => '06c4e8ffb6872fad96f9aaca5eee1553eb62aed0ad7198cef42e87f6a616c844'],
        // 7 字节
        ['input_len' => 7, 'hash' => '3f8770f387faad08faa9d8414e9f449ac68e6ff0417f673f602a646a891419fe'],
        // 8 字节
        ['input_len' => 8, 'hash' => '2351207d04fc16ade43ccab08600939c7c1fa70a5c0aaca76063d04c3228eaeb'],
        // 63 字节 (接近一个块的边界)
        ['input_len' => 63, 'hash' => 'e9bc37a594daad83be9470df7f7b3798297c3d834ce80ba85d6e207627b7db7b'],
        // 64 字节 (一个完整的块)
        ['input_len' => 64, 'hash' => '4eed7141ea4a5cd4b788606bd23f46e212af9cacebacdc7d1f4c6dc7f2511b98'],
        // 65 字节 (刚好超过一个块)
        ['input_len' => 65, 'hash' => 'de1e5fa0be70df6d2be8fffd0e99ceaa8eb6e8c93a63f2d8d1c30ecb6b263dee'],
        // 127 字节
        ['input_len' => 127, 'hash' => 'd81293fda863f008c09e92fc382a81f5a0b4a1251cba1634016a0f86a6bd640d'],
        // 128 字节 (两个完整的块)
        ['input_len' => 128, 'hash' => 'f17e570564b26578c33bb7f44643f539624b05df1a76c81f30acd548c44b45ef'],
        // 129 字节
        ['input_len' => 129, 'hash' => '683aaae9f3c5ba37eaaf072aed0f9e30bac0865137bae68b1fde4ca2aebdcb12'],
        // 1023 字节 (接近一个数据块的边界)
        ['input_len' => 1023, 'hash' => '10108970eeda3eb932baac1428c7a2163b0e924c9a9e25b35bba72b28f70bd11'],
        // 1024 字节 (一个完整的数据块)
        ['input_len' => 1024, 'hash' => '42214739f095a406f3fc83deb889744ac00df831c10daa55189b5d121c855af7'],
        // 1025 字节 (刚好超过一个数据块)
        ['input_len' => 1025, 'hash' => 'd00278ae47eb27b34faecf67b4fe263f82d5412916c1ffd97c8cb7fb814b8444'],
        // 2048 字节 (两个完整的数据块)
        ['input_len' => 2048, 'hash' => 'e776b6028c7cd22a4d0ba182a8bf62205d2ef576467e838ed6f2529b85fba24a'],
        // 2049 字节
        ['input_len' => 2049, 'hash' => '5f4d72f40d7a5f82b15ca2b2e44b1de3c2ef86c426c95c1af0b6879522563030'],
        // 3072 字节 (3KB)
        ['input_len' => 3072, 'hash' => 'b98cb0ff3623be03326b373de6b9095218513e64f1ee2edd2525c7ad1e5cffd2'],
        // 3073 字节
        ['input_len' => 3073, 'hash' => '7124b49501012f81cc7f11ca069ec9226cecb8a2c850cfe644e327d22d3e1cd3'],
        // 4096 字节 (4KB)
        ['input_len' => 4096, 'hash' => '015094013f57a5277b59d8475c0501042c0b642e531b0a1c8f58d2163229e969'],
        // 4097 字节
        ['input_len' => 4097, 'hash' => '9b4052b38f1c5fc8b1f9ff7ac7b27cd242487b3d890d15c96a1c25b8aa0fb995'],
        // 5120 字节 (5KB)
        ['input_len' => 5120, 'hash' => '9cadc15fed8b5d854562b26a9536d9707cadeda9b143978f319ab34230535833'],
        // 5121 字节
        ['input_len' => 5121, 'hash' => '628bd2cb2004694adaab7bbd778a25df25c47b9d4155a55f8fbd79f2fe154cff'],
        // 6144 字节 (6KB)
        ['input_len' => 6144, 'hash' => '3e2e5b74e048f3add6d21faab3f83aa44d3b2278afb83b80b3c35164ebeca205'],
        // 6145 字节
        ['input_len' => 6145, 'hash' => 'f1323a8631446cc50536a9f705ee5cb619424d46887f3c376c695b70e0f0507f'],
        // 7168 字节 (7KB)
        ['input_len' => 7168, 'hash' => '61da957ec2499a95d6b8023e2b0e604ec7f6b50e80a9678b89d2628e99ada77a'],
        // 7169 字节
        ['input_len' => 7169, 'hash' => 'a003fc7a51754a9b3c7fae0367ab3d782dccf28855a03d435f8cfe74605e7817'],
        // 8192 字节 (8KB)
        ['input_len' => 8192, 'hash' => 'aae792484c8efe4f19e2ca7d371d8c467ffb10748d8a5a1ae579948f718a2a63'],
        // 8193 字节
        ['input_len' => 8193, 'hash' => 'bab6c09cb8ce8cf459261398d2e7aef35700bf488116ceb94a36d0f5f1b7bc3b'],
        // 16384 字节 (16KB)
        ['input_len' => 16384, 'hash' => 'f875d6646de28985646f34ee13be9a576fd515f76b5b0a26bb324735041ddde4'],
        // 31744 字节 (31KB)
        ['input_len' => 31744, 'hash' => '62b6960e1a44bcc1eb1a611a8d6235b6b4b78f32e7abc4fb4c6cdcce94895c47'],
        // 102400 字节 (100KB)
        ['input_len' => 102400, 'hash' => 'bc3e3d41a1146b069abffad3c0d44860cf664390afce4d9661f7902e7943e085'],
    ];

    /**
     * 测试官方测试向量
     *
     * @dataProvider officialVectorsProvider
     */
    public function testOfficialVectors(int $inputLen, string $expectedHash): void
    {
        $input = $this->generateTestInput($inputLen);

        $hasher = Blake3::newInstance();
        if ($inputLen > 0) {
            $hasher->update($input);
        }
        $hash = bin2hex($hasher->finalize());

        $this->assertEquals(
            $expectedHash,
            $hash,
            "Failed for input length {$inputLen} bytes"
        );
    }

    /**
     * 生成测试输入数据
     *
     * 根据 BLAKE3 官方测试向量规范，输入数据是 0-250 的重复序列
     * 注意：是 251 字节的循环（0, 1, 2, ..., 250, 0, 1, 2, ...）
     *
     * @param int $length 需要生成的字节长度
     * @return string 二进制字符串
     */
    private function generateTestInput(int $length): string
    {
        $data = '';
        for ($i = 0; $i < $length; $i++) {
            $data .= chr($i % 251);  // 注意：是 251，不是 256
        }
        return $data;
    }

    /**
     * 提供官方测试向量数据
     *
     * @return array<array{int, string}>
     */
    public function officialVectorsProvider(): array
    {
        $vectors = [];
        foreach (self::TEST_VECTORS as $vector) {
            $vectors[] = [$vector['input_len'], $vector['hash']];
        }
        return $vectors;
    }

    /**
     * 测试扩展输出长度
     *
     * 测试 BLAKE3 的可扩展输出功能，验证较短的输出是较长输出的前缀
     */
    public function testExtendedOutput(): void
    {
        $testCases = [
            ['input' => '', 'len' => 32],
            ['input' => 'abc', 'len' => 32],
            ['input' => 'The quick brown fox jumps over the lazy dog', 'len' => 32],
            ['input' => $this->generateTestInput(1024), 'len' => 32],
        ];

        foreach ($testCases as $case) {
            $hasher = Blake3::newInstance();
            if (!empty($case['input'])) {
                $hasher->update($case['input']);
            }

            // 获取不同长度的输出
            $output32 = $hasher->finalize(32);
            $output64 = $hasher->finalize(64);
            $output128 = $hasher->finalize(128);
            $output256 = $hasher->finalize(256);

            // 验证较短的输出是较长输出的前缀
            $this->assertEquals(
                $output32,
                substr($output64, 0, 32),
                "32-byte output should be prefix of 64-byte output"
            );
            
            $this->assertEquals(
                $output64,
                substr($output128, 0, 64),
                "64-byte output should be prefix of 128-byte output"
            );
            
            $this->assertEquals(
                $output128,
                substr($output256, 0, 128),
                "128-byte output should be prefix of 256-byte output"
            );
        }
    }

    /**
     * 测试增量更新
     *
     * 验证分块更新和一次性更新产生相同的结果
     */
    public function testIncrementalUpdate(): void
    {
        $testData = $this->generateTestInput(8193); // 刚好超过8KB
        
        // 一次性更新
        $hasher1 = Blake3::newInstance();
        $hasher1->update($testData);
        $hash1 = $hasher1->finalize();
        
        // 分块更新 - 使用不同的块大小
        $chunkSizes = [1, 31, 32, 63, 64, 65, 127, 128, 129, 1023, 1024, 1025];
        
        foreach ($chunkSizes as $chunkSize) {
            $hasher2 = Blake3::newInstance();
            $offset = 0;
            while ($offset < strlen($testData)) {
                $chunk = substr($testData, $offset, $chunkSize);
                $hasher2->update($chunk);
                $offset += $chunkSize;
            }
            $hash2 = $hasher2->finalize();
            
            $this->assertEquals(
                $hash1,
                $hash2,
                "Incremental update with chunk size {$chunkSize} should produce same hash"
            );
        }
    }

    /**
     * 测试已知的测试向量
     *
     * 包含一些常见的测试字符串及其对应的 BLAKE3 哈希值
     */
    public function testKnownVectors(): void
    {
        $knownVectors = [
            // 空字符串
            [
                'input' => '',
                'hash' => 'af1349b9f5f9a1a6a0404dea36dcc9499bcb25c9adc112b7cc9a93cae41f3262'
            ],
            // 单个字符
            [
                'input' => 'a',
                'hash' => '17762fddd969a453925d65717ac3eea21320b66b54342fde15128d6caf21215f'
            ],
            // 'abc'
            [
                'input' => 'abc',
                'hash' => '6437b3ac38465133ffb63b75273a8db548c558465d79db03fd359c6cd5bd9d85'
            ],
            // 消息摘要标准测试字符串
            [
                'input' => 'message digest',
                'hash' => '7bc2a2eeb95ddbf9b7ecf6adcb76b453091c58dc43955e1d9482b1942f08d19b'
            ],
            // 字母表
            [
                'input' => 'abcdefghijklmnopqrstuvwxyz',
                'hash' => '2468eec8894acfb4e4df3a51ea916ba115d48268287754290aae8e9e6228e85f'
            ],
            // 大小写字母和数字
            [
                'input' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
                'hash' => '8bee3200baa9f3a1acd279f049f914f110e730555ff15109bd59cdd73895e239'
            ],
            // 长重复字符串
            [
                'input' => str_repeat('1234567890', 8),
                'hash' => 'f263acf51621980b9c8de5da4a17d314984e05abe4a21cc83a07fe3e1e366dd1'
            ],
            // The quick brown fox
            [
                'input' => 'The quick brown fox jumps over the lazy dog',
                'hash' => '2f1514181aadccd913abd94cfa592701a5686ab23f8df1dff1b74710febc6d4a'
            ],
            // Unicode 字符串
            [
                'input' => 'BLAKE3 是一个加密哈希函数',
                'hash' => 'f084632bea675b5ba528bdae1111b5847f13887c3df17b1f5cb322265c68e1e5'
            ],
        ];

        foreach ($knownVectors as $vector) {
            $hasher = Blake3::newInstance();
            if (!empty($vector['input'])) {
                $hasher->update($vector['input']);
            }
            $hash = bin2hex($hasher->finalize());
            
            $this->assertEquals(
                $vector['hash'],
                $hash,
                "Failed for input: '{$vector['input']}'"
            );
        }
    }
}