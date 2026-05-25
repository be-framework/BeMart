<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use Ray\MediaQuery\Annotation\DbQuery;

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
    #[DbQuery('tnews_list')]
    public function list(): array;

    #[DbQuery('tnews_get')]
    public function item(string $newsId): NewsEntity|null;

    #[DbQuery('tnews_put')]
    public function put(NewsEntity $news): void;

    #[DbQuery('tnews_delete')]
    public function delete(string $newsId): void;

    #[DbQuery('tnews_visible')]
    public function visible(string $newsId, bool $visible): void;
}
