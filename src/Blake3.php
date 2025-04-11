<?php

namespace Tourze\Blake3;

use Tourze\Blake3\ChunkState\Blake3ChunkState;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Output\Blake3Output;
use Tourze\Blake3\Util\Blake3Util;
use Tourze\Blake3\Util\BufferSizeManager;

/**
 * Blake3 哈希算法实现
 */
class Blake3
{
    private array $key;
    private int $flags;
    private array $chunk_state;
    private array $stack;
    private int $stack_size;
    private bool $lowMemoryMode;

    /**
     * 使用标准哈希模式创建Blake3哈希实例
     *
     * @param bool $lowMemoryMode 是否使用低内存模式
     * @return self
     */
    public static function newInstance(bool $lowMemoryMode = false): self
    {
        return new self(Blake3Constants::IV, 0, $lowMemoryMode);
    }

    /**
     * 使用密钥派生模式创建Blake3哈希实例
     */
    public static function newKeyDerivationInstance(string $context): self
    {
        $instance = new self(Blake3Constants::IV, Blake3Constants::DERIVE_KEY_CONTEXT);
        $instance->update($context);
        return $instance;
    }

    /**
     * 使用密钥哈希模式创建Blake3哈希实例
     */
    public static function newKeyedInstance(string $key): self
    {
        if (strlen($key) !== 32) {
            throw new \InvalidArgumentException("Key must be 32 bytes");
        }

        $key_words = Blake3Util::words_from_little_endian_bytes($key);
        return new self($key_words, Blake3Constants::KEYED_HASH);
    }

    /**
     * 构造函数
     */
    protected function __construct(array $key, int $flags, bool $lowMemoryMode = false)
    {
        assert(count($key) === 8, "Key must be 8 words");

        $this->key = $key;
        $this->flags = $flags;
        $this->chunk_state = [new Blake3ChunkState($key, 0, $flags)];
        $this->stack = [];
        $this->stack_size = 0;
        $this->lowMemoryMode = $lowMemoryMode;
    }

    /**
     * 更新哈希状态
     * 性能优化：减少内存分配和复制，使用引用传递
     */
    public function update(string $input): self
    {
        // 如果没有输入，什么也不做
        if ($input === "") {
            return $this;
        }

        $inputLength = strlen($input);
        $offset = 0;

        while ($offset < $inputLength) {
            // 如果当前数据块已满，处理树结构
            $current_chunk = &$this->chunk_state[0];
            $chunk_len = $current_chunk->len();

            if ($chunk_len === Blake3Constants::CHUNK_LEN) {
                // 当前块已满，需要将其添加到合并树中
                $this->add_chunk_chaining_value($current_chunk->output()->chaining_value(), $current_chunk->getChunkCounter());

                // 创建新的块状态
                $this->chunk_state[0] = new Blake3ChunkState(
                    $this->key,
                    $current_chunk->getChunkCounter() + 1,
                    $this->flags
                );

                $current_chunk = &$this->chunk_state[0];
                $chunk_len = 0;
            }

            // 计算可以添加到当前块的字节数量
            $want = Blake3Constants::CHUNK_LEN - $chunk_len;
            $take = min($want, $inputLength - $offset);

            // 更新当前数据块，直接传递偏移量而不复制子字符串
            $current_chunk->updateWithOffset($input, $offset, $take);

            // 更新偏移量
            $offset += $take;
        }

        return $this;
    }

