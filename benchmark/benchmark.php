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
 * Blake3哈希算法性能基准测试
 *
 * 比较 Blake3 与 PHP 内置哈希算法的性能表现
 *
 * 参考: https://www.cnblogs.com/freemindblog/p/18460416
 */
class HashBenchmark
{
    /**
     * 测试的数据大小（字节）
     *
     * @var array<int>
     */
    private array $dataSizes = [
        4,
        100,
        1000,
        10000,
        100000,
    ];

    /**
     * 每种大小的测试次数
     */
    private int $iterations = 100;

    /**
     * 测试的哈希算法
     *
     * @var array<string, callable(string): string>
     */
    private array $algorithms = [];

    /**
     * 结果存储
     *
     * @var array<string, array<string, mixed>>
     */
    private array $results = [];

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
            'SHA256' => function ($data) {
                return hash('sha256', $data, true);
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
        $data = '';
        for ($i = 0; $i < $size; $i++) {
            $data .= chr($i % 256);
        }
        return $data;
    }

    /**
     * 运行单个哈希算法测试
     *
     * @param string $algoName 算法名称
     * @param callable(string): string $algoFunc 算法函数
     * @param string $data 测试数据
     * @param int $dataSize 数据大小
     * @return array<string, mixed> 测试结果
     */
    private function runTest(string $algoName, callable $algoFunc, string $data, int $dataSize): array
    {
        // 预热
        $algoFunc($data);

        $times = [];

        // 多次运行测试
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);
            $algoFunc($data);
            $end = microtime(true);

            $times[] = ($end - $start) * 1000; // 转换为毫秒
        }

        // 计算统计信息
        sort($times);
        $min = $times[0];
        $max = $times[count($times) - 1];
        $avg = array_sum($times) / count($times);

        // 去掉最高和最低10%的值，计算稳定平均值
        $trimIndex = (int)(count($times) * 0.1);
        $stableTimes = array_slice($times, $trimIndex, count($times) - $trimIndex * 2);
        $stableAvg = array_sum($stableTimes) / count($stableTimes);

        return [
            'algorithm' => $algoName,
            'data_size' => $dataSize,
            'min_time' => $min,
            'max_time' => $max,
            'avg_time' => $avg,
            'stable_avg_time' => $stableAvg,
            'iterations' => $this->iterations,
        ];
    }

    /**
     * 运行所有测试
     */
    public function runAllTests(): void
    {
        foreach ($this->dataSizes as $size) {
            $data = $this->generateData($size);

            foreach ($this->algorithms as $name => $func) {
                echo "测试 $name 算法，数据大小 $size 字节...\n";
                $result = $this->runTest((string) $name, $func, $data, (int) $size);
                $this->results[] = $result;
            }

            echo "\n";
        }
    }

    /**
     * 展示测试结果
     */
    public function showResults(): void
    {
        echo str_repeat('-', 110) . "\n";
        printf("| %-10s | %-10s | %-15s | %-15s | %-15s | %-15s |\n",
            "算法", "字节数", "最小耗时(ms)", "最大耗时(ms)", "平均耗时(ms)", "稳定平均(ms)");
        echo str_repeat('-', 110) . "\n";

        foreach ($this->dataSizes as $size) {
            foreach ($this->algorithms as $name => $func) {
                $result = $this->findResult($name, $size);

                if ($result !== null) {
                    printf("| %-10s | %-10d | %-15.4f | %-15.4f | %-15.4f | %-15.4f |\n",
                        (string) $result['algorithm'],
                        (int) $result['data_size'],
                        (float) $result['min_time'],
                        (float) $result['max_time'],
                        (float) $result['avg_time'],
                        (float) $result['stable_avg_time']);
                }
            }
            echo str_repeat('-', 110) . "\n";
        }
    }

    /**
     * 查找指定算法和数据大小的测试结果
     *
     * @param string $algorithm 算法名称
     * @param int $size 数据大小
     * @return array<string, mixed>|null 找到的结果或null
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
     * 导出结果为markdown表格
     */
    public function exportMarkdown(): string
    {
        $markdown = "# Blake3 哈希算法性能基准测试\n\n";
        $markdown .= "测试环境：PHP " . PHP_VERSION . ", " . php_uname() . "\n\n";
        $markdown .= "每个测试运行 " . $this->iterations . " 次取平均值\n\n";

        $markdown .= "| 算法 | 字节数 | 最小耗时(ms) | 最大耗时(ms) | 平均耗时(ms) | 稳定平均(ms) |\n";
        $markdown .= "| --- | --- | --- | --- | --- | --- |\n";

        foreach ($this->dataSizes as $size) {
            foreach ($this->algorithms as $name => $func) {
                $result = $this->findResult($name, $size);

                if ($result !== null) {
                    $markdown .= sprintf("| %s | %d | %.4f | %.4f | %.4f | %.4f |\n",
                        (string) $result['algorithm'],
                        (int) $result['data_size'],
                        (float) $result['min_time'],
                        (float) $result['max_time'],
                        (float) $result['avg_time'],
                        (float) $result['stable_avg_time']);
                }
            }
        }

        return $markdown;
    }
}

// 运行测试
echo "启动 Blake3 哈希算法性能基准测试...\n\n";

$benchmark = new HashBenchmark();
$benchmark->runAllTests();
$benchmark->showResults();

// 导出markdown到文件
$markdown = $benchmark->exportMarkdown();
file_put_contents(__DIR__ . '/benchmark_results.md', $markdown);

echo "\n基准测试完成，结果已保存到 benchmark_results.md\n";
