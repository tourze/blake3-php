<?php
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Tourze\Blake3\Constants\Blake3Constants;

echo "=== Understanding chunk_counter usage ===\n\n";

echo "In BLAKE3, each chunk processes up to 1024 bytes.\n";
echo "Within a chunk, there are up to 16 blocks of 64 bytes each.\n\n";

echo "The counter parameter in compress() represents:\n";
echo "- For blocks within a chunk: the chunk_counter\n";
echo "- For parent nodes: always 0\n";
echo "- For output nodes: depends on the context\n\n";

echo "Key insight from the C implementation:\n";
echo "- Blocks within a chunk use the chunk_counter\n";
echo "- The chunk output also uses the chunk_counter\n";
echo "- Parent nodes use counter=0\n\n";

echo "The issue might be more subtle...\n\n";

// Let's think about what happens for Mersenne vs non-Mersenne
echo "=== Why Mersenne numbers work ===\n";
echo "For n chunks where n = 2^k - 1 (Mersenne):\n";
echo "- The tree is perfectly balanced\n";
echo "- All chunks are at the same level\n";
echo "- Example for 7 chunks (2^3 - 1):\n";
echo "       root\n";
echo "      /    \\\n";
echo "    p1      p2\n";
echo "   /  \\    /  \\\n";
echo "  p3  p4  p5  p6\n";
echo " / \\  / \\  / \\  / \\\n";
echo "0  1 2 3 4 5 6 (7)\n\n";

echo "For non-Mersenne numbers:\n";
echo "- The tree is unbalanced\n";
echo "- Some paths are longer than others\n";
echo "- Example for 5 chunks:\n";
echo "     root\n";
echo "    /    \\\n";
echo "   p1     4\n";
echo "  /  \\\n";
echo " p2  p3\n";
echo " / \\  / \\\n";
echo "0  1 2  3\n\n";

echo "=== The real issue ===\n";
echo "When we have an unbalanced tree, the final chunk (e.g., chunk 5)\n";
echo "is processed differently than in a balanced tree.\n\n";

echo "Let me check if the issue is in how we handle the final chunk...\n";

// Check if it's about the blocks_compressed count
echo "\n=== Another hypothesis ===\n";
echo "The chunk_counter might be used to calculate something else,\n";
echo "like the total block count or position in the stream.\n";
echo "\nFor 5121 bytes:\n";
echo "- 5 complete chunks (0-4) = 5 * 16 = 80 blocks\n";
echo "- 1 partial chunk with 1 byte = 1 block\n";
echo "- Total: 81 blocks\n";
echo "\nBut wait, within each chunk, the counter is the chunk index,\n";
echo "not the block index...\n";