    /**
     * 从文件流更新哈希状态
     * 支持处理超大文件而不占用过多内存
     *
     * @param resource $stream 文件或流资源
     * @param int|null $bufferSize 每次读取的缓冲区大小，null表示自动确定
     * @param int|null $fileSize 文件大小（字节），用于优化缓冲区大小，null表示未知
     * @return self 返回自身以支持链式调用
     * @throws \InvalidArgumentException 如果提供的不是有效的流资源
     */
    public function updateStream($stream, ?int $bufferSize = null, ?int $fileSize = null): self
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException("参数必须是有效的流资源");
        }

        // 获取当前流的位置，之后再恢复
        $position = ftell($stream);

        // 智能确定缓冲区大小
        if ($bufferSize === null) {
            $bufferSize = BufferSizeManager::getOptimalBufferSize($fileSize, $this->lowMemoryMode);
        }

        // 用于动态缓冲区调整的统计信息
        $startTime = microtime(true);
        $totalBytesProcessed = 0;
        $lastAdjustmentTime = $startTime;
        $lastAdjustmentBytes = 0;

        // 每次读取指定大小的块，更新哈希状态
        $buffer = '';
        while (!feof($stream)) {
            $buffer = fread($stream, $bufferSize);
            if ($buffer === false) {
                break;
            }

            $bytesRead = strlen($buffer);
            $totalBytesProcessed += $bytesRead;
            $this->update($buffer);

            // 超过5MB数据或5秒后，考虑动态调整缓冲区大小
            $currentTime = microtime(true);
            if ($totalBytesProcessed - $lastAdjustmentBytes > 5 * 1024 * 1024 ||
                $currentTime - $lastAdjustmentTime > 5.0) {

                // 计算此段时间内的处理性能
                $segmentTime = $currentTime - $lastAdjustmentTime;
                $segmentBytes = $totalBytesProcessed - $lastAdjustmentBytes;

                // 仅当处理了足够的数据和时间才调整
                if ($segmentBytes > 1024 * 1024 && $segmentTime > 1.0) {
                    // 调整缓冲区大小
                    $newBufferSize = BufferSizeManager::getDynamicBufferSize(
                        $bufferSize,
                        $segmentBytes,
                        $segmentTime
                    );

                    // 只有当缓冲区大小变化较大时才更新
                    if (abs($newBufferSize - $bufferSize) / $bufferSize > 0.2) {
                        $bufferSize = $newBufferSize;
                    }

                    // 更新统计信息
                    $lastAdjustmentTime = $currentTime;
                    $lastAdjustmentBytes = $totalBytesProcessed;
                }
            }
        }

        // 如果流可定位，恢复原来的位置
        if ($position !== false) {
            fseek($stream, $position);
        }

        return $this;
    }

    /**
     * 从文件路径更新哈希状态
     *
     * @param string $filePath 文件路径
     * @param int|null $bufferSize 每次读取的缓冲区大小，null表示自动确定
     * @param bool $autoDetectSize 是否自动检测文件大小以优化缓冲区
     * @return self 返回自身以支持链式调用
     * @throws \RuntimeException 如果文件无法打开
     */
    public function updateFile(string $filePath, ?int $bufferSize = null, bool $autoDetectSize = true): self
    {
        // 检查文件是否存在
        if (!file_exists($filePath)) {
            throw new \RuntimeException("文件不存在: " . $filePath);
        }

        // 获取文件大小，用于优化缓冲区大小
        $fileSize = null;
        if ($autoDetectSize) {
            $fileSize = @filesize($filePath);
        }

        // 如果未指定缓冲区大小，根据文件大小自动确定
        if ($bufferSize === null) {
            $bufferSize = BufferSizeManager::getOptimalBufferSize($fileSize, $this->lowMemoryMode);
        }

        $stream = @fopen($filePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException("无法打开文件: " . $filePath);
        }

        try {
            $this->updateStream($stream, $bufferSize, $fileSize);
        } finally {
            fclose($stream);
        }

        return $this;
    }

    /**
     * 添加块链接值到合并树
     * 性能优化：提高树合并逻辑性能
     */
    private function add_chunk_chaining_value(array $new_cv, int $total_chunks): void
    {
        // 初始化当前父级节点
        $cur = $new_cv;
        $cur_height = 0;

        // 合并树节点直到找到空槽
        while ($cur_height < $this->stack_size) {
            // 获取栈中的节点
            $existing_cv = $this->stack[$cur_height];

            // 计算父节点 - 优化合并操作
            $block_words = array_merge($existing_cv, $cur);

            // 使用优化的压缩函数
            $cur = Blake3Util::compress(
                $this->key,
                $block_words,
                0, // 父节点的块计数器始终为0
                Blake3Constants::BLOCK_LEN,
                $this->flags | Blake3Constants::PARENT
            );

            // 取出前8个字作为新的链接值，优化切片操作
            $cur = array_slice($cur, 0, 8);

            // 增加高度继续检查
            $cur_height++;
        }

        // 直接赋值，不需要条件判断
        $this->stack[$cur_height] = $cur;
        $this->stack_size = max($this->stack_size, $cur_height + 1);
    }

    /**
     * 获取哈希输出
     */
    public function finalize(int $output_size = 32): string
    {
        $output = $this->output();
        return $output->root_output_bytes($output_size);
    }

    /**
     * 将当前状态转换为Blake3Output
     */
    private function output(): Blake3Output
    {
        // 先获取当前处理的数据块的输出
        $output = $this->chunk_state[0]->output();
        $output_chaining_value = $output->chaining_value();
        $parent_nodes_remaining = $this->stack_size;

        // 自右向左合并所有节点
        while ($parent_nodes_remaining > 0) {
            $parent_nodes_remaining--;

            // 创建合并区块，左子节点来自栈，右子节点是当前的链接值
            $block_words = array_merge($this->stack[$parent_nodes_remaining], $output_chaining_value);

            // 创建父节点输出，使用PARENT标志
            $output = new Blake3Output(
                $this->key,
                $block_words,
                0, // 父节点计数器始终为0
                Blake3Constants::BLOCK_LEN,
                $this->flags | Blake3Constants::PARENT
            );

            $output_chaining_value = $output->chaining_value();
        }

        return $output;
    }

    /**
     * 便捷静态方法：计算字符串的哈希值
     *
     * @param string $data 要计算哈希的字符串
     * @param int $outputLength 输出长度（默认32字节）
     * @return string 哈希结果二进制字符串
     */
    public static function hash(string $data, int $outputLength = 32): string
    {
        $hasher = self::newInstance();
        $hasher->update($data);
        return $hasher->finalize($outputLength);
    }

    /**
     * 便捷静态方法：计算文件的哈希值
     *
     * @param string $filePath 文件路径
     * @param int $outputLength 输出长度（默认32字节）
     * @param int|null $bufferSize 缓冲区大小，null表示自动确定
     * @param bool $lowMemoryMode 是否使用低内存模式
     * @return string 哈希结果二进制字符串
     * @throws \RuntimeException 如果文件无法打开
     */
    public static function hashFile(string $filePath, int $outputLength = 32, ?int $bufferSize = null, bool $lowMemoryMode = false): string
    {
        $hasher = self::newInstance($lowMemoryMode);
        $hasher->updateFile($filePath, $bufferSize);
        return $hasher->finalize($outputLength);
    }

    /**
     * 便捷静态方法：以十六进制格式输出哈希值
     *
     * @param string $data 要计算哈希的字符串
     * @param int $outputLength 输出长度（默认32字节）
     * @return string 十六进制格式的哈希结果
     */
    public static function hashHex(string $data, int $outputLength = 32): string
    {
        return bin2hex(self::hash($data, $outputLength));
    }

    /**
     * 便捷静态方法：以十六进制格式输出文件的哈希值
     *
     * @param string $filePath 文件路径
     * @param int $outputLength 输出长度（默认32字节）
     * @param int|null $bufferSize 缓冲区大小，null表示自动确定
     * @param bool $lowMemoryMode 是否使用低内存模式
     * @return string 十六进制格式的哈希结果
     * @throws \RuntimeException 如果文件无法打开
     */
    public static function hashFileHex(string $filePath, int $outputLength = 32, ?int $bufferSize = null, bool $lowMemoryMode = false): string
    {
        return bin2hex(self::hashFile($filePath, $outputLength, $bufferSize, $lowMemoryMode));
    }

    /**
     * 将哈希结果直接写入文件
     *
     * @param string $filePath 输出文件路径
     * @param int $outputLength 输出长度
     * @return int 写入的字节数
     * @throws \RuntimeException 如果文件无法打开
     */
    public function finalizeToFile(string $filePath, int $outputLength = 32): int
    {
        return $this->output()->writeToFile($filePath, $outputLength);
    }

    /**
     * 将哈希结果直接写入流
     *
     * @param resource $stream 输出流
     * @param int $outputLength 输出长度
     * @return int 写入的字节数
     * @throws \InvalidArgumentException 如果提供的不是有效的流资源
     */
    public function finalizeToStream($stream, int $outputLength = 32): int
    {
        return $this->output()->writeToStream($stream, $outputLength);
    }
}
