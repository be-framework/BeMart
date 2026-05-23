<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use Override;

use function ctype_digit;

final class SqlBlockStorage implements BlockStorageInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    /** @return list<BlockEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->rows('tblock_list'));
    }

    #[Override]
    public function getById(string $blockId): BlockEntity|null
    {
        if (! ctype_digit($blockId)) {
            return null;
        }
        $row = $this->db->row('tblock_get', ['id' => (int) $blockId]);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(BlockEntity $block): void
    {
        if (! ctype_digit($block->blockId)) {
            return;
        }
        $id = (int) $block->blockId;
        $values = [
            'id' => $id,
            'blockName' => $block->blockName,
            'fileName' => $block->blockFileName,
            'deletable' => (int) $block->blockDeletable,
        ];
        $this->db->exec($this->db->row('tblock_exists', ['id' => $id]) === null ? 'tblock_insert' : 'tblock_update', $values);
    }

    #[Override]
    public function remove(string $blockId): void
    {
        if (! ctype_digit($blockId)) {
            return;
        }
        $id = (int) $blockId;
        $this->db->exec('tblock_position_delete', ['id' => $id]);
        $this->db->exec('tblock_delete', ['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): BlockEntity
    {
        return new BlockEntity(
            blockId: (string) (int) $row['id'],
            blockName: (string) ($row['block_name'] ?? ''),
            blockFileName: (string) ($row['file_name'] ?? ''),
            blockDeletable: (bool) $row['deletable'],
        );
    }
}
