<?php

namespace Tourze\Blake3\ChunkState;

use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Output\Blake3Output;
use Tourze\Blake3\Util\Blake3Util;

/**
 * Blake3数据块状态类
 *
 * 管理单个数据块的压缩状态
 */
class Blake3ChunkState
{
    private array $chaining_value;
    private int $chunk_counter;
    private string $block;
    private int $block_len;
    private int $blocks_compressed;
    private int $flags;

    public function __construct(array $key_words, int $chunk_counter, int $flags)
    {
        // 验证关键字长度
        assert(count($key_words) === 8, "Key words must be 8 words");

        $this->chaining_value = $key_words;
        $this->chunk_counter = $chunk_counter;
        $this->block = str_repeat("\0", Blake3Constants::BLOCK_LEN); // 初始化块缓冲区
        $this->block_len = 0;
        $this->blocks_compressed = 0;
        $this->flags = $flags;
    }

    /**
     * 获取当前处理的数据长度
     */
    public function len(): int
    {
        return Blake3Constants::BLOCK_LEN * $this->blocks_compressed + $this->block_len;
    }

    /**
     * 获取开始标志
     * 第一个块需要添加CHUNK_START标志
     */
    public function start_flag(): int
    {
        return $this->blocks_compressed === 0 ? Blake3Constants::CHUNK_START : 0;
    }

    /**
     * 直接以字节偏移方式更新块状态
     * 通过引用传递字符串并使用偏移量，减少内存分配和复制
     *
     * @param string $input_bytes 输入的字节数据
     * @param int $offset 起始偏移量
     * @param int $length 要处理的长度
     */
    public function updateWithOffset(string $input_bytes, int $offset, int $length): void
    {
        // 如果没有输入数据，直接返回
        if ($length <= 0) {
            return;
        }

        // 当前偏移位置
        $current_offset = $offset;
        // 结束位置
        $end_offset = $offset + $length;

        while ($current_offset < $end_offset) {
            // 如果块缓冲区已满，压缩并清除
            if ($this->block_len === Blake3Constants::BLOCK_LEN) {
                $block_words = Blake3Util::words_from_little_endian_bytes($this->block);

                // 计算当前块的压缩状态
                $compression_result = Blake3Util::compress(
                    $this->chaining_value,
                    $block_words,
                    $this->chunk_counter,
                    Blake3Constants::BLOCK_LEN, // 完整块长度始终是BLOCK_LEN
                    $this->flags | $this->start_flag()
                );

                // 更新链接值为压缩结果的前8个字
                $this->chaining_value = array_slice($compression_result, 0, 8);

                // 更新状态
                $this->blocks_compressed++;
                $this->block = str_repeat("\0", Blake3Constants::BLOCK_LEN);
                $this->block_len = 0;
            }

            // 计算可以复制到块缓冲区的字节数
            $want = Blake3Constants::BLOCK_LEN - $this->block_len;
            $take = min($want, $end_offset - $current_offset);

            // 直接按字节复制，避免创建子字符串
            for ($i = 0; $i < $take; $i++) {
                $this->block[$this->block_len + $i] = $input_bytes[$current_offset + $i];
            }

            // 更新状态
            $this->block_len += $take;
            $current_offset += $take;
        }
    }

    /**
     * 更新数据块状态
     * 性能优化：使用优化的updateWithOffset方法
     *
     * @param string $input_bytes 输入的字节数据
     */
    public function update(string $input_bytes): void
    {
        if ($input_bytes === "") {
            return;
        }

        $this->updateWithOffset($input_bytes, 0, strlen($input_bytes));
    }

    /**
     * 获取输出
     */
    public function output(): Blake3Output
    {
        // 将当前块转换为words
        $block_words = Blake3Util::words_from_little_endian_bytes($this->block);

        // 创建一个带有CHUNK_END标志的输出
        return new Blake3Output(
            $this->chaining_value,
            $block_words,
            $this->chunk_counter,
            $this->block_len,
            $this->flags | $this->start_flag() | Blake3Constants::CHUNK_END
        );
    }

    /**
     * 获取数据块计数器
     */
    public function getChunkCounter(): int
    {
        return $this->chunk_counter;
    }
}
