<?php

/**
 * Blake3 基准测试套件
 *
 * 此脚本会依次运行所有基准测试并收集结果
 */

// 设置执行时间为无限制
set_time_limit(0);
ini_set('memory_limit', '1G');

echo "========================================\n";
echo "Blake3 哈希算法基准测试套件\n";
echo "========================================\n\n";

echo "测试环境信息:\n";
echo "PHP版本: " . PHP_VERSION . "\n";
echo "操作系统: " . php_uname() . "\n";
echo "日期时间: " . date('Y-m-d H:i:s') . "\n";
echo "----------------------------------------\n\n";

// 创建结果目录
$resultsDir = __DIR__ . '/results/' . date('Ymd_His');
if (!is_dir($resultsDir)) {
    mkdir($resultsDir, 0777, true);
}

// 运行基本比较测试
echo "运行基础版基准测试 (benchmark.php)...\n";
echo "----------------------------------------\n";
ob_start();
include __DIR__ . '/benchmark.php';
$output = ob_get_clean();
echo $output;
echo "\n";

// 基础测试结果
if (file_exists(__DIR__ . '/benchmark_results.md')) {
    copy(__DIR__ . '/benchmark_results.md', $resultsDir . '/benchmark_results.md');
    echo "基础测试结果已保存到 results 目录\n\n";
}

// 运行 Blake3 三种模式对比测试
echo "运行 Blake3 三种模式对比测试 (benchmark_modes.php)...\n";
echo "----------------------------------------\n";
ob_start();
include __DIR__ . '/benchmark_modes.php';
$output = ob_get_clean();
echo $output;
echo "\n";

// 模式对比测试结果
if (file_exists(__DIR__ . '/benchmark_modes_results.md')) {
    copy(__DIR__ . '/benchmark_modes_results.md', $resultsDir . '/benchmark_modes_results.md');
    echo "模式对比测试结果已保存到 results 目录\n\n";
}

// 询问是否运行高级测试（因为较慢）
echo "是否运行高级版基准测试? 这可能需要较长时间 (y/n) [n]: ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) === 'y' || strtolower($line) === 'yes') {
    echo "\n运行高级版基准测试 (benchmark_advanced.php)...\n";
    echo "----------------------------------------\n";
    ob_start();
    include __DIR__ . '/benchmark_advanced.php';
    $output = ob_get_clean();
    echo $output;

    // 高级测试结果
    if (file_exists(__DIR__ . '/benchmark_advanced_results.md')) {
        copy(__DIR__ . '/benchmark_advanced_results.md', $resultsDir . '/benchmark_advanced_results.md');
        echo "高级测试结果已保存到 results 目录\n\n";
    }
} else {
    echo "已跳过高级版基准测试\n\n";
}

// 生成汇总报告
echo "生成汇总报告...\n";
$summary = "# Blake3 哈希算法基准测试汇总报告\n\n";
$summary .= "## 测试环境\n\n";
$summary .= "- PHP版本: " . PHP_VERSION . "\n";
$summary .= "- 操作系统: " . php_uname() . "\n";
$summary .= "- 测试日期: " . date('Y-m-d H:i:s') . "\n\n";

$summary .= "## 测试结果文件\n\n";
$summary .= "以下是本次测试生成的结果文件：\n\n";
$summary .= "- [基础测试结果](benchmark_results.md)\n";
$summary .= "- [Blake3 三种模式对比](benchmark_modes_results.md)\n";

if (strtolower($line) === 'y' || strtolower($line) === 'yes') {
    $summary .= "- [高级测试结果](benchmark_advanced_results.md)\n";
}

$summary .= "\n## 结论与建议\n\n";
$summary .= "- Blake3 是一种高性能、安全的哈希算法，适合用于文件完整性校验和数据验证场景\n";
$summary .= "- 与传统哈希算法（如SHA系列）相比，Blake3在大多数情况下提供更好的性能\n";
$summary .= "- 在大数据量处理时，Blake3的性能优势更为明显\n";
$summary .= "- Blake3的三种模式（普通、密钥、派生）提供了灵活的应用选择，性能差异较小\n\n";

file_put_contents($resultsDir . '/summary.md', $summary);

echo "基准测试套件执行完毕！\n";
echo "所有测试结果已保存到: " . $resultsDir . "\n";
echo "========================================\n";
