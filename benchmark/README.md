# Blake3 哈希算法基准测试

本目录包含用于测试 Blake3 哈希算法性能的基准测试脚本。这些测试脚本可以帮助评估 Blake3 与其他哈希算法的性能差异，以及
Blake3 不同模式下的表现。

## 测试脚本说明

本目录包含三个基准测试脚本：

1. **benchmark.php** - 基础版基准测试，比较 Blake3 与其他常见哈希算法的性能
2. **benchmark_advanced.php** - 高级版基准测试，提供更详细的性能指标和可视化结果
3. **benchmark_modes.php** - 专注于测试 Blake3 三种模式(普通哈希、密钥哈希、密钥派生)的性能差异

## 如何运行测试

确保你已经安装了所有依赖项，然后可以使用以下命令运行测试：

```bash
# 运行基础版基准测试
php benchmark.php

# 运行高级版基准测试
php benchmark_advanced.php

# 运行 Blake3 三种模式性能对比
php benchmark_modes.php
```

每个脚本执行后会输出测试结果，并生成对应的 Markdown 格式报告：

- benchmark_results.md
- benchmark_advanced_results.md
- benchmark_modes_results.md

## 基准测试参数调整

如果需要调整测试参数，可以在脚本中修改以下变量：

- `$dataSizes` - 测试数据大小（字节）
- `$iterations` - 每种测试组合重复次数
- `$repeats` - (仅高级版)每次测试重复轮数

## 参考信息

- [Blake3 官方网站](https://github.com/BLAKE3-team/BLAKE3)
- [Blake3 算法分析](https://www.cnblogs.com/freemindblog/p/18460416)

## 测试环境

为获得最佳结果，建议在以下环境中运行测试：

- PHP 7.4+ (推荐 PHP 8.0+)
- 至少 2GB 可用内存
- 运行时关闭其他占用大量 CPU 或内存的应用程序

## 结果解释

测试结果将显示各算法在不同数据大小下的执行时间和性能比较：

1. **数据处理速度** - 以毫秒(ms)为单位的处理时间，越低越好
2. **吞吐量** - 每秒处理数据量(MB/s)，越高越好
3. **相对性能** - 与基准算法(通常是SHA256)的性能比较
4. **速度比** - 表示算法速度比基准算法快多少倍

## 注意事项

- 测试结果会因系统配置和运行环境而异
- 高级版基准测试运行时间较长，请耐心等待
- 结果仅反映算法在计算哈希值的速度，不代表安全性评估
- 