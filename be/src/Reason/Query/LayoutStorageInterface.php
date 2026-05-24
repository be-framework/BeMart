<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin CMS layouts — unified Query + Command (Wave 9).
 *
 * Layouts have only list + update affordances per ALPS (no create or
 * delete). The Storage shape still exposes `put` so the update Final
 * can persist its merge.
 */
interface LayoutStorageInterface
{
    /** @return list<LayoutEntity> */
    #[DbQuery('tlayout_list')]
    public function list(): array;

    #[DbQuery('tlayout_get')]
    public function item(string $layoutId): LayoutEntity|null;

    #[DbQuery('tlayout_put')]
    public function put(LayoutEntity $layout): void;
}
