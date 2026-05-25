<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;

/**
 * Admin CMS news — unified Query + Command (Wave 9).
 */
interface NewsStorageInterface
{
    /** @return list<NewsEntity> */
    public function list(): array;

    public function getById(string $newsId): NewsEntity|null;

    public function put(NewsEntity $news): void;

    public function remove(string $newsId): void;
}
