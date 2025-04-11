<?php

// 修正相对路径，向上一级查找 vendor/autoload.php
$autoloadPaths = [
    __DIR__ . '/../../../vendor/autoload.php',  // 从benchmark目录
    __DIR__ . '/../../vendor/autoload.php',     // 备选路径
    __DIR__ . '/../vendor/autoload.php',        // 备选路径
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require $path;
        break;
    }
}

use Tourze\Blake3\Blake3;

/**
 * Blake3 三种模式性能基准测试
 *
 * 测试 Blake3 的三种操作模式:
 * 1. 普通哈希模式
 * 2. 密钥哈希模式
 * 3. 密钥派生模式
 */
class Blake3ModesBenchmark
{
    /**
     * 测试的数据大小（字节）
     */
    private array $dataSizes = [
        4,
        100,
        1000,
        10000,
        100000,
        1000000,
    ];

    /**
     * 每种大小的测试次数
     */
    private int $iterations = 100;

    /**
     * 结果存储
     */
    private array $results = [];

    /**
     * 运行基准测试
     */
    public function run(): void
    {
        echo "开始 Blake3 三种模式基准测试...\n\n";

        foreach ($this->dataSizes as $size) {
            echo "测试数据大小: $size 字节\n";

            // 生成测试数据
            $data = $this->generateData($size);
            $key = str_repeat('A', 32); // 32字节密钥
            $context = "benchmark-context";

            // 测试普通哈希
            $startTime = microtime(true);
            for ($i = 0; $i < $this->iterations; $i++) {
                $hasher = Blake3::newInstance();
                $hasher->update($data);
                $hash = $hasher->finalize();
            }
            $normalTime = (microtime(true) - $startTime) * 1000 / $this->iterations;
            echo "  普通哈希模式:   " . number_format($normalTime, 4) . " ms\n";

            // 测试密钥哈希
            $startTime = microtime(true);
            for ($i = 0; $i < $this->iterations; $i++) {
                $hasher = Blake3::newKeyedInstance($key);
                $hasher->update($data);
                $hash = $hasher->finalize();
            }
            $keyedTime = (microtime(true) - $startTime) * 1000 / $this->iterations;
            echo "  密钥哈希模式:   " . number_format($keyedTime, 4) . " ms\n";

            // 测试密钥派生
            $startTime = microtime(true);
            for ($i = 0; $i < $this->iterations; $i++) {
                $hasher = Blake3::newKeyDerivationInstance($context);
                $hasher->update($data);
                $hash = $hasher->finalize();
            }
            $derivedTime = (microtime(true) - $startTime) * 1000 / $this->iterations;
            echo "  密钥派生模式:   " . number_format($derivedTime, 4) . " ms\n";

            // 存储结果
            $this->results[] = [
                'size' => $size,
                'normal' => $normalTime,
                'keyed' => $keyedTime,
                'derived' => $derivedTime,
            ];

            echo "\n";
        }

        $this->showResults();
    }

    /**
     * 生成测试数据
     */
    private function generateData(int $size): string
    {
        if ($size <= 100) {
            return random_bytes($size);
        } else {
            $randomPart = random_bytes($size / 4);
            $repeatedPart = str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", (int)($size * 3 / 4 / 62) + 1);
            return substr($randomPart . $repeatedPart, 0, $size);
        }
    }

    /**
     * 显示测试结果
     */
    private function showResults(): void
    {
        echo "========================================\n";
        echo "Blake3 三种模式性能对比\n";
        echo "========================================\n\n";

        echo "| 数据大小 | 普通哈希 (ms) | 密钥哈希 (ms) | 密钥派生 (ms) | 密钥/普通比 | 派生/普通比 |\n";
        echo "|----------|--------------|--------------|--------------|------------|------------|\n";

        foreach ($this->results as $result) {
            $sizeStr = $this->formatSize($result['size']);
            $normalTime = number_format($result['normal'], 4);
            $keyedTime = number_format($result['keyed'], 4);
            $derivedTime = number_format($result['derived'], 4);

            $keyedRatio = number_format($result['keyed'] / $result['normal'], 2);
            $derivedRatio = number_format($result['derived'] / $result['normal'], 2);

            echo "| $sizeStr | $normalTime | $keyedTime | $derivedTime | $keyedRatio | $derivedRatio |\n";
        }

        echo "\n";

        // 导出结果为markdown
        $this->exportMarkdown();
    }

