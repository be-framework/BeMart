<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\Factory\TemplateFactory;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin design templates — list-only (Wave 9). No CRUD affordances
 * in ALPS beyond goTemplateList.
 */
interface TemplateStorageInterface
{
    /** @return list<TemplateEntity> */
    #[DbQuery('ttemplate_list', factory: TemplateFactory::class)]
    public function list(): array;
}
