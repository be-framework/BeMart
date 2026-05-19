<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;

/**
 * Admin design templates — list-only (Wave 9). No CRUD affordances
 * in ALPS beyond goTemplateList.
 */
interface TemplateStorageInterface
{
    /** @return list<TemplateEntity> */
    public function list(): array;
}
