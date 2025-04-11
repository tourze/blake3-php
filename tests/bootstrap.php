<?php

/**
 * BLAKE3 测试引导文件
 */

// 引入Composer自动加载器
require __DIR__ . '/../vendor/autoload.php';

// 设置测试环境
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M'); // 增加内存限制，以便运行大数据测试
