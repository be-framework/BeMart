<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory Block store. Seeded with one undeletable system block so
 * the layout editor has something to render against.
 */
final class FakeBlockStorage implements BlockStorageInterface
{
    public const SEED_BLOCK_ID = 'bk-header';

    /** @var array<string, BlockEntity> keyed by blockId */
    private array $byId;

    public function __construct()
    {
        $this->byId = [
            self::SEED_BLOCK_ID => new BlockEntity(
                blockId: self::SEED_BLOCK_ID,
                blockName: 'ヘッダー',
                blockFileName: 'header',
                blockDeletable: false,
            ),
        ];
    }

    /** @return list<BlockEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $blockId): BlockEntity|null
    {
        return $this->byId[$blockId] ?? null;
    }

    #[Override]
    public function put(BlockEntity $block): void
    {
        $this->byId[$block->blockId] = $block;
    }

    #[Override]
    public function remove(string $blockId): void
    {
        unset($this->byId[$blockId]);
    }
}
