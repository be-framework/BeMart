<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory Tag store. Seeded with two stock tags so the admin list
 * view has rows to render on a fresh install.
 */
final class FakeTagStorage implements TagStorageInterface
{
    public const SEED_NEW_TAG_ID = 'tg-new';
    public const SEED_SALE_TAG_ID = 'tg-sale';

    /** @var array<string, TagEntity> keyed by tagId */
    private array $byId;

    /**
     * Storage-only `sort_no` per row — dtb_tag has the column but
     * {@see TagEntity} does not project it. Populated by `reorder`.
     *
     * @var array<string, int>
     */
    private array $sortNo = [];

    public function __construct()
    {
        $this->byId = [
            self::SEED_NEW_TAG_ID => new TagEntity(self::SEED_NEW_TAG_ID, '新商品'),
            self::SEED_SALE_TAG_ID => new TagEntity(self::SEED_SALE_TAG_ID, 'セール'),
        ];
    }

    /** @return list<TagEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $tagId): TagEntity|null
    {
        return $this->byId[$tagId] ?? null;
    }

    #[Override]
    public function put(TagEntity $tag): void
    {
        $this->byId[$tag->tagId] = $tag;
    }

    #[Override]
    public function remove(string $tagId): void
    {
        unset($this->byId[$tagId], $this->sortNo[$tagId]);
    }

    #[Override]
    public function reorder(string $tagId, int $sortNo): void
    {
        if (! isset($this->byId[$tagId])) {
            // Silent no-op on a missing row — same shape as `remove`.
            return;
        }

        $this->sortNo[$tagId] = $sortNo;
    }

    /**
     * Test introspection: the `sort_no` last written for a row, or null
     * if `reorder` was never called for it.
     */
    public function sortNoOf(string $tagId): int|null
    {
        return $this->sortNo[$tagId] ?? null;
    }
}
