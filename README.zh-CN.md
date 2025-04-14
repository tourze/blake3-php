# BLAKE3 PHP 实现

[English](README.md) | [中文](README.zh-CN.md)

这是BLAKE3密码学哈希函数的PHP实现，提供了高性能、安全的哈希计算功能。

## 特性

- 遵循BLAKE3规范的纯PHP实现
- 支持标准哈希、带密钥哈希和派生密钥功能
- 简单易用的API
- 符合PSR-4和PSR-12标准

## 安装

使用Composer安装此包：

```bash
composer require tourze/blake3-php
```

## 性能考虑因素

需要注意，这个纯PHP实现的BLAKE3并不能达到BLAKE3算法在其他语言中原生实现所具有的性能优势。根据我们的基准测试：

- PHP版BLAKE3实现比PHP内置的哈希函数（SHA256、SHA1、MD5）慢得多
- 对于100KB的输入，BLAKE3需要约300ms处理时间，而SHA256仅需0.42ms
- 随着输入数据量增大，性能差距更加明显

这种性能差异的原因是：

1. 缺乏BLAKE3在原生实现中受益的硬件加速和SIMD指令支持
2. PHP解释器对复杂操作的额外开销
3. 这是一个纯PHP实现，优先考虑了兼容性而非性能

如果性能对你的应用至关重要，请考虑使用PHP内置的哈希函数或带有原生BLAKE3实现的PHP扩展。这个库最适合那些需要BLAKE3算法特性但原始性能不是主要考虑因素的应用场景。

详细性能数据请参阅[基准测试结果](benchmark/benchmark_results.md)。

## 使用方法

### 基本哈希

```php
use Tourze\Blake3\Blake3;

// 创建新的哈希实例
$hasher = Blake3::newInstance();

// 更新哈希状态
$hasher->update('hello ');
$hasher->update('world');

// 完成哈希计算并获取32字节(默认)的输出
$hash = $hasher->finalize();
echo bin2hex($hash); // 以十六进制字符串形式输出哈希值
```

### 带密钥的哈希

```php
use Tourze\Blake3\Blake3;

// 创建32字节的密钥
$key = str_repeat('k', 32);

// 创建带密钥的哈希实例
$hasher = Blake3::newKeyedInstance($key);
$hasher->update('message');
$hash = $hasher->finalize();
```

### 密钥派生

```php
use Tourze\Blake3\Blake3;

// 从上下文派生密钥
$hasher = Blake3::newKeyDerivationInstance('application context string');
$hasher->update('input');
$derived_key = $hasher->finalize(32); // 派生一个32字节的密钥
```

## 测试

本包包含全面的测试套件，基于来自BLAKE3官方实现和其他主流语言实现的测试向量：

```bash
composer test
```

## 目录结构

```shell
src/
  ├── Blake3.php                 # 主类
  ├── ChunkState/                # 数据块状态
  │   └── Blake3ChunkState.php
  ├── Constants/                 # 常量定义
  │   └── Blake3Constants.php
  ├── Output/                    # 输出处理
  │   └── Blake3Output.php
  └── Util/                      # 工具函数
      └── Blake3Util.php
```

## 参考文档

- [BLAKE3官方规范](https://github.com/BLAKE3-team/BLAKE3-specs/blob/master/blake3.pdf)
- [BLAKE3官方代码库](https://github.com/BLAKE3-team/BLAKE3)

## 许可

MIT许可证。更多信息请查看[许可文件](LICENSE)。
