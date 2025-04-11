<?php

namespace Tourze\Blake3\Output;

use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Util\Blake3Util;

/**
 * Blake3输出类，表示哈希运算的中间或最终输出状态
 *
 * 每个数据块或父节点可以产生8个字的链值，
 * 或者通过设置ROOT标志，产生任意数量的最终输出字节。
 * Output类捕获了在选择这两种可能性之前的状态。
 */
class Blake3Output
{
    private array $input_chaining_value;
    private array $block_words;
    private int $counter;
    private int $block_len;
    private int $flags;

    public function __construct(array $input_chaining_value, array $block_words, int $counter, int $block_len, int $flags) 
    {
        // 验证输入参数
        assert(count($input_chaining_value) === 8, "输入链接值必须是8个字");
        assert(count($block_words) === 16, "块字必须是16个字");

        $this->input_chaining_value = $input_chaining_value;
        $this->block_words = $block_words;
        $this->counter = $counter;
        $this->block_len = $block_len;
        $this->flags = $flags;
    }

    /**
     * 计算链接值
     */
    public function chaining_value(): array
    {
        $compression_output = Blake3Util::compress(
            $this->input_chaining_value,
            $this->block_words,
            $this->counter,
            $this->block_len,
            $this->flags
        );

        // 返回前8个字作为链接值
        return array_slice($compression_output, 0, 8);
    }

    /**
     * 生成根节点输出字节
     * 性能优化：减少循环次数和内存分配
     */
    public function root_output_bytes(int $length): string
    {
        if ($length === 0) {
            return '';
        }

        // 预分配输出缓冲区以减少内存重新分配
        $output_bytes = '';
        $bytes_generated = 0;
        $output_block_counter = 0;

        // 每个输出块可以生成64字节（16个字 * 4字节/字）
        $bytes_per_block = 64;
        $blocks_needed = (int)ceil($length / $bytes_per_block);

        for ($block = 0; $block < $blocks_needed; $block++) {
            // 对每个输出块使用递增的计数器
            $words = Blake3Util::compress(
                $this->input_chaining_value,
                $this->block_words,
                $output_block_counter,
                $this->block_len,
                $this->flags | Blake3Constants::ROOT
            );

            // 批量处理字到字节的转换
            $word_bytes = '';
            foreach ($words as $word) {
                $word_bytes .= pack("V", $word);
            }

            $remaining = $length - $bytes_generated;
            $take = min(64, $remaining);

            $output_bytes .= substr($word_bytes, 0, $take);
            $bytes_generated += $take;

            $output_block_counter++;
        }

        return $output_bytes;
    }

    /**
     * 将输出直接写入流，适用于超大输出
     *
     * @param resource $stream 输出流资源
     * @param int $length 要输出的字节数
     * @return int 写入的字节数
     * @throws \InvalidArgumentException 如果提供的不是有效的流资源
     */
    public function writeToStream($stream, int $length = 32): int
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException("参数必须是有效的流资源");
        }

        if ($length <= 0) {
            return 0;
        }

        $bytes_written = 0;
        $output_block_counter = 0;

        // 每个输出块可以生成64字节
        $bytes_per_block = 64;
        $blocks_needed = (int)ceil($length / $bytes_per_block);

        for ($block = 0; $block < $blocks_needed; $block++) {
            // 对每个输出块使用递增的计数器
            $words = Blake3Util::compress(
                $this->input_chaining_value,
                $this->block_words,
                $output_block_counter,
                $this->block_len,
                $this->flags | Blake3Constants::ROOT
            );

            // 批量处理字到字节的转换
            $word_bytes = '';
            foreach ($words as $word) {
                $word_bytes .= pack("V", $word);
            }

            $remaining = $length - $bytes_written;
            $take = min($bytes_per_block, $remaining);

            // 直接写入流
            $chunk = substr($word_bytes, 0, $take);
            $written = fwrite($stream, $chunk);

            if ($written === false || $written < $take) {
                // 写入出错或不完整
                break;
            }

            $bytes_written += $written;
            $output_block_counter++;
        }

        return $bytes_written;
    }

    /**
     * 将哈希输出写入文件
     *
     * @param string $filePath 文件路径
     * @param int $length 要输出的字节数
     * @return int 写入的字节数
     * @throws \RuntimeException 如果文件无法打开
     */
    public function writeToFile(string $filePath, int $length = 32): int
    {
        $stream = @fopen($filePath, 'wb');
        if ($stream === false) {
            throw new \RuntimeException("无法打开文件: " . $filePath);
        }

        try {
            return $this->writeToStream($stream, $length);
        } finally {
            fclose($stream);
        }
    }
}
