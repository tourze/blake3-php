<?php

namespace Tourze\Blake3\Util;

/**
 * 缓冲区大小管理工具类
 *
 * 此类用于根据不同处理场景自动优化缓冲区大小
 */
class BufferSizeManager
{
    /**
     * 预定义的缓冲区大小配置（字节）
     */
    public const BUFFER_TINY = 4 * 1024;       // 4KB - 适用于内存极度受限环境
    public const BUFFER_SMALL = 16 * 1024;     // 16KB - 适用于小文件或内存受限环境
    public const BUFFER_DEFAULT = 64 * 1024;   // 64KB - 默认配置，平衡性能和内存使用
    public const BUFFER_LARGE = 256 * 1024;    // 256KB - 适用于大文件和高性能环境
    public const BUFFER_HUGE = 1024 * 1024;    // 1MB - 适用于大文件和内存充足的环境

    /**
     * 根据文件大小自动选择最佳缓冲区大小
     *
     * @param int|null $fileSize 文件大小（字节），如果未知则为null
     * @param bool $lowMemory 是否处于低内存模式
     * @return int 建议的缓冲区大小（字节）
     */
    public static function getOptimalBufferSize(?int $fileSize = null, bool $lowMemory = false): int
    {
        // 如果指定为低内存模式，始终使用较小的缓冲区
        if ($lowMemory) {
            return self::BUFFER_SMALL;
        }

        // 如果文件大小未知，使用默认大小
        if ($fileSize === null) {
            return self::BUFFER_DEFAULT;
        }

        // 根据文件大小调整缓冲区大小
        // 文件越大，缓冲区越大，但有上限
        if ($fileSize < 1024 * 1024) { // < 1MB
            return self::BUFFER_SMALL;
        } elseif ($fileSize < 10 * 1024 * 1024) { // < 10MB
            return self::BUFFER_DEFAULT;
        } elseif ($fileSize < 100 * 1024 * 1024) { // < 100MB
            return self::BUFFER_LARGE;
        } else {
            return self::BUFFER_HUGE;
        }
    }

    /**
     * 根据可用内存自动选择最佳缓冲区大小
     *
     * @return int 根据可用内存确定的缓冲区大小（字节）
     */
    public static function getMemoryAwareBufferSize(): int
    {
        // 尝试获取当前内存使用情况
        $memoryLimit = self::getMemoryLimitBytes();

        // 如果无法获取内存限制，使用默认值
        if ($memoryLimit === -1) {
            return self::BUFFER_DEFAULT;
        }

        // 根据可用内存调整缓冲区大小
        $availableMemory = $memoryLimit - memory_get_usage(true);
        if ($availableMemory < 10 * 1024 * 1024) { // < 10MB可用
            return self::BUFFER_TINY;
        } elseif ($availableMemory < 50 * 1024 * 1024) { // < 50MB可用
            return self::BUFFER_SMALL;
        } elseif ($availableMemory < 200 * 1024 * 1024) { // < 200MB可用
            return self::BUFFER_DEFAULT;
        } else {
            return self::BUFFER_LARGE;
        }
    }

    /**
     * 获取当前PHP内存限制（字节）
     *
     * @return int 内存限制（字节），如果无限制则返回-1
     */
    private static function getMemoryLimitBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');

        // 如果设置为-1，表示无限制
        if ($memoryLimit === '-1') {
            return -1;
        }

        // 解析内存限制值
        if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
            $value = (int)$matches[1];
            $unit = strtolower($matches[2]);

            switch ($unit) {
                case 'g':
                    $value *= 1024;
                // 故意不加break，让它继续往下计算
                case 'm':
                    $value *= 1024;
                // 故意不加break，让它继续往下计算
                case 'k':
                    $value *= 1024;
            }

            return $value;
        }

        // 如果没有单位，假设是字节
        return (int)$memoryLimit;
    }

    /**
     * 适应流处理的动态缓冲区大小
     *
     * 根据处理进度和性能动态调整缓冲区大小
     *
     * @param int $initialSize 初始缓冲区大小
     * @param int $processedBytes 已处理的字节数
     * @param float $processingTime 处理时间（秒）
     * @return int 调整后的缓冲区大小
     */
    public static function getDynamicBufferSize(int $initialSize, int $processedBytes, float $processingTime): int
    {
        // 防止除以零
        if ($processingTime <= 0) {
            return $initialSize;
        }

        // 计算处理速度（字节/秒）
        $bytesPerSecond = $processedBytes / $processingTime;

        // 计算理想的缓冲区大小 - 大约0.1秒处理量
        $idealBufferSize = (int)($bytesPerSecond * 0.1);

        // 限制最小和最大值
        $idealBufferSize = max(self::BUFFER_TINY, $idealBufferSize);
        $idealBufferSize = min(self::BUFFER_HUGE, $idealBufferSize);

        // 如果变化不大，保持当前大小
        if ($idealBufferSize > $initialSize * 0.8 && $idealBufferSize < $initialSize * 1.2) {
            return $initialSize;
        }

        return $idealBufferSize;
    }
}
