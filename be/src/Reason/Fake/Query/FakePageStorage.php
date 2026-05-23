<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory Page store. Seeded with one system page (homepage) so an
 * admin who just installed the app has at least one row to render.
 * Singleton-bound so a request's reads see its writes within the same
 * Becoming chain.
 */
final class FakePageStorage implements PageStorageInterface
{
    public const SEED_PAGE_ID = 'pg-homepage';

    /** @var array<string, PageEntity> keyed by pageId */
    private array $byId;

    public function __construct()
    {
        $this->byId = [
            self::SEED_PAGE_ID => new PageEntity(
                pageId: self::SEED_PAGE_ID,
                pageName: 'ホームページ',
                pageUrl: 'homepage',
                pageFileName: 'index',
                pageEditType: 2, // EDIT_TYPE_DEFAULT (system page)
            ),
        ];
    }

    /** @return list<PageEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $pageId): PageEntity|null
    {
        return $this->byId[$pageId] ?? null;
    }

    #[Override]
    public function put(PageEntity $page): void
    {
        $this->byId[$page->pageId] = $page;
    }

    #[Override]
    public function remove(string $pageId): void
    {
        unset($this->byId[$pageId]);
    }
}
