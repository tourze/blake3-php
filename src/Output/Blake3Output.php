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
     * 修复XOF模式的输出生成逻辑
     */
    public function root_output_bytes(int $length): string
    {
        if ($length === 0) {
            return '';
        }
        
        $output_bytes = '';
        $i = 0;
        $output_block_counter = 0;

        while ($i < $length) {
            // 对每个输出块使用不同的计数器
            $words = Blake3Util::compress(
                $this->input_chaining_value,
                $this->block_words,
                $output_block_counter,
                $this->block_len,
                $this->flags | Blake3Constants::ROOT
            );
            
            // 将每个字转换为字节并添加到输出
            foreach ($words as $word) {
                // 如果已经达到所需长度，就停止添加字节
                if ($i >= $length) {
                    break;
                }
                
                // 将word转换为小端字节
                $word_bytes = pack("V", $word);
                $remaining = $length - $i;
                $take = min(4, $remaining); // 最多取4字节（1个字）
                
                $output_bytes .= substr($word_bytes, 0, $take);
                $i += $take;
            }
            
            $output_block_counter++;
        }

        return $output_bytes;
    }
}
