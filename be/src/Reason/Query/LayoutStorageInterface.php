<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;

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
    public function list(): array;

    public function getById(string $layoutId): LayoutEntity|null;

    public function put(LayoutEntity $layout): void;
}
