<?php

namespace Tourze\Blake3\Constants;

/**
 * Blake3算法常量定义
 */
class Blake3Constants
{
    /**
     * 输出长度
     */
    public const OUT_LEN = 32;

    /**
     * 密钥长度
     */
    public const KEY_LEN = 32;

    /**
     * 块长度
     */
    public const BLOCK_LEN = 64;

    /**
     * 数据块长度
     */
    public const CHUNK_LEN = 1024;

    /**
     * 数据块开始标志
     */
    public const CHUNK_START = 1 << 0;

    /**
     * 数据块结束标志
     */
    public const CHUNK_END = 1 << 1;

    /**
     * 父节点标志
     */
    public const PARENT = 1 << 2;

    /**
     * 根节点标志
     */
    public const ROOT = 1 << 3;

    /**
     * 密钥哈希标志
     */
    public const KEYED_HASH = 1 << 4;

    /**
     * 派生密钥上下文标志
     */
    public const DERIVE_KEY_CONTEXT = 1 << 5;

    /**
     * 派生密钥材料标志
     */
    public const DERIVE_KEY_MATERIAL = 1 << 6;

    /**
     * 初始向量
     */
    public const IV = [
        0x6A09E667,
        0xBB67AE85,
        0x3C6EF372,
        0xA54FF53A,
        0x510E527F,
        0x9B05688C,
        0x1F83D9AB,
        0x5BE0CD19
    ];

    /**
     * 消息排列顺序
     */
    public const MSG_PERMUTATION = [2, 6, 3, 10, 7, 0, 4, 13, 1, 11, 12, 5, 9, 14, 15, 8];
}
