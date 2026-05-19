<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

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
        unset($this->byId[$tagId]);
    }
}