    /**
     * 格式化数据大小
     */
    private function formatSize(int $size): string
    {
        if ($size < 1000) {
            return "${size}B";
        } elseif ($size < 1000000) {
            return round($size / 1024, 1) . "KB";
        } else {
            return round($size / 1024 / 1024, 1) . "MB";
        }
    }

    /**
     * 导出结果为markdown
     */
    private function exportMarkdown(): void
    {
        $markdown = "# Blake3 三种模式性能对比\n\n";
        $markdown .= "## 测试环境\n\n";
        $markdown .= "- PHP: " . PHP_VERSION . "\n";
        $markdown .= "- 操作系统: " . php_uname() . "\n";
        $markdown .= "- 测试日期: " . date('Y-m-d H:i:s') . "\n";
        $markdown .= "- 每种组合测试次数: " . $this->iterations . "\n\n";

        $markdown .= "## 测试结果\n\n";
        $markdown .= "| 数据大小 | 普通哈希 (ms) | 密钥哈希 (ms) | 密钥派生 (ms) | 密钥/普通比 | 派生/普通比 |\n";
        $markdown .= "|----------|--------------|--------------|--------------|------------|------------|\n";

        foreach ($this->results as $result) {
            $sizeStr = $this->formatSize($result['size']);
            $normalTime = number_format($result['normal'], 4);
            $keyedTime = number_format($result['keyed'], 4);
            $derivedTime = number_format($result['derived'], 4);

            $keyedRatio = number_format($result['keyed'] / $result['normal'], 2);
            $derivedRatio = number_format($result['derived'] / $result['normal'], 2);

            $markdown .= "| $sizeStr | $normalTime | $keyedTime | $derivedTime | $keyedRatio | $derivedRatio |\n";
        }

        $markdown .= "\n## 结论\n\n";

        // 计算平均比率
        $avgKeyedRatio = 0;
        $avgDerivedRatio = 0;
        foreach ($this->results as $result) {
            $avgKeyedRatio += $result['keyed'] / $result['normal'];
            $avgDerivedRatio += $result['derived'] / $result['normal'];
        }
        $avgKeyedRatio /= count($this->results);
        $avgDerivedRatio /= count($this->results);

        $markdown .= "- 密钥哈希模式平均比普通哈希模式慢 " . number_format(($avgKeyedRatio - 1) * 100, 1) . "%\n";
        $markdown .= "- 密钥派生模式平均比普通哈希模式慢 " . number_format(($avgDerivedRatio - 1) * 100, 1) . "%\n\n";

        $markdown .= "三种模式的性能差异主要来自内部实现的不同：\n\n";
        $markdown .= "1. **普通哈希模式**：最基本的哈希计算，无附加开销\n";
        $markdown .= "2. **密钥哈希模式**：在哈希计算中融入了密钥，有轻微的额外计算\n";
        $markdown .= "3. **密钥派生模式**：使用上下文字符串作为派生因子，有额外的处理逻辑\n\n";

        $markdown .= "无论使用哪种模式，Blake3 都保持了较高的性能，三种模式的性能差异相对较小。";

        // 保存 markdown 文件
        file_put_contents(__DIR__ . '/benchmark_modes_results.md', $markdown);

        echo "测试结果已保存到 benchmark_modes_results.md\n";
    }
}

// 运行测试
$benchmark = new Blake3ModesBenchmark();
$benchmark->run(); 