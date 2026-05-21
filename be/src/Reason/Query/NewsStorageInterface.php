<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;

/**
 * Admin CMS news — unified Query + Command (Wave 9).
 *
 *   - setVisible(newsId, visible): generic `doToggleVisible` — rewrites
 *     the storage-only `visible` column of dtb_news (outside the
 *     {@see NewsEntity} projection). dtb_news has NO `sort_no` column,
 *     so the News master does NOT carry `reorder` — `doSortNoMove` is
 *     intentionally not attached to NewsList in alps.json.
 */
interface NewsStorageInterface
{
    /** @return list<NewsEntity> */
    public function list(): array;

    public function getById(string $newsId): NewsEntity|null;

    public function put(NewsEntity $news): void;

    public function remove(string $newsId): void;

    public function setVisible(string $newsId, bool $visible): void;
}
