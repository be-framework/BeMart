<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use Override;

use function array_values;
use function usort;

/**
 * In-memory News store. Seeded with a single welcome post so the
 * admin list view renders something on a fresh install.
 *
 * The list projection is sorted by (publishDate desc, newsId asc) to
 * mirror EC-CUBE's "newest first" display convention.
 */
final class FakeNewsStorage implements NewsStorageInterface
{
    public const SEED_NEWS_ID = 'nw-welcome';

    /** @var array<string, NewsEntity> keyed by newsId */
    private array $byId;

    /**
     * Storage-only `visible` per row — dtb_news has the column but
     * {@see NewsEntity} does not project it. A row with no entry is
     * considered visible (the schema default of 1).
     *
     * @var array<string, bool>
     */
    private array $visible = [];

    public function __construct()
    {
        $this->byId = [
            self::SEED_NEWS_ID => new NewsEntity(
                newsId: self::SEED_NEWS_ID,
                newsTitle: 'ようこそ',
                newsDescription: 'EC-CUBE へようこそ。',
                newsUrl: null,
                publishDate: '2026-01-01T00:00:00+09:00',
                linkMethod: false,
            ),
        ];
    }

    /** @return list<NewsEntity> */
    #[Override]
    public function list(): array
    {
        $rows = array_values($this->byId);
        usort($rows, static function (NewsEntity $a, NewsEntity $b): int {
            return $b->publishDate <=> $a->publishDate ?: $a->newsId <=> $b->newsId;
        });

        return $rows;
    }

    #[Override]
    public function getById(string $newsId): NewsEntity|null
    {
        return $this->byId[$newsId] ?? null;
    }

    #[Override]
    public function put(NewsEntity $news): void
    {
        $this->byId[$news->newsId] = $news;
    }

    #[Override]
    public function remove(string $newsId): void
    {
        unset($this->byId[$newsId], $this->visible[$newsId]);
    }

    #[Override]
    public function setVisible(string $newsId, bool $visible): void
    {
        if (! isset($this->byId[$newsId])) {
            return;
        }

        $this->visible[$newsId] = $visible;
    }

    /**
     * Test introspection: the `visible` flag last written for a row.
     * Defaults to true (schema default) when never toggled.
     */
    public function visibleOf(string $newsId): bool
    {
        return $this->visible[$newsId] ?? true;
    }
}
