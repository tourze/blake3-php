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
 * Blake3哈希算法高级性能基准测试
 *
 * 提供更详细的性能指标和比较结果
 *
 * 参考: https://www.cnblogs.com/freemindblog/p/18460416
 */
class AdvancedHashBenchmark
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
    private int $iterations = 50;

    /**
     * 性能测试重复次数
     */
    private int $repeats = 3;

    /**
     * 测试的哈希算法
     */
    private array $algorithms = [];

    /**
     * 结果存储
     */
    private array $results = [];

    /**
     * 性能比较基准算法
     */
    private string $baselineAlgorithm = 'SHA256';

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 设置测试的哈希算法
        $this->algorithms = [
            'Blake3' => function ($data) {
                $hasher = Blake3::newInstance();
                $hasher->update($data);
                return $hasher->finalize();
            },
            'Blake3Keyed' => function ($data) {
                $key = str_repeat('A', 32); // 32字节密钥
                $hasher = Blake3::newKeyedInstance($key);
                $hasher->update($data);
                return $hasher->finalize();
            },
            'Blake3DeriveKey' => function ($data) {
                $context = "benchmark-context";
                $hasher = Blake3::newKeyDerivationInstance($context);
                $hasher->update($data);
                return $hasher->finalize();
            },
            'SHA256' => function ($data) {
                return hash('sha256', $data, true);
            },
            'SHA512' => function ($data) {
                return hash('sha512', $data, true);
            },
            'SHA1' => function ($data) {
                return hash('sha1', $data, true);
            },
            'MD5' => function ($data) {
                return hash('md5', $data, true);
            },
        ];
    }

    /**
     * 生成测试数据
     *
     * @param int $size 数据大小（字节）
     * @return string 生成的测试数据
     */
    private function generateData(int $size): string
    {
        // 使用更真实的数据构成
        if ($size <= 100) {
            // 小数据使用随机字符
            return random_bytes($size);
        } else {
            // 大数据使用一些结构化内容
            $randomData = random_bytes($size / 5);
            $repeatedData = str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz', (int)($size * 4 / 5 / 62) + 1);

            return substr($randomData . $repeatedData, 0, $size);
        }
    }

    /**
     * 运行单个哈希算法测试
     *
     * @param string $algoName 算法名称
     * @param callable $algoFunc 算法函数
     * @param string $data 测试数据
     * @param int $dataSize 数据大小
     * @return array 测试结果
     */
    private function runTest(string $algoName, callable $algoFunc, string $data, int $dataSize): array
    {
        // 预热
        $hash = '';
        for ($i = 0; $i < 5; $i++) {
            $hash = $algoFunc($data);
        }

        $allTimes = [];

        // 多次运行测试
        for ($r = 0; $r < $this->repeats; $r++) {
            $times = [];

            for ($i = 0; $i < $this->iterations; $i++) {
                $start = microtime(true);
                $hash = $algoFunc($data);
                $end = microtime(true);

                $times[] = ($end - $start) * 1000; // 转换为毫秒
            }

            $allTimes = array_merge($allTimes, $times);

            // 在重复之间暂停一会，让系统冷却
            if ($r < $this->repeats - 1) {
                usleep(100000); // 100ms
            }
        }

        // 计算统计信息
        sort($allTimes);
        $min = $allTimes[0];
        $max = $allTimes[count($allTimes) - 1];
        $avg = array_sum($allTimes) / count($allTimes);

        // 去掉最高和最低10%的值，计算稳定平均值
        $trimIndex = (int)(count($allTimes) * 0.1);
        $stableTimes = array_slice($allTimes, $trimIndex, count($allTimes) - $trimIndex * 2);
        $stableAvg = array_sum($stableTimes) / count($stableTimes);

        // 计算吞吐量 (MB/s)
        $throughput = ($dataSize / 1024 / 1024) / ($stableAvg / 1000);

        // 计算中位数和标准差
        $median = $allTimes[(int)floor(count($allTimes) / 2)];
        $variance = 0;
        foreach ($allTimes as $time) {
            $variance += pow($time - $avg, 2);
        }
        $stdDev = sqrt($variance / count($allTimes));

        return [
            'algorithm' => $algoName,
            'data_size' => $dataSize,
            'min_time' => $min,
            'max_time' => $max,
            'avg_time' => $avg,
            'stable_avg_time' => $stableAvg,
            'median_time' => $median,
            'std_dev' => $stdDev,
            'throughput' => $throughput,
            'iterations' => $this->iterations * $this->repeats,
            'hash_length' => strlen($hash),
        ];
    }

    /**
     * 运行所有测试
     */
    public function runAllTests(): void
    {
        foreach ($this->dataSizes as $size) {
            echo "生成 $size 字节的测试数据...\n";
            $data = $this->generateData($size);

            foreach ($this->algorithms as $name => $func) {
                echo "测试 $name 算法，数据大小 $size 字节...";
                $result = $this->runTest($name, $func, $data, $size);
                $this->results[] = $result;
                echo " 完成！平均时间: " . number_format($result['stable_avg_time'], 4) . " ms\n";
            }

            echo "\n";
        }
    }

    /**
     * 展示测试结果
     */
    public function showResults(): void
    {
        echo str_repeat('=', 120) . "\n";
        echo "基准测试结果摘要\n";
        echo str_repeat('=', 120) . "\n\n";

        // 按数据大小分组显示
        foreach ($this->dataSizes as $size) {
            echo "数据大小: $size 字节\n";
            echo str_repeat('-', 120) . "\n";
            printf("| %-15s | %-12s | %-12s | %-12s | %-12s | %-12s | %-15s |\n",
                "算法", "最小(ms)", "最大(ms)", "平均(ms)", "中位数(ms)", "标准差", "吞吐量(MB/s)");
            echo str_repeat('-', 120) . "\n";

            $sizeResults = [];
            foreach ($this->algorithms as $name => $func) {
                $result = $this->findResult($name, $size);
                if ($result !== null) {
                    $sizeResults[] = $result;
                }
            }

            // 按平均时间排序
            usort($sizeResults, function ($a, $b) {
                return $a['stable_avg_time'] <=> $b['stable_avg_time'];
            });

            foreach ($sizeResults as $result) {
                printf("| %-15s | %-12.4f | %-12.4f | %-12.4f | %-12.4f | %-12.4f | %-15.4f |\n",
                    $result['algorithm'],
                    $result['min_time'],
                    $result['max_time'],
                    $result['stable_avg_time'],
                    $result['median_time'],
                    $result['std_dev'],
                    $result['throughput']);
            }

            echo str_repeat('-', 120) . "\n\n";

            // 显示相对性能
            echo "相对性能 (与 {$this->baselineAlgorithm} 比较):\n";
            echo str_repeat('-', 80) . "\n";
            printf("| %-15s | %-15s | %-20s | %-20s |\n",
                "算法", "速度比", "比 {$this->baselineAlgorithm} 快", "性能提升");
            echo str_repeat('-', 80) . "\n";

            $baselineResult = $this->findResult($this->baselineAlgorithm, $size);
            if ($baselineResult !== null) {
                $baselineTime = $baselineResult['stable_avg_time'];

                foreach ($sizeResults as $result) {
                    $speedRatio = $baselineTime / $result['stable_avg_time'];
                    $fasterBy = ($speedRatio - 1) * 100;
                    $performanceGain = ($fasterBy > 0) ? "{$fasterBy}% 提升" : abs($fasterBy) . "% 下降";

                    printf("| %-15s | %-15.2f | %-20s | %-20s |\n",
                        $result['algorithm'],
                        $speedRatio,
                        ($fasterBy > 0) ? "快 " . number_format($speedRatio, 2) . " 倍" : "慢 " . number_format(1 / $speedRatio, 2) . " 倍",
                        $performanceGain);
                }
            }

            echo str_repeat('-', 80) . "\n\n";
        }

        // 总结表格 - 所有数据大小下的平均性能比较
        echo "总结: 各算法在所有数据大小下的平均性能比\n";
        echo str_repeat('-', 80) . "\n";
        printf("| %-15s | %-10s | %-15s | %-15s | %-15s |\n",
            "算法", "平均排名", "小数据(<1KB)", "中等数据", "大数据(>100KB)");
        echo str_repeat('-', 80) . "\n";

        $algorithmStats = [];

        // 初始化统计数据
        foreach ($this->algorithms as $name => $func) {
            $algorithmStats[$name] = [
                'small_ratio' => 0,
                'medium_ratio' => 0,
                'large_ratio' => 0,
                'rank_sum' => 0,
                'count' => 0,
                'avg_rank' => 0,
            ];
        }

        // 计算每个算法在不同数据大小下的性能比
        foreach ($this->dataSizes as $size) {
            $sizeResults = [];
            foreach ($this->algorithms as $name => $func) {
                $result = $this->findResult($name, $size);
                if ($result !== null) {
                    $sizeResults[] = $result;
                }
            }

            // 按性能排序
            usort($sizeResults, function ($a, $b) {
                return $a['stable_avg_time'] <=> $b['stable_avg_time'];
            });

            // 更新排名和性能比
            $baselineResult = $this->findResult($this->baselineAlgorithm, $size);
            if ($baselineResult !== null) {
                $baselineTime = $baselineResult['stable_avg_time'];

                foreach ($sizeResults as $rank => $result) {
                    $speedRatio = $baselineTime / $result['stable_avg_time'];
                    $algorithmStats[$result['algorithm']]['rank_sum'] += ($rank + 1);
                    $algorithmStats[$result['algorithm']]['count']++;

                    if ($size < 1000) {
                        $algorithmStats[$result['algorithm']]['small_ratio'] += $speedRatio;
                    } elseif ($size < 100000) {
                        $algorithmStats[$result['algorithm']]['medium_ratio'] += $speedRatio;
                    } else {
                        $algorithmStats[$result['algorithm']]['large_ratio'] += $speedRatio;
                    }
                }
            }
        }

        // 计算平均值
        $smallSizeCount = 0;
        $mediumSizeCount = 0;
        $largeSizeCount = 0;

        foreach ($this->dataSizes as $size) {
            if ($size < 1000) $smallSizeCount++;
            elseif ($size < 100000) $mediumSizeCount++;
            else $largeSizeCount++;
        }

        foreach ($algorithmStats as $name => &$stats) {
            if ($stats['count'] > 0) {
                $stats['avg_rank'] = $stats['rank_sum'] / $stats['count'];
                $stats['small_ratio'] = ($smallSizeCount > 0) ? $stats['small_ratio'] / $smallSizeCount : 0;
                $stats['medium_ratio'] = ($mediumSizeCount > 0) ? $stats['medium_ratio'] / $mediumSizeCount : 0;
                $stats['large_ratio'] = ($largeSizeCount > 0) ? $stats['large_ratio'] / $largeSizeCount : 0;
            }
        }

        // 按平均排名排序
        uasort($algorithmStats, function ($a, $b) {
            return $a['avg_rank'] <=> $b['avg_rank'];
        });

        // 输出总结表格
        foreach ($algorithmStats as $name => $stats) {
            printf("| %-15s | %-10.2f | %-15.2f | %-15.2f | %-15.2f |\n",
                $name,
                $stats['avg_rank'],
                $stats['small_ratio'],
                $stats['medium_ratio'],
                $stats['large_ratio']);
        }

        echo str_repeat('-', 80) . "\n\n";

        // 简单的ASCII图表展示各算法在不同数据大小下的表现
        echo "性能比较图表 (y轴：速度比，x轴：数据大小)\n";
        echo "注：图表中的值是相对于 {$this->baselineAlgorithm} 的速度比\n\n";

        $this->showAsciiChart();
    }

    /**
     * 显示ASCII图表
     */
    private function showAsciiChart(): void
    {
        $chartHeight = 20;
        $chartWidth = 100;

        // 创建空图表
        $chart = array_fill(0, $chartHeight, str_repeat(' ', $chartWidth));

        // 绘制Y轴
        for ($i = 0; $i < $chartHeight; $i++) {
            $chart[$i][0] = '|';
        }

        // 绘制X轴
        $chart[$chartHeight - 1] = str_repeat('-', $chartWidth);

        // 为每个算法计算一种字符
        $algorithmChars = [];
        $charIndex = 0;
        $chars = ['*', '+', 'x', 'o', '#', '@', '^', '&', '%', '$'];

        foreach ($this->algorithms as $name => $func) {
            if ($name != $this->baselineAlgorithm) {
                $algorithmChars[$name] = $chars[$charIndex % count($chars)];
                $charIndex++;
            }
        }

        // 找到最大速度比值用于缩放
        $maxRatio = 1.0;
        foreach ($this->dataSizes as $size) {
            $baselineResult = $this->findResult($this->baselineAlgorithm, $size);
            if ($baselineResult !== null) {
                $baselineTime = $baselineResult['stable_avg_time'];

                foreach ($this->algorithms as $name => $func) {
                    if ($name != $this->baselineAlgorithm) {
                        $result = $this->findResult($name, $size);
                        if ($result !== null) {
                            $speedRatio = $baselineTime / $result['stable_avg_time'];
                            $maxRatio = max($maxRatio, $speedRatio);
                        }
                    }
                }
            }
        }

        // 绘制数据点
        $sizePositions = [];
        $xStep = ($chartWidth - 5) / count($this->dataSizes);

        foreach ($this->dataSizes as $index => $size) {
            $xPos = 5 + (int)($index * $xStep);
            $sizePositions[$size] = $xPos;

            // 标记X轴
            $sizeLabel = $this->formatSize($size);
            $startPos = max(1, $xPos - strlen($sizeLabel) / 2);
            for ($i = 0; $i < strlen($sizeLabel); $i++) {
                if ($startPos + $i < $chartWidth) {
                    $chart[$chartHeight - 1][$startPos + $i] = $sizeLabel[$i];
                }
            }
        }

        // 绘制Y轴标签
        $yMax = ceil($maxRatio * 1.1); // 给最大值增加10%空间
        $yStep = $yMax / ($chartHeight - 2);

        for ($i = 0; $i < $chartHeight - 1; $i++) {
            $yValue = $yMax - $i * $yStep;
            if ($yValue >= 0 && $i % 4 == 0) {
                $yLabel = number_format($yValue, 1);
                for ($j = 0; $j < strlen($yLabel); $j++) {
                    if (isset($chart[$i][2 + $j])) {
                        $chart[$i][2 + $j] = $yLabel[$j];
                    }
                }
            }
        }

        // 绘制基准线
        $baselineY = $chartHeight - 2 - (int)(1.0 / $yStep);
        if ($baselineY >= 0 && $baselineY < $chartHeight - 1) {
            for ($x = 1; $x < $chartWidth; $x++) {
                $chart[$baselineY][$x] = '-';
            }
        }

        // 绘制各算法的数据点
        foreach ($this->dataSizes as $size) {
            $xPos = $sizePositions[$size];
            $baselineResult = $this->findResult($this->baselineAlgorithm, $size);

            if ($baselineResult !== null) {
                $baselineTime = $baselineResult['stable_avg_time'];

                foreach ($this->algorithms as $name => $func) {
                    if ($name != $this->baselineAlgorithm) {
                        $result = $this->findResult($name, $size);
                        if ($result !== null) {
                            $speedRatio = $baselineTime / $result['stable_avg_time'];
                            $yPos = $chartHeight - 2 - (int)($speedRatio / $yStep);

                            if ($yPos >= 0 && $yPos < $chartHeight - 1) {
                                $chart[$yPos][$xPos] = $algorithmChars[$name];
                            }
                        }
                    }
                }
            }
        }

        // 输出图表
        for ($i = 0; $i < $chartHeight; $i++) {
            echo $chart[$i] . "\n";
        }

        // 输出图例
        echo "\n图例:\n";
        echo "基准线(1.0) 表示与 {$this->baselineAlgorithm} 相同的性能\n";
        foreach ($algorithmChars as $name => $char) {
            echo "$char - $name\n";
        }
        echo "\n";
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
     * 查找指定算法和数据大小的测试结果
     *
     * @param string $algorithm 算法名称
     * @param int $size 数据大小
     * @return array|null 找到的结果或null
     */
    private function findResult(string $algorithm, int $size): ?array
    {
        foreach ($this->results as $result) {
            if ($result['algorithm'] === $algorithm && $result['data_size'] === $size) {
                return $result;
            }
        }
        return null;
    }

    /**
     * 导出结果为详细的markdown表格
     */
    public function exportMarkdown(): string
    {
        $markdown = "# Blake3 哈希算法性能基准测试报告\n\n";
        $markdown .= "## 测试环境\n\n";
        $markdown .= "- PHP: " . PHP_VERSION . "\n";
        $markdown .= "- 操作系统: " . php_uname() . "\n";
        $markdown .= "- 测试日期: " . date('Y-m-d H:i:s') . "\n";
        $markdown .= "- 测试次数: 每个数据大小和算法组合测试 " . ($this->iterations * $this->repeats) . " 次\n\n";

        $markdown .= "## 测试结果摘要\n\n";

        // 添加每个数据大小的测试结果
        foreach ($this->dataSizes as $size) {
            $formattedSize = $this->formatSize($size);
            $markdown .= "### 数据大小: $formattedSize ($size 字节)\n\n";

            // 基本性能表
            $markdown .= "#### 基本性能指标\n\n";
            $markdown .= "| 算法 | 最小耗时(ms) | 最大耗时(ms) | 平均耗时(ms) | 中位数(ms) | 标准差 | 吞吐量(MB/s) |\n";
            $markdown .= "| --- | --- | --- | --- | --- | --- | --- |\n";

            $sizeResults = [];
            foreach ($this->algorithms as $name => $func) {
                $result = $this->findResult($name, $size);
                if ($result !== null) {
                    $sizeResults[] = $result;
                }
            }

            // 按平均时间排序
            usort($sizeResults, function ($a, $b) {
                return $a['stable_avg_time'] <=> $b['stable_avg_time'];
            });

            foreach ($sizeResults as $result) {
                $markdown .= sprintf("| %s | %.4f | %.4f | %.4f | %.4f | %.4f | %.4f |\n",
                    $result['algorithm'],
                    $result['min_time'],
                    $result['max_time'],
                    $result['stable_avg_time'],
                    $result['median_time'],
                    $result['std_dev'],
                    $result['throughput']);
            }

            $markdown .= "\n";

            // 相对性能表
            $markdown .= "#### 相对性能 (与 {$this->baselineAlgorithm} 比较)\n\n";
            $markdown .= "| 算法 | 速度比 | 比 {$this->baselineAlgorithm} 快 | 性能提升 |\n";
            $markdown .= "| --- | --- | --- | --- |\n";

            $baselineResult = $this->findResult($this->baselineAlgorithm, $size);
            if ($baselineResult !== null) {
                $baselineTime = $baselineResult['stable_avg_time'];

                foreach ($sizeResults as $result) {
                    $speedRatio = $baselineTime / $result['stable_avg_time'];
                    $fasterBy = ($speedRatio - 1) * 100;
                    $performanceGain = ($fasterBy > 0) ? "{$fasterBy}% 提升" : abs($fasterBy) . "% 下降";

                    $markdown .= sprintf("| %s | %.2f | %s | %s |\n",
                        $result['algorithm'],
                        $speedRatio,
                        ($fasterBy > 0) ? "快 " . number_format($speedRatio, 2) . " 倍" : "慢 " . number_format(1 / $speedRatio, 2) . " 倍",
                        $performanceGain);
                }
            }

            $markdown .= "\n";
        }

        // 总结表格
        $markdown .= "## 总体性能比较\n\n";
        $markdown .= "各算法在不同数据大小下的平均性能比较：\n\n";
        $markdown .= "| 算法 | 平均排名 | 小数据(<1KB) | 中等数据 | 大数据(>100KB) |\n";
        $markdown .= "| --- | --- | --- | --- | --- |\n";

        $algorithmStats = [];

        // 初始化统计数据
        foreach ($this->algorithms as $name => $func) {
            $algorithmStats[$name] = [
                'small_ratio' => 0,
                'medium_ratio' => 0,
                'large_ratio' => 0,
                'rank_sum' => 0,
                'count' => 0,
                'avg_rank' => 0,
            ];
        }

        // 计算每个算法在不同数据大小下的性能比
        foreach ($this->dataSizes as $size) {
            $sizeResults = [];
            foreach ($this->algorithms as $name => $func) {
                $result = $this->findResult($name, $size);
                if ($result !== null) {
                    $sizeResults[] = $result;
                }
            }

            // 按性能排序
            usort($sizeResults, function ($a, $b) {
                return $a['stable_avg_time'] <=> $b['stable_avg_time'];
            });

            // 更新排名和性能比
            $baselineResult = $this->findResult($this->baselineAlgorithm, $size);
            if ($baselineResult !== null) {
                $baselineTime = $baselineResult['stable_avg_time'];

                foreach ($sizeResults as $rank => $result) {
                    $speedRatio = $baselineTime / $result['stable_avg_time'];
                    $algorithmStats[$result['algorithm']]['rank_sum'] += ($rank + 1);
                    $algorithmStats[$result['algorithm']]['count']++;

                    if ($size < 1000) {
                        $algorithmStats[$result['algorithm']]['small_ratio'] += $speedRatio;
                    } elseif ($size < 100000) {
                        $algorithmStats[$result['algorithm']]['medium_ratio'] += $speedRatio;
                    } else {
                        $algorithmStats[$result['algorithm']]['large_ratio'] += $speedRatio;
                    }
                }
            }
        }

        // 计算平均值
        $smallSizeCount = 0;
        $mediumSizeCount = 0;
        $largeSizeCount = 0;

        foreach ($this->dataSizes as $size) {
            if ($size < 1000) $smallSizeCount++;
            elseif ($size < 100000) $mediumSizeCount++;
            else $largeSizeCount++;
        }

        foreach ($algorithmStats as $name => &$stats) {
            if ($stats['count'] > 0) {
                $stats['avg_rank'] = $stats['rank_sum'] / $stats['count'];
                $stats['small_ratio'] = ($smallSizeCount > 0) ? $stats['small_ratio'] / $smallSizeCount : 0;
                $stats['medium_ratio'] = ($mediumSizeCount > 0) ? $stats['medium_ratio'] / $mediumSizeCount : 0;
                $stats['large_ratio'] = ($largeSizeCount > 0) ? $stats['large_ratio'] / $largeSizeCount : 0;
            }
        }

        // 按平均排名排序
        uasort($algorithmStats, function ($a, $b) {
            return $a['avg_rank'] <=> $b['avg_rank'];
        });

        // 输出总结表格
        foreach ($algorithmStats as $name => $stats) {
            $markdown .= sprintf("| %s | %.2f | %.2f | %.2f | %.2f |\n",
                $name,
                $stats['avg_rank'],
                $stats['small_ratio'],
                $stats['medium_ratio'],
                $stats['large_ratio']);
        }

        $markdown .= "\n";

        // 添加结论和注释
        $markdown .= "## 结论\n\n";

        // 找出总体最快的算法
        $fastestAlgo = key($algorithmStats);
        $fastestRatio = reset($algorithmStats)['medium_ratio'];

        $markdown .= "- 在各种数据大小下，**{$fastestAlgo}** 平均表现最好\n";

        if ($fastestAlgo == 'Blake3') {
            $markdown .= "- Blake3 在总体表现上比 {$this->baselineAlgorithm} " .
                ($fastestRatio > 1 ? "快约 " . number_format($fastestRatio, 2) . " 倍" : "慢约 " . number_format(1 / $fastestRatio, 2) . " 倍") . "\n";

            // 找出Blake3在大数据下的表现
            $largeDataRatio = $algorithmStats['Blake3']['large_ratio'];
            $markdown .= "- 在处理大量数据 (>100KB) 时，Blake3 比 {$this->baselineAlgorithm} " .
                ($largeDataRatio > 1 ? "快约 " . number_format($largeDataRatio, 2) . " 倍" : "慢约 " . number_format(1 / $largeDataRatio, 2) . " 倍") . "\n";
        }

        $markdown .= "\n## 注意事项\n\n";
        $markdown .= "- 此基准测试在单一环境下进行，不同的硬件和系统配置可能会产生不同的结果\n";
        $markdown .= "- 测试结果仅反映算法在计算哈希值的速度，不代表安全性的评估\n";
        $markdown .= "- Blake3 相比传统哈希算法的主要优势在于其高度并行化的设计，在多核环境中可能表现更佳\n";

        return $markdown;
    }
}

// 运行测试
echo "启动 Blake3 哈希算法高级性能基准测试...\n\n";

$benchmark = new AdvancedHashBenchmark();
$benchmark->runAllTests();
$benchmark->showResults();

// 导出markdown到文件
$markdown = $benchmark->exportMarkdown();
file_put_contents(__DIR__ . '/benchmark_advanced_results.md', $markdown);

echo "\n高级基准测试完成，详细结果已保存到 benchmark_advanced_results.md\n"; 