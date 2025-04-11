<?php

namespace Tourze\Blake3;

use Tourze\Blake3\ChunkState\Blake3ChunkState;
use Tourze\Blake3\Constants\Blake3Constants;
use Tourze\Blake3\Output\Blake3Output;
use Tourze\Blake3\Util\Blake3Util;

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

    /**
     * 使用标准哈希模式创建Blake3哈希实例
     */
    public static function newInstance(): self
    {
        return new self(Blake3Constants::IV, 0);
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
    protected function __construct(array $key, int $flags)
    {
        assert(count($key) === 8, "Key must be 8 words");

        $this->key = $key;
        $this->flags = $flags;
        $this->chunk_state = [new Blake3ChunkState($key, 0, $flags)];
        $this->stack = [];
        $this->stack_size = 0;
    }

    /**
     * 更新哈希状态
     */
    public function update(string $input): self
    {
        // 如果没有输入，什么也不做
        if (strlen($input) === 0) {
            return $this;
        }

        $bytes_remaining = $input;

        while ($bytes_remaining !== "") {
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
            $take = min($want, strlen($bytes_remaining));
            
            // 更新当前数据块
            $current_chunk->update(substr($bytes_remaining, 0, $take));
            
            // 更新剩余字节
            $bytes_remaining = substr($bytes_remaining, $take);
        }

        return $this;
    }

    /**
     * 添加块链接值到合并树
     * 这个函数修复了原来的树合并逻辑
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
            
            // 计算父节点
            $block_words = array_merge($existing_cv, $cur);
            
            $cur = Blake3Util::compress(
                $this->key,
                $block_words,
                0, // 父节点的块计数器始终为0
                Blake3Constants::BLOCK_LEN,
                $this->flags | Blake3Constants::PARENT
            );
            
            // 取出前8个字作为新的链接值
            $cur = array_slice($cur, 0, 8);
            
            // 增加高度继续检查
            $cur_height++;
        }
        
        // 如果我们找到了一个空槽，将当前链接值存储在那里
        if ($cur_height === $this->stack_size) {
            $this->stack[$cur_height] = $cur;
            $this->stack_size++;
        } else {
            // 这里不应该发生，但如果发生了，我们需要替换栈中的节点
            $this->stack[$cur_height] = $cur;
        }
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
}